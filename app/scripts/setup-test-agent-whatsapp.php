#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Setup Test Agent for WhatsApp Cloud API
 *
 * Quick script to configure an existing Agent with test WhatsApp Cloud API credentials.
 * This is for LOCAL TESTING only with the webhook tester HTML page.
 *
 * Usage:
 *   docker-compose exec app php scripts/setup-test-agent-whatsapp.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

// Boot environment
(new Dotenv())->bootEnv(__DIR__ . '/../.env');

// Boot Symfony kernel
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

// Get Doctrine EntityManager
$em = $container->get('doctrine')->getManager();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   WhatsApp Cloud API - Test Agent Setup                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    // Find first active Agent
    $agent = $em->createQueryBuilder()
        ->select('a')
        ->from('App\Entity\Agent', 'a')
        ->where('a.active = :active')
        ->setParameter('active', true)
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    if (!$agent) {
        echo "❌ No active Agents found in database.\n";
        echo "   Please create an Agent first.\n";
        exit(1);
    }

    echo "📋 Agent Selected:\n";
    echo "   ID: {$agent->getId()}\n";
    echo "   Name: {$agent->getName()}\n";
    echo "   WhatsApp Active: " . ($agent->getWhatsappActive() ? 'Yes' : 'No') . "\n";
    echo "\n";

    // Configure with test credentials
    $testPhoneNumberId = '123456789012345';
    $testAccessToken = 'EAA_TEST_TOKEN_FOR_LOCAL_DEVELOPMENT';

    echo "🔧 Configuring WhatsApp Cloud API (TEST MODE):\n";
    echo "   Phone Number ID: $testPhoneNumberId\n";
    echo "   Access Token: $testAccessToken\n";
    echo "\n";

    // Update Agent
    $agent->setWhatsappActive(true);
    $agent->setWhatsAppPhoneNumberId($testPhoneNumberId);
    $agent->setWhatsAppAccessToken($testAccessToken);

    // Persist changes
    $em->flush();

    echo "✅ Agent configured successfully!\n";
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║   NEXT STEPS                                                   ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "1. Open the WhatsApp Webhook Tester:\n";
    echo "   👉 https://localhost/whatsapp-webhook-tester.html\n";
    echo "\n";
    echo "2. Configure the tester:\n";
    echo "   - Phone Number ID: $testPhoneNumberId\n";
    echo "   - Customer Phone: 5511999999999 (any number)\n";
    echo "   - Customer Name: Test User (any name)\n";
    echo "\n";
    echo "3. Send a test message and monitor:\n";
    echo "   - Check webhook tester for responses\n";
    echo "   - Check logs: docker-compose logs -f app | grep -i whatsapp\n";
    echo "   - Check TalkFlow execution in your chat page\n";
    echo "\n";
    echo "4. View Agent details:\n";
    echo "   Agent ID: {$agent->getId()}\n";
    echo "   Organization ID: {$agent->getOrganization()->getId()}\n";
    echo "\n";
    echo "Happy testing! 🚀\n";
    echo "\n";

} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
