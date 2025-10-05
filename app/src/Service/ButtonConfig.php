<?php

declare(strict_types=1);

namespace App\Service;

/**
 * ButtonConfig - Single Source of Truth for Button Definitions
 *
 * Centralizes button configuration including:
 * - Default icons, CSS classes, and translations
 * - Button types and behaviors
 * - Data attributes for Stimulus controllers
 * - Loading states and confirmation requirements
 *
 * Following Symfony 2025 best practices:
 * - Single configuration point for all buttons
 * - Consistent UX across entire application
 * - Easy maintenance and updates
 * - Automatic tooltip and permission integration
 */
final class ButtonConfig
{
    /**
     * Get button configuration by type
     *
     * @return array<string, array{
     *   class: string,
     *   default_icon: string,
     *   default_translation_key: string,
     *   type: string,
     *   data_controller?: string,
     *   data_action?: string,
     *   requires_confirm?: bool,
     *   default_confirm_key?: string,
     *   icon_create?: string,
     *   icon_edit?: string,
     *   translation_key_create?: string,
     *   translation_key_edit?: string,
     *   aria_label?: string
     * }>
     */
    public function getButtonConfig(string $buttonType): array
    {
        $buttons = $this->getAllButtons();

        if (!isset($buttons[$buttonType])) {
            throw new \InvalidArgumentException(sprintf('Unknown button type: %s', $buttonType));
        }

        return $buttons[$buttonType];
    }

