<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

/**
 * Voter Generator for Genmax
 *
 * Generates Symfony Security Voters using Base/Extension pattern.
 * All naming uses centralized Utils methods via GenmaxExtension.
 */
class VoterGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        protected readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        protected readonly array $paths,
        #[Autowire(param: 'genmax.templates')]
        protected readonly array $templates,
        protected readonly Environment $twig,
        protected readonly SmartFileWriter $fileWriter,
        protected readonly GenmaxExtension $genmaxExtension,
        protected readonly LoggerInterface $logger
    ) {}

    /**
     * Generate voter files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        if (!$entity->isVoterEnabled()) {
            $this->logger->info('[GENMAX] Voter generation disabled', [
                'entity' => $entity->getEntityName()
            ]);
            return [];
        }

        $generatedFiles = [];

        $this->logger->info('[GENMAX] Generating voter', [
            'entity' => $entity->getEntityName(),
            'permissions' => $this->getPermissions($entity),
            'has_organization' => $entity->isHasOrganization(),
        ]);

        // Always generate base class (can be regenerated safely)
        $generatedFiles[] = $this->generateBaseVoter($entity);

        // Generate extension class ONCE only (user can customize)
        $extensionFile = $this->generateExtensionVoter($entity);
        if ($extensionFile) {
            $generatedFiles[] = $extensionFile;
        }

        return array_filter($generatedFiles);
    }

    /**
     * Generate base voter class: src/Security/Voter/Generated/{Entity}VoterGenerated.php
     */
    protected function generateBaseVoter(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sVoterGenerated.php',
            $this->projectDir,
            $this->paths['voter_generated_dir'],
            $entity->getEntityName()
        );

        try {
            $context = $this->buildTemplateContext($entity);

            // Render from template
            $content = $this->twig->render($this->templates['voter_generated'], $context);

            // Write file with smart comparison
            $status = $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated voter base class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate voter base class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate voter base class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate extension voter class: src/Security/Voter/{Entity}Voter.php
     */
    protected function generateExtensionVoter(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%sVoter.php',
            $this->projectDir,
            $this->paths['voter_dir'],
            $entity->getEntityName()
        );

        // Skip if exists (user may have customized)
        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping extension voter (already exists)', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);
            return null;
        }

        try {
            $context = $this->buildTemplateContext($entity);

            // Render from template
            $content = $this->twig->render($this->templates['voter_extension'], $context);

            // Write file with smart comparison
            $status = $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated voter extension class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate voter extension class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate voter extension class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Build template context with all variables needed for voter generation
     */
    protected function buildTemplateContext(GeneratorEntity $entity): array
    {
        $entityName = $entity->getEntityName();
        $entityVariable = $this->genmaxExtension->toCamelCase($entityName, false);
        $entityUpperSnake = $this->genmaxExtension->toSnakeCase($entityName);
        $permissions = $this->getPermissions($entity);

        // Special handling for User entity to avoid parameter name conflicts
        $isUserEntity = $entityName === 'User';
        $targetEntityVariable = $isUserEntity ? 'targetUser' : $entityVariable;
        $currentUserVariable = $isUserEntity ? 'currentUser' : 'user';

        return [
            'entity' => $entity,
            'entityName' => $entityName,
            'entityVariable' => $entityVariable,
            'targetEntityVariable' => $targetEntityVariable,
            'currentUserVariable' => $currentUserVariable,
            'isUserEntity' => $isUserEntity,
            'entityUpperSnake' => strtoupper($entityUpperSnake),
            'className' => $entityName . 'Voter',
            'baseClassName' => $entityName . 'VoterGenerated',
            'permissions' => $permissions,
            'hasOrganization' => $entity->isHasOrganization(),
            'hasOwner' => $this->hasOwnerProperty($entity),
            'namespace' => $this->paths['voter_namespace'],
            'generatedNamespace' => $this->paths['voter_generated_namespace'],
        ];
    }

    /**
     * Get permissions to generate for this entity
     */
    protected function getPermissions(GeneratorEntity $entity): array
    {
        $permissions = [];

        // Use custom permissions if defined, otherwise use default CRUD
        $voterAttributes = $entity->getVoterAttributes();
        $attributeNames = $voterAttributes ?? ['LIST', 'CREATE', 'VIEW', 'EDIT', 'DELETE'];

        foreach ($attributeNames as $attribute) {
            $permissions[] = [
                'name' => $attribute,
                'constant' => $attribute,
                'methodName' => 'can' . $this->genmaxExtension->toPascalCase($attribute),
                'requiresInstance' => !in_array($attribute, ['LIST', 'CREATE'], true),
            ];
        }

        return $permissions;
    }

    /**
     * Check if entity has owner property
     */
    protected function hasOwnerProperty(GeneratorEntity $entity): bool
    {
        foreach ($entity->getProperties() as $property) {
            if ($property->getPropertyName() === 'owner') {
                return true;
            }
        }
        return false;
    }
}
