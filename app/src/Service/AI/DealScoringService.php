<?php

declare(strict_types=1);

namespace App\Service\AI;

use App\Entity\Deal;
use App\Service\OpenAI\OpenAIService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DealScoringService
{
    public function __construct(
        private OpenAIService $openAI,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * Analyze deal using AI and update scores
     */
    public function analyzeDeal(Deal $deal): array
    {
        $context = $this->buildDealContext($deal);

        $systemPrompt = 'You are a sales pipeline AI analyst. Analyze deals and provide structured JSON responses.';

        $userPrompt = <<<PROMPT
Analyze this sales deal and provide:

1. Health Score (0-100): Overall deal health
2. Win Probability (0-100): Likelihood to close
3. Risk Factors: Array of concerning issues
4. Strengths: Array of positive signals
5. Recommended Actions: Array of next steps

Deal Data:
{$context}

Respond ONLY with valid JSON in this exact format:
{
  "healthScore": 85,
  "winProbability": 72.5,
  "riskFactors": ["No contact in 15 days", "Competitor mentioned"],
  "strengths": ["Budget confirmed", "Decision maker engaged"],
  "recommendedActions": ["Follow up within 24h", "Send pricing proposal"]
}
PROMPT;

        try {
            $response = $this->openAI->analyzeStructured($userPrompt, $systemPrompt);
            $analysis = json_decode($response, true);

            if (!$analysis || json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from AI');
            }

            // Update deal with AI insights
            $deal->setAiScore($analysis['healthScore'] ?? null);
            $winProbability = $analysis['winProbability'] ?? null;
            $deal->setWinProbability($winProbability !== null ? (string) $winProbability : null);
            $deal->setRiskFactors($analysis['riskFactors'] ?? []);
            $deal->setRecommendedActions($analysis['recommendedActions'] ?? []);
            $deal->setLastAiAnalysisAt(new \DateTimeImmutable());

            $this->em->flush();

            $this->logger->info('Deal analyzed successfully', [
                'deal_id' => $deal->getId()->toRfc4122(),
                'score' => $deal->getAiScore(),
                'win_probability' => $deal->getWinProbability(),
            ]);

            return $analysis;

        } catch (\Exception $e) {
            $this->logger->error('Failed to analyze deal', [
                'deal_id' => $deal->getId()->toRfc4122(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build context for AI analysis
     */
    private function buildDealContext(Deal $deal): string
    {
        $pipeline = $deal->getPipeline();
        $company = $deal->getCompany();

        return json_encode([
            'deal' => [
                'name' => $deal->getName(),
                'amount' => $deal->getExpectedAmount(),
                'currency' => $deal->getCurrency(),
                'stage' => $deal->getCurrentStage()?->getName(),
                'stage_probability' => $deal->getCurrentStage()?->getProbability(),
                'days_in_stage' => $deal->getDaysInCurrentStage(),
                'created_days_ago' => $deal->getCreatedAt()->diff(new \DateTime())->days,
                'priority' => $deal->getPriority(),
                'expected_closure_date' => $deal->getExpectedClosureDate()?->format('Y-m-d'),
            ],
            'company' => [
                'name' => $company?->getName(),
                'size' => $company?->getCompanySize(),
                'industry' => $company?->getIndustry(),
                'annual_revenue' => $company?->getAnnualRevenue(),
            ],
            'activities' => [
                'total_talks' => $deal->getTalks()->count(),
                'last_activity_date' => $deal->getLastActivityDate()?->format('Y-m-d'),
                'days_since_activity' => $deal->getDaysStale(),
                'completed_tasks' => $deal->getTasks()->filter(fn($t) => $t->getCompletionPercentage() >= 100)->count(),
                'pending_tasks' => $deal->getTasks()->filter(fn($t) => $t->getCompletionPercentage() < 100)->count(),
            ],
            'engagement' => [
                'primary_contact' => $deal->getPrimaryContact()?->getName(),
                'contact_title' => $deal->getPrimaryContact()?->getTitle(),
                'total_contacts' => $deal->getContacts()->count(),
            ],
            'competition' => [
                'competitors' => $deal->getCompetitors()->map(fn($c) => $c->getName())->toArray(),
            ],
            'benchmarks' => [
                'pipeline_avg_cycle_time' => $pipeline?->getAvgCycleTime(),
                'pipeline_win_rate' => $pipeline?->getWinRate(),
                'pipeline_avg_deal_size' => $pipeline?->getAvgDealSize(),
            ],
        ], JSON_PRETTY_PRINT);
    }
}
