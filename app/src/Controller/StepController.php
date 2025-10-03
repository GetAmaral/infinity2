<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Step;
use App\Form\StepFormType;
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
final class StepController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly StepRepository $stepRepository,
    ) {}

    #[Route('/{treeflowId}/step/new', name: 'step_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $treeflowId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $step = new Step();
        $step->setTreeFlow($treeFlow);

        $form = $this->createForm(StepFormType::class, $step, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($step);
            $this->entityManager->flush();

            $this->addFlash('success', 'step.flash.created_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/step/_form_modal.html.twig', [
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('treeflow/step/new.html.twig', [
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/edit', name: 'step_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $treeflowId, string $stepId): Response
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

        $form = $this->createForm(StepFormType::class, $step, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'step.flash.updated_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/step/_form_modal.html.twig', [
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('treeflow/step/edit.html.twig', [
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/delete', name: 'step_delete', methods: ['POST'])]
    public function delete(Request $request, string $treeflowId, string $stepId): Response
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

        $stepId = $step->getId()?->toString();

        if ($this->isCsrfTokenValid('delete-step-' . $stepId, $request->request->get('_token'))) {
            $this->entityManager->remove($step);
            $this->entityManager->flush();

            $this->addFlash('success', 'step.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'step.flash.invalid_csrf_token');
        }

        return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
    }

    #[Route('/{treeflowId}/step/reorder', name: 'step_reorder', methods: ['POST'])]
    public function reorder(Request $request, string $treeflowId): JsonResponse
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            return new JsonResponse([
                'success' => false,
                'message' => 'TreeFlow not found'
            ], 404);
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        // Get JSON data from request
        $data = json_decode($request->getContent(), true);

        if (!isset($data['steps']) || !is_array($data['steps'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid request data'
            ], 400);
        }

        try {
            // Process reorder - for now just validate the step IDs belong to this TreeFlow
            $stepIds = array_column($data['steps'], 'id');
            $treeFlowStepIds = array_map(
                fn($step) => $step->getId()->toString(),
                $treeFlow->getSteps()->toArray()
            );

            foreach ($stepIds as $stepId) {
                if (!in_array($stepId, $treeFlowStepIds)) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Invalid step ID'
                    ], 400);
                }
            }

            // Persist the new order (Enhancement: Step viewOrder field)
            foreach ($data['steps'] as $stepData) {
                $step = $this->stepRepository->find($stepData['id']);
                if ($step) {
                    $step->setViewOrder($stepData['order']);
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Steps reordered successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error reordering steps: ' . $e->getMessage()
            ], 500);
        }
    }
}
