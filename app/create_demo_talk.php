<?php
// Simple script to create demo Talk with TreeFlow

$dsn = "host=database port=5432 dbname=luminai_db user=luminai_user password=LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9";
$pdo = new PDO("pgsql:$dsn");

echo "=== Creating Demo Talk ===\n\n";

$orgId = '0199cac4-dd0e-7ffe-8f47-4eb654dd19b2';
$agentId = '222e702d-6ccb-42b2-a0d7-5f61896b951c';
$treeFlowId = 'fe1bb139-7088-48ce-bd4d-4ec2941eb23e';

// Create a simple company first
$sql = "INSERT INTO company (id, organization_id, name, time_zone, created_at, updated_at)
        SELECT gen_random_uuid(), :org_id, 'Demo Corp', 'UTC', NOW(), NOW()
        WHERE NOT EXISTS (SELECT 1 FROM company WHERE organization_id = :org_id LIMIT 1)
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['org_id' => $orgId]);
$companyId = $stmt->fetchColumn();

if (!$companyId) {
    // Get existing company
    $stmt = $pdo->prepare("SELECT id FROM company WHERE organization_id = :org_id LIMIT 1");
    $stmt->execute(['org_id' => $orgId]);
    $companyId = $stmt->fetchColumn();
}
echo "✓ Company ID: $companyId\n";

// Create or get contact (use unique email per organization)
$email = 'john-' . substr($orgId, 0, 8) . '@demo.com';
$stmt = $pdo->prepare("SELECT id FROM contact WHERE email = :email AND organization_id = :org_id");
$stmt->execute(['email' => $email, 'org_id' => $orgId]);
$contactId = $stmt->fetchColumn();

if (!$contactId) {
    $sql = "INSERT INTO contact (id, organization_id, company_id, name, first_name, last_name, email, phone, email_opt_out, do_not_call, created_at, updated_at)
            VALUES (gen_random_uuid(), :org_id, :company_id, 'John Doe', 'John', 'Doe', :email, '+15550123', false, false, NOW(), NOW())
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['org_id' => $orgId, 'company_id' => $companyId, 'email' => $email]);
    $contactId = $stmt->fetchColumn();
}
echo "✓ Contact ID: $contactId (email: $email)\n";

// Get first talk_type
$stmt = $pdo->query("SELECT id FROM talk_type LIMIT 1");
$talkTypeId = $stmt->fetchColumn();
if (!$talkTypeId) {
    // Create a default talk type
    $stmt = $pdo->prepare("INSERT INTO talk_type (id, organization_id, name, created_at, updated_at) VALUES (gen_random_uuid(), :org_id, 'General', NOW(), NOW()) RETURNING id");
    $stmt->execute(['org_id' => $orgId]);
    $talkTypeId = $stmt->fetchColumn();
}

// Get or create user as owner
$stmt = $pdo->prepare("SELECT id FROM \"user\" WHERE organization_id = :org_id LIMIT 1");
$stmt->execute(['org_id' => $orgId]);
$ownerId = $stmt->fetchColumn();

if (!$ownerId) {
    // Create a system user for demo
    // Password: 'demo123' hashed with bcrypt
    $passwordHash = password_hash('demo123', PASSWORD_BCRYPT);
    $sql = "INSERT INTO \"user\" (id, organization_id, name, email, password, verified, terms_signed,
            failed_login_attempts, two_factor_enabled, must_change_password, passkey_enabled,
            email_notifications_enabled, sms_notifications_enabled, calendar_sync_enabled,
            agent, login_count, visible, profile_completeness, locked, created_at, updated_at)
            VALUES (gen_random_uuid(), :org_id, 'Demo User', 'demo@luminai.com', :password,
            true, true, 0, false, false, false, true, false, false, false, 0, true, 0, false, NOW(), NOW())
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['org_id' => $orgId, 'password' => $passwordHash]);
    $ownerId = $stmt->fetchColumn();
    echo "✓ Created User ID: $ownerId\n";
} else {
    echo "✓ User ID: $ownerId\n";
}

// Create talk
$sql = "INSERT INTO talk (id, organization_id, talk_type_id, owner_id, contact_id, subject, channel, status, message_count, archived, internal, tree_flow_id, paused, created_at, updated_at)
        VALUES (gen_random_uuid(), :org_id, :talk_type_id, :owner_id, :contact_id, 'AI SDR Demo Conversation', 1, 1, 0, false, false, :tree_flow_id, false, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'org_id' => $orgId,
    'talk_type_id' => $talkTypeId,
    'owner_id' => $ownerId,
    'contact_id' => $contactId,
    'tree_flow_id' => $treeFlowId
]);
$talkId = $stmt->fetchColumn();
echo "✓ Talk ID: $talkId\n";

// Link agent to talk
$sql = "INSERT INTO talk_agents (talk_generated_id, agent_id) VALUES (:talk_id, :agent_id)";
$stmt = $pdo->prepare($sql);
$stmt->execute(['talk_id' => $talkId, 'agent_id' => $agentId]);
echo "✓ Linked Agent to Talk\n";

// Initialize TalkFlow using the TreeFlow's talkFlow template
$sql = "SELECT talk_flow FROM tree_flow WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $treeFlowId]);
$talkFlowTemplate = $stmt->fetchColumn();

$sql = "UPDATE talk SET talk_flow = :talk_flow WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['talk_flow' => $talkFlowTemplate, 'id' => $talkId]);
echo "✓ Initialized TalkFlow\n";

// Create inbound message
$sql = "INSERT INTO talk_message (id, talk_id, organization_id, from_contact_id, body, direction, message_type, read_prop, internal, system_prop, sent_at, created_at, updated_at)
        VALUES (gen_random_uuid(), :talk_id, :org_id, :contact_id, :body, 'inbound', 'text', false, false, false, NOW(), NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'talk_id' => $talkId,
    'org_id' => $orgId,
    'contact_id' => $contactId,
    'body' => "Hi! I'm interested in learning more about your services. Can you help me understand what you offer?"
]);
$messageId = $stmt->fetchColumn();

// Update talk message count
$sql = "UPDATE talk SET message_count = message_count + 1, date_last_message = NOW() WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $talkId]);

echo "✓ Created Message ID: $messageId\n\n";

echo "=== Setup Complete! ===\n\n";
echo "Talk ID: $talkId\n";
echo "Message ID: $messageId\n";
echo "Contact: John Doe (john@demo.com)\n";
echo "Agent: AI SDR Agent - Sarah\n";
echo "TreeFlow: test_treeflow_for_api_23\n\n";

echo "The message has been created and will trigger async processing!\n";
echo "Start the messenger worker to see the AI agent respond:\n\n";
echo "  docker-compose exec app php bin/console messenger:consume async -vv\n\n";
