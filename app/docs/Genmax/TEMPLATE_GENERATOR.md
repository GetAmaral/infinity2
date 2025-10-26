# Genmax Template Generation - Comprehensive Implementation Plan

**Version:** 1.0
**Status:** Ready for Implementation
**Updated:** January 2025

---

## 🎯 Objective

Implement template generation for Genmax following the **Generated/Extended pattern**, integrating with existing Luminai architecture including:
- `_base_entity_list.html.twig` system with data-bind rendering
- `form-navigation` controller for Enter-as-Tab behavior
- Proper field type formatting and relationship display
- Multi-view support (Grid/List/Table)

---

## 📐 Architecture Overview

### Pattern Consistency

Templates follow **Genmax Generated/Extended Pattern**:
- **Generated templates**: `app/templates/genmax/twig/*.html.twig` (master templates, ALWAYS regenerated)
- **Extension templates**: `app/templates/{entity_slug}/*.html.twig` (entity-specific, created ONCE, safe to edit)

### Files to Create

#### 1. Service Layer
```
app/src/Service/Genmax/TemplateGenerator.php
```

#### 2. Master Templates (Generated - Always Overwritten)
```
app/templates/genmax/twig/
├── index_generated.html.twig       # Master list page template
├── show_generated.html.twig        # Master detail page template
├── form_generated.html.twig        # Master form template (shared by new/edit)
├── new_generated.html.twig         # Master create page template
└── edit_generated.html.twig        # Master edit page template
```

#### 3. Configuration Updates
```
app/config/services.yaml            # Add template paths
```

#### 4. Orchestrator Updates
```
app/src/Service/Genmax/GenmaxOrchestrator.php  # Enable template generation
```

---

## 🔧 Detailed Implementation Steps

### STEP 1: Create TemplateGenerator Service

**File:** `app/src/Service/Genmax/TemplateGenerator.php`

**Purpose:** Generate Twig templates using Base/Extension pattern

**Key Methods:**
```php
public function generate(GeneratorEntity $entity): array
protected function generateIndexTemplate(GeneratorEntity $entity): string
protected function generateShowTemplate(GeneratorEntity $entity): string
protected function generateFormTemplate(GeneratorEntity $entity): string
protected function generateNewTemplate(GeneratorEntity $entity): string
protected function generateEditTemplate(GeneratorEntity $entity): string
protected function buildTemplateContext(GeneratorEntity $entity): array
protected function getPropertyFormatting(GeneratorProperty $property): array
protected function getListProperties(GeneratorEntity $entity): array
protected function getShowProperties(GeneratorEntity $entity): array
protected function getRelationshipRoute(GeneratorProperty $property): string
```

**Context Data to Provide:**
```php
[
    'entity' => $entity,
    'entityName' => 'Contact',           // PascalCase
    'entitySlug' => 'contact',           // snake_case (for routes/paths)
    'entityVariable' => 'contact',        // camelCase (for Twig variables)
    'entityPluralName' => 'Contacts',
    'entityPluralVariable' => 'contacts',
    'routePrefix' => 'contact',          // For route names
    'pageIcon' => 'bi-person',           // Bootstrap icon
    'entityLabel' => 'Contact',
    'translationDomain' => 'contact',

    // Properties for list view
    'listProperties' => [
        [
            'name' => 'fullName',
            'label' => 'Full Name',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'getter' => 'getFullName',
            'isRelationship' => false,
            'icon' => 'person',
        ],
        [
            'name' => 'company',
            'label' => 'Company',
            'type' => 'string',
            'sortable' => true,
            'isRelationship' => true,
            'relationshipProperty' => 'name',
            'relationshipRoute' => 'company_show',
            'icon' => 'building',
        ],
        // ... more properties
    ],

    // Properties for show view
    'showProperties' => [
        // Same structure as listProperties
        // + formatting information per type
        [
            'name' => 'createdAt',
            'label' => 'Created At',
            'type' => 'datetime_immutable',
            'getter' => 'getCreatedAt',
            'format' => 'datetime',
            'dateFormat' => 'F j, Y, g:i A',
            'icon' => 'calendar-event',
        ],
    ],

    // Form properties
    'formProperties' => [
        // Properties to show in forms
    ],

    // Operations enabled
    'operations' => [
        'index' => true,
        'show' => true,
        'new' => true,
        'edit' => true,
        'delete' => true,
    ],

    // Voter class for permissions
    'voterClass' => 'ContactVoter',
    'hasVoter' => true,

    // Form class
    'formTypeClass' => 'ContactType',
]
```

**Property Formatting Logic:**

