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
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
class TalkMessageSubscriber
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly WhatsAppService $whatsAppService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TalkMessage) {
            return;
        }

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
    }
}
