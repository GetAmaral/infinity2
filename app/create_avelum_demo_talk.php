<?php

$dsn = "host=database port=5432 dbname=luminai_db user=luminai_user password=LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9";
$pdo = new PDO("pgsql:$dsn");

echo "=== Criando Talk de Demonstração Avelum ===\n\n";

$orgId = '0199cac4-dd0e-7ffe-8f47-4eb654dd19b2'; // Avelum organization
$agentId = '222e702d-6ccb-42b2-a0d7-5f61896b951c'; // Sarah agent
$treeFlowId = '750b422a-b7c5-48dc-be24-e8b37ed0cb04'; // TreeFlow avelum_teste

// Get or create company
$sql = "INSERT INTO company (id, organization_id, name, time_zone, created_at, updated_at)
        SELECT gen_random_uuid(), :org_id, 'Cliente Teste Ltda', 'America/Sao_Paulo', NOW(), NOW()
        WHERE NOT EXISTS (SELECT 1 FROM company WHERE organization_id = :org_id AND name = 'Cliente Teste Ltda')
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['org_id' => $orgId]);
$companyId = $stmt->fetchColumn();

if (!$companyId) {
    $stmt = $pdo->prepare("SELECT id FROM company WHERE organization_id = :org_id AND name = 'Cliente Teste Ltda' LIMIT 1");
    $stmt->execute(['org_id' => $orgId]);
    $companyId = $stmt->fetchColumn();
}
echo "✓ Company ID: $companyId\n";

// Create contact with unique email
$email = 'maria.silva@clienteteste.com.br';
$stmt = $pdo->prepare("SELECT id FROM contact WHERE email = :email AND organization_id = :org_id");
$stmt->execute(['email' => $email, 'org_id' => $orgId]);
$contactId = $stmt->fetchColumn();

if (!$contactId) {
    $sql = "INSERT INTO contact (id, organization_id, company_id, name, first_name, last_name, email, phone, email_opt_out, do_not_call, created_at, updated_at)
            VALUES (gen_random_uuid(), :org_id, :company_id, 'Maria Silva', 'Maria', 'Silva', :email, '+5511987654321', false, false, NOW(), NOW())
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['org_id' => $orgId, 'company_id' => $companyId, 'email' => $email]);
    $contactId = $stmt->fetchColumn();
}
echo "✓ Contact ID: $contactId (Maria Silva)\n";

// Get or create user
$stmt = $pdo->prepare("SELECT id FROM \"user\" WHERE organization_id = :org_id LIMIT 1");
$stmt->execute(['org_id' => $orgId]);
$ownerId = $stmt->fetchColumn();

if (!$ownerId) {
    $passwordHash = password_hash('demo123', PASSWORD_BCRYPT);
    $sql = "INSERT INTO \"user\" (id, organization_id, name, email, password, verified, terms_signed,
            failed_login_attempts, two_factor_enabled, must_change_password, passkey_enabled,
            email_notifications_enabled, sms_notifications_enabled, calendar_sync_enabled,
            agent, login_count, visible, profile_completeness, locked, created_at, updated_at)
            VALUES (gen_random_uuid(), :org_id, 'Demo User Avelum', 'demo@avelum.ai', :password,
            true, true, 0, false, false, false, true, false, false, false, 0, true, 0, false, NOW(), NOW())
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['org_id' => $orgId, 'password' => $passwordHash]);
    $ownerId = $stmt->fetchColumn();
    echo "✓ Created User ID: $ownerId\n";
} else {
    echo "✓ User ID: $ownerId\n";
}

// Get talk_type
$stmt = $pdo->prepare("SELECT id FROM talk_type WHERE organization_id = :org_id LIMIT 1");
$stmt->execute(['org_id' => $orgId]);
$talkTypeId = $stmt->fetchColumn();
if (!$talkTypeId) {
    $stmt = $pdo->prepare("INSERT INTO talk_type (id, organization_id, name, created_at, updated_at) VALUES (gen_random_uuid(), :org_id, 'WhatsApp', NOW(), NOW()) RETURNING id");
    $stmt->execute(['org_id' => $orgId]);
    $talkTypeId = $stmt->fetchColumn();
}

// Create talk
$sql = "INSERT INTO talk (id, organization_id, talk_type_id, owner_id, contact_id, subject, channel, status, message_count, archived, internal, tree_flow_id, paused, created_at, updated_at)
        VALUES (gen_random_uuid(), :org_id, :talk_type_id, :owner_id, :contact_id, 'Conversa Avelum - Maria Silva', 1, 1, 0, false, false, :tree_flow_id, false, NOW(), NOW())
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
echo "✓ Agente vinculado ao Talk\n";

// Initialize TalkFlow using the TreeFlow's talkFlow template
$sql = "SELECT talk_flow FROM tree_flow WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $treeFlowId]);
$talkFlowTemplate = $stmt->fetchColumn();

$sql = "UPDATE talk SET talk_flow = :talk_flow WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['talk_flow' => $talkFlowTemplate, 'id' => $talkId]);
echo "✓ TalkFlow inicializado\n\n";

echo "=== Demonstração Configurada! ===\n\n";
echo "Talk ID: $talkId\n";
echo "TreeFlow: avelum_teste\n";
echo "Contato: Maria Silva (maria.silva@clienteteste.com.br)\n";
echo "Agente: AI SDR Agent - Sarah\n\n";

echo "Para testar o fluxo, use o script send_demo_message.php editando o talkId para:\n";
echo "  '$talkId'\n\n";

echo "Ou envie uma mensagem manualmente via API/UI.\n";
