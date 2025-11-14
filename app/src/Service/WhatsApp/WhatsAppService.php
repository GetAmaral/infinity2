<?php

declare(strict_types=1);

namespace App\Service\WhatsApp;

use App\Entity\Agent;
use App\Entity\Attachment;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\Organization;
use App\Entity\Talk;
use App\Entity\TalkMessage;
use App\Entity\TalkType;
use App\Entity\User;
use App\Repository\AgentRepository;
use App\Repository\CompanyRepository;
use App\Repository\ContactRepository;
use App\Repository\TalkRepository;
use App\Repository\TalkTypeRepository;
use App\Repository\UserRepository;
use App\Service\TalkFlow\TalkFlowService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * WhatsApp Service
 *
 * Core business logic for WhatsApp message processing via Evolution API.
 * Handles incoming webhooks and outgoing message delivery.
 */
class WhatsAppService
{
    public function __construct(
        private readonly AgentRepository $agentRepository,
        private readonly ContactRepository $contactRepository,
        private readonly TalkRepository $talkRepository,
        private readonly TalkTypeRepository $talkTypeRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly UserRepository $userRepository,
        private readonly TalkFlowService $talkFlowService,
        private readonly EvolutionApiClient $evolutionApiClient,
        private readonly MediaDownloadService $mediaDownloadService,
        private readonly WebhookIdempotencyService $idempotencyService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process incoming WhatsApp message from Evolution API webhook
     *
     * @param array $webhookPayload Raw webhook payload from Evolution API
     * @throws \Exception
     */
    public function processIncomingMessage(array $webhookPayload): void
    {
        // 1. Validate event type
        $event = $webhookPayload['event'] ?? null;
        if ($event !== 'messages.upsert') {
            $this->logger->debug('Ignoring non-message event', ['event' => $event]);
            return;
        }

        // 2. Extract data
        $data = $webhookPayload['data'] ?? [];
        $key = $data['key'] ?? [];
        $message = $data['message'] ?? [];

        // 3. Ignore messages sent by us (fromMe = true)
        if ($key['fromMe'] ?? false) {
            $this->logger->debug('Ignoring outbound message (fromMe=true)');
            return;
        }

        // 4. Ignore group messages (remoteJid contains @g.us)
        $remoteJid = $key['remoteJid'] ?? '';
        if (str_contains($remoteJid, '@g.us')) {
            $this->logger->debug('Ignoring group message', ['remoteJid' => $remoteJid]);
            return;
        }

        // 5. Extract required fields
        $instanceName = $webhookPayload['instance'] ?? null;
        $pushName = $data['pushName'] ?? 'Unknown';
        $messageText = $this->extractMessageText($message);
        $messageId = $key['id'] ?? null;

        if (!$instanceName || !$remoteJid || !$messageText) {
            $this->logger->warning('Missing required fields in webhook', [
                'instance' => $instanceName,
                'remoteJid' => $remoteJid,
                'messageText' => $messageText,
            ]);
            return;
        }

        // 6. Check idempotency (prevent duplicate processing)
        if ($messageId && $this->idempotencyService->isProcessed($messageId)) {
            $this->logger->info('Skipping duplicate message', [
                'message_id' => $messageId,
                'instance' => $instanceName,
            ]);
            return;
        }

        // Mark message as being processed
        if ($messageId) {
            $this->idempotencyService->markAsProcessed($messageId);
        }

        $this->logger->info('Processing incoming WhatsApp message', [
            'instance' => $instanceName,
            'remoteJid' => $remoteJid,
            'pushName' => $pushName,
            'messageLength' => strlen($messageText),
        ]);

        // 7. Identify Agent by instance name
        $agent = $this->identifyAgentByInstance($instanceName);
        if (!$agent) {
            $this->logger->error('No active agent found for instance', [
                'instance' => $instanceName,
            ]);
            return;
        }

        // 7. Normalize phone number to E.164 format
        $phoneNumber = $this->normalizePhoneNumber($remoteJid);

        // 8. Find or create Contact
        $contact = $this->identifyOrCreateContact(
            $phoneNumber,
            $pushName,
            $agent->getOrganization()
        );

        // 9. Find or create Talk
        $talk = $this->findOrCreateTalk($contact, $agent);

        // 10. Detect message type (text or media)
        $mediaInfo = $this->extractMediaInfo($message);
        $messageType = $mediaInfo ? $mediaInfo['type'] : 'text';

        // 11. Create inbound TalkMessage
        $talkMessage = new TalkMessage();
        $talkMessage->setOrganization($agent->getOrganization());
        $talkMessage->setTalk($talk);
        $talkMessage->setFromContact($contact);
        $talkMessage->setBody($messageText);
        $talkMessage->setDirection('inbound');
        $talkMessage->setChannel('whatsapp');
        $talkMessage->setMessageType($messageType);
        $talkMessage->setSentAt(new \DateTimeImmutable());
        $talkMessage->setMetadata([
            'whatsappMessageId' => $messageId,
            'instanceName' => $instanceName,
            'remoteJid' => $remoteJid,
            'pushName' => $pushName,
            'hasMedia' => $mediaInfo !== null,
        ]);

        $this->entityManager->persist($talkMessage);
        $this->entityManager->flush();

        // 12. Process media attachment if present
        if ($mediaInfo) {
            try {
                $this->processMediaAttachment($talkMessage, $mediaInfo, $agent);
            } catch (\Exception $e) {
                $this->logger->error('Failed to process media attachment', [
                    'message_id' => $talkMessage->getId()->toRfc4122(),
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the whole message processing
            }
        }

        $this->logger->info('Incoming WhatsApp message processed', [
            'talk_id' => $talk->getId()->toRfc4122(),
            'message_id' => $talkMessage->getId()->toRfc4122(),
            'contact_phone' => $phoneNumber,
        ]);
    }

    /**
     * Send outgoing WhatsApp message with retry logic
     *
     * @param TalkMessage $talkMessage The outbound message to send
     */
    public function sendOutgoingMessage(TalkMessage $talkMessage): void
    {
        // 1. Validate message is outbound and channel is WhatsApp
        if ($talkMessage->getDirection() !== 'outbound') {
            $this->logger->warning('Attempted to send non-outbound message', [
                'message_id' => $talkMessage->getId()->toRfc4122(),
                'direction' => $talkMessage->getDirection(),
            ]);
            return;
        }

        if ($talkMessage->getChannel() !== 'whatsapp') {
            return; // Not a WhatsApp message, skip
        }

        // 2. Get Agent and validate WhatsApp is active
        $talk = $talkMessage->getTalk();
        $agent = $talk->getAgents()->first();

        if (!$agent) {
            $this->logger->error('No agent assigned to talk', [
                'message_id' => $talkMessage->getId()->toRfc4122(),
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        if (!$agent->getWhatsappActive()) {
            $this->logger->warning('Agent WhatsApp not active', [
                'message_id' => $talkMessage->getId()->toRfc4122(),
                'agent_id' => $agent->getId()->toRfc4122(),
            ]);
            return;
        }

        // 3. Get contact phone number
        $contact = $talk->getContact();
        if (!$contact) {
            $this->logger->error('No contact associated with talk', [
                'message_id' => $talkMessage->getId()->toRfc4122(),
                'talk_id' => $talk->getId()->toRfc4122(),
            ]);
            return;
        }

        $phoneNumber = $contact->getPhone() ?? $contact->getMobilePhone();
        if (!$phoneNumber) {
            $this->logger->error('Contact has no phone number', [
                'message_id' => $talkMessage->getId()->toRfc4122(),
                'contact_id' => $contact->getId()->toRfc4122(),
            ]);
            return;
        }

        // 4. Normalize phone for Evolution API (remove + and @ symbols)
        $normalizedPhone = $this->normalizePhoneNumberForEvolution($phoneNumber);

        // 5. Retry loop (max 3 attempts with exponential backoff)
        $maxAttempts = 3;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $this->logger->debug('Sending WhatsApp message', [
                    'message_id' => $talkMessage->getId()->toRfc4122(),
                    'attempt' => $attempt,
                    'phone' => $normalizedPhone,
                ]);

                $response = $this->evolutionApiClient->sendTextMessage(
                    $agent->getWhatsappServerUrl(),
                    $agent->getWhatsappApiKey(),
                    $agent->getWhatsappInstanceName(),
                    $normalizedPhone,
                    $talkMessage->getBody()
                );

                // Success! Update message metadata
                $metadata = $talkMessage->getMetadata() ?? [];
                $metadata['whatsappSent'] = true;
                $metadata['whatsappResponse'] = $response;
                $metadata['whatsappAttempts'] = $attempt;
                $talkMessage->setMetadata($metadata);
                $talkMessage->setDeliveredAt(new \DateTimeImmutable());

                $this->entityManager->flush();

                $this->logger->info('WhatsApp message sent successfully', [
                    'message_id' => $talkMessage->getId()->toRfc4122(),
                    'attempts' => $attempt,
                ]);

                return; // Success, exit function

            } catch (\Exception $e) {
                $this->logger->warning('Failed to send WhatsApp message', [
                    'message_id' => $talkMessage->getId()->toRfc4122(),
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                // If not last attempt, wait before retrying
                if ($attempt < $maxAttempts) {
                    $waitSeconds = 2 * $attempt; // 2s, 4s
                    sleep($waitSeconds);
                }
            }
        }

        // All retries failed, mark message as failed
        $metadata = $talkMessage->getMetadata() ?? [];
        $metadata['whatsappSent'] = false;
        $metadata['whatsappError'] = 'Failed after ' . $maxAttempts . ' attempts';
        $metadata['whatsappAttempts'] = $maxAttempts;
        $metadata['whatsappFailedAt'] = (new \DateTimeImmutable())->format('c');
        $talkMessage->setMetadata($metadata);

        $this->entityManager->flush();

        $this->logger->error('WhatsApp message failed after all retries', [
            'message_id' => $talkMessage->getId()->toRfc4122(),
            'attempts' => $maxAttempts,
        ]);
    }

    /**
     * Identify Agent by Evolution API instance name
     *
     * @param string $instanceName Evolution API instance identifier
     * @return Agent|null
     */
    public function identifyAgentByInstance(string $instanceName): ?Agent
    {
        return $this->agentRepository->createQueryBuilder('a')
            ->where('a.whatsappInstanceName = :instanceName')
            ->andWhere('a.whatsappActive = :active')
            ->andWhere('a.active = :generalActive')
            ->setParameter('instanceName', $instanceName)
            ->setParameter('active', true)
            ->setParameter('generalActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find existing contact or create new one
     *
     * @param string $phone Normalized phone number (E.164 format)
     * @param string $name Contact name from WhatsApp
     * @param Organization $org Organization entity
     * @return Contact
     */
    public function identifyOrCreateContact(string $phone, string $name, Organization $org): Contact
    {
        // 1. Search by phone field
        $contact = $this->contactRepository->findOneBy([
            'phone' => $phone,
            'organization' => $org,
        ]);

        if ($contact) {
            // Update last contact date
            $contact->setLastContactDate(new \DateTimeImmutable());
            $this->entityManager->flush();
            return $contact;
        }

        // 2. Search by mobilePhone field
        $contact = $this->contactRepository->findOneBy([
            'mobilePhone' => $phone,
            'organization' => $org,
        ]);

        if ($contact) {
            // Update last contact date
            $contact->setLastContactDate(new \DateTimeImmutable());
            $this->entityManager->flush();
            return $contact;
        }

        // 3. Not found, create new contact
        $this->logger->info('Creating new contact from WhatsApp', [
            'phone' => $phone,
            'name' => $name,
            'organization' => $org->getId()->toRfc4122(),
        ]);

        $contact = new Contact();
        $contact->setOrganization($org);
        $contact->setName($name);

        // Parse name into firstName and lastName
        $nameParts = $this->parseFullName($name);
        $contact->setFirstName($nameParts['firstName']);
        $contact->setLastName($nameParts['lastName']);

        // Set phone numbers
        $contact->setPhone($phone);
        $contact->setMobilePhone($phone);

        // Email is nullable, no dummy email!
        $contact->setEmail(null);

        // Get or create default company
        $defaultCompany = $this->getOrCreateDefaultCompany($org);
        $contact->setCompany($defaultCompany);

        // Set lead source and origin
        $contact->setLeadSource('WhatsApp');
        $contact->setOrigin('WhatsApp - Evolution API');

        // Set timestamps
        $contact->setLastContactDate(new \DateTimeImmutable());
        $contact->setFirstTalkDate(new \DateTimeImmutable());

        // Set default boolean values
        $contact->setEmailOptOut(false);
        $contact->setDoNotCall(false);

        // Handle race condition (concurrent webhook processing)
        try {
            $this->entityManager->persist($contact);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            // Another process created this contact, fetch it
            $this->entityManager->clear();
            $contact = $this->contactRepository->findOneBy([
                'phone' => $phone,
                'organization' => $org,
            ]);

            if (!$contact) {
                throw $e; // Still not found, re-throw
            }

            $this->logger->info('Contact created by another process (race condition)', [
                'phone' => $phone,
                'contact_id' => $contact->getId()->toRfc4122(),
            ]);
        }

        return $contact;
    }

    /**
     * Find active Talk or create new one
     *
     * @param Contact $contact Contact entity
     * @param Agent $agent Agent entity
     * @return Talk
     */
    public function findOrCreateTalk(Contact $contact, Agent $agent): Talk
    {
        // 1. Search for existing active Talk
        $talk = $this->talkRepository->createQueryBuilder('t')
            ->where('t.contact = :contact')
            ->andWhere(':agent MEMBER OF t.agents')
            ->andWhere('t.channel = :channel')
            ->andWhere('t.status = :status')
            ->andWhere('t.archived = :archived')
            ->andWhere('t.closedAt IS NULL')
            ->setParameter('contact', $contact)
            ->setParameter('agent', $agent)
            ->setParameter('channel', Talk::CHANNEL_WHATSAPP)
            ->setParameter('status', 0) // Active status
            ->setParameter('archived', false)
            ->orderBy('t.dateLastMessage', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($talk) {
            // Update last message date
            $talk->setDateLastMessage(new \DateTimeImmutable());
            $this->entityManager->flush();
            return $talk;
        }

        // 2. Not found, create new Talk
        $this->logger->info('Creating new Talk for WhatsApp', [
            'contact_id' => $contact->getId()->toRfc4122(),
            'agent_id' => $agent->getId()->toRfc4122(),
        ]);

        $talk = new Talk();
        $talk->setOrganization($agent->getOrganization());
        $talk->setContact($contact);
        $talk->setSubject('WhatsApp - ' . $contact->getName());
        $talk->setChannel(Talk::CHANNEL_WHATSAPP);
        $talk->setStatus(0); // Active
        $talk->setDateStart(new \DateTimeImmutable());
        $talk->setDateLastMessage(new \DateTimeImmutable());

        // Get or create WhatsApp TalkType
        $talkType = $this->getOrCreateWhatsAppTalkType($agent->getOrganization());
        $talk->setTalkType($talkType);

        // Get default owner (required field)
        $owner = $this->getDefaultOwner($agent->getOrganization());
        $talk->setOwner($owner);

        // Add agent to agents collection
        $talk->getAgents()->add($agent);

        $this->entityManager->persist($talk);
        $this->entityManager->flush();

        // 3. Initialize TalkFlow from Agent's TreeFlow
        if ($agent->getTreeFlow()) {
            try {
                $this->talkFlowService->initializeTalkFlow($talk);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to initialize TalkFlow', [
                    'talk_id' => $talk->getId()->toRfc4122(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $talk;
    }

    /**
     * Extract message text from various WhatsApp message formats
     *
     * @param array $message WhatsApp message object
     * @return string Message text
     */
    private function extractMessageText(array $message): string
    {
        // Simple text
        if (isset($message['conversation'])) {
            return $message['conversation'];
        }

        // Extended text (rich formatting)
        if (isset($message['extendedTextMessage']['text'])) {
            return $message['extendedTextMessage']['text'];
        }

        // Image with caption
        if (isset($message['imageMessage']['caption'])) {
            return $message['imageMessage']['caption'];
        }

        // Video with caption
        if (isset($message['videoMessage']['caption'])) {
            return $message['videoMessage']['caption'];
        }

        // Document with caption
        if (isset($message['documentMessage']['caption'])) {
            return $message['documentMessage']['caption'];
        }

        return '';
    }

    /**
     * Normalize WhatsApp JID to E.164 phone format
     *
     * @param string $remoteJid WhatsApp JID (e.g., "5511999999999@s.whatsapp.net")
     * @return string E.164 phone number (e.g., "+5511999999999")
     */
    private function normalizePhoneNumber(string $remoteJid): string
    {
        // Remove @s.whatsapp.net suffix
        $phone = str_replace('@s.whatsapp.net', '', $remoteJid);

        // Add + prefix if not present
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Normalize phone number for Evolution API send request
     *
     * @param string $phone Phone number in any format
     * @return string Phone number with only digits (e.g., "5511999999999")
     */
    private function normalizePhoneNumberForEvolution(string $phone): string
    {
        // Remove all non-digit characters
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Parse full name into firstName and lastName
     *
     * @param string $fullName Full name
     * @return array ['firstName' => string, 'lastName' => string]
     */
    private function parseFullName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [
            'firstName' => $parts[0] ?? 'Unknown',
            'lastName' => $parts[1] ?? '',
        ];
    }

    /**
     * Get or create default Company for organization
     *
     * @param Organization $org Organization entity
     * @return Company
     */
    private function getOrCreateDefaultCompany(Organization $org): Company
    {
        $company = $this->companyRepository->findOneBy([
            'name' => 'Default Company',
            'organization' => $org,
        ]);

        if ($company) {
            return $company;
        }

        // Create default company
        $company = new Company();
        $company->setOrganization($org);
        $company->setName('Default Company');

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    /**
     * Get or create WhatsApp TalkType
     *
     * @param Organization $org Organization entity
     * @return TalkType
     */
    private function getOrCreateWhatsAppTalkType(Organization $org): TalkType
    {
        $talkType = $this->talkTypeRepository->findOneBy([
            'name' => 'WhatsApp',
            'organization' => $org,
        ]);

        if ($talkType) {
            return $talkType;
        }

        // Create WhatsApp talk type
        $talkType = new TalkType();
        $talkType->setOrganization($org);
        $talkType->setName('WhatsApp');
        $talkType->setActive(true);

        $this->entityManager->persist($talkType);
        $this->entityManager->flush();

        return $talkType;
    }

    /**
     * Get default owner (first user in organization)
     *
     * @param Organization $org Organization entity
     * @return User
     * @throws \RuntimeException if no users found
     */
    private function getDefaultOwner(Organization $org): User
    {
        $user = $this->userRepository->createQueryBuilder('u')
            ->where('u.organization = :org')
            ->setParameter('org', $org)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            throw new \RuntimeException('No users found in organization: ' . $org->getName());
        }

        return $user;
    }

    /**
     * Extract media information from WhatsApp message
     *
     * @param array $message WhatsApp message object
     * @return array|null Media info with keys: type, url, mimeType, caption
     */
    private function extractMediaInfo(array $message): ?array
    {
        // Image message
        if (isset($message['imageMessage'])) {
            return [
                'type' => 'image',
                'url' => $message['imageMessage']['url'] ?? null,
                'mimeType' => $message['imageMessage']['mimetype'] ?? 'image/jpeg',
                'caption' => $message['imageMessage']['caption'] ?? '',
                'fileName' => $message['imageMessage']['fileName'] ?? null,
                'fileLength' => $message['imageMessage']['fileLength'] ?? null,
            ];
        }

        // Video message
        if (isset($message['videoMessage'])) {
            return [
                'type' => 'video',
                'url' => $message['videoMessage']['url'] ?? null,
                'mimeType' => $message['videoMessage']['mimetype'] ?? 'video/mp4',
                'caption' => $message['videoMessage']['caption'] ?? '',
                'fileName' => $message['videoMessage']['fileName'] ?? null,
                'fileLength' => $message['videoMessage']['fileLength'] ?? null,
            ];
        }

        // Audio message
        if (isset($message['audioMessage'])) {
            return [
                'type' => 'audio',
                'url' => $message['audioMessage']['url'] ?? null,
                'mimeType' => $message['audioMessage']['mimetype'] ?? 'audio/ogg',
                'caption' => '',
                'fileName' => $message['audioMessage']['fileName'] ?? null,
                'fileLength' => $message['audioMessage']['fileLength'] ?? null,
            ];
        }

        // Document message
        if (isset($message['documentMessage'])) {
            return [
                'type' => 'document',
                'url' => $message['documentMessage']['url'] ?? null,
                'mimeType' => $message['documentMessage']['mimetype'] ?? 'application/octet-stream',
                'caption' => $message['documentMessage']['caption'] ?? '',
                'fileName' => $message['documentMessage']['fileName'] ?? 'document',
                'fileLength' => $message['documentMessage']['fileLength'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Process media attachment - download and create Attachment entity
     *
     * @param TalkMessage $talkMessage Message to attach media to
     * @param array $mediaInfo Media information from extractMediaInfo()
     * @param Agent $agent Agent for API authentication
     * @throws \Exception on download or save failure
     */
    private function processMediaAttachment(TalkMessage $talkMessage, array $mediaInfo, Agent $agent): void
    {
        $url = $mediaInfo['url'] ?? null;
        $mimeType = $mediaInfo['mimeType'] ?? 'application/octet-stream';

        if (!$url) {
            $this->logger->warning('Media URL not found in message', [
                'message_id' => $talkMessage->getId()->toRfc4122(),
                'media_type' => $mediaInfo['type'],
            ]);
            return;
        }

        // Check if MIME type is supported
        if (!$this->mediaDownloadService->isSupportedMimeType($mimeType)) {
            $this->logger->warning('Unsupported media type', [
                'message_id' => $talkMessage->getId()->toRfc4122(),
                'mime_type' => $mimeType,
            ]);
            return;
        }

        $this->logger->info('Processing media attachment', [
            'message_id' => $talkMessage->getId()->toRfc4122(),
            'media_type' => $mediaInfo['type'],
            'mime_type' => $mimeType,
            'url' => $url,
        ]);

        // Download media file
        try {
            // Try with authentication first (some Evolution API instances require it)
            $file = $this->mediaDownloadService->downloadWithAuth(
                $url,
                $agent->getWhatsappApiKey(),
                $mimeType
            );
        } catch (\Exception $e) {
            $this->logger->warning('Failed to download with auth, trying without', [
                'error' => $e->getMessage(),
            ]);

            // Fallback: try without authentication
            try {
                $file = $this->mediaDownloadService->downloadFromUrl($url, $mimeType);
            } catch (\Exception $e2) {
                throw new \Exception('Failed to download media: ' . $e2->getMessage());
            }
        }

        // Create Attachment entity
        $attachment = new Attachment();
        $attachment->setOrganization($agent->getOrganization());
        $attachment->setTalkMessage($talkMessage);
        $attachment->setFilename($mediaInfo['fileName'] ?? 'media_' . uniqid());
        $attachment->setFileType($mimeType);
        $attachment->setFileSize((int) ($mediaInfo['fileLength'] ?? $file->getSize()));
        $attachment->setFile($file);

        $this->entityManager->persist($attachment);
        $this->entityManager->flush();

        $this->logger->info('Media attachment saved', [
            'message_id' => $talkMessage->getId()->toRfc4122(),
            'attachment_id' => $attachment->getId()->toRfc4122(),
            'file_size' => $attachment->getFileSize(),
        ]);
    }
}
