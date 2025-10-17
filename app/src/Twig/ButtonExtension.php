<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\ButtonConfig;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * ButtonExtension - Twig Extension for Rendering Standardized Buttons
 *
 * Provides Twig functions to render buttons with consistent styling, icons, tooltips,
 * and permission checks across the entire application.
 *
 * Following Symfony 2025 best practices:
 * - Single source of truth (ButtonConfig) for button definitions
 * - Automatic permission filtering using Security Voters
 * - Template-based rendering for separation of concerns
 * - Bootstrap tooltip integration
 * - Icon-only and labeled button variants
 * - Inline style override support
 *
 * Usage in Twig:
 *   {{ button_create(path('user_new'), 'user.button.create', 'user') }}
 *   {{ button_edit(user.id, path('user_edit', {id: user.id})) }}
 *   {{ button_delete(user.id, path('user_delete', {id: user.id})) }}
 */
final class ButtonExtension extends AbstractExtension
{
    public function __construct(
        private readonly ButtonConfig $buttonConfig,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly Environment $twig,
    ) {}

    public function getFunctions(): array
    {
        return [
            // Primary action buttons
            new TwigFunction('button_create', [$this, 'buttonCreate'], ['is_safe' => ['html']]),
            new TwigFunction('button_edit', [$this, 'buttonEdit'], ['is_safe' => ['html']]),
            new TwigFunction('button_delete', [$this, 'buttonDelete'], ['is_safe' => ['html']]),

            // Navigation buttons
            new TwigFunction('button_back', [$this, 'buttonBack'], ['is_safe' => ['html']]),
            new TwigFunction('button_view', [$this, 'buttonView'], ['is_safe' => ['html']]),
            new TwigFunction('button_link', [$this, 'buttonLink'], ['is_safe' => ['html']]),

            // Modal buttons
            new TwigFunction('button_modal_trigger', [$this, 'buttonModalTrigger'], ['is_safe' => ['html']]),
            new TwigFunction('button_modal_submit', [$this, 'buttonModalSubmit'], ['is_safe' => ['html']]),
            new TwigFunction('button_modal_cancel', [$this, 'buttonModalCancel'], ['is_safe' => ['html']]),

            // Dropdown buttons
            new TwigFunction('button_dropdown_toggle', [$this, 'buttonDropdownToggle'], ['is_safe' => ['html']]),
            new TwigFunction('button_dropdown_item', [$this, 'buttonDropdownItem'], ['is_safe' => ['html']]),

            // Utility buttons
            new TwigFunction('button_copy', [$this, 'buttonCopy'], ['is_safe' => ['html']]),
            new TwigFunction('button_print', [$this, 'buttonPrint'], ['is_safe' => ['html']]),
            new TwigFunction('button_download', [$this, 'buttonDownload'], ['is_safe' => ['html']]),
            new TwigFunction('button_clear_search', [$this, 'buttonClearSearch'], ['is_safe' => ['html']]),

            // State toggle buttons
            new TwigFunction('button_toggle', [$this, 'buttonToggle'], ['is_safe' => ['html']]),

            // View toggle buttons
            new TwigFunction('button_view_toggle', [$this, 'buttonViewToggle'], ['is_safe' => ['html']]),

            // Filter buttons
            new TwigFunction('button_filter', [$this, 'buttonFilter'], ['is_safe' => ['html']]),

            // Form buttons
            new TwigFunction('button_submit', [$this, 'buttonSubmit'], ['is_safe' => ['html']]),

            // Danger buttons
            new TwigFunction('button_danger', [$this, 'buttonDanger'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render create/add button (modal trigger)
     */
    public function buttonCreate(
        string $modalUrl,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null,
        ?string $cssClass = null
    ): string {
        return $this->renderButton('create', [
            'url' => $modalUrl,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
            'css_class_override' => $cssClass,
        ]);
    }

    /**
     * Render edit button (modal trigger dropdown item)
     */
    public function buttonEdit(
        string $entityId,
        string $modalUrl,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null
    ): string {
        return $this->renderButton('edit', [
            'entity_id' => $entityId,
            'url' => $modalUrl,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
        ]);
    }

    /**
     * Render delete button (dropdown item with confirmation)
     */
    public function buttonDelete(
        string $entityId,
        string $deleteUrl,
        ?string $confirmTranslationKey = null,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        ?string $csrfPrefix = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null
    ): string {
        return $this->renderButton('delete', [
            'entity_id' => $entityId,
            'url' => $deleteUrl,
            'confirm_key' => $confirmTranslationKey,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'csrf_prefix' => $csrfPrefix,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
        ]);
    }

    /**
     * Render back button (navigation link)
     */
    public function buttonBack(
        string $path,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null
    ): string {
        return $this->renderButton('back', [
            'url' => $path,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
        ]);
    }

    /**
     * Render view button (navigation link or dropdown item)
     */
    public function buttonView(
        string $path,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null,
        bool $isDropdownItem = true
    ): string {
        return $this->renderButton('view', [
            'url' => $path,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
            'is_dropdown_item' => $isDropdownItem,
        ]);
    }

    /**
     * Render link button (generic navigation)
     */
    public function buttonLink(
        string $path,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null,
        string $variant = 'primary'
    ): string {
        $buttonType = $variant === 'secondary' ? 'link_secondary' : 'link_primary';

        return $this->renderButton($buttonType, [
            'url' => $path,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
        ]);
    }

    /**
     * Render modal trigger button
     */
    public function buttonModalTrigger(
        string $modalUrl,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null
    ): string {
        return $this->renderButton('modal_trigger', [
            'url' => $modalUrl,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
        ]);
    }

    /**
     * Render modal submit button
     */
    public function buttonModalSubmit(
        bool $isEdit = false,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $style = null
    ): string {
        return $this->renderButton('modal_submit', [
            'is_edit' => $isEdit,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'style' => $style,
        ]);
    }

    /**
     * Render modal cancel button
     */
    public function buttonModalCancel(
        ?string $translationKey = null,
        string $translationDomain = 'messages'
    ): string {
        return $this->renderButton('modal_cancel', [
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
        ]);
    }

    /**
     * Render dropdown toggle button
     */
    public function buttonDropdownToggle(
        string $size = 'sm',
        ?string $tooltip = null,
        ?string $icon = null
    ): string {
        return $this->renderButton('dropdown_toggle', [
            'size' => $size,
            'tooltip' => $tooltip,
            'icon' => $icon,
        ]);
    }

    /**
     * Render dropdown item button
     */
    public function buttonDropdownItem(
        string $action,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $icon = null,
        bool $isDanger = false,
        ?string $permission = null,
        ?string $dataController = null,
        ?string $dataAction = null
    ): string {
        return $this->renderButton('dropdown_item', [
            'action' => $action,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'icon' => $icon,
            'is_danger' => $isDanger,
            'permission' => $permission,
            'data_controller' => $dataController,
            'data_action' => $dataAction,
        ]);
    }

    /**
     * Render copy button
     */
    public function buttonCopy(
        string $targetId,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        string $size = '',
        ?string $icon = null
    ): string {
        return $this->renderButton('copy', [
            'target_id' => $targetId,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'size' => $size,
            'icon' => $icon,
        ]);
    }

    /**
     * Render print button
     */
    public function buttonPrint(
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        string $size = '',
        ?string $icon = null
    ): string {
        return $this->renderButton('print', [
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'size' => $size,
            'icon' => $icon,
        ]);
    }

    /**
     * Render download button
     */
    public function buttonDownload(
        string $path,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null
    ): string {
        return $this->renderButton('download', [
            'url' => $path,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
        ]);
    }

    /**
     * Render clear search button
     */
    public function buttonClearSearch(
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $icon = null
    ): string {
        return $this->renderButton('clear_search', [
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'icon' => $icon,
        ]);
    }

    /**
     * Render toggle button (active/inactive state)
     */
    public function buttonToggle(
        string $url,
        bool $isActive,
        ?string $activeTranslationKey = null,
        ?string $inactiveTranslationKey = null,
        string $translationDomain = 'messages',
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        string $size = 'sm',
        ?string $confirmKey = null
    ): string {
        $buttonType = $isActive ? 'toggle_warning' : 'toggle_success';

        return $this->renderButton($buttonType, [
            'url' => $url,
            'is_active' => $isActive,
            'active_translation_key' => $activeTranslationKey,
            'inactive_translation_key' => $inactiveTranslationKey,
            'translation_domain' => $translationDomain,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'size' => $size,
            'confirm_key' => $confirmKey,
        ]);
    }

    /**
     * Render view toggle button (grid/list/timeline)
     */
    public function buttonViewToggle(
        string $view,
        ?string $icon = null,
        bool $isActive = false,
        ?string $tooltip = null,
        string $size = 'sm'
    ): string {
        return $this->renderButton('view_toggle', [
            'view' => $view,
            'icon' => $icon,
            'is_active' => $isActive,
            'tooltip' => $tooltip,
            'size' => $size,
        ]);
    }

    /**
     * Render filter button
     */
    public function buttonFilter(
        string $filterValue,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        string $variant = 'primary',
        bool $isActive = false,
        string $size = 'sm'
    ): string {
        $buttonType = 'filter_' . $variant;

        return $this->renderButton($buttonType, [
            'filter_value' => $filterValue,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'is_active' => $isActive,
            'size' => $size,
        ]);
    }

    /**
     * Render submit button (form submit)
     */
    public function buttonSubmit(
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $icon = null,
        string $size = '',
        ?string $style = null,
        string $variant = 'primary'
    ): string {
        $buttonType = $variant === 'success' ? 'submit_success' : 'submit_primary';

        return $this->renderButton($buttonType, [
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'icon' => $icon,
            'size' => $size,
            'style' => $style,
        ]);
    }

    /**
     * Render danger button (destructive action with confirmation)
     */
    public function buttonDanger(
        string $action,
        ?string $translationKey = null,
        string $translationDomain = 'messages',
        ?string $confirmKey = null,
        ?string $tooltip = null,
        ?string $label = null,
        ?string $permission = null,
        string $size = '',
        ?string $style = null,
        ?string $icon = null
    ): string {
        return $this->renderButton('danger', [
            'action' => $action,
            'translation_key' => $translationKey,
            'translation_domain' => $translationDomain,
            'confirm_key' => $confirmKey,
            'tooltip' => $tooltip,
            'label' => $label,
            'permission' => $permission,
            'size' => $size,
            'style' => $style,
            'icon' => $icon,
        ]);
    }

    /**
     * Render button using template
     *
     * @param array<string, mixed> $context
     */
    private function renderButton(string $buttonType, array $context): string
    {
        $config = $this->buttonConfig->getButtonConfig($buttonType);

        // Check permission if provided
        if (isset($context['permission']) && $context['permission']) {
            $context['is_granted'] = $this->authorizationChecker->isGranted($context['permission']);
        } else {
            $context['is_granted'] = true;
        }

        // Merge config and context
        $context['config'] = $config;
        $context['button_type'] = $buttonType;

        // Render button template
        $templatePath = sprintf('_partials/buttons/_%s_button.html.twig', $buttonType);

        return $this->twig->render($templatePath, $context);
    }
}
