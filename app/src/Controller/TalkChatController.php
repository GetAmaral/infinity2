<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Talk;
use App\Entity\TalkMessage;
use App\Repository\TalkMessageRepository;
use App\Repository\TalkRepository;
use App\Security\Voter\TalkVoter;
use App\Security\Voter\TalkMessageVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * TalkChat Controller - WhatsApp-like Chat Interface
 *
 * Handles chat interface routes and real-time messaging API endpoints
 */
#[Route('/chat')]
final class TalkChatController extends AbstractController
{
    public function __construct(
        private readonly TalkRepository $talkRepository,
        private readonly TalkMessageRepository $messageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HubInterface $mercureHub
    ) {
    }

    /**
     * Display chat interface with talk list and message thread
     */
    #[Route('', name: 'chat_index', methods: ['GET'])]
    #[Route('/{id}', name: 'chat_talk', methods: ['GET'])]
    public function index(?Talk $talk = null): Response
    {
        // Verify access to talk if specified
        if ($talk) {
            $this->denyAccessUnlessGranted(TalkVoter::VIEW, $talk);
        }

        return $this->render('chat/index.html.twig', [
            'currentTalk' => $talk,
        ]);
    }

    /**
     * API: Get list of talks for current user
     * Returns talks with last message preview, unread count, sorted by dateLastMessage DESC
     */
    #[Route('/api/talks/list', name: 'chat_api_list', methods: ['GET'])]
    public function apiListTalks(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $limit = min((int) $request->query->get('limit', 50), 100);
        $filter = $request->query->get('filter', 'all'); // 'all' or 'user'

        $qb = $this->talkRepository->createQueryBuilder('t');

        if ($filter === 'user') {
            // User Only: only talks where current user is owner
            $qb->where('t.owner = :user')
                ->setParameter('user', $user);
        } else {
            // All from Org: ALL talks from the same organization
            $qb->where('t.organization = :org')
                ->setParameter('org', $user->getOrganization());
        }

        $qb->andWhere('t.archived = false')
            ->orderBy('t.dateLastMessage', 'DESC')
            ->setMaxResults($limit);

        $talks = $qb->getQuery()->getResult();

        // Transform to API response
        $data = array_map(function (Talk $talk) use ($user) {
            return [
                'id' => $talk->getId()->toRfc4122(),
                'subject' => $talk->getSubject(),
                'lastMessagePreview' => $talk->getLastMessagePreview(),
                'dateLastMessage' => $talk->getDateLastMessage()?->format('c'),
                'unreadCount' => $this->getUnreadCount($talk, $user),
                'status' => $talk->getStatus(),
                'channel' => $talk->getChannel(),
                'participants' => $this->getParticipantsString($talk),
            ];
        }, $talks);

        return $this->json($data);
    }

    /**
     * API: Get messages for a specific talk
     * Supports pagination with ?before=<messageId> for infinite scroll
     * or ?after=<messageId> for polling new messages
     */
    #[Route('/api/talk/{id}/messages', name: 'chat_api_messages', methods: ['GET'])]
    public function apiMessages(Talk $talk, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(TalkVoter::VIEW, $talk);

        $before = $request->query->get('before');
        $after = $request->query->get('after');
        $limit = min((int) $request->query->get('limit', 50), 100);

        $qb = $this->messageRepository->createQueryBuilder('m')
            ->where('m.talk = :talk')
            ->setParameter('talk', $talk)
            ->setMaxResults($limit);

        // For polling (get newer messages)
        if ($after) {
            $qb->andWhere('m.sentAt > (SELECT m2.sentAt FROM App\Entity\TalkMessage m2 WHERE m2.id = :after)')
                ->setParameter('after', $after)
                ->orderBy('m.sentAt', 'ASC');
        }
        // For pagination (get older messages)
        elseif ($before) {
            $qb->andWhere('m.sentAt < (SELECT m2.sentAt FROM App\Entity\TalkMessage m2 WHERE m2.id = :before)')
                ->setParameter('before', $before)
                ->orderBy('m.sentAt', 'DESC');
        }
        // Initial load
        else {
            $qb->orderBy('m.sentAt', 'DESC');
        }

        $messages = $qb->getQuery()->getResult();

        // Reverse for chronological order if DESC
        if (!$after) {
            $messages = array_reverse($messages);
        }

        // Transform to API response
        $data = [
            'messages' => array_map(function (TalkMessage $message) {
                return $this->serializeMessage($message);
            }, $messages),
            'talk' => [
                'id' => $talk->getId()->toRfc4122(),
                'subject' => $talk->getSubject(),
                'participants' => $this->getParticipantsString($talk),
            ],
        ];

        return $this->json($data);
    }

