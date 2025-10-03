<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FewShotExample;
use App\Form\FewShotExampleFormType;
use App\Repository\FewShotExampleRepository;
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
final class FewShotExampleController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly StepRepository $stepRepository,
        private readonly QuestionRepository $questionRepository,
        private readonly FewShotExampleRepository $fewShotRepository,
    ) {}

    #[Route('/{treeflowId}/step/{stepId}/question/{questionId}/fewshot/new', name: 'fewshot_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $treeflowId, string $stepId, string $questionId): Response
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

        $fewShot = new FewShotExample();
        $fewShot->setQuestion($question);

        $form = $this->createForm(FewShotExampleFormType::class, $fewShot, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($fewShot);
            $this->entityManager->flush();

            $this->addFlash('success', 'fewshot.flash.created_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/fewshot/_form_modal.html.twig', [
                'fewshot' => $fewShot,
                'question' => $question,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('treeflow/fewshot/new.html.twig', [
            'fewshot' => $fewShot,
            'question' => $question,
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/question/{questionId}/fewshot/{fewshotId}/edit', name: 'fewshot_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $treeflowId, string $stepId, string $questionId, string $fewshotId): Response
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

        $fewShot = $this->fewShotRepository->find($fewshotId);
        if (!$fewShot || $fewShot->getQuestion()->getId()->toString() !== $questionId) {
            throw $this->createNotFoundException('FewShot example not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $form = $this->createForm(FewShotExampleFormType::class, $fewShot, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'fewshot.flash.updated_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/fewshot/_form_modal.html.twig', [
                'fewshot' => $fewShot,
                'question' => $question,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('treeflow/fewshot/edit.html.twig', [
            'fewshot' => $fewShot,
            'question' => $question,
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/question/{questionId}/fewshot/{fewshotId}/delete', name: 'fewshot_delete', methods: ['POST'])]
    public function delete(Request $request, string $treeflowId, string $stepId, string $questionId, string $fewshotId): Response
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

        $fewShot = $this->fewShotRepository->find($fewshotId);
        if (!$fewShot || $fewShot->getQuestion()->getId()->toString() !== $questionId) {
            throw $this->createNotFoundException('FewShot example not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $fewShotIdStr = $fewShot->getId()?->toString();

        if ($this->isCsrfTokenValid('delete-fewshot-' . $fewShotIdStr, $request->request->get('_token'))) {
            $this->entityManager->remove($fewShot);
            $this->entityManager->flush();

            $this->addFlash('success', 'fewshot.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'fewshot.flash.invalid_csrf_token');
        }

        return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
    }
}
