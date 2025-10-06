<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StepOutput;
use App\Form\StepOutputFormType;
use App\Repository\StepOutputRepository;
use App\Repository\StepRepository;
use App\Repository\TreeFlowRepository;
use App\Security\Voter\TreeFlowVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/treeflow')]
final class StepOutputController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly StepRepository $stepRepository,
        private readonly StepOutputRepository $outputRepository,
    ) {}

    #[Route('/{treeflowId}/step/{stepId}/output/new', name: 'output_new', methods: ['GET', 'POST'])]
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

        $output = new StepOutput();
        $output->setStep($step);

        $form = $this->createForm(StepOutputFormType::class, $output, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($output);
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Output created successfully',
                ]);
            }

            $this->addFlash('success', 'output.flash.created_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Return validation errors as JSON for AJAX requests
        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $html = $this->renderView('treeflow/output/_form_modal.html.twig', [
                'output' => $output,
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
            return $this->render('treeflow/output/_form_modal.html.twig', [
                'output' => $output,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('treeflow/output/new.html.twig', [
            'output' => $output,
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/output/{outputId}/edit', name: 'output_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $treeflowId, string $stepId, string $outputId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $output = $this->outputRepository->find($outputId);
        if (!$output || $output->getStep()->getId()->toString() !== $stepId) {
            throw $this->createNotFoundException('Output not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $form = $this->createForm(StepOutputFormType::class, $output, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Output updated successfully',
                ]);
            }

            $this->addFlash('success', 'output.flash.updated_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Return validation errors as JSON for AJAX requests
        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $html = $this->renderView('treeflow/output/_form_modal.html.twig', [
                'output' => $output,
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
            return $this->render('treeflow/output/_form_modal.html.twig', [
                'output' => $output,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('treeflow/output/edit.html.twig', [
            'output' => $output,
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/output/{outputId}/delete', name: 'output_delete', methods: ['POST'])]
    public function delete(Request $request, string $treeflowId, string $stepId, string $outputId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $output = $this->outputRepository->find($outputId);
        if (!$output || $output->getStep()->getId()->toString() !== $stepId) {
            throw $this->createNotFoundException('Output not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $outputIdStr = $output->getId()?->toString();

        if ($this->isCsrfTokenValid('delete-output-' . $outputIdStr, $request->request->get('_token'))) {
            $this->entityManager->remove($output);
            $this->entityManager->flush();

            $this->addFlash('success', 'output.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
    }
}
