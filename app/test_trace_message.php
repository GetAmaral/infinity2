<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

// Load environment
(new Dotenv())->bootEnv(__DIR__ . '/.env');

// Boot kernel
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get services
$entityManager = $container->get('doctrine.orm.entity_manager');
$talkRepository = $entityManager->getRepository(\App\Entity\Talk::class);
$organizationRepository = $entityManager->getRepository(\App\Entity\Organization::class);

// Find the Avelum talk with TreeFlow
$talk = $talkRepository->createQueryBuilder('t')
    ->where('t.talkFlow IS NOT NULL')
    ->orderBy('t.dateLastMessage', 'DESC')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if (!$talk) {
    echo "No talk with talkFlow found!\n";
    exit(1);
}

echo "Found talk: " . $talk->getId()->toRfc4122() . "\n";
echo "Subject: " . $talk->getSubject() . "\n";

// Create an inbound message
$message = new \App\Entity\TalkMessage();
$message->setTalk($talk);
$message->setOrganization($talk->getOrganization());
$message->setBody('Teste de rastreamento - ' . time());
$message->setDirection('inbound');
$message->setMessageType('text');
$message->setChannel('whatsapp');
$message->setSentAt(new \DateTimeImmutable());

// If there's a contact, set it
$contact = $talk->getContact();
if ($contact) {
    $message->setFromContact($contact);
}

echo "Creating message...\n";
$entityManager->persist($message);
$entityManager->flush();

echo "Message created with ID: " . $message->getId()->toRfc4122() . "\n";
echo "Now check logs for TRACE output\n";
