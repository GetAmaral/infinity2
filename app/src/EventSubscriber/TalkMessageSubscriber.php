<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\TalkMessage;
use App\Message\ProcessTalkMessageCommand;
use App\Service\WhatsApp\WhatsAppService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
class TalkMessageSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly WhatsAppService $whatsAppService,
        private readonly HubInterface $mercureHub,
        private readonly LoggerInterface $logger
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TalkMessage) {
            return;
        }

        $persistTraceId = uniqid('persist_', true);
        $this->logger->info('PERSIST TRACE: TalkMessage postPersist triggered', [
            'message_id' => $entity->getId()->toRfc4122(),
            'direction' => $entity->getDirection(),
            'trace_id' => $persistTraceId,
            'message_preview' => substr($entity->getBody() ?? '', 0, 50),
        ]);

        $talk = $entity->getTalk();
        if (!$talk) {
            return;
        }

        // Automatically add fromUser to talk.users if not already there
        $fromUser = $entity->getFromUser();
        if ($fromUser && !$talk->getUsers()->contains($fromUser)) {
            $talk->addUser($fromUser);

            // Schedule the change for next flush
            $em = $args->getObjectManager();
            $em->persist($talk);
            // Note: Don't flush here - we're already in a flush operation
            // The change will be committed in the current transaction
            $em->getUnitOfWork()->computeChangeSet(
                $em->getClassMetadata(get_class($talk)),
                $talk
            );
        }

        // === EXISTING LOGIC: Process inbound messages for AI ===
        if ($entity->getDirection() === 'inbound') {
            // Only process if Talk has Agent with TreeFlow
            if ($talk->getTreeFlow()) { // Uses Talk->Agent->TreeFlow
                // Dispatch async processing
                $traceId = uniqid('process_', true);
                $this->logger->info('DISPATCH TRACE: Dispatching ProcessTalkMessageCommand', [
                    'message_id' => $entity->getId()->toRfc4122(),
                    'talk_id' => $talk->getId()->toRfc4122(),
                    'trace_id' => $traceId,
                    'source' => 'TalkMessageSubscriber::postPersist',
                ]);

                $this->messageBus->dispatch(
                    new ProcessTalkMessageCommand($entity->getId()->toRfc4122())
                );
            }
        }

        // === NEW LOGIC: Send outbound WhatsApp messages ===
        if ($entity->getDirection() === 'outbound' && $entity->getChannel() === 'whatsapp') {
            try {
                $this->whatsAppService->sendOutgoingMessage($entity);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send WhatsApp message', [
                    'message_id' => $entity->getId()->toRfc4122(),
                    'error' => $e->getMessage(),
                ]);
                // Don't re-throw - we don't want to rollback the transaction
            }
        }

        // === MERCURE: Publish real-time update for chat UI ===
        try {
            $messageData = $this->serializeMessage($entity);
            $preview = $entity->getBody() ?: '';

            // Update for active chat (messages)
            $chatUpdate = new Update(
                'https://luminai.app/chat/talk/' . $talk->getId()->toRfc4122(),
                json_encode([
                    'type' => 'new_message',
                    'message' => $messageData,
                    'talk' => [
                        'id' => $talk->getId()->toRfc4122(),
                        'lastMessagePreview' => substr($preview, 0, 100),
                        'dateLastMessage' => $entity->getSentAt()->format('c'),
                    ]
                ])
            );
            $this->mercureHub->publish($chatUpdate);

            // Update for sidebar (talk list) - organization-specific topic
            $sidebarUpdate = new Update(
                'https://luminai.app/organization/' . $talk->getOrganization()->getId()->toRfc4122() . '/talks',
                json_encode([
                    'type' => 'talk_updated',
                    'talk' => [
                        'id' => $talk->getId()->toRfc4122(),
                        'subject' => $talk->getSubject(),
                        'lastMessagePreview' => substr($preview, 0, 100),
                        'dateLastMessage' => $entity->getSentAt()->format('c'),
                        'messageCount' => $talk->getMessageCount(),
                        'status' => $talk->getStatus(),
                        'channel' => $talk->getChannel(),
                        // Increment unread if message is inbound
                        'hasNewMessage' => $entity->getDirection() === 'inbound',
                    ]
                ])
            );
            $this->mercureHub->publish($sidebarUpdate);

        } catch (\Exception $e) {
            $this->logger->error('Failed to publish Mercure update', [
                'message_id' => $entity->getId()->toRfc4122(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function serializeMessage(TalkMessage $message): array
    {
        return [
            'id' => $message->getId()->toRfc4122(),
            'body' => $message->getBody(),
            'sentAt' => $message->getSentAt()->format('c'),
            'read' => $message->getRead(),
            'readAt' => $message->getReadAt()?->format('c'),
            'deliveredAt' => $message->getDeliveredAt()?->format('c'),
            'messageType' => $message->getMessageType(),
            'direction' => $message->getDirection(),
            'senderLabel' => $message->getSenderLabel(),
            'attachments' => [],
            'fromUser' => $message->getFromUser() ? [
                'id' => $message->getFromUser()->getId()->toRfc4122(),
                'name' => $message->getFromUser()->__toString(),
            ] : null,
            'fromContact' => $message->getFromContact() ? [
                'id' => $message->getFromContact()->getId()->toRfc4122(),
                'name' => $message->getFromContact()->getName(),
            ] : null,
            'fromAgent' => $message->getFromAgent() ? [
                'id' => $message->getFromAgent()->getId()->toRfc4122(),
                'name' => $message->getFromAgent()->getName(),
            ] : null,
        ];
    }
}
