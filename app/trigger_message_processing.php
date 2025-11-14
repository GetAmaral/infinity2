<?php

require_once 'vendor/autoload.php';

use App\Message\ProcessTalkMessageCommand;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$messageBus = $container->get('messenger.default_bus');

// Message ID from the demo setup
$messageId = $argv[1] ?? null;

if (!$messageId) {
    echo "Usage: php trigger_message_processing.php <message-id>\n";
    exit(1);
}

echo "Dispatching ProcessTalkMessageCommand for message: $messageId\n";

$messageBus->dispatch(new ProcessTalkMessageCommand($messageId));

echo "✓ Message dispatched to async queue!\n";
echo "The messenger worker should now process it.\n";