    /**
     * API: Send a new message in a talk (Enhanced with file upload support - Phase 3)
     */
    #[Route('/api/talk/{id}/send', name: 'chat_api_send', methods: ['POST'])]
    public function apiSendMessage(Talk $talk, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(TalkMessageVoter::CREATE, new TalkMessage());

        // Check if request has files (multipart/form-data)
        $hasFiles = $request->files->count() > 0;

        if ($hasFiles) {
            // File upload request
            $body = $request->request->get('body', '');
            $files = $request->files->get('attachments', []);
        } else {
            // JSON request (text only)
            $data = json_decode($request->getContent(), true);
            $body = $data['body'] ?? '';
            $files = [];
        }

        // Validate: must have either body or files
        if (empty(trim($body)) && empty($files)) {
            return $this->json(['error' => 'Message must have body or attachments'], 400);
        }

        // Create message
        $message = new TalkMessage();
        $message->setTalk($talk);
        $message->setOrganization($talk->getOrganization());
        $message->setFromUser($this->getUser());
        $message->setBody($body ?: '');
        $message->setSentAt(new \DateTimeImmutable());
        $message->setDirection('outbound');
        $message->setRead(false);

        // Set message type based on content
        if (!empty($files)) {
            $message->setMessageType(count($files) > 1 ? 'multiple' : 'attachment');
        } else {
            $message->setMessageType('text');
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush(); // Flush to get message ID

        // Handle file uploads (Phase 3)
        $attachments = [];
        if (!empty($files)) {
            foreach ($files as $uploadedFile) {
                try {
                    $attachment = $this->createAttachment($uploadedFile, $message);
                    $this->entityManager->persist($attachment);
                    $attachments[] = $attachment;
                } catch (\Exception $e) {
                    return $this->json([
                        'error' => 'Failed to upload file: ' . $e->getMessage()
                    ], 500);
                }
            }
            $this->entityManager->flush();
        }

        // Update talk
        $preview = $body ?: (count($attachments) > 0 ? '📎 ' . count($attachments) . ' file(s)' : '');
        $talk->setDateLastMessage(new \DateTimeImmutable());
        $talk->setLastMessagePreview($this->truncateText($preview, 100));
        $talk->setMessageCount($talk->getMessageCount() + 1);

        $this->entityManager->flush();

        // Publish Mercure update for real-time chat
        $update = new Update(
            'https://luminai.app/chat/talk/' . $talk->getId()->toRfc4122(),
            json_encode([
                'type' => 'new_message',
                'message' => $this->serializeMessage($message),
                'talk' => [
                    'id' => $talk->getId()->toRfc4122(),
                    'lastMessagePreview' => $talk->getLastMessagePreview(),
                    'dateLastMessage' => $talk->getDateLastMessage()?->format('c'),
                ]
            ])
        );
        $this->mercureHub->publish($update);

        return $this->json($this->serializeMessage($message), 201);
    }

    /**
     * Create attachment from uploaded file (Phase 3)
     */
    private function createAttachment($uploadedFile, TalkMessage $message): \App\Entity\Attachment
    {
        $attachment = new \App\Entity\Attachment();
        $attachment->setOrganization($message->getOrganization());
        $attachment->setTalkMessage($message);
        $attachment->setFile($uploadedFile);
        // VichUploader will automatically populate filename, fileSize, fileType

        return $attachment;
    }

    /**
     * API: Mark a message as read
     */
    #[Route('/api/message/{id}/read', name: 'chat_api_mark_read', methods: ['POST'])]
    public function apiMarkRead(TalkMessage $message): JsonResponse
    {
        $this->denyAccessUnlessGranted(TalkMessageVoter::VIEW, $message);

        // Only mark as read if not already read and not own message
        if (!$message->getRead() && $message->getFromUser() !== $this->getUser()) {
            $message->setRead(true);
            $message->setReadAt(new \DateTimeImmutable());

            $this->entityManager->flush();
        }

        return $this->json(['success' => true]);
    }

    /**
     * API: Update typing status (for typing indicators - Phase 4)
     */
    #[Route('/api/talk/{id}/typing', name: 'chat_api_typing', methods: ['POST'])]
    public function apiTyping(Talk $talk, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(TalkVoter::VIEW, $talk);

        $data = json_decode($request->getContent(), true);
        $isTyping = $data['isTyping'] ?? false;
        $userId = $this->getUser()->getId()->toRfc4122();

        // Get current typing users
        $typingUsers = $talk->getTypingUsers() ?? [];

        if ($isTyping && !in_array($userId, $typingUsers)) {
            $typingUsers[] = $userId;
        } elseif (!$isTyping) {
            $typingUsers = array_filter($typingUsers, fn($id) => $id !== $userId);
        }

        $talk->setTypingUsers(array_values($typingUsers));
        $this->entityManager->flush();

        return $this->json(['success' => true, 'typingUsers' => $typingUsers]);
    }

    /**
     * Helper: Get unread message count for user in talk
     */
    private function getUnreadCount(Talk $talk, $user): int
    {
        return $this->messageRepository->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.talk = :talk')
            ->andWhere('m.read = false')
            ->andWhere('m.fromUser != :user')
            ->setParameter('talk', $talk)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Helper: Get participants as string
     */
    private function getParticipantsString(Talk $talk): string
    {
        $participants = [];

        if ($contact = $talk->getContact()) {
            $participants[] = $contact->__toString();
        }

        if ($company = $talk->getCompany()) {
            $participants[] = $company->__toString();
        }

        foreach ($talk->getUsers() as $user) {
            $participants[] = $user->__toString();
        }

        return implode(', ', array_unique($participants)) ?: 'No participants';
    }

    /**
     * Helper: Serialize message to array (Enhanced with attachments - Phase 3)
     */
    private function serializeMessage(TalkMessage $message): array
    {
        // Serialize attachments
        $attachments = [];
        foreach ($message->getAttachments() as $attachment) {
            $attachments[] = [
                'id' => $attachment->getId()->toRfc4122(),
                'filename' => $attachment->getFilename(),
                'fileSize' => $attachment->getFileSize(),
                'fileType' => $attachment->getFileType(),
                'formattedSize' => $attachment->getFormattedFileSize(),
                'url' => $attachment->getFileUrl(),
                'isImage' => $attachment->isImage(),
                'icon' => $attachment->getFileIcon(),
            ];
        }

        return [
            'id' => $message->getId()->toRfc4122(),
            'body' => $message->getBody(),
            'sentAt' => $message->getSentAt()->format('c'),
            'read' => $message->getRead(),
            'readAt' => $message->getReadAt()?->format('c'),
            'deliveredAt' => $message->getDeliveredAt()?->format('c'),
            'messageType' => $message->getMessageType(),
            'direction' => $message->getDirection(),
            'attachments' => $attachments,
            'fromUser' => $message->getFromUser() ? [
                'id' => $message->getFromUser()->getId()->toRfc4122(),
                'name' => $message->getFromUser()->__toString(),
            ] : null,
            'fromContact' => $message->getFromContact() ? [
                'id' => $message->getFromContact()->getId()->toRfc4122(),
                'name' => $message->getFromContact()->__toString(),
            ] : null,
        ];
    }

    /**
     * Helper: Truncate text
     */
    private function truncateText(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 3) . '...';
    }
}
