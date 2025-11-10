<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StepAction;
use App\Form\StepActionFormType;
use App\Repository\StepActionRepository;
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
final class StepActionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly StepRepository $stepRepository,
        private readonly StepActionRepository $actionRepository,
    ) {}

    #[Route('/{treeflowId}/step/{stepId}/action/new', name: 'action_new', methods: ['GET', 'POST'])]
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

        $action = new StepAction();
        $action->setStep($step);

        // Auto-calculate viewOrder - set to last position
        $maxOrder = $this->actionRepository->createQueryBuilder('q')
            ->select('MAX(q.viewOrder)')
            ->where('q.step = :step')
            ->setParameter('step', $step)
            ->getQuery()
            ->getSingleScalarResult();

        $action->setViewOrder(($maxOrder ?? 0) + 1);

        $form = $this->createForm(StepActionFormType::class, $action, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($action);
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Action created successfully',
                ]);
            }

            $this->addFlash('success', 'action.flash.created_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Return validation errors as JSON for AJAX requests
        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $html = $this->renderView('treeflow/action/_form_modal.html.twig', [
                'action' => $action,
                'step' => $step,
                'treeFlow' => $treeFlow,
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
            return $this->render('treeflow/action/_form_modal.html.twig', [
                'action' => $action,
                'step' => $step,
                'treeFlow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('treeflow/action/new.html.twig', [
            'action' => $action,
            'step' => $step,
            'treeFlow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/action/{actionId}/edit', name: 'action_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $treeflowId, string $stepId, string $actionId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $action = $this->actionRepository->find($actionId);
        if (!$action || $action->getStep()->getId()->toString() !== $stepId) {
            throw $this->createNotFoundException('Action not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $form = $this->createForm(StepActionFormType::class, $action, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            // Return JSON for AJAX requests
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Action updated successfully',
                ]);
            }

            $this->addFlash('success', 'action.flash.updated_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Return validation errors as JSON for AJAX requests
        if ($request->isXmlHttpRequest() && $form->isSubmitted()) {
            $html = $this->renderView('treeflow/action/_form_modal.html.twig', [
                'action' => $action,
                'step' => $step,
                'treeFlow' => $treeFlow,
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
            return $this->render('treeflow/action/_form_modal.html.twig', [
                'action' => $action,
                'step' => $step,
                'treeFlow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('treeflow/action/edit.html.twig', [
            'action' => $action,
            'step' => $step,
            'treeFlow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/action/{actionId}/delete', name: 'action_delete', methods: ['POST'])]
    public function delete(Request $request, string $treeflowId, string $stepId, string $actionId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $action = $this->actionRepository->find($actionId);
        if (!$action || $action->getStep()->getId()->toString() !== $stepId) {
            throw $this->createNotFoundException('Action not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $actionIdStr = $action->getId()?->toString();

        if ($this->isCsrfTokenValid('delete-action-' . $actionIdStr, $request->request->get('_token'))) {
            $this->entityManager->remove($action);
            $this->entityManager->flush();

            $this->addFlash('success', 'action.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'common.error.invalid_csrf');
        }

        return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
    }

    #[Route('/{treeflowId}/step/{stepId}/action/reorder', name: 'action_reorder', methods: ['POST'])]
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

        if (!isset($data['actions']) || !is_array($data['actions'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid request data'
            ], 400);
        }

        try {
            // Process reorder - validate action IDs belong to this Step
            $actionIds = array_column($data['actions'], 'id');
            $stepActionIds = array_map(
                fn($action) => $action->getId()->toString(),
                $step->getActions()->toArray()
            );

            foreach ($actionIds as $actionId) {
                if (!in_array($actionId, $stepActionIds)) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Invalid action ID'
                    ], 400);
                }
            }

            // Persist the new order
            foreach ($data['actions'] as $actionData) {
                $action = $this->actionRepository->find($actionData['id']);
                if ($action) {
                    $action->setViewOrder($actionData['order']);
                }
            }

            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Actions reordered successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error reordering actions: ' . $e->getMessage()
            ], 500);
        }
    }
}
