<?php

require_once 'vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$talkFlowService = $container->get('App\Service\TalkFlow\TalkFlowService');
$em = $container->get('doctrine')->getManager();

$talkId = '0a0c06bf-03c1-482b-8e49-43c7bf6af46d';

echo "=== Testing TalkFlowService ===\n\n";

$talk = $em->getRepository('App\Entity\Talk')->find($talkId);

if (!$talk) {
    echo "ERROR: Talk not found\n";
    exit(1);
}

echo "✓ Talk found: {$talk->getSubject()}\n";
echo "✓ TreeFlow: " . ($talk->getTreeFlow() ? $talk->getTreeFlow()->getName() : 'NULL') . "\n";
echo "✓ Talk has talk_flow: " . ($talk->getTalkFlow() ? 'YES' : 'NO') . "\n\n";

if ($talk->getTalkFlow()) {
    echo "Talk Flow JSON:\n";
    echo json_encode($talk->getTalkFlow(), JSON_PRETTY_PRINT) . "\n\n";
}

echo "Getting current step...\n";
try {
    $currentStep = $talkFlowService->getCurrentStep($talk);

    if ($currentStep) {
        echo "✓ Current Step found:\n";
        echo json_encode($currentStep, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "✗ Current Step is NULL\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
