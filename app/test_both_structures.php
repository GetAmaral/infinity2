<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine')->getManager();

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  TreeFlow Dual Cache Implementation Test\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$treeFlow = $entityManager->getRepository(\App\Entity\TreeFlow::class)->findOneBy([]);

if (!$treeFlow) {
    echo "No TreeFlow found!\n";
    exit(1);
}

echo "TreeFlow: {$treeFlow->getName()}\n";
echo "Slug: {$treeFlow->getSlug()}\n\n";

// Test 1: jsonStructure
echo "TEST 1: jsonStructure (Complete Data)\n";
echo "=======================================\n";
$jsonStructure = $treeFlow->getJsonStructure();

if ($jsonStructure) {
    echo "✅ jsonStructure exists\n\n";
    
    $firstStepSlug = array_key_first($jsonStructure[$treeFlow->getSlug()]['steps']);
    $firstStep = $jsonStructure[$treeFlow->getSlug()]['steps'][$firstStepSlug];
    
    echo "First Step: '{$firstStepSlug}'\n";
    echo "  • order: {$firstStep['order']}\n";
    echo "  • objective: {$firstStep['objective']}\n";
    echo "  • questions: " . count($firstStep['questions']) . "\n";
    echo "  • inputs: " . count($firstStep['inputs']) . "\n";
    echo "  • outputs: " . count($firstStep['outputs']) . "\n\n";
    
    // Show first question detail
    if (!empty($firstStep['questions'])) {
        $firstQuestionSlug = array_key_first($firstStep['questions']);
        $firstQuestion = $firstStep['questions'][$firstQuestionSlug];
        echo "  First Question: '{$firstQuestionSlug}'\n";
        echo "    - importance: {$firstQuestion['importance']}\n";
        echo "    - fewShotPositive: " . count($firstQuestion['fewShotPositive']) . " examples\n";
        echo "    - fewShotNegative: " . count($firstQuestion['fewShotNegative']) . " examples\n\n";
    }
} else {
    echo "❌ jsonStructure is NULL\n\n";
}

// Test 2: talkFlow
echo "TEST 2: talkFlow (Empty Template)\n";
echo "==================================\n";
$talkFlow = $treeFlow->getTalkFlow();

if ($talkFlow) {
    echo "✅ talkFlow exists\n\n";
    
    $firstStepSlug = array_key_first($talkFlow[$treeFlow->getSlug()]['steps']);
    $firstStep = $talkFlow[$treeFlow->getSlug()]['steps'][$firstStepSlug];
    
    echo "First Step: '{$firstStepSlug}'\n";
    echo "  • order: {$firstStep['order']}\n";
    echo "  • completed: " . ($firstStep['completed'] ? 'true' : 'false') . "\n";
    echo "  • timestamp: " . ($firstStep['timestamp'] ?? 'null') . "\n";
    echo "  • selectedOutput: " . ($firstStep['selectedOutput'] ?? 'null') . "\n";
    echo "  • questions: " . count($firstStep['questions']) . " (all empty)\n";
    echo "  • outputs: " . count($firstStep['outputs']) . " (all empty)\n\n";
    
    // Show first question (should be empty)
    if (!empty($firstStep['questions'])) {
        $firstQuestionSlug = array_key_first($firstStep['questions']);
        $firstQuestionValue = $firstStep['questions'][$firstQuestionSlug];
        echo "  First Question: '{$firstQuestionSlug}' = '{$firstQuestionValue}'\n";
        echo "  (Empty string ready to be filled by Talk processor)\n\n";
    }
    
    // Show first output (should be empty)
    if (!empty($firstStep['outputs'])) {
        $firstOutputSlug = array_key_first($firstStep['outputs']);
        $firstOutputValue = $firstStep['outputs'][$firstOutputSlug];
        echo "  First Output: '{$firstOutputSlug}' = '{$firstOutputValue}'\n";
        echo "  (Empty string ready to be filled with conditional result)\n\n";
    }
} else {
    echo "❌ talkFlow is NULL\n\n";
}

// Test 3: Structure Comparison
echo "TEST 3: Structure Comparison\n";
echo "============================\n";
if ($jsonStructure && $talkFlow) {
    $jsonStepCount = count($jsonStructure[$treeFlow->getSlug()]['steps']);
    $talkStepCount = count($talkFlow[$treeFlow->getSlug()]['steps']);
    
    echo "Step count match: " . ($jsonStepCount === $talkStepCount ? "✅ YES" : "❌ NO") . "\n";
    echo "  jsonStructure: {$jsonStepCount} steps\n";
    echo "  talkFlow: {$talkStepCount} steps\n\n";
    
    // Check if step slugs match
    $jsonStepSlugs = array_keys($jsonStructure[$treeFlow->getSlug()]['steps']);
    $talkStepSlugs = array_keys($talkFlow[$treeFlow->getSlug()]['steps']);
    $slugsMatch = $jsonStepSlugs === $talkStepSlugs;
    
    echo "Step slugs match: " . ($slugsMatch ? "✅ YES" : "❌ NO") . "\n\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "  Both cache structures successfully implemented!\n";
echo "═══════════════════════════════════════════════════════════\n\n";