```php
protected function getPropertyFormatting(GeneratorProperty $property): array
{
    $type = $property->getPropertyType();
    $isRelationship = $property->getRelationshipType() !== null;

    if ($isRelationship) {
        return [
            'format' => 'relationship',
            'relationshipRoute' => $this->getRelationshipRoute($property),
            'icon' => 'arrow-right-circle',
        ];
    }

    return match($type) {
        'boolean' => [
            'format' => 'boolean',
            'icon' => 'toggle-on',
        ],
        'datetime', 'datetime_immutable' => [
            'format' => 'datetime',
            'icon' => 'calendar-event',
            'dateFormat' => 'F j, Y, g:i A',
        ],
        'date' => [
            'format' => 'date',
            'icon' => 'calendar',
            'dateFormat' => 'M d, Y',
        ],
        'time' => [
            'format' => 'time',
            'icon' => 'clock',
            'dateFormat' => 'H:i',
        ],
        'uuid' => [
            'format' => 'uuid',
            'icon' => 'key',
        ],
        'text' => [
            'format' => 'text',
            'icon' => 'align-left',
        ],
        'integer', 'smallint', 'bigint' => [
            'format' => 'integer',
            'icon' => 'hash',
        ],
        'float', 'decimal' => [
            'format' => 'decimal',
            'icon' => 'currency-dollar',
        ],
        default => [
            'format' => 'string',
            'icon' => 'circle',
        ],
    };
}
```

---

### STEP 2: Create Master Templates

#### 2.1: index_generated.html.twig

**Extends:** `_base_entity_list.html.twig`

**Purpose:** List page with Grid/List/Table views using data-bind system

**Key Features:**
1. **Extends base list template** - Inherits multi-view system
2. **Data-bind rendering** - Client-side templating via Stimulus `view-toggle` controller
3. **Override blocks:**
   - `page_icon` - Entity-specific icon
   - `grid_view_item_template` - Card layout for grid view
   - `list_view_item_template` - Compact layout for list view
   - `table_headers` - Table column headers
   - `table_view_row_template` - Table row layout

