<?php

$dsn = "host=database port=5432 dbname=luminai_db user=luminai_user password=LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9";
$pdo = new PDO("pgsql:$dsn");

echo "=== Criando TreeFlow Avelum ===\n\n";

$orgId = '0199cac4-dd0e-7ffe-8f47-4eb654dd19b2'; // Avelum organization

// Create TreeFlow structure
$treeFlowData = [
    'steps' => [
        [
            'id' => 'step_' . bin2hex(random_bytes(8)),
            'slug' => 'saudacao',
            'name' => 'Saudação e Apresentação',
            'order' => 1,
            'description' => 'Saudar o usuário e apresentar a Avelum',
            'type' => 'message',
            'actions' => [
                [
                    'id' => 'action_' . bin2hex(random_bytes(8)),
                    'slug' => 'mensagem_saudacao',
                    'name' => 'Mensagem de Saudação',
                    'type' => 'ask_question',
                    'prompt' => 'Olá! Sou assistente da Avelum, uma empresa especializada em soluções de Inteligência Artificial para empresas. Desenvolvemos sistemas de IA personalizados que otimizam processos, automatizam tarefas e geram insights valiosos para o seu negócio. Como posso ajudá-lo hoje?',
                    'required' => true,
                    'order' => 1
                ]
            ],
            'outputs' => [
                [
                    'id' => 'output_' . bin2hex(random_bytes(8)),
                    'slug' => 'usuario_respondeu',
                    'name' => 'Usuário Respondeu',
                    'condition' => 'any_response',
                    'next_step' => 'interesse',
                    'order' => 1
                ]
            ]
        ],
        [
            'id' => 'step_' . bin2hex(random_bytes(8)),
            'slug' => 'interesse',
            'name' => 'Verificar Interesse',
            'order' => 2,
            'description' => 'Perguntar se o usuário tem interesse em conhecer as soluções',
            'type' => 'question',
            'actions' => [
                [
                    'id' => 'action_' . bin2hex(random_bytes(8)),
                    'slug' => 'pergunta_interesse',
                    'name' => 'Pergunta sobre Interesse',
                    'type' => 'ask_question',
                    'prompt' => 'Você teria interesse em conhecer nossas soluções de IA e como elas podem transformar o seu negócio?',
                    'required' => true,
                    'order' => 1
                ]
            ],
            'outputs' => [
                [
                    'id' => 'output_' . bin2hex(random_bytes(8)),
                    'slug' => 'tem_interesse',
                    'name' => 'Tem Interesse',
                    'condition' => 'contains_affirmative',
                    'next_step' => 'enviar_link',
                    'order' => 1
                ],
                [
                    'id' => 'output_' . bin2hex(random_bytes(8)),
                    'slug' => 'sem_interesse',
                    'name' => 'Sem Interesse',
                    'condition' => 'contains_negative',
                    'next_step' => 'encerramento',
                    'order' => 2
                ]
            ]
        ],
        [
            'id' => 'step_' . bin2hex(random_bytes(8)),
            'slug' => 'enviar_link',
            'name' => 'Enviar Link das Soluções',
            'order' => 3,
            'description' => 'Enviar link para a página de soluções',
            'type' => 'message',
            'actions' => [
                [
                    'id' => 'action_' . bin2hex(random_bytes(8)),
                    'slug' => 'mensagem_link',
                    'name' => 'Mensagem com Link',
                    'type' => 'send_message',
                    'prompt' => 'Excelente! Você pode conhecer todas as nossas soluções de IA acessando: https://localhost/conheca\n\nLá você encontrará detalhes sobre nossos produtos, casos de sucesso e como podemos ajudar sua empresa a crescer com tecnologia de ponta. Caso tenha alguma dúvida, estou à disposição!',
                    'required' => false,
                    'order' => 1
                ]
            ],
            'outputs' => []
        ],
        [
            'id' => 'step_' . bin2hex(random_bytes(8)),
            'slug' => 'encerramento',
            'name' => 'Agradecimento e Encerramento',
            'order' => 4,
            'description' => 'Agradecer e encerrar a conversa',
            'type' => 'message',
            'actions' => [
                [
                    'id' => 'action_' . bin2hex(random_bytes(8)),
                    'slug' => 'mensagem_agradecimento',
                    'name' => 'Mensagem de Agradecimento',
                    'type' => 'send_message',
                    'prompt' => 'Sem problemas! Agradecemos seu tempo. Caso mude de ideia ou tenha alguma dúvida sobre soluções de IA no futuro, estaremos aqui para ajudar. Tenha um ótimo dia!',
                    'required' => false,
                    'order' => 1
                ]
            ],
            'outputs' => []
        ]
    ]
];

// Convert to JSON for storage
$treeFlowJson = json_encode($treeFlowData, JSON_PRETTY_PRINT);
$talkFlowTemplate = json_encode([
    'avelum_teste' => [
        'currentStep' => 'saudacao',
        'steps' => [
            'saudacao' => [
                'order' => 1,
                'completed' => false,
                'timestamp' => null,
                'selectedOutput' => null,
                'actions' => [
                    'mensagem_saudacao' => ''
                ],
                'outputs' => [
                    'usuario_respondeu' => ''
                ]
            ],
            'interesse' => [
                'order' => 2,
                'completed' => false,
                'timestamp' => null,
                'selectedOutput' => null,
                'actions' => [
                    'pergunta_interesse' => ''
                ],
                'outputs' => [
                    'tem_interesse' => '',
                    'sem_interesse' => ''
                ]
            ],
            'enviar_link' => [
                'order' => 3,
                'completed' => false,
                'timestamp' => null,
                'selectedOutput' => null,
                'actions' => [
                    'mensagem_link' => ''
                ],
                'outputs' => []
            ],
            'encerramento' => [
                'order' => 4,
                'completed' => false,
                'timestamp' => null,
                'selectedOutput' => null,
                'actions' => [
                    'mensagem_agradecimento' => ''
                ],
                'outputs' => []
            ]
        ]
    ]
], JSON_PRETTY_PRINT);

// Insert TreeFlow
$sql = "INSERT INTO tree_flow (id, organization_id, name, slug, json_structure, talk_flow, active, version_prop, created_at, updated_at)
        VALUES (gen_random_uuid(), :org_id, 'Avelum - Fluxo de Apresentação', 'avelum_teste',
        :json_structure, :talk_flow, true, 1, NOW(), NOW())
        RETURNING id, slug";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'org_id' => $orgId,
    'json_structure' => $treeFlowJson,
    'talk_flow' => $talkFlowTemplate
]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "✓ TreeFlow criado com sucesso!\n\n";
echo "ID: {$result['id']}\n";
echo "Slug: {$result['slug']}\n";
echo "Nome: Avelum - Fluxo de Apresentação\n\n";

echo "Estrutura do Fluxo:\n";
echo "1. Saudação → Apresenta a Avelum e suas soluções de IA\n";
echo "2. Interesse → Pergunta se o usuário quer conhecer as soluções\n";
echo "   ├─ Se SIM → 3. Enviar Link (https://localhost/conheca)\n";
echo "   └─ Se NÃO → 4. Encerramento (Agradecimento)\n\n";

echo "Para usar este TreeFlow, crie um Talk associado a ele.\n";
