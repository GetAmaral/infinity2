<?php

$dsn = "host=database port=5432 dbname=luminai_db user=luminai_user password=LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9";
$pdo = new PDO("pgsql:$dsn");

echo "=== Creating Steps for Avelum TreeFlow ===\n\n";

$treeFlowId = '750b422a-b7c5-48dc-be24-e8b37ed0cb04';
$orgId = '0199cac4-dd0e-7ffe-8f47-4eb654dd19b2';

// Create Step 1: Saudação
$sql = "INSERT INTO step (id, tree_flow_id, name, slug, objective, position_x, position_y, first_prop, view_order, created_at, updated_at)
        VALUES (gen_random_uuid(), :tree_flow_id, 'Saudação e Apresentação', 'saudacao',
        'Saudar o usuário e apresentar a Avelum', 100, 100, true, 1, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['tree_flow_id' => $treeFlowId]);
$step1Id = $stmt->fetchColumn();
echo "✓ Step 1 (Saudação) ID: $step1Id\n";

// Create output for Step 1
$sql = "INSERT INTO step_output (id, step_id, name, created_at, updated_at)
        VALUES (gen_random_uuid(), :step_id, 'Usuário Respondeu', NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['step_id' => $step1Id]);
$output1Id = $stmt->fetchColumn();
echo "  ✓ Output: $output1Id\n";

// Create Step 2: Interesse
$sql = "INSERT INTO step (id, tree_flow_id, name, slug, objective, position_x, position_y, first_prop, view_order, created_at, updated_at)
        VALUES (gen_random_uuid(), :tree_flow_id, 'Verificar Interesse', 'interesse',
        'Perguntar se o usuário tem interesse em conhecer as soluções', 500, 100, false, 2, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['tree_flow_id' => $treeFlowId]);
$step2Id = $stmt->fetchColumn();
echo "✓ Step 2 (Interesse) ID: $step2Id\n";

// Create outputs for Step 2
$sql = "INSERT INTO step_output (id, step_id, name, created_at, updated_at)
        VALUES (gen_random_uuid(), :step_id, 'Tem Interesse', NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['step_id' => $step2Id]);
$output2aId = $stmt->fetchColumn();
echo "  ✓ Output (Sim): $output2aId\n";

$sql = "INSERT INTO step_output (id, step_id, name, created_at, updated_at)
        VALUES (gen_random_uuid(), :step_id, 'Sem Interesse', NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['step_id' => $step2Id]);
$output2bId = $stmt->fetchColumn();
echo "  ✓ Output (Não): $output2bId\n";

// Create Step 3: Enviar Link
$sql = "INSERT INTO step (id, tree_flow_id, name, slug, objective, position_x, position_y, first_prop, view_order, created_at, updated_at)
        VALUES (gen_random_uuid(), :tree_flow_id, 'Enviar Link das Soluções', 'enviar_link',
        'Enviar link para a página de soluções', 900, 50, false, 3, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['tree_flow_id' => $treeFlowId]);
$step3Id = $stmt->fetchColumn();
echo "✓ Step 3 (Enviar Link) ID: $step3Id\n";

// Create Step 4: Encerramento
$sql = "INSERT INTO step (id, tree_flow_id, name, slug, objective, position_x, position_y, first_prop, view_order, created_at, updated_at)
        VALUES (gen_random_uuid(), :tree_flow_id, 'Agradecimento e Encerramento', 'encerramento',
        'Agradecer e encerrar a conversa', 900, 200, false, 4, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['tree_flow_id' => $treeFlowId]);
$step4Id = $stmt->fetchColumn();
echo "✓ Step 4 (Encerramento) ID: $step4Id\n\n";

// Create connections
echo "Creating connections...\n";

// Step 1 -> Step 2
$sql = "INSERT INTO step_connection (id, source_output_id, target_step_id, created_at, updated_at)
        VALUES (gen_random_uuid(), :output_id, :target_step_id, NOW(), NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute(['output_id' => $output1Id, 'target_step_id' => $step2Id]);
echo "✓ Connected: Saudação -> Interesse\n";

// Step 2 (Sim) -> Step 3
$stmt->execute(['output_id' => $output2aId, 'target_step_id' => $step3Id]);
echo "✓ Connected: Interesse (Sim) -> Enviar Link\n";

// Step 2 (Não) -> Step 4
$stmt->execute(['output_id' => $output2bId, 'target_step_id' => $step4Id]);
echo "✓ Connected: Interesse (Não) -> Encerramento\n\n";

echo "=== Steps Created Successfully! ===\n\n";
echo "TreeFlow: avelum_teste\n";
echo "Steps: 4\n";
echo "Connections: 3\n\n";
echo "You can now view the TreeFlow in the canvas editor!\n";
