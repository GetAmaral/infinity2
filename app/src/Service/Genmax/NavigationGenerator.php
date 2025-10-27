<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Repository\Generator\GeneratorEntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

final class NavigationGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        private readonly array $paths,
        private readonly GeneratorEntityRepository $generatorEntityRepository,
        private readonly Environment $twig,
        private readonly SmartFileWriter $smartFileWriter,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Generate NavigationConfig files (Generated + Extension)
     *
     * @return array<string> Array of generated file paths
     */
    public function generate(): array
    {
        $this->logger->info('[NavigationGenerator] Starting navigation config generation');

        $generatedFiles = [];

        // 1. Generate NavigationConfigGenerated.php (ALWAYS OVERWRITTEN)
        $generatedFiles[] = $this->generateNavigationConfigGenerated();

        // 2. Generate NavigationConfig.php (CREATED ONCE, safe to edit)
        $extensionFile = $this->generateNavigationConfigExtension();
        if ($extensionFile) {
            $generatedFiles[] = $extensionFile;
        }

        $this->logger->info('[NavigationGenerator] Navigation config generation completed', [
            'files_count' => count($generatedFiles),
        ]);

        return $generatedFiles;
    }

    /**
     * Generate NavigationConfigGenerated.php
     *
     * Contains ALL menu items from database, organized by groups
     */
    private function generateNavigationConfigGenerated(): string
    {
        // Fetch all entities that should appear in navigation
        $allEntities = $this->generatorEntityRepository->findAll();
        $navEntities = array_filter($allEntities, fn($e) => $e->isShownInNavigation());

        // Group and sort entities
        $groupedItems = $this->buildGroupedMenuItems($navEntities);

        // Render template
        $content = $this->twig->render('genmax/php/navigation_config_generated.php.twig', [
            'grouped_items' => $groupedItems,
            'generation_timestamp' => new \DateTimeImmutable(),
        ]);

        // Write file
        $filePath = sprintf(
            '%s/%s/NavigationConfigGenerated.php',
            $this->projectDir,
            $this->paths['navigation_config_generated_dir']
        );

        $this->smartFileWriter->writeFile($filePath, $content);

        $this->logger->info('[NavigationGenerator] Generated NavigationConfigGenerated.php', [
            'file' => $filePath,
            'groups_count' => count($groupedItems),
        ]);

        return $filePath;
    }

    /**
     * Generate NavigationConfig.php (extension)
     *
     * Created ONCE, safe to customize
     */
    private function generateNavigationConfigExtension(): ?string
    {
        $filePath = sprintf(
            '%s/%s/NavigationConfig.php',
            $this->projectDir,
            $this->paths['navigation_config_dir']
        );

        // Only create if doesn't exist
        if (file_exists($filePath)) {
            $this->logger->info('[NavigationGenerator] Extension file already exists, skipping', [
                'file' => $filePath,
            ]);
            return null;
        }

        // Render template
        $content = $this->twig->render('genmax/php/navigation_config_extension.php.twig', [
            'generation_timestamp' => new \DateTimeImmutable(),
        ]);

        $this->smartFileWriter->writeFile($filePath, $content);

        $this->logger->info('[NavigationGenerator] Created NavigationConfig.php extension', [
            'file' => $filePath,
        ]);

        return $filePath;
    }

    /**
     * Build grouped and sorted menu items
     *
     * @param GeneratorEntity[] $entities
     * @return array<string, array> Grouped menu items
     */
    private function buildGroupedMenuItems(array $entities): array
    {
        $grouped = [];

        foreach ($entities as $entity) {
            $group = $entity->getEffectiveMenuGroup();
            if ($group === null) {
                continue;  // Should not happen due to isShownInNavigation() filter
            }

            // Skip entities without voters (e.g., AuditLog)
            if (!$entity->isVoterEnabled()) {
                $this->logger->info('[NavigationGenerator] Skipping entity without voter', [
                    'entity' => $entity->getEntityName(),
                ]);
                continue;
            }

            // Skip entities without controllers (e.g., TalkMessage)
            if (!$entity->isGenerateController()) {
                $this->logger->info('[NavigationGenerator] Skipping entity without controller', [
                    'entity' => $entity->getEntityName(),
                ]);
                continue;
            }

            // Skip entities that don't have LIST or VIEW permissions
            $permissionConstant = $this->getNavigationPermission($entity);
            if ($permissionConstant === null) {
                $this->logger->info('[NavigationGenerator] Skipping entity without LIST or VIEW permission', [
                    'entity' => $entity->getEntityName(),
                    'voter_attributes' => $entity->getVoterAttributes(),
                ]);
                continue;
            }

            // Use entity's getSlug() method for consistency (uses Utils::stringToSlug)
            $slug = $entity->getSlug();
            $voterClass = $entity->getEntityName() . 'Voter';

            $grouped[$group][] = [
                'key' => $slug,
                'entity_name' => $entity->getEntityName(),
                'label_translation_key' => "{$slug}.plural",  // Translation key
                'route' => "{$slug}_index",
                'icon' => $entity->getIcon(),
                'voter_class' => $voterClass,
                'permission_constant' => $permissionConstant,
                'translation_domain' => $slug,
                'menu_order' => $entity->getEffectiveMenuOrder(),
                'entity_label' => $entity->getEntityLabel(),  // For sorting
            ];
        }

        // Sort groups by name (alphabetically)
        ksort($grouped);

        // Sort items within each group by menuOrder ASC, then entityLabel ASC
        foreach ($grouped as $group => &$items) {
            usort($items, function ($a, $b) {
                // Primary sort: menuOrder
                if ($a['menu_order'] !== $b['menu_order']) {
                    return $a['menu_order'] <=> $b['menu_order'];
                }
                // Secondary sort: entityLabel (alphabetical, case-insensitive)
                return strcasecmp($a['entity_label'], $b['entity_label']);
            });
        }

        return $grouped;
    }

    /**
     * Get the appropriate permission constant for navigation
     *
     * Returns the first available permission from:
     * 1. LIST (preferred for navigation)
     * 2. VIEW (fallback, more universal)
     *
     * Returns null if neither LIST nor VIEW are available
     */
    private function getNavigationPermission(GeneratorEntity $entity): ?string
    {
        $voterAttributes = $entity->getVoterAttributes();

        // No voter attributes - cannot determine permission
        if (!$voterAttributes) {
            return null;
        }

        // Prefer LIST if available
        if (in_array('LIST', $voterAttributes, true)) {
            return 'LIST';
        }

        // Fallback to VIEW if available
        if (in_array('VIEW', $voterAttributes, true)) {
            return 'VIEW';
        }

        // Neither LIST nor VIEW available - skip this entity
        return null;
    }
}
