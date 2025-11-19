<?php

declare(strict_types=1);

/**
 * Agent Migration Script for WhatsApp Cloud API
 *
 * This script helps migrate existing Agents from Evolution API to WhatsApp Cloud API
 * by updating their whatsappMetadata field with phone_number_id and access_token.
 *
 * Usage:
 *   docker-compose exec app php scripts/migrate-agents-to-cloud-api.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

// Boot environment
(new Dotenv())->bootEnv(__DIR__ . '/../.env');

// Boot Symfony kernel
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get services
$entityManager = $container->get('doctrine')->getManager();
$agentRepository = $container->get('App\Repository\AgentRepository');

echo "\n";
echo "========================================\n";
echo "WhatsApp Cloud API Agent Migration Tool\n";
echo "========================================\n\n";

// Find all active WhatsApp agents
$agents = $agentRepository->createQueryBuilder('a')
    ->where('a.whatsappActive = :active')
    ->setParameter('active', true)
    ->getQuery()
    ->getResult();

if (empty($agents)) {
    echo "No active WhatsApp agents found.\n";
    exit(0);
}

echo "Found " . count($agents) . " active WhatsApp agent(s)\n\n";

foreach ($agents as $agent) {
    echo "----------------------------------------\n";
    echo "Agent ID: " . $agent->getId() . "\n";
    echo "Name: " . $agent->getName() . "\n";
    echo "Organization: " . $agent->getOrganization()->getName() . "\n";
    echo "Evolution Instance: " . ($agent->getWhatsappInstanceName() ?? 'N/A') . "\n";

    // Check if already configured for Cloud API
    if ($agent->isWhatsAppCloudConfigured()) {
        echo "Status: ✅ Already configured for Cloud API\n";
        echo "  phone_number_id: " . $agent->getWhatsAppPhoneNumberId() . "\n";
        echo "  access_token: " . substr($agent->getWhatsAppAccessToken(), 0, 20) . "...\n";
        echo "\n";
        continue;
    }

    echo "Status: ⚠️  Not configured for Cloud API\n\n";

    echo "Enter WhatsApp Cloud API Configuration:\n";
    echo "-----------------------------------------\n";

    // Get phone_number_id
    echo "phone_number_id (15 digits from Meta): ";
    $phoneNumberId = trim(fgets(STDIN));

    if (empty($phoneNumberId)) {
        echo "❌ Skipped (no phone_number_id provided)\n\n";
        continue;
    }

    // Get access_token
    echo "access_token (starts with EAA): ";
    $accessToken = trim(fgets(STDIN));

    if (empty($accessToken)) {
        echo "❌ Skipped (no access_token provided)\n\n";
        continue;
    }

    // Get optional display_phone_number
    echo "display_phone_number (optional, e.g., +5511999999999): ";
    $displayPhoneNumber = trim(fgets(STDIN));

    // Validate inputs
    if (!preg_match('/^\d{15}$/', $phoneNumberId)) {
        echo "❌ ERROR: Invalid phone_number_id format (expected 15 digits)\n\n";
        continue;
    }

    if (!str_starts_with($accessToken, 'EAA')) {
        echo "❌ ERROR: Invalid access_token format (expected to start with EAA)\n\n";
        continue;
    }

    // Build metadata
    $metadata = [
        'phone_number_id' => $phoneNumberId,
        'access_token' => $accessToken,
        'migrated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        'migrated_from' => 'evolution_api',
    ];

    if (!empty($displayPhoneNumber)) {
        $metadata['display_phone_number'] = $displayPhoneNumber;
    }

    // Update agent
    try {
        $agent->setWhatsAppMetadata($metadata);
        $agent->validateWhatsAppCloudConfig();

        $entityManager->persist($agent);
        $entityManager->flush();

        echo "✅ Configuration saved successfully!\n";
        echo "  phone_number_id: " . $phoneNumberId . "\n";
        echo "  access_token: " . substr($accessToken, 0, 20) . "...\n";
        if ($displayPhoneNumber) {
            echo "  display_phone_number: " . $displayPhoneNumber . "\n";
        }
        echo "\n";

    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "========================================\n";
echo "Migration complete!\n";
echo "========================================\n\n";

echo "Next Steps:\n";
echo "1. Configure webhook in Meta for Developers:\n";
echo "   - URL: https://yourdomain.com/api/webhooks/whatsapp/cloud\n";
echo "   - Verify Token: (your WHATSAPP_VERIFY_TOKEN from .env)\n";
echo "   - Subscribe to: messages, message_status\n\n";
echo "2. Update .env file with your Meta credentials if using default phone_number_id\n\n";
echo "3. Test webhook verification:\n";
echo "   curl -X GET \"https://yourdomain.com/api/webhooks/whatsapp/cloud?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test\"\n\n";

exit(0);
