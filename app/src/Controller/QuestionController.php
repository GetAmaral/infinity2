<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Question;
use App\Form\QuestionFormType;
use App\Repository\QuestionRepository;
use App\Repository\StepRepository;
use App\Repository\TreeFlowRepository;
use App\Security\Voter\TreeFlowVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/treeflow')]
final class QuestionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly StepRepository $stepRepository,
        private readonly QuestionRepository $questionRepository,
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

        $question = new Question();
        $question->setStep($step);

        // Auto-calculate viewOrder - set to last position
        $maxOrder = $this->questionRepository->createQueryBuilder('q')
            ->select('MAX(q.viewOrder)')
            ->where('q.step = :step')
            ->setParameter('step', $step)
            ->getQuery()
            ->getSingleScalarResult();

        $question->setViewOrder(($maxOrder ?? 0) + 1);

        $form = $this->createForm(QuestionFormType::class, $question, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($question);
            $this->entityManager->flush();

            $this->addFlash('success', 'question.flash.created_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Handle modal/AJAX requests
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

        $form = $this->createForm(QuestionFormType::class, $question, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'question.flash.updated_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Handle modal/AJAX requests
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
            $this->addFlash('error', 'question.flash.invalid_csrf_token');
        }

        return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
    }
}
