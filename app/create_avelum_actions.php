<?php

$dsn = "host=database port=5432 dbname=luminai_db user=luminai_user password=LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9";
$pdo = new PDO("pgsql:$dsn");

echo "=== Creating Actions for Avelum TreeFlow Steps ===\n\n";

$treeFlowId = '750b422a-b7c5-48dc-be24-e8b37ed0cb04';

// Get step IDs
$stmt = $pdo->prepare("SELECT id, slug FROM step WHERE tree_flow_id = :tree_flow_id ORDER BY view_order");
$stmt->execute(['tree_flow_id' => $treeFlowId]);
$steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stepIds = [];
foreach ($steps as $step) {
    $stepIds[$step['slug']] = $step['id'];
}

echo "Found steps:\n";
foreach ($stepIds as $slug => $id) {
    echo "  - $slug: $id\n";
}
echo "\n";

// Action 1: Saudação
$prompt1 = "Você é um assistente virtual da Avelum. Sua tarefa é saudar o cliente de forma profissional e apresentar brevemente a empresa.

A Avelum é especializada em soluções de Inteligência Artificial para empresas, desenvolvendo sistemas de IA personalizados que otimizam processos, automatizam tarefas e geram insights valiosos para o negócio.

Seja cordial, profissional e demonstre interesse em ajudar o cliente.";

