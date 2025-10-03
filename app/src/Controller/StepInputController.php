<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StepInput;
use App\Form\StepInputFormType;
use App\Repository\StepInputRepository;
use App\Repository\StepRepository;
use App\Repository\TreeFlowRepository;
use App\Security\Voter\TreeFlowVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/treeflow')]
final class StepInputController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TreeFlowRepository $treeFlowRepository,
        private readonly StepRepository $stepRepository,
        private readonly StepInputRepository $inputRepository,
    ) {}

    #[Route('/{treeflowId}/step/{stepId}/input/new', name: 'input_new', methods: ['GET', 'POST'])]
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

        $input = new StepInput();
        $input->setStep($step);

        // Get all steps from this TreeFlow except current step
        $availableSteps = $treeFlow->getSteps()->filter(
            fn($s) => $s->getId()->toString() !== $stepId
        );

        $form = $this->createForm(StepInputFormType::class, $input, [
            'is_edit' => false,
            'available_steps' => $availableSteps,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($input);
            $this->entityManager->flush();

            $this->addFlash('success', 'input.flash.created_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/input/_form_modal.html.twig', [
                'input' => $input,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => false,
            ]);
        }

        return $this->render('treeflow/input/new.html.twig', [
            'input' => $input,
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/input/{inputId}/edit', name: 'input_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $treeflowId, string $stepId, string $inputId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $input = $this->inputRepository->find($inputId);
        if (!$input || $input->getStep()->getId()->toString() !== $stepId) {
            throw $this->createNotFoundException('Input not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        // Get all steps from this TreeFlow except current step
        $availableSteps = $treeFlow->getSteps()->filter(
            fn($s) => $s->getId()->toString() !== $stepId
        );

        $form = $this->createForm(StepInputFormType::class, $input, [
            'is_edit' => true,
            'available_steps' => $availableSteps,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'input.flash.updated_successfully');

            return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
        }

        // Handle modal/AJAX requests
        if ($request->isXmlHttpRequest() || $request->headers->get('Turbo-Frame')) {
            return $this->render('treeflow/input/_form_modal.html.twig', [
                'input' => $input,
                'step' => $step,
                'treeflow' => $treeFlow,
                'form' => $form,
                'is_edit' => true,
            ]);
        }

        return $this->render('treeflow/input/edit.html.twig', [
            'input' => $input,
            'step' => $step,
            'treeflow' => $treeFlow,
            'form' => $form,
        ]);
    }

    #[Route('/{treeflowId}/step/{stepId}/input/{inputId}/delete', name: 'input_delete', methods: ['POST'])]
    public function delete(Request $request, string $treeflowId, string $stepId, string $inputId): Response
    {
        $treeFlow = $this->treeFlowRepository->find($treeflowId);
        if (!$treeFlow) {
            throw $this->createNotFoundException('TreeFlow not found');
        }

        $step = $this->stepRepository->find($stepId);
        if (!$step || $step->getTreeFlow()->getId()->toString() !== $treeflowId) {
            throw $this->createNotFoundException('Step not found');
        }

        $input = $this->inputRepository->find($inputId);
        if (!$input || $input->getStep()->getId()->toString() !== $stepId) {
            throw $this->createNotFoundException('Input not found');
        }

        $this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

        $inputIdStr = $input->getId()?->toString();

        if ($this->isCsrfTokenValid('delete-input-' . $inputIdStr, $request->request->get('_token'))) {
            $this->entityManager->remove($input);
            $this->entityManager->flush();

            $this->addFlash('success', 'input.flash.deleted_successfully');
        } else {
            $this->addFlash('error', 'input.flash.invalid_csrf_token');
        }

        return $this->redirectToRoute('treeflow_show', ['id' => $treeflowId]);
    }
}
