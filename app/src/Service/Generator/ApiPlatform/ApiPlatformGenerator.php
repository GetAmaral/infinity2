<?php

declare(strict_types=1);

namespace App\Service\Generator\ApiPlatform;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * API Platform YAML Configuration Generator
 *
 * ARCHITECTURAL DECISION: Why YAML Instead of PHP Attributes?
 * ===========================================================
 *
 * This generator creates YAML configuration files for API Platform resources.
 * While API Platform 4 supports PHP attributes (#[ApiResource]), we deliberately
 * use YAML configuration for this CSV-driven generator due to a fundamental
 * limitation in PHP's attribute inheritance system.
 *
 * THE INHERITANCE PROBLEM:
 * ------------------------
 * PHP attributes DO NOT inherit from parent to child classes. This creates
 * a critical issue for our Generated/Extension pattern:
 *
 * ```php
 * // ❌ This DOESN'T work - attributes don't inherit
 * #[ApiResource(operations: [new Get(), new Post()])]
 * abstract class ContactGenerated extends EntityBase {
 *     // All properties from CSV
 * }
 *
 * // API Platform won't see the #[ApiResource] from parent
 * class Contact extends ContactGenerated {
 *     // Custom logic here
 * }
 * ```
 *
 * If we put #[ApiResource] on the abstract ContactGenerated class, API Platform
 * will never see it on the concrete Contact class because PHP doesn't inherit
 * attributes. We would have to manually add #[ApiResource] to every extension
 * class, defeating the purpose of CSV-driven generation.
 *
 * WHY YAML WORKS:
 * ---------------
 * YAML configuration references the FINAL concrete class directly, completely
 * bypassing PHP's attribute inheritance limitation:
 *
 * ```yaml
 * # ✅ This DOES work - references concrete class directly
 * App\Entity\Contact:  # The actual usable class
 *   operations:
 *     - Get
 *     - Post
 *   security: "is_granted('ROLE_SALES_MANAGER')"
 * ```
 *
 * ARCHITECTURAL BENEFITS:
 * -----------------------
 * 1. ✅ SUPPORTS INHERITANCE PATTERN
 *    - Works perfectly with Generated/Extension classes
 *    - Configuration applies to concrete class regardless of where it's defined
 *
 * 2. ✅ FULLY REGENERABLE
 *    - API Platform config can be completely regenerated from CSV
 *    - No manual attribute management needed
 *
 * 3. ✅ NOT DEPRECATED
 *    - YAML is fully supported in API Platform 4
 *    - Official docs: "Configuration can be done using attributes, XML, or YAML"
 *
 * 4. ✅ SEPARATION OF CONCERNS
 *    - Configuration separated from code
 *    - Entity classes remain clean
 *
 * 5. ✅ FLEXIBILITY
 *    - Extension class can still add attributes to override/extend YAML config
 *    - Best of both worlds: generated YAML + manual attributes
 *
 * API PLATFORM 4 OFFICIAL STANCE:
 * --------------------------------
 * From API Platform documentation:
 * "Configuration can be done using attributes, XML, or YAML. While attributes
 * are convenient for grouping code and configuration, XML and YAML mappings
 * can be used to decouple classes from metadata."
 *
 * For generated code with inheritance, YAML is the SUPERIOR choice.
 *
 * REFERENCES:
 * -----------
 * - API Platform 4 Configuration: https://api-platform.com/docs/core/configuration/
 * - PHP Attribute Inheritance: https://www.php.net/manual/en/language.attributes.php
 * - Audit Report: GENERATOR_BEST_PRACTICES_AUDIT.md (lines 23-89)
 *
 * @see EntityDefinitionDto For CSV entity metadata structure
 * @see \App\Entity\Generated For generated abstract base classes
 */
class ApiPlatformGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate API Platform YAML configuration
     *
     * @return string|null File path if generated, null if skipped
     */
    public function generate(EntityDefinitionDto $entity): ?string
    {
        if (!$entity->apiEnabled) {
            $this->logger->info('Skipping API Platform config (API not enabled)', [
                'entity' => $entity->entityName
            ]);
            return null; // Skip if API not enabled
        }

        $filePath = sprintf(
            '%s/config/api_platform/%s.yaml',
            $this->projectDir,
            $entity->entityName
        );

        try {
            // Create directory
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                $this->filesystem->mkdir($dir, 0755);
            }

            // Render from template
            $content = $this->twig->render('Generator/yaml/api_platform_resource.yaml.twig', [
                'entity' => $entity,
            ]);

            // Atomic write using Filesystem component
            $this->filesystem->dumpFile($filePath, $content);

            $this->logger->info('Generated API Platform configuration', ['file' => $filePath]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate API Platform configuration', [
                'entity' => $entity->entityName,
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate API Platform configuration for {$entity->entityName}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
