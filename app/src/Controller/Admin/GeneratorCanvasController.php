<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use App\Entity\Generator\GeneratorCanvasState;
use App\Form\Generator\GeneratorEntityFormType;
use App\Form\Generator\GeneratorPropertyFormType;
use App\Repository\Generator\GeneratorEntityRepository;
use App\Repository\Generator\GeneratorPropertyRepository;
use App\Service\Generator\DatabaseDefinitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/generator')]
#[IsGranted('ROLE_ADMIN')]
class GeneratorCanvasController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GeneratorEntityRepository $entityRepository,
        private readonly GeneratorPropertyRepository $propertyRepository,
        private readonly DatabaseDefinitionService $definitionService
    ) {
    }

    #[Route('/studio', name: 'admin_generator_studio', methods: ['GET'])]
    public function studio(): Response
    {
        // Load all entities with properties
        $entities = $this->entityRepository->findAllWithProperties();

        // Load canvas state (singleton)
        $canvasState = $this->em->getRepository(GeneratorCanvasState::class)->find(1);

        if (!$canvasState) {
            $canvasState = new GeneratorCanvasState();
            $this->em->persist($canvasState);
            $this->em->flush();
        }

        return $this->render('generator/studio.html.twig', [
            'entities' => $entities,
            'canvasState' => $canvasState
        ]);
    }

    #[Route('/entity/{id}/position', name: 'admin_generator_entity_position', methods: ['PATCH'])]
    public function saveEntityPosition(GeneratorEntity $entity, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $entity->setCanvasX((int) $data['x']);
        $entity->setCanvasY((int) $data['y']);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/canvas-state', name: 'admin_generator_canvas_state', methods: ['POST'])]
    public function saveCanvasState(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $canvasState = $this->em->getRepository(GeneratorCanvasState::class)->find(1);

        if (!$canvasState) {
            $canvasState = new GeneratorCanvasState();
            $this->em->persist($canvasState);
        }

        $canvasState->setScale((float) $data['scale']);
        $canvasState->setOffsetX((int) $data['offsetX']);
        $canvasState->setOffsetY((int) $data['offsetY']);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/entity/create', name: 'admin_generator_entity_create', methods: ['GET', 'POST'])]
    public function createEntity(Request $request): Response
    {
        $entity = new GeneratorEntity();

        $form = $this->createForm(GeneratorEntityFormType::class, $entity, [
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($entity);
            $this->em->flush();

            $this->addFlash('success', 'Entity created successfully');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'reload' => true]);
            }

            return $this->redirectToRoute('admin_generator_studio');
        }

        return $this->render('generator/entity_form_modal.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity,
            'isEdit' => false
        ]);
    }

    #[Route('/entity/{id}/edit', name: 'admin_generator_entity_edit', methods: ['GET', 'POST'])]
    public function editEntity(GeneratorEntity $entity, Request $request): Response
    {
        $form = $this->createForm(GeneratorEntityFormType::class, $entity, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Entity updated successfully');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'reload' => true]);
            }

            return $this->redirectToRoute('admin_generator_studio');
        }

        return $this->render('generator/entity_form_modal.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity,
            'isEdit' => true
        ]);
    }

    #[Route('/entity/{id}/delete', name: 'admin_generator_entity_delete', methods: ['DELETE'])]
    public function deleteEntity(GeneratorEntity $entity): JsonResponse
    {
        $this->em->remove($entity);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/property/create/{entityId}', name: 'admin_generator_property_create', methods: ['GET', 'POST'])]
    public function createProperty(string $entityId, Request $request): Response
    {
        $entity = $this->entityRepository->find($entityId);
        if (!$entity) {
            throw $this->createNotFoundException();
        }

        $property = new GeneratorProperty();
        $property->setEntity($entity);

        $form = $this->createForm(GeneratorPropertyFormType::class, $property, [
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($property);
            $this->em->flush();

            $this->addFlash('success', 'Property created successfully');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'reload' => true]);
            }

            return $this->redirectToRoute('admin_generator_studio');
        }

        return $this->render('generator/property_form_modal.html.twig', [
            'form' => $form->createView(),
            'property' => $property,
            'entity' => $entity,
            'isEdit' => false
        ]);
    }

    #[Route('/property/{id}/edit', name: 'admin_generator_property_edit', methods: ['GET', 'POST'])]
    public function editProperty(GeneratorProperty $property, Request $request): Response
    {
        $form = $this->createForm(GeneratorPropertyFormType::class, $property, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Property updated successfully');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'reload' => true]);
            }

            return $this->redirectToRoute('admin_generator_studio');
        }

        return $this->render('generator/property_form_modal.html.twig', [
            'form' => $form->createView(),
            'property' => $property,
            'entity' => $property->getEntity(),
            'isEdit' => true
        ]);
    }

    #[Route('/property/{id}/delete', name: 'admin_generator_property_delete', methods: ['DELETE'])]
    public function deleteProperty(GeneratorProperty $property): JsonResponse
    {
        $this->em->remove($property);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/relationship', name: 'admin_generator_relationship_create', methods: ['POST'])]
    public function createRelationship(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $property = $this->propertyRepository->find($data['propertyId']);
        $targetEntity = $this->entityRepository->find($data['targetEntityId']);

        if (!$property || !$targetEntity) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid property or entity'], 400);
        }

        // Update property with relationship
        $property->setTargetEntity($targetEntity->getEntityName());
        $property->setRelationshipType($data['relationshipType']);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/relationship/{id}', name: 'admin_generator_relationship_delete', methods: ['DELETE'])]
    public function deleteRelationship(GeneratorProperty $property): JsonResponse
    {
        // Clear relationship from property
        $property->setTargetEntity(null);
        $property->setRelationshipType(null);
        $property->setInversedBy(null);
        $property->setMappedBy(null);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/auto-layout', name: 'admin_generator_auto_layout', methods: ['POST'])]
    public function autoLayout(): JsonResponse
    {
        $entities = $this->entityRepository->findAll();

        // Build relationship graph
        $graph = [];
        foreach ($entities as $entity) {
            foreach ($entity->getProperties() as $property) {
                if ($property->getRelationshipType() && $property->getTargetEntity()) {
                    if (!isset($graph[$entity->getId()->toRfc4122()])) {
                        $graph[$entity->getId()->toRfc4122()] = [];
                    }

                    $targetEntity = $this->entityRepository->findOneBy(['entityName' => $property->getTargetEntity()]);
                    if ($targetEntity) {
                        $graph[$entity->getId()->toRfc4122()][] = $targetEntity->getId()->toRfc4122();
                    }
                }
            }
        }

        // Level assignment (BFS-based)
        $levels = [];
        $connectedIds = array_keys($graph);

        foreach ($graph as $edges) {
            foreach ($edges as $targetId) {
                $connectedIds[] = $targetId;
            }
        }
        $connectedIds = array_unique($connectedIds);

        // Simple level assignment
        foreach ($entities as $entity) {
            $id = $entity->getId()->toRfc4122();

            if (in_array($id, $connectedIds)) {
                $level = $this->calculateLevel($id, $graph, []);
                $levels[$id] = $level;
            }
        }

        // Position entities
        $horizontalSpacing = 350;
        $verticalSpacing = 150;
        $startX = 100;
        $startY = 100;

        $nodesByLevel = [];
        foreach ($levels as $id => $level) {
            if (!isset($nodesByLevel[$level])) {
                $nodesByLevel[$level] = [];
            }
            $nodesByLevel[$level][] = $id;
        }

        ksort($nodesByLevel);

        foreach ($nodesByLevel as $level => $ids) {
            $x = $startX + ($level * $horizontalSpacing);
            $y = $startY;

            foreach ($ids as $id) {
                $entity = $this->entityRepository->find($id);
                if ($entity) {
                    $entity->setCanvasX($x);
                    $entity->setCanvasY($y);

                    $y += $verticalSpacing;
                }
            }
        }

        // Position orphan entities
        $orphanX = $startX + (count($nodesByLevel) * $horizontalSpacing);
        $orphanY = $startY;

        foreach ($entities as $entity) {
            $id = $entity->getId()->toRfc4122();

            if (!isset($levels[$id])) {
                $entity->setCanvasX($orphanX);
                $entity->setCanvasY($orphanY);

                $orphanY += $verticalSpacing;
            }
        }

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    private function calculateLevel(string $id, array $graph, array $visited): int
    {
        if (in_array($id, $visited)) {
            return 0; // Cycle detection
        }

        $visited[] = $id;

        if (!isset($graph[$id]) || empty($graph[$id])) {
            return 0;
        }

        $maxChildLevel = 0;
        foreach ($graph[$id] as $childId) {
            $childLevel = $this->calculateLevel($childId, $graph, $visited);
            $maxChildLevel = max($maxChildLevel, $childLevel);
        }

        return $maxChildLevel + 1;
    }

    #[Route('/entity/{id}/generate-preview', name: 'admin_generator_entity_preview', methods: ['GET'])]
    public function generatePreview(GeneratorEntity $entity): Response
    {
        $definition = $this->definitionService->buildEntityDefinition($entity);

        $generatedCode = $this->definitionService->previewGeneration($definition);

        return $this->render('generator/preview_modal.html.twig', [
            'entity' => $entity,
            'generatedCode' => $generatedCode
        ]);
    }

    #[Route('/entity/{id}/generate', name: 'admin_generator_entity_generate', methods: ['POST'])]
    public function generateEntity(GeneratorEntity $entity): JsonResponse
    {
        try {
            $definition = $this->definitionService->buildEntityDefinition($entity);

            // Generate all files
            $generatedFiles = $this->definitionService->generateAllFiles($definition);

            // Mark as generated
            $entity->setIsGenerated(true);
            $entity->setLastGeneratedAt(new \DateTimeImmutable());
            $entity->setLastGenerationLog(sprintf(
                'Successfully generated %d files: %s',
                count($generatedFiles),
                implode(', ', array_keys($generatedFiles))
            ));

            $this->em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Entity generated successfully',
                'files' => array_keys($generatedFiles)
            ]);
        } catch (\Exception $e) {
            $entity->setLastGenerationLog('Error: ' . $e->getMessage());
            $this->em->flush();

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