**Template Structure:**
```twig
{% extends '_base_entity_list.html.twig' %}

{#
 # Data-Bind Template System for {{ entityName }}
 # ============================================
 # Example API response for {{ entityName }}:
 # {
 #   "id": "uuid-here",
 #   "fullName": "John Doe",
 #   "company": {
 #     "id": "uuid-here",
 #     "display": "Acme Corporation"
 #   },
 #   "isActive": true,
 #   "createdAt": "2025-01-01T00:00:00+00:00",
 #   "createdAtFormatted": "Jan 01, 2025"
 # }
#}

{# Customize page icon #}
{% block page_icon %}<i class="bi bi-{{ pageIcon }} text-neon fs-2 me-3"></i>{% endblock %}

{# Grid View Template #}
{% block grid_view_item_template %}
    <div class="col">
        <div class="luminai-card h-100 p-4" style="cursor: pointer;"
             onclick="window.location.href='/{{ routePrefix }}/' + this.closest('[data-entity-id]').dataset.entityId">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div onclick="event.stopPropagation();" class="dropdown card-dropdown me-3">
                    {{ button_dropdown_toggle() }}
                    <ul class="dropdown-menu dropdown-menu-dark" style="background: var(--luminai-dark-surface); z-index: 1060;">
                        <li><a class="dropdown-item" onclick="window.location.href='/{{ routePrefix }}/'+this.closest('[data-entity-id]').dataset.entityId"><i class="bi bi-eye me-2"></i>{{ 'common.action.view.details'|trans }}</a></li>
                        <li>{{ button_edit('{entityId}', '/{{ routePrefix }}/{entityId}/edit', null, 'messages', label='button.edit') }}</li>
                        <li><hr class="dropdown-divider"></li>
                        <li>{{ button_delete('{entityId}', '/{{ routePrefix }}/{entityId}/delete', 'confirm.delete.entity', label='button.delete') }}</li>
                    </ul>
                </div>
                <div class="d-flex align-items-center flex-grow-1">
                    <div class="rounded-3 d-flex align-items-center justify-content-center me-3"
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="bi bi-{{ pageIcon }} text-white fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        {% for property in listProperties|slice(0, 3) %}
                            {% if loop.first %}
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h5 class="mb-0 text-white" data-bind="{{ property.name }}" data-bind-text></h5>
                                </div>
                            {% else %}
                                <p class="text-muted mb-2" style="font-size: 0.85rem;">
                                    {% if property.isRelationship %}
                                        <span data-bind="{{ property.name }}.display" data-bind-text></span>
                                    {% else %}
                                        <span data-bind="{{ property.name }}" data-bind-text></span>
                                    {% endif %}
                                </p>
                            {% endif %}
                        {% endfor %}
                    </div>
                </div>
            </div>
            <div class="mt-auto pt-2 border-top" style="border-color: rgba(255, 255, 255, 0.1) !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-calendar-plus me-1"></i><span data-bind="createdAtFormatted" data-bind-text></span>
                    </small>
                    <small class="text-muted" data-bind-if="updatedAtFormatted">
                        <i class="bi bi-clock-history me-1"></i><span data-bind="updatedAtFormatted" data-bind-text></span>
                    </small>
                </div>
            </div>
        </div>
    </div>
{% endblock %}

{# List View Template #}
{% block list_view_item_template %}
    <div class="luminai-card mb-3 p-3">
        <div class="d-flex align-items-center">
            <div onclick="event.stopPropagation();" class="dropdown card-dropdown me-3">
                {{ button_dropdown_toggle() }}
                <ul class="dropdown-menu dropdown-menu-dark" style="background: var(--luminai-dark-surface); z-index: 1060;">
                    <li><a class="dropdown-item" onclick="window.location.href='/{{ routePrefix }}/'+this.closest('[data-entity-id]').dataset.entityId"><i class="bi bi-eye me-2"></i>{{ 'common.action.view.details'|trans }}</a></li>
                    <li>{{ button_edit('{entityId}', '/{{ routePrefix }}/{entityId}/edit', null, 'messages') }}</li>
                    <li><hr class="dropdown-divider"></li>
                    <li>{{ button_delete('{entityId}', '/{{ routePrefix }}/{entityId}/delete', 'confirm.delete.entity') }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center flex-grow-1" style="cursor: pointer;"
                 onclick="window.location.href='/{{ routePrefix }}/' + this.closest('[data-entity-id]').dataset.entityId">
                <div class="rounded-3 d-flex align-items-center justify-content-center me-3"
                     style="width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="bi bi-{{ pageIcon }} text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        {% for property in listProperties|slice(0, 4) %}
                            {% if loop.first %}
                                <h6 class="mb-0 text-white" data-bind="{{ property.name }}" data-bind-text></h6>
                            {% else %}
                                {% if property.isRelationship %}
                                    <span class="badge bg-info">
                                        <span data-bind="{{ property.name }}.display" data-bind-text></span>
                                    </span>
                                {% else %}
                                    <span class="text-muted" data-bind="{{ property.name }}" data-bind-text></span>
                                {% endif %}
                            {% endif %}
                        {% endfor %}
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endblock %}

{# Table Headers #}
{% block table_headers %}
    <th style="width: 60px;">{{ 'common.label.actions'|trans }}</th>
    {% for property in listProperties %}
        <th {% if property.sortable %}data-sort-field="{{ property.name }}"{% endif %}
            {% if property.type == 'boolean' %}data-field-type="boolean"{% endif %}
            {% if property.type in ['datetime', 'datetime_immutable', 'date'] %}data-field-type="date"{% endif %}>
            {{ property.label|trans }}
        </th>
    {% endfor %}
{% endblock %}

{# Table Row Template #}
{% block table_view_row_template %}
    <tr style="cursor: pointer;"
        onclick="window.location.href='/{{ routePrefix }}/' + this.closest('[data-entity-id]').dataset.entityId">
        <td onclick="event.stopPropagation();">
            <div class="dropdown card-dropdown">
                {{ button_dropdown_toggle() }}
                <ul class="dropdown-menu dropdown-menu-dark" style="background: var(--luminai-dark-surface); z-index: 1060;">
                    <li><a class="dropdown-item" onclick="window.location.href='/{{ routePrefix }}/'+this.closest('[data-entity-id]').dataset.entityId"><i class="bi bi-eye me-2"></i>{{ 'common.action.view.details'|trans }}</a></li>
                    <li>{{ button_edit('{entityId}', '/{{ routePrefix }}/{entityId}/edit', null, 'messages') }}</li>
                    <li><hr class="dropdown-divider"></li>
                    <li>{{ button_delete('{entityId}', '/{{ routePrefix }}/{entityId}/delete', 'confirm.delete.entity') }}</li>
                </ul>
            </div>
        </td>
        {% for property in listProperties %}
            <td>
                {% if property.isRelationship %}
                    <span data-bind="{{ property.name }}.display" data-bind-text></span>
                {% elseif property.type == 'boolean' %}
                    <span class="badge bg-success" data-bind-if="{{ property.name }}">
                        <i class="bi bi-check-circle me-1"></i>{{ 'common.yes'|trans }}
                    </span>
                    <span class="badge bg-secondary" data-bind-if="!{{ property.name }}">
                        <i class="bi bi-x-circle me-1"></i>{{ 'common.no'|trans }}
                    </span>
                {% else %}
                    <span data-bind="{{ property.name }}" data-bind-text></span>
                {% endif %}
            </td>
        {% endfor %}
    </tr>
{% endblock %}
```

---

#### 2.2: show_generated.html.twig

**Purpose:** Detail/show page with properly formatted field values

**Key Features:**
1. **Bento Grid Layout** - Responsive card-based layout
2. **Field Type Formatting:**
   - **String/Text**: Direct display
   - **Boolean**: Badge with icon
   - **DateTime**: `{{ entity.createdAt|date('F j, Y, g:i A') }}`
   - **Date**: `{{ entity.birthDate|date('M d, Y') }}`
   - **Relationships**: Clickable link with toString
   - **UUID**: `{{ entity.id.toString }}`
   - **Enum**: Display label/name
   - **Null values**: Display `-` or "Not set"

**Template Structure:**
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ {{ entityVariable }} }} - {{ '{{ entitySlug }}.singular'|trans({}, '{{ translationDomain }}') }} - {{ 'page.luminai.suffix'|trans }}{% endblock %}