$fewShots1 = json_encode([
    [
        'user' => 'Olá',
        'assistant' => 'Olá! Sou assistente da Avelum, uma empresa especializada em soluções de Inteligência Artificial para empresas. Desenvolvemos sistemas de IA personalizados que otimizam processos, automatizam tarefas e geram insights valiosos para o seu negócio. Como posso ajudá-lo hoje?'
    ],
    [
        'user' => 'Oi',
        'assistant' => 'Oi! Muito prazer! Sou da Avelum, empresa líder em soluções de IA corporativa. Criamos sistemas inteligentes sob medida que transformam a forma como empresas operam. Em que posso ajudá-lo?'
    ],
    [
        'user' => 'Bom dia',
        'assistant' => 'Bom dia! Que bom ter você aqui! Sou assistente da Avelum, especializada em Inteligência Artificial para empresas. Desenvolvemos soluções que automatizam processos e geram resultados reais. Como posso ser útil hoje?'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$sql = "INSERT INTO step_action (id, step_id, name, slug, prompt, few_shot, importance, view_order, created_at, updated_at)
        VALUES (gen_random_uuid(), :step_id, 'Saudação ao Cliente', 'acao_saudacao', :prompt, :few_shot, 3, 1, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'step_id' => $stepIds['saudacao'],
    'prompt' => $prompt1,
    'few_shot' => $fewShots1
]);
$action1Id = $stmt->fetchColumn();
echo "✓ Action 1 (Saudação) ID: $action1Id\n";

// Action 2: Verificar Interesse
$prompt2 = "Agora você deve perguntar se o cliente tem interesse em conhecer as soluções de IA da Avelum.

Seja direto mas amigável. Explique brevemente como as soluções da Avelum podem transformar o negócio do cliente.

IMPORTANTE: Você deve fazer uma pergunta clara esperando uma resposta sim/não do cliente.";

$fewShots2 = json_encode([
    [
        'user' => 'Interessante, gostaria de saber mais',
        'assistant' => 'Que ótimo! Você teria interesse em conhecer nossas soluções de IA e como elas podem transformar o seu negócio? Posso lhe mostrar casos de sucesso e benefícios concretos.'
    ],
    [
        'user' => 'Vocês trabalham com que exatamente?',
        'assistant' => 'Trabalhamos com IA aplicada a processos empresariais - automatização, análise de dados, chatbots inteligentes e muito mais. Você teria interesse em conhecer nossas soluções e como elas podem beneficiar especificamente o seu negócio?'
    ],
    [
        'user' => 'Pode me contar mais',
        'assistant' => 'Claro! Nossas soluções incluem automação de processos, análise preditiva, assistentes virtuais e sistemas de recomendação. Tudo personalizado para sua empresa. Você gostaria de conhecer melhor essas soluções e ver como podem impactar seus resultados?'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$sql = "INSERT INTO step_action (id, step_id, name, slug, prompt, few_shot, importance, view_order, created_at, updated_at)
        VALUES (gen_random_uuid(), :step_id, 'Pergunta sobre Interesse', 'acao_interesse', :prompt, :few_shot, 3, 1, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'step_id' => $stepIds['interesse'],
    'prompt' => $prompt2,
    'few_shot' => $fewShots2
]);
$action2Id = $stmt->fetchColumn();
echo "✓ Action 2 (Interesse) ID: $action2Id\n";

// Action 3: Enviar Link
$prompt3 = "O cliente demonstrou interesse! Agora você deve:
1. Agradecer o interesse
2. Enviar o link https://localhost/conheca onde ele pode conhecer todas as soluções
3. Explicar brevemente o que ele encontrará no link
4. Oferecer-se para tirar dúvidas

Seja entusiasta mas profissional.";

$fewShots3 = json_encode([
    [
        'user' => 'Sim, tenho interesse',
        'assistant' => 'Excelente! Você pode conhecer todas as nossas soluções de IA acessando: https://localhost/conheca

Lá você encontrará detalhes sobre nossos produtos, casos de sucesso e como podemos ajudar sua empresa a crescer com tecnologia de ponta. Caso tenha alguma dúvida, estou à disposição!'
    ],
    [
        'user' => 'Gostaria de conhecer sim',
        'assistant' => 'Perfeito! Preparei um link especial para você: https://localhost/conheca

Nessa página você vai encontrar informações completas sobre nossas soluções, exemplos práticos de implementação e depoimentos de clientes satisfeitos. Qualquer dúvida, é só falar!'
    ],
    [
        'user' => 'Tenho interesse, pode enviar',
        'assistant' => 'Ótimo! Segue o link: https://localhost/conheca

Lá você terá acesso a todo nosso portfólio de soluções de IA, incluindo casos de uso, benefícios e resultados reais. Explore à vontade e se precisar de ajuda, estarei aqui!'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$sql = "INSERT INTO step_action (id, step_id, name, slug, prompt, few_shot, importance, view_order, created_at, updated_at)
        VALUES (gen_random_uuid(), :step_id, 'Envio do Link', 'acao_enviar_link', :prompt, :few_shot, 3, 1, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'step_id' => $stepIds['enviar_link'],
    'prompt' => $prompt3,
    'few_shot' => $fewShots3
]);
$action3Id = $stmt->fetchColumn();
echo "✓ Action 3 (Enviar Link) ID: $action3Id\n";

// Action 4: Encerramento
$prompt4 = "O cliente não demonstrou interesse neste momento. Você deve:
1. Agradecer o tempo dele
2. Deixar a porta aberta para contato futuro
3. Ser cordial e profissional
4. Encerrar a conversa de forma amigável

IMPORTANTE: Não insista. Seja respeitoso com a decisão do cliente.";

$fewShots4 = json_encode([
    [
        'user' => 'Não, obrigado',
        'assistant' => 'Sem problemas! Agradecemos seu tempo. Caso mude de ideia ou tenha alguma dúvida sobre soluções de IA no futuro, estaremos aqui para ajudar. Tenha um ótimo dia!'
    ],
    [
        'user' => 'Por enquanto não',
        'assistant' => 'Entendo perfeitamente! Fico à disposição se no futuro você quiser conhecer nossas soluções. Agradecemos o contato e desejamos muito sucesso! Até breve!'
    ],
    [
        'user' => 'Não estou interessado',
        'assistant' => 'Tudo bem, sem problema algum! Obrigado pelo seu tempo. Se algum dia precisar de soluções de IA para sua empresa, estaremos aqui para ajudar. Tenha um excelente dia!'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$sql = "INSERT INTO step_action (id, step_id, name, slug, prompt, few_shot, importance, view_order, created_at, updated_at)
        VALUES (gen_random_uuid(), :step_id, 'Agradecimento Final', 'acao_encerramento', :prompt, :few_shot, 3, 1, NOW(), NOW())
        RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'step_id' => $stepIds['encerramento'],
    'prompt' => $prompt4,
    'few_shot' => $fewShots4
]);
$action4Id = $stmt->fetchColumn();
echo "✓ Action 4 (Encerramento) ID: $action4Id\n\n";

echo "=== Actions Created Successfully! ===\n\n";
echo "All 4 steps now have actions with:\n";
echo "  ✓ Detailed prompts to guide the AI\n";
echo "  ✓ Few-shot examples for better responses\n";
echo "  ✓ Clear instructions for each interaction\n\n";
echo "The AI agent is now ready to handle conversations following this TreeFlow!\n";