    /**
     * Get all button configurations
     *
     * @return array<string, array<string, mixed>>
     */
    private function getAllButtons(): array
    {
        return [
            // PRIMARY ACTION BUTTONS
            'create' => [
                'class' => 'btn luminai-btn-primary',
                'default_icon' => 'bi-plus-circle',
                'default_translation_key' => 'button.create',
                'data_controller' => 'modal-opener',
                'type' => 'modal_trigger',
            ],

            'edit' => [
                'class' => 'dropdown-item',
                'default_icon' => 'bi-pencil',
                'default_translation_key' => 'button.edit',
                'data_controller' => 'modal-opener',
                'type' => 'dropdown_item',
            ],

            'delete' => [
                'class' => 'dropdown-item text-danger',
                'default_icon' => 'bi-trash',
                'default_translation_key' => 'button.delete',
                'default_confirm_key' => 'confirm.delete.entity',
                'requires_confirm' => true,
                'type' => 'dropdown_item',
            ],

            // NAVIGATION BUTTONS
            'back' => [
                'class' => 'btn luminai-btn-ai',
                'default_icon' => 'bi-arrow-left',
                'default_translation_key' => 'button.back.to.list',
                'type' => 'link',
            ],

            'view' => [
                'class' => 'dropdown-item',
                'default_icon' => 'bi-eye',
                'default_translation_key' => 'button.view',
                'type' => 'link',
            ],

            'link_primary' => [
                'class' => 'btn luminai-btn-primary',
                'default_icon' => 'bi-link-45deg',
                'default_translation_key' => 'button.view',
                'type' => 'link',
            ],

            'link_secondary' => [
                'class' => 'btn luminai-btn-ai',
                'default_icon' => 'bi-link-45deg',
                'default_translation_key' => 'button.view',
                'type' => 'link',
            ],

            // MODAL BUTTONS
            'modal_trigger' => [
                'class' => 'btn luminai-btn-primary',
                'default_icon' => 'bi-plus-circle',
                'default_translation_key' => 'button.open',
                'data_controller' => 'modal-opener',
                'type' => 'modal_trigger',
            ],

            'modal_submit' => [
                'class' => 'btn-modal-primary',
                'icon_create' => 'bi-plus-lg',
                'icon_edit' => 'bi-check2',
                'translation_key_create' => 'button.create',
                'translation_key_edit' => 'button.save.changes',
                'type' => 'submit',
                'data_controller' => 'button-loading',
            ],

            'modal_cancel' => [
                'class' => 'btn-modal-secondary',
                'default_icon' => '',
                'default_translation_key' => 'button.cancel',
                'data_action' => 'crud-modal#close',
                'type' => 'button',
            ],

            // DROPDOWN BUTTONS
            'dropdown_toggle' => [
                'class' => 'btn btn-outline-light',
                'default_icon' => 'bi-three-dots-vertical',
                'default_translation_key' => 'common.label.actions',
                'aria_label' => 'common.label.actions',
                'type' => 'button',
            ],

            'dropdown_item' => [
                'class' => 'dropdown-item',
                'default_icon' => 'bi-circle',
                'default_translation_key' => 'button.action',
                'type' => 'button',
            ],

            // UTILITY BUTTONS
            'copy' => [
                'class' => 'btn luminai-btn-ai',
                'default_icon' => 'bi-clipboard',
                'default_translation_key' => 'button.copy',
                'type' => 'button',
            ],

            'print' => [
                'class' => 'btn btn-primary',
                'default_icon' => 'bi-printer',
                'default_translation_key' => 'button.print',
                'type' => 'button',
            ],

            'download' => [
                'class' => 'btn luminai-btn-success',
                'default_icon' => 'bi-download',
                'default_translation_key' => 'button.download',
                'type' => 'link',
            ],

            'clear_search' => [
                'class' => 'btn-clear-search',
                'default_icon' => 'bi-x-lg',
                'default_translation_key' => 'button.clear',
                'type' => 'button',
            ],

            // STATE TOGGLE BUTTONS
            'toggle_success' => [
                'class' => 'btn luminai-btn-success',
                'default_icon' => 'bi-toggle-on',
                'default_translation_key' => 'button.activate',
                'type' => 'button',
            ],

            'toggle_warning' => [
                'class' => 'btn luminai-btn-warning',
                'default_icon' => 'bi-toggle-off',
                'default_translation_key' => 'button.deactivate',
                'type' => 'button',
            ],

            // VIEW TOGGLE BUTTONS
            'view_toggle' => [
                'class' => 'btn btn-outline-secondary',
                'default_icon' => 'bi-grid',
                'default_translation_key' => 'button.view',
                'type' => 'button',
            ],

            // FILTER BUTTONS
            'filter_primary' => [
                'class' => 'btn btn-outline-primary',
                'default_icon' => 'bi-funnel',
                'default_translation_key' => 'button.filter',
                'type' => 'button',
            ],

            'filter_success' => [
                'class' => 'btn btn-outline-success',
                'default_icon' => 'bi-funnel',
                'default_translation_key' => 'button.filter',
                'type' => 'button',
            ],

            'filter_warning' => [
                'class' => 'btn btn-outline-warning',
                'default_icon' => 'bi-funnel',
                'default_translation_key' => 'button.filter',
                'type' => 'button',
            ],

            'filter_danger' => [
                'class' => 'btn btn-outline-danger',
                'default_icon' => 'bi-funnel',
                'default_translation_key' => 'button.filter',
                'type' => 'button',
            ],

            // FORM BUTTONS
            'submit_primary' => [
                'class' => 'btn luminai-btn-primary',
                'default_icon' => 'bi-check2',
                'default_translation_key' => 'button.submit',
                'type' => 'submit',
            ],

            'submit_success' => [
                'class' => 'btn luminai-btn-success',
                'default_icon' => 'bi-check2',
                'default_translation_key' => 'button.submit',
                'type' => 'submit',
            ],

            // DANGER BUTTONS
            'danger' => [
                'class' => 'btn luminai-btn-danger',
                'default_icon' => 'bi-exclamation-triangle',
                'default_translation_key' => 'button.danger',
                'requires_confirm' => true,
                'default_confirm_key' => 'confirm.action',
                'type' => 'button',
            ],
        ];
    }

    /**
     * Get button types list
     *
     * @return array<string>
     */
    public function getButtonTypes(): array
    {
        return array_keys($this->getAllButtons());
    }
}
