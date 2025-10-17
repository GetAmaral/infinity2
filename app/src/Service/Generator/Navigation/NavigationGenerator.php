<?php

declare(strict_types=1);

namespace App\Service\Generator\Navigation;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Psr\Log\LoggerInterface;

class NavigationGenerator
{
    private const BASE_TEMPLATE_PATH = '/templates/base.html.twig';

    public function __construct(
        private readonly string $projectDir,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Update navigation menu in base template
     *
     * @param EntityDefinitionDto[] $entities
     */
    public function generate(array $entities): void
    {
        $templatePath = $this->projectDir . self::BASE_TEMPLATE_PATH;

        if (!file_exists($templatePath)) {
            $this->logger->warning('Base template not found, skipping navigation generation', [
                'path' => $templatePath
            ]);
            return;
        }

        $template = file_get_contents($templatePath);

        // Group entities by menuGroup
        $groups = [];
        foreach ($entities as $entity) {
            $groupName = $entity->menuGroup ?: 'default';

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'order' => $entity->menuOrder,
                    'entities' => []
                ];
            }
            $groups[$groupName]['entities'][] = $entity;
        }

        // Sort groups by order
        uasort($groups, fn($a, $b) => $a['order'] <=> $b['order']);

        // Generate menu HTML for each group
        foreach ($groups as $groupName => $group) {
            // Sort entities within group by menuOrder
            usort($group['entities'], fn($a, $b) => $a->menuOrder <=> $b->menuOrder);

            $menuHtml = $this->generateMenuItems($group['entities']);

            // Replace between markers
            $startMarker = "<!-- GENERATOR_NAV_START:{$groupName} -->";
            $endMarker = "<!-- GENERATOR_NAV_END:{$groupName} -->";

            if (str_contains($template, $startMarker) && str_contains($template, $endMarker)) {
                // Markers exist, replace content
                $pattern = '/(' . preg_quote($startMarker, '/') . ').*?(' . preg_quote($endMarker, '/') . ')/s';
                $replacement = "$1\n{$menuHtml}\n                $2";
                $template = preg_replace($pattern, $replacement, $template);

                $this->logger->info('Updated navigation group', [
                    'group' => $groupName,
                    'entity_count' => count($group['entities'])
                ]);
            } else {
                // Markers don't exist, append to nav
                // Find </ul> before closing nav
                $navEndPattern = '/<\/ul>\s*<\/nav>/';
                if (preg_match($navEndPattern, $template, $matches, PREG_OFFSET_CAPTURE)) {
                    $ulEndPos = $matches[0][1];
                    $insertion = "\n                {$startMarker}\n{$menuHtml}\n                {$endMarker}\n                ";
                    $template = substr_replace($template, $insertion, $ulEndPos, 0);

                    $this->logger->info('Added new navigation group', [
                        'group' => $groupName,
                        'entity_count' => count($group['entities'])
                    ]);
                }
            }
        }

        file_put_contents($templatePath, $template);

        $this->logger->info('Navigation generation complete', [
            'group_count' => count($groups),
            'total_entities' => count($entities)
        ]);
    }

    /**
     * Generate menu HTML for entities
     */
    private function generateMenuItems(array $entities): string
    {
        $html = '';
        foreach ($entities as $entity) {
            $html .= sprintf(
                '                <li class="nav-item">' . "\n" .
                '                    <a class="nav-link" href="{{ path(\'%s_index\') }}" data-turbo="true">' . "\n" .
                '                        <i class="%s me-2"></i>' . "\n" .
                '                        {{ \'%s\'|trans }}' . "\n" .
                '                    </a>' . "\n" .
                '                </li>' . "\n",
                $entity->getSnakeCaseName(),
                $entity->icon,
                $entity->pluralLabel
            );
        }
        return rtrim($html);
    }
}
