<?php

require_once 'vendor/autoload.php';

use App\Entity\TalkMessage;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$talkId = '36d9d9b6-a9db-4993-8018-6c3c812ff86f';

echo "=== Sending Demo Message via Doctrine ===\n\n";

$talk = $em->getRepository('App\Entity\Talk')->find($talkId);

if (!$talk) {
    echo "ERROR: Talk not found\n";
    exit(1);
}

echo "✓ Talk found: {$talk->getSubject()}\n";
echo "✓ TreeFlow: " . ($talk->getTreeFlow() ? $talk->getTreeFlow()->getName() : 'NULL') . "\n";
echo "✓ Agent: " . ($talk->getAgents()->first() ? $talk->getAgents()->first()->getName() : 'NONE') . "\n\n";

// Create inbound message - this will trigger TalkMessageSubscriber
$message = new TalkMessage();
$message->setTalk($talk);
$message->setOrganization($talk->getOrganization());
$message->setFromContact($talk->getContact());
$message->setBody("Hi Sarah! I'm interested in learning about your services. Can you tell me what you offer?");
$message->setDirection('inbound');
$message->setMessageType('text');
$message->setRead(false);
$message->setInternal(false);
$message->setSystem(false);
$message->setSentAt(new \DateTimeImmutable());

echo "Creating inbound message...\n";
$em->persist($message);

// Update talk message count
$talk->setMessageCount($talk->getMessageCount() + 1);
$talk->setDateLastMessage(new \DateTimeImmutable());

$em->flush();

echo "✓ Message created: {$message->getId()->toRfc4122()}\n";
echo "✓ TalkMessageSubscriber will automatically dispatch ProcessTalkMessageCommand\n\n";

echo "The messenger worker should now process the message and generate an AI response!\n";
echo "Check messages with:\n";
echo "  docker-compose exec -T app php bin/console dbal:run-sql \"SELECT direction, body FROM talk_message WHERE talk_id = '$talkId' ORDER BY created_at;\"\n\n";
