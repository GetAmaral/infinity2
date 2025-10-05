<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StepQuestion;
use App\Form\StepQuestionFormType;
use App\Repository\StepQuestionRepository;
use App\Repository\StepRepository;
use App\Repository\TreeFlowRepository;
use App\Security\Voter\TreeFlowVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/treeflow')]
final class StepQuestionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly StepRepository $stepRepository,
        private readonly StepQuestionRepository $questionRepository,
    ) {}

    #[Route('/{treeflowId}/step/{stepId}/question/new', name: 'question_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $treeflowId, string $stepId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $question = new StepQuestion();
        $question->setStep($step);

        // Auto-calculate viewOrder - set to last position
        $maxOrder = $this->questionRepository->createQueryBuilder('q')
            ->select('MAX(q.viewOrder)')
            ->where('q.step = :step')
            ->setParameter('step', $step)
            ->getQuery()
            ->getSingleScalarResult();

        $question->setViewOrder(($maxOrder ?? 0) + 1);

        $form = $this->createForm(StepQuestionFormType::class, $question, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($question);
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Question created successfully',
                ]);
            }

            $this->addFlash('success', 'question.flash.created_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Return validation errors as JSON for AJAX requests
        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $html = $this->renderView('treeflow/question/_form_modal.html.twig', [
                'question' => $question,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
            ]);

            return $this->json([
                'success' => false,
                'html' => $html,
            ]);
        }

        // Handle modal/AJAX requests for GET
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/question/_form_modal.html.twig', [
                'question' => $question,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('treeflow/question/new.html.twig', [
            'question' => $question,
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/question/{questionId}/edit', name: 'question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $treeflowId, string $stepId, string $questionId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $question = $this->questionRepository->find($questionId);
        if (!$question || $question->getStep()->getId()->toString() !== $stepId) {
            throw $this->createNotFoundException('Question not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $form = $this->createForm(StepQuestionFormType::class, $question, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Question updated successfully',
                ]);
            }

            $this->addFlash('success', 'question.flash.updated_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Return validation errors as JSON for AJAX requests
        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $html = $this->renderView('treeflow/question/_form_modal.html.twig', [
                'question' => $question,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);

            return $this->json([
                'success' => false,
                'html' => $html,
            ]);
        }

        // Handle modal/AJAX requests for GET
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/question/_form_modal.html.twig', [
                'question' => $question,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('treeflow/question/edit.html.twig', [
            'question' => $question,
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/question/{questionId}/delete', name: 'question_delete', methods: ['POST'])]
    public function delete(Request $request, string $treeflowId, string $stepId, string $questionId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $question = $this->questionRepository->find($questionId);
        if (!$question || $question->getStep()->getId()->toString() !== $stepId) {
            throw $this->createNotFoundException('Question not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $questionIdStr = $question->getId()?->toString();

        if ($this->isCsrfTokenValid('delete-question-' . $questionIdStr, $request->request->get('_token'))) {
            $this->entityManager->remove($question);
            $this->entityManager->flush();

            $this->addFlash('success', 'question.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
    }

    #[Route('/{treeflowId}/step/{stepId}/question/reorder', name: 'question_reorder', methods: ['POST'])]
    public function reorder(Request $request, string $treeflowId, string $stepId): JsonResponse
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            return new JsonResponse([
                'success' => false,
                'message' => 'TreeFlow not found'
            ], 404);
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Step not found'
            ], 404);
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        // Get JSON data from request
        $data = json_decode($request->getContent(), true);

        if (!isset($data['questions']) || !is_array($data['questions'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid request data'
            ], 400);
        }

        try {
            // Process reorder - validate question IDs belong to this Step
            $questionIds = array_column($data['questions'], 'id');
            $stepQuestionIds = array_map(
                fn($question) => $question->getId()->toString(),
                $step->getQuestions()->toArray()
            );

            foreach ($questionIds as $questionId) {
                if (!in_array($questionId, $stepQuestionIds)) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Invalid question ID'
                    ], 400);
                }
            }

            // Persist the new order
            foreach ($data['questions'] as $questionData) {
                $question = $this->questionRepository->find($questionData['id']);
                if ($question) {
                    $question->setViewOrder($questionData['order']);
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Questions reordered successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error reordering questions: ' . $e->getMessage()
            ], 500);
        }
    }
}