{% block body %}
{# Navigation #}
<div class="mb-4 d-flex gap-2 align-items-center">
    {{ button_back(path('{{ routePrefix }}_index'), '{{ entitySlug }}.back.to.list', '{{ translationDomain }}') }}
    {% if hasVoter %}
        {% if is_granted(constant('App\\Security\\Voter\\{{ voterClass }}::EDIT'), {{ entityVariable }}) %}
            {{ button_edit({{ entityVariable }}.id, path('{{ routePrefix }}_edit', {id: {{ entityVariable }}.id}), 'button.edit', 'messages', null, null, null, '', null, null, false) }}
        {% endif %}
    {% else %}
        {{ button_edit({{ entityVariable }}.id, path('{{ routePrefix }}_edit', {id: {{ entityVariable }}.id}), 'button.edit', 'messages', null, null, null, '', null, null, false) }}
    {% endif %}
</div>

{# Entity Header #}
<div class="ai-dashboard-header mb-4">
    <div class="d-flex justify-content-between align-items-start">
        <div class="d-flex align-items-center">
            <div class="position-relative me-4">
                <div class="p-3 rounded-3" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="bi bi-{{ pageIcon }} text-white fs-2"></i>
                </div>
                <div class="position-absolute top-0 end-0 translate-middle">
                    <span class="badge rounded-pill" style="background: var(--luminai-ai-gradient); font-size: 0.7rem;">{{ 'ui.ai'|trans }}</span>
                </div>
            </div>
            <div>
                <h1 class="text-gradient mb-2">{{ {{ entityVariable }} }}</h1>
                <p class="text-secondary mb-0 fs-5">{{ '{{ entitySlug }}.singular'|trans({}, '{{ translationDomain }}') }}</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <div class="ai-status-indicator">
                <i class="bi bi-{{ pageIcon }}"></i> {{ '{{ entitySlug }}.section'|trans({}, '{{ translationDomain }}') }}
            </div>
            <div class="real-time-badge">
                {{ 'ui.active'|trans }}
            </div>
        </div>
    </div>
</div>

{# Entity Details Bento Grid #}
<div class="bento-grid">
    {# Main Information Card #}
    <div class="bento-item large">
        <div class="luminai-card ai-enhanced p-4 h-100">
            <h5 class="text-neon mb-4">
                <i class="bi bi-info-circle me-2"></i>{{ 'entity.details'|trans }}
            </h5>

            <div class="row g-3">
                {% for property in showProperties %}
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-{{ property.icon|default('circle') }} text-neon fs-5"></i>
                            <div class="flex-grow-1">
                                <div class="text-muted small mb-1">{{ property.label|trans }}</div>
                                <div class="text-white">
                                    {% if property.isRelationship %}
                                        {# Relationship: Link with toString #}
                                        {% if {{ entityVariable }}.{{ property.getter }}() %}
                                            <a href="{{ path('{{ property.relationshipRoute }}', {id: {{ entityVariable }}.{{ property.getter }}().id}) }}"
                                               class="text-white text-decoration-none">
                                                {{ {{ entityVariable }}.{{ property.getter }}() }}
                                                <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        {% else %}
                                            <span class="text-muted">-</span>
                                        {% endif %}
                                    {% elseif property.type == 'boolean' %}
                                        {# Boolean: Badge #}
                                        {% if {{ entityVariable }}.{{ property.getter }}() %}
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>{{ 'common.yes'|trans }}
                                            </span>
                                        {% else %}
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-x-circle me-1"></i>{{ 'common.no'|trans }}
                                            </span>
                                        {% endif %}
                                    {% elseif property.type in ['datetime', 'datetime_immutable'] %}
                                        {# DateTime: Full format #}
                                        {{ {{ entityVariable }}.{{ property.getter }}()|date('F j, Y, g:i A') }}
                                    {% elseif property.type == 'date' %}
                                        {# Date: Short format #}
                                        {{ {{ entityVariable }}.{{ property.getter }}()|date('M d, Y') }}
                                    {% elseif property.type == 'time' %}
                                        {# Time: 24-hour format #}
                                        {{ {{ entityVariable }}.{{ property.getter }}()|date('H:i') }}
                                    {% elseif property.type == 'uuid' %}
                                        {# UUID: toString #}
                                        <code class="text-muted">{{ {{ entityVariable }}.{{ property.getter }}().toString }}</code>
                                    {% elseif property.type == 'text' %}
                                        {# Text: Multi-line #}
                                        <div class="text-white" style="white-space: pre-wrap;">{{ {{ entityVariable }}.{{ property.getter }}() ?? '-' }}</div>
                                    {% else %}
                                        {# Default: Direct output #}
                                        {{ {{ entityVariable }}.{{ property.getter }}() ?? '-' }}
                                    {% endif %}
                                </div>
                            </div>
                        </div>
                    </div>
                {% endfor %}
            </div>
        </div>
    </div>

    {# Metadata Card #}
    <div class="bento-item">
        <div class="luminai-card ai-enhanced p-4 h-100">
            <h5 class="text-neon mb-4">
                <i class="bi bi-clock-history me-2"></i>{{ 'entity.metadata'|trans }}
            </h5>

            <div class="d-flex flex-column gap-3">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-calendar-plus text-neon fs-5"></i>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">{{ 'field.created_at'|trans }}</div>
                        <div class="text-white">{{ {{ entityVariable }}.createdAt|date('F j, Y, g:i A') }}</div>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-clock-history text-neon fs-5"></i>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">{{ 'field.updated_at'|trans }}</div>
                        <div class="text-white">{{ {{ entityVariable }}.updatedAt|date('F j, Y, g:i A') }}</div>
                    </div>
                </div>

                {% if hasOrganization %}
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-building text-neon fs-5"></i>
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">{{ 'field.organization'|trans }}</div>
                        <div class="text-white">
                            <a href="{{ path('organization_show', {id: {{ entityVariable }}.organization.id}) }}"
                               class="text-white text-decoration-none">
                                {{ {{ entityVariable }}.organization }}
                                <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                {% endif %}
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

---

#### 2.3: form_generated.html.twig

**Purpose:** Shared form template for create/edit operations

**Key Features:**
1. **Form Navigation Controller**: `data-controller="form-navigation"` for Enter-as-Tab behavior
2. **Turbo Drive**: `data-turbo="true"` for seamless navigation
3. **CSRF Protection**: Built-in via Symfony forms
4. **Conditional Rendering**: Show/hide fields based on create vs edit

**Template Structure:**
```twig
{% extends 'base.html.twig' %}

{% block title %}
    {% if {{ entityVariable }}.id %}
        {{ 'action.edit'|trans }} {{ '{{ entitySlug }}.singular'|trans({}, '{{ translationDomain }}') }}
    {% else %}
        {{ 'action.create'|trans }} {{ '{{ entitySlug }}.singular'|trans({}, '{{ translationDomain }}') }}
    {% endif %}
{% endblock %}

{% block body %}
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="luminai-card p-4">
                <div class="mb-4">
                    <h2>
                        {% if {{ entityVariable }}.id %}
                            <i class="bi bi-pencil me-2"></i>{{ 'action.edit'|trans }} {{ '{{ entitySlug }}.singular'|trans({}, '{{ translationDomain }}') }}
                        {% else %}
                            <i class="bi bi-plus-circle me-2"></i>{{ 'action.create'|trans }} {{ '{{ entitySlug }}.singular'|trans({}, '{{ translationDomain }}') }}
                        {% endif %}
                    </h2>
                </div>

                {# CRITICAL: Add form-navigation controller for Enter-as-Tab behavior #}
                {{ form_start(form, {
                    'attr': {
                        'data-turbo': 'true',
                        'data-controller': 'form-navigation'
                    }
                }) }}

                {{ form_errors(form) }}

                {# Render all form fields automatically #}
                {% for child in form.children %}
                    {% if child.vars.name not in ['_token'] %}
                        <div class="mb-3">
                            {{ form_row(child) }}
                        </div>
                    {% endif %}
                {% endfor %}

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ path('{{ routePrefix }}_index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>{{ 'action.back'|trans }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>{{ 'action.save'|trans }}
                    </button>
                </div>

                {{ form_end(form) }}
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

---

#### 2.4: new_generated.html.twig

**Purpose:** Wrapper for create operation

**Template:**
```twig
{% set {{ entityVariable }} = {{ entityVariable }} ?? null %}
{% include 'genmax/twig/form_generated.html.twig' with {
    '{{ entityVariable }}': {{ entityVariable }},
    'form': form
} %}
```

---

#### 2.5: edit_generated.html.twig

**Purpose:** Wrapper for edit operation

**Template:**
```twig
{% include 'genmax/twig/form_generated.html.twig' with {
    '{{ entityVariable }}': {{ entityVariable }},
    'form': form
} %}
```

---

### STEP 3: Configuration Updates

**File:** `app/config/services.yaml`

**Add to parameters section:**
```yaml
parameters:
    genmax.paths:
        # ... existing paths ...
        template_dir: 'templates'
        template_genmax_dir: 'templates/genmax/twig'

    genmax.templates:
        # ... existing templates ...
        template_index_generated: 'genmax/twig/index_generated.html.twig'
        template_show_generated: 'genmax/twig/show_generated.html.twig'
        template_form_generated: 'genmax/twig/form_generated.html.twig'
        template_new_generated: 'genmax/twig/new_generated.html.twig'
        template_edit_generated: 'genmax/twig/edit_generated.html.twig'
```

---

### STEP 4: GenmaxOrchestrator Updates

**File:** `app/src/Service/Genmax/GenmaxOrchestrator.php`

**Changes Required:**

#### 4.1: Enable Template Generation (Line 40)
```php
private const TEMPLATE_ACTIVE = true;  // Change from false to true
```

#### 4.2: Inject TemplateGenerator (Constructor)
```php
public function __construct(
    private readonly string $projectDir,
    #[Autowire(param: 'genmax.paths')]
    private readonly array $paths,
    private readonly GeneratorEntityRepository $generatorEntityRepository,
    private readonly EntityManagerInterface $entityManager,
    private readonly BackupService $backupService,
    private readonly EntityGenerator $entityGenerator,
    private readonly ApiGenerator $apiGenerator,
    private readonly DtoGenerator $dtoGenerator,
    private readonly StateProcessorGenerator $stateProcessorGenerator,
    private readonly RepositoryGenerator $repositoryGenerator,
    private readonly StateProviderGenerator $stateProviderGenerator,
    private readonly ControllerGenerator $controllerGenerator,
    private readonly VoterGenerator $voterGenerator,
    private readonly FormGenerator $formGenerator,
    private readonly TemplateGenerator $templateGenerator,  // ADD THIS LINE
    private readonly LoggerInterface $logger
) {}
```

#### 4.3: Add Template Generation Call (Around line 260)
```php
// Templates (ACTIVE)
if (self::TEMPLATE_ACTIVE) {
    try {
        $files = $this->templateGenerator->generate($entity);
        $generatedFiles = array_merge($generatedFiles, $files);
        $currentStep++;
    } catch (\Throwable $e) {
        $this->logger->error("[GENMAX] Template generation failed", [
            'entity' => $entity->getEntityName(),
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
```

#### 4.4: Add Template Files to Backup (collectFilesToBackup method, around line 394)
```php
// Templates (ACTIVE)
if (self::TEMPLATE_ACTIVE) {
    $slug = $entity->getSlug();
    $files[] = sprintf('%s/%s/%s/index.html.twig', $this->projectDir, $this->paths['template_dir'], $slug);
    $files[] = sprintf('%s/%s/%s/show.html.twig', $this->projectDir, $this->paths['template_dir'], $slug);
    $files[] = sprintf('%s/%s/%s/new.html.twig', $this->projectDir, $this->paths['template_dir'], $slug);
    $files[] = sprintf('%s/%s/%s/edit.html.twig', $this->projectDir, $this->paths['template_dir'], $slug);
}
```

#### 4.5: Update countActiveGenerators (Around line 440)
```php
private function countActiveGenerators(): int
{
    $count = 0;
    $count += self::ENTITY_ACTIVE ? 1 : 0;
    $count += self::API_ACTIVE ? 1 : 0;
    $count += self::DTO_ACTIVE ? 1 : 0;
    $count += self::STATE_PROCESSOR_ACTIVE ? 1 : 0;
    $count += self::REPOSITORY_ACTIVE ? 1 : 0;
    $count += self::STATE_PROVIDER_ACTIVE ? 1 : 0;
    $count += self::CONTROLLER_ACTIVE ? 1 : 0;
    $count += self::VOTER_ACTIVE ? 1 : 0;
    $count += self::FORM_ACTIVE ? 1 : 0;
    $count += self::TEMPLATE_ACTIVE ? 1 : 0;  // ADD THIS LINE
    $count += self::TESTS_ACTIVE ? 1 : 0;
    return $count;
}
```

#### 4.6: Update getActiveGenerators (Around line 460)
```php
private function getActiveGenerators(): array
{
    $active = [];
    if (self::ENTITY_ACTIVE) $active[] = 'entity';
    if (self::API_ACTIVE) $active[] = 'api';
    if (self::DTO_ACTIVE) $active[] = 'dto';
    if (self::STATE_PROCESSOR_ACTIVE) $active[] = 'state_processor';
    if (self::REPOSITORY_ACTIVE) $active[] = 'repository';
    if (self::STATE_PROVIDER_ACTIVE) $active[] = 'state_provider';
    if (self::CONTROLLER_ACTIVE) $active[] = 'controller';
    if (self::VOTER_ACTIVE) $active[] = 'voter';
    if (self::FORM_ACTIVE) $active[] = 'form';
    if (self::TEMPLATE_ACTIVE) $active[] = 'template';  // ADD THIS LINE
    if (self::NAVIGATION_ACTIVE) $active[] = 'navigation';
    if (self::TRANSLATION_ACTIVE) $active[] = 'translation';
    if (self::TESTS_ACTIVE) $active[] = 'tests';
    return $active;
}
```

---

## 🎨 Special Considerations

### 1. Enter-as-Tab Behavior

**CRITICAL**: All generated forms MUST include:
```twig
{{ form_start(form, {'attr': {'data-controller': 'form-navigation'}}) }}
```

This enables the `form_navigation_controller.js` which:
- Pressing Enter moves to next field (instead of submitting)
- Last field: Enter submits form
- Textareas: Enter inserts new line (natural behavior)
- Select fields: Enter selects item and moves next
- Tom-select fields: Properly handled with focus

**Controller Location:** `app/assets/controllers/form_navigation_controller.js`

### 2. Relationship Field Display

**In Show Templates:**
```twig
{# CORRECT: Use entity toString with clickable link #}
{% if contact.company %}
    <a href="{{ path('company_show', {id: contact.company.id}) }}">
        {{ contact.company }}  {# Calls __toString() automatically #}
    </a>
{% else %}
    <span class="text-muted">-</span>
{% endif %}
```

**In List Templates (data-bind):**
```twig
{# API provides: { "company": { "id": "uuid", "display": "Acme Corp" } } #}
<span data-bind="company.display" data-bind-text></span>
```

**IMPORTANT**: The controller's `entityToArray()` method MUST format relationships as:
```php
'company' => $companyRel ? [
    'id' => $companyRel->getId()->toString(),
    'display' => (string) $companyRel,  // Uses __toString()
] : null
```

### 3. Boolean Field Display

**Always use badges with icons:**
```twig
{% if entity.isActive %}
    <span class="badge bg-success">
        <i class="bi bi-check-circle me-1"></i>{{ 'common.yes'|trans }}
    </span>
{% else %}
    <span class="badge bg-secondary">
        <i class="bi bi-x-circle me-1"></i>{{ 'common.no'|trans }}
    </span>
{% endif %}
```

**In data-bind (list views):**
```twig
<span class="badge bg-success" data-bind-if="isActive">
    <i class="bi bi-check-circle me-1"></i>{{ 'common.yes'|trans }}
</span>
<span class="badge bg-secondary" data-bind-if="!isActive">
    <i class="bi bi-x-circle me-1"></i>{{ 'common.no'|trans }}
</span>
```

### 4. DateTime Formatting

```twig
{# DateTime: Full format #}
{{ entity.createdAt|date('F j, Y, g:i A') }}
{# Output: January 15, 2025, 2:30 PM #}

{# Date: Short format #}
{{ entity.birthDate|date('M d, Y') }}
{# Output: Jan 15, 2025 #}

{# Time: 24-hour #}
{{ entity.startTime|date('H:i') }}
{# Output: 14:30 #}

{# Date: ISO format for sorting #}
{{ entity.createdAt|date('Y-m-d H:i:s') }}
{# Output: 2025-01-15 14:30:00 #}
```

**For data-bind (formatted by controller):**
```twig
<span data-bind="createdAtFormatted" data-bind-text></span>
```

Controller provides: `"createdAtFormatted": "Jan 15, 2025"`

### 5. Null Value Handling

```twig
{# Display dash for null values #}
{{ entity.propertyName ?? '-' }}

{# Or with null check #}
{% if entity.propertyName %}
    {{ entity.propertyName }}
{% else %}
    <span class="text-muted">{{ 'common.not.set'|trans }}</span>
{% endif %}
```

### 6. Text Field Display (Multi-line)

```twig
{# Preserve line breaks #}
<div class="text-white" style="white-space: pre-wrap;">
    {{ entity.description ?? '-' }}
</div>
```

### 7. UUID Display

```twig
{# Monospace font for UUIDs #}
<code class="text-muted">{{ entity.id.toString }}</code>
```

### 8. Enum Field Display

```twig
{# Enum properties are stored as strings - direct display #}
{{ entity.status }}

{# Or with translation #}
{{ ('enum.status.' ~ entity.status)|trans }}
```

---

## ✅ Validation Checklist

After implementation, verify:

### Service Layer
- [ ] `TemplateGenerator.php` created with all methods
- [ ] Property formatting logic handles all field types
- [ ] Relationship routing logic implemented
- [ ] Template context builder provides all required data
- [ ] Icon assignment for properties
- [ ] List properties filtered correctly (showInList = true)
- [ ] Show properties filtered correctly
- [ ] Smart file writer used for all file operations

### Master Templates
- [ ] `index_generated.html.twig` extends `_base_entity_list.html.twig`
- [ ] `index_generated.html.twig` overrides all required blocks
- [ ] Grid view template uses proper card layout
- [ ] List view template uses compact layout
- [ ] Table view has sortable headers
- [ ] Data-bind attributes correctly placed
- [ ] Dropdown actions use proper event.stopPropagation()
- [ ] `show_generated.html.twig` uses Bento Grid layout
- [ ] `show_generated.html.twig` formats all field types correctly
- [ ] Relationships show as clickable links
- [ ] Boolean fields show as badges
- [ ] DateTime fields formatted properly
- [ ] Null values handled gracefully
- [ ] `form_generated.html.twig` includes `data-controller="form-navigation"`
- [ ] `form_generated.html.twig` includes `data-turbo="true"`
- [ ] Form renders all fields automatically
- [ ] `new_generated.html.twig` includes form template
- [ ] `edit_generated.html.twig` includes form template

### Configuration
- [ ] `services.yaml` updated with template paths
- [ ] Template paths in genmax.templates parameter
- [ ] Template directory in genmax.paths parameter

### Orchestrator
- [ ] `TEMPLATE_ACTIVE = true` (line 40)
- [ ] `TemplateGenerator` injected in constructor
- [ ] Template generation called in generate loop (around line 260)
- [ ] Template files added to backup (around line 394)
- [ ] `countActiveGenerators()` updated (around line 440)
- [ ] `getActiveGenerators()` updated (around line 460)

### Testing
- [ ] Generate templates for test entity (e.g., Contact)
- [ ] Test index page loads
- [ ] Test Grid view displays correctly
- [ ] Test List view displays correctly
- [ ] Test Table view displays correctly
- [ ] Test view toggle switches work
- [ ] Test sorting in table view
- [ ] Test search functionality
- [ ] Test pagination
- [ ] Test show page loads
- [ ] Test all field types display correctly
- [ ] Test relationship links work
- [ ] Test boolean badges display
- [ ] Test datetime formatting
- [ ] Test null values show dash
- [ ] Test new page loads
- [ ] Test form fields render
- [ ] Test Enter-as-Tab behavior works
- [ ] Test form submission works
- [ ] Test edit page loads with data
- [ ] Test form updates entity
- [ ] Test validation errors display
- [ ] Test delete operation works

---

## 📚 References

### Existing Architecture
- **Base List Template**: `app/templates/_base_entity_list.html.twig`
- **Form Navigation Controller**: `app/assets/controllers/form_navigation_controller.js`
- **View Toggle Controller**: `app/assets/controllers/view_toggle_controller.js`
- **Example List Page**: `app/templates/organization/index.html.twig`
- **Example Show Page**: `app/templates/user/show.html.twig`
- **Form Theme**: `app/templates/genmax/twig/form_theme.html.twig`

### Existing Genmax Generators
- **Form Generator**: `app/src/Service/Genmax/FormGenerator.php`
- **Controller Generator**: `app/src/Service/Genmax/ControllerGenerator.php`
- **Voter Generator**: `app/src/Service/Genmax/VoterGenerator.php`
- **DTO Generator**: `app/src/Service/Genmax/DtoGenerator.php`
- **Entity Generator**: `app/src/Service/Genmax/EntityGenerator.php`

### Utilities
- **Smart File Writer**: `app/src/Service/Genmax/SmartFileWriter.php`
- **Genmax Extension**: `app/src/Service/Genmax/GenmaxExtension.php`
- **Utils**: `app/src/Service/Utils.php`

---

## 🚀 Implementation Notes

### Development Workflow

1. **Create TemplateGenerator Service**
   - Copy structure from `FormGenerator.php` as reference
   - Implement all template generation methods
   - Add property formatting logic
   - Test each method individually

2. **Create Master Templates**
   - Start with `index_generated.html.twig`
   - Test with existing entity
   - Create `show_generated.html.twig`
   - Test field formatting
   - Create `form_generated.html.twig`
   - Test form navigation
   - Create wrapper templates (new, edit)

3. **Update Configuration**
   - Add template paths to services.yaml
   - Clear Symfony cache

4. **Update Orchestrator**
   - Enable template generation flag
   - Add service injection
   - Add generation call
   - Add backup logic
   - Update helper methods

5. **Test End-to-End**
   - Run `php bin/console genmax:generate Contact`
   - Visit `/contact` route
   - Test all views (Grid/List/Table)
   - Visit `/contact/{id}` route
   - Test field display
   - Visit `/contact/new` route
   - Test form behavior
   - Create new record
   - Visit `/contact/{id}/edit` route
   - Update record
   - Delete record

### Debugging Tips

1. **Template Not Found**
   - Check file path matches configuration
   - Clear Twig cache: `php bin/console cache:clear`

2. **Data-bind Not Working**
   - Check browser console for JavaScript errors
   - Verify API response format matches template expectations
   - Check view-toggle controller is loaded

3. **Form Navigation Not Working**
   - Verify `data-controller="form-navigation"` attribute
   - Check browser console for Stimulus errors
   - Verify form-navigation-controller.js is loaded

4. **Field Not Displaying**
   - Check `showInList` or `showInForm` property settings
   - Verify property getter exists on entity
   - Check property type mapping

5. **Relationship Not Linking**
   - Verify relationship route exists
   - Check entity has `__toString()` method
   - Verify relationship property is not null

---

## 🎓 Best Practices

### Code Quality
- Follow PSR-12 coding standards
- Use type hints for all method parameters and return types
- Add PHPDoc comments for complex methods
- Use meaningful variable names
- Keep methods under 50 lines
- Extract complex logic into helper methods

### Template Quality
- Keep templates DRY (Don't Repeat Yourself)
- Use blocks for customization points
- Add comments explaining complex logic
- Use consistent indentation (4 spaces)
- Group related properties together
- Use semantic HTML5 elements

### Security
- Always use CSRF tokens in forms
- Use voter checks for sensitive operations
- Escape output where necessary (Twig auto-escapes)
- Validate user input on backend
- Don't expose sensitive data in templates

### Performance
- Use data-bind for list rendering (client-side)
- Minimize database queries in show pages
- Use eager loading for relationships
- Cache frequently accessed data
- Use pagination for large datasets

### Accessibility
- Use semantic HTML
- Add ARIA labels where appropriate
- Ensure keyboard navigation works
- Use proper heading hierarchy
- Add alt text for icons

### Maintainability
- Follow Genmax Generated/Extended pattern
- Never edit generated files
- Document customizations in extension files
- Keep template logic simple
- Use Symfony forms for complex forms

---

**END OF DOCUMENTATION**

**Version:** 1.0
**Last Updated:** January 2025
**Status:** Ready for Implementation
