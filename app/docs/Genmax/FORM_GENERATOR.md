# Genmax Form Generator - Implementation Guide

**Version:** 2.0
**Status:** Ready for Implementation
**Created:** October 2025
**Last Updated:** October 2025

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture](#2-architecture)
3. [Database Schema](#3-database-schema)
4. [Field Type Mapping](#4-field-type-mapping)
5. [Relationship Handling](#5-relationship-handling)
6. [Translation Strategy](#6-translation-strategy)
7. [Frontend Components](#7-frontend-components)
8. [Form Theme](#8-form-theme)
9. [FormGenerator Service](#9-formgenerator-service)
10. [Templates](#10-templates)
11. [Implementation Phases](#11-implementation-phases)
12. [Configuration](#12-configuration)
13. [Usage Examples](#13-usage-examples)
14. [Testing](#14-testing)

---

## 1. Overview

### 1.1 Purpose

The Genmax Form Generator automatically creates production-ready Symfony forms from database configuration (`GeneratorEntity` and `GeneratorProperty` tables), following the established Base/Extension pattern.

### 1.2 Key Features

✅ **Fully Automated** - Forms generated entirely from database configuration
✅ **Existing Infrastructure** - Reuses existing apiSearch, fullscreen textarea, modal system
✅ **i18n Compliant** - All text translated (zero hardcoded strings)
✅ **Relationship Support** - ManyToOne, ManyToMany, OneToOne, OneToMany with Add buttons
✅ **Modern UX** - Symfony UX Live Component for collections (zero JavaScript)
✅ **Custom Theme** - Luminai-styled forms with light/dark mode support
✅ **Validation Reuse** - Leverages existing validation_rules JSON field

### 1.3 Integration Points

**Reuses Existing:**
- `BaseApiController::apiSearch()` - UNACCENT + lowercase search
- `fullscreen_textarea_controller.js` - Automatic fullscreen button for textareas
- `crud_modal_controller.js` - Modal system for "Add" buttons
- `BaseRepository::apiSearch()` - Multi-tenant aware search
- `validation_rules` - Existing validation configuration

---

## 2. Architecture

### 2.1 Files Structure

```
app/
├── src/Service/Genmax/
│   └── FormGenerator.php                    # NEW - Form generation service
│
├── src/Form/
│   ├── {Entity}Type.php                     # Extension (safe to edit)
│   └── Generated/
│       └── {Entity}TypeGenerated.php        # Base (always regenerated)
│
├── templates/genmax/php/
│   ├── form_generated.php.twig              # NEW - Base form template
│   └── form_extension.php.twig              # NEW - Extension form template
│
├── templates/genmax/twig/
│   └── form_theme.html.twig                 # NEW - Custom Luminai form theme
│
├── assets/controllers/
│   ├── relation_select_controller.js         # NEW - Relation field + Add button
│   └── collection_form_controller.js         # NEW - OneToMany collection (optional)
│
└── translations/
    └── messages.en.yaml                      # AUTO-UPDATED - Entity translation keys
```

### 2.2 Generation Flow

```
GenmaxOrchestrator
    └─> FormGenerator::generate()
        ├─> generateBaseForm()           # Always regenerated
        ├─> generateExtensionForm()      # Once only
        └─> generateTranslationKeys()    # Updates messages.en.yaml
```

### 2.3 Base/Extension Pattern

**Base Class** (`src/Form/Generated/{Entity}TypeGenerated.php`):
- ALWAYS regenerated
- Contains all auto-generated form fields
- DO NOT EDIT

**Extension Class** (`src/Form/{Entity}Type.php`):
- Created once
- Safe to customize
- Add custom fields, transformers, event listeners

---

## 3. Database Schema

### 3.1 New Fields in `generator_property`

Add **6 new fields** to the `generator_property` table:

| Field Name | Type | Default | Description |
|------------|------|---------|-------------|
| `form_expanded` | boolean | false | Use radio/checkboxes instead of select dropdown |
| `collection_allow_add` | boolean | true | Allow adding items in OneToMany collections |
| `collection_allow_delete` | boolean | true | Allow deleting items in OneToMany collections |
| `form_widget_attr` | json | null | HTML attributes for widget (CSS classes, data-* attrs) |
| `form_label_attr` | json | null | HTML attributes for label element |
| `form_row_attr` | json | null | HTML attributes for row container |

### 3.2 Migration SQL

```sql
-- Add new form configuration fields
ALTER TABLE generator_property
  ADD COLUMN form_expanded BOOLEAN DEFAULT FALSE,
  ADD COLUMN collection_allow_add BOOLEAN DEFAULT TRUE,
  ADD COLUMN collection_allow_delete BOOLEAN DEFAULT TRUE,
  ADD COLUMN form_widget_attr JSON DEFAULT NULL,
  ADD COLUMN form_label_attr JSON DEFAULT NULL,
  ADD COLUMN form_row_attr JSON DEFAULT NULL;

-- Add helpful comments
COMMENT ON COLUMN generator_property.form_expanded IS
  'Use radio buttons/checkboxes instead of select dropdown for choice fields';
COMMENT ON COLUMN generator_property.collection_allow_add IS
  'Allow adding new items to OneToMany collections in forms';
COMMENT ON COLUMN generator_property.collection_allow_delete IS
  'Allow removing items from OneToMany collections in forms';
COMMENT ON COLUMN generator_property.form_widget_attr IS
  'JSON object with HTML attributes for the form widget (e.g., {"class": "custom-class", "data-foo": "bar"})';
COMMENT ON COLUMN generator_property.form_label_attr IS
  'JSON object with HTML attributes for the form label (e.g., {"class": "custom-label"})';
COMMENT ON COLUMN generator_property.form_row_attr IS
  'JSON object with HTML attributes for the form row container (e.g., {"class": "custom-row"})';
```

### 3.3 Existing Fields Reused

**From `GeneratorProperty`:**
- `form_type` - Manual form type override (e.g., 'EmailType')
- `form_options` - Manual form options override (JSON)
- `form_required` - Required flag (overrides nullable)
- `form_read_only` - Read-only flag (disables field)
- `form_help` - Help text shown below field
- `show_in_form` - Whether to include field in form
- `validation_rules` - Validation constraints (reused from DTO)
- `dto_nested_max_items` - Max items in OneToMany collections

### 3.4 Update GeneratorProperty Entity

```php
// src/Entity/Generator/GeneratorProperty.php

#[ORM\Column(type: 'boolean', options: ['default' => false])]
private bool $formExpanded = false;

#[ORM\Column(type: 'boolean', options: ['default' => true])]
private bool $collectionAllowAdd = true;

#[ORM\Column(type: 'boolean', options: ['default' => true])]
private bool $collectionAllowDelete = true;

#[ORM\Column(type: 'json', nullable: true)]
private ?array $formWidgetAttr = null;

#[ORM\Column(type: 'json', nullable: true)]
private ?array $formLabelAttr = null;

#[ORM\Column(type: 'json', nullable: true)]
private ?array $formRowAttr = null;

// Getters and Setters
public function isFormExpanded(): bool
{
    return $this->formExpanded;
}

public function setFormExpanded(bool $formExpanded): self
{
    $this->formExpanded = $formExpanded;
    return $this;
}

public function isCollectionAllowAdd(): ?bool
{
    return $this->collectionAllowAdd;
}

public function setCollectionAllowAdd(bool $collectionAllowAdd): self
{
    $this->collectionAllowAdd = $collectionAllowAdd;
    return $this;
}

public function isCollectionAllowDelete(): ?bool
{
    return $this->collectionAllowDelete;
}

public function setCollectionAllowDelete(bool $collectionAllowDelete): self
{
    $this->collectionAllowDelete = $collectionAllowDelete;
    return $this;
}

public function getFormWidgetAttr(): ?array
{
    return $this->formWidgetAttr;
}

public function setFormWidgetAttr(?array $formWidgetAttr): self
{
    $this->formWidgetAttr = $formWidgetAttr;
    return $this;
}

public function getFormLabelAttr(): ?array
{
    return $this->formLabelAttr;
}

public function setFormLabelAttr(?array $formLabelAttr): self
{
    $this->formLabelAttr = $formLabelAttr;
    return $this;
}

public function getFormRowAttr(): ?array
{
    return $this->formRowAttr;
}

public function setFormRowAttr(?array $formRowAttr): self
{
    $this->formRowAttr = $formRowAttr;
    return $this;
}
```

---

## 4. Field Type Mapping

### 4.1 Automatic Type Detection

The generator intelligently maps Doctrine property types to Symfony form types:

| Doctrine Type | Form Type | Special Features |
|---------------|-----------|------------------|
| `string` (length ≤ 255) | `TextType` | Standard text input |
| `string` (length > 255) | `TextareaType` | **Automatic fullscreen button** |
| `text` | `TextareaType` | **Automatic fullscreen button** |
| `integer`, `smallint`, `bigint` | `IntegerType` | Numeric input |
| `float`, `decimal` | `NumberType` | Decimal with scale/precision |
| `boolean` | `CheckboxType` | Single checkbox |
| `datetime`, `datetime_immutable` | `DateTimeType` | Date + time picker |
| `date` | `DateType` | Date picker only |
| `time` | `TimeType` | Time picker only |
| `json` | `TextareaType` | JSON editor |
| `uuid` | N/A | **Hidden** (auto-generated) |
| **Enum-backed** | `EnumType` | When `is_enum = true` |
| **ManyToOne** | `EntityType` | **Searchable select + Add button** |
| **ManyToMany** | `EntityType` (multiple) | **Searchable multi-select + Add button** |
| **OneToOne** | `EntityType` | **Searchable select (unrelated only) + Add button** |
| **OneToMany** | `CollectionType` | **Live Component with add/remove** |

### 4.2 Manual Override

Properties can override auto-detection via `form_type` field:

```sql
-- Example: Force email field to use EmailType
UPDATE generator_property
SET form_type = 'EmailType',
    form_widget_attr = '{"placeholder": "user@example.com"}'::json
WHERE property_name = 'email';
```

### 4.3 Excluded Fields

These fields are **automatically excluded** from forms:

- `id` - Auto-generated UUID
- `createdAt` - Timestamp (auto-set)
- `updatedAt` - Timestamp (auto-updated)
- `organization` - Set via session context

---

## 5. Relationship Handling

### 5.1 ManyToOne - Searchable Select + Add Button

**Features:**
- ✅ Ajax-powered search using **existing apiSearch route**
- ✅ Sorted by entity's `__toString()` method
- ✅ Multi-tenant filtering (automatic)
- ✅ "Add new" button opens modal
- ✅ All text translated

**Generated Code:**

```php
$builder->add('company', EntityType::class, [
    'class' => Company::class,
    'choice_label' => '__toString',  // ALWAYS use __toString()
    'required' => !$property->isNullable(),
    'attr' => [
        'class' => 'form-input-modern entity-select',
        'data-controller' => 'relation-select',
        'data-relation-select-entity-value' => 'Company',
        'data-relation-select-route-value' => 'company_api_search',  // Existing route!
        'data-relation-select-add-route-value' => 'company_new_modal',
        'data-relation-select-multiple-value' => 'false',
        'placeholder' => $this->translator->trans(
            'form.select.placeholder',
            ['%entity%' => $this->translator->trans('entity.company.singular', [], 'messages')],
            'messages'
        ),
    ],
]);
```

**How It Works:**

1. User types in select field
2. `relation-select` controller calls `company_api_search` route
3. Existing `BaseRepository::apiSearch()` handles search (UNACCENT + lowercase)
4. Results filtered by organization automatically
5. Results sorted by `__toString()` ascending
6. "Add new company" button opens modal
7. On successful creation, new entity added to select and auto-selected

### 5.2 ManyToMany - Multi-Select + Add Button

**Same as ManyToOne** but with `multiple: true`:

```php
$builder->add('tags', EntityType::class, [
    'class' => Tag::class,
    'choice_label' => '__toString',
    'multiple' => true,  // KEY DIFFERENCE
    'required' => false,
    'attr' => [
        'class' => 'form-input-modern entity-select',
        'data-controller' => 'relation-select',
        'data-relation-select-entity-value' => 'Tag',
        'data-relation-select-route-value' => 'tag_api_search',
        'data-relation-select-add-route-value' => 'tag_new_modal',
        'data-relation-select-multiple-value' => 'true',  // Multiple selection
        'placeholder' => $this->translator->trans(
            'form.select.multiple.placeholder',
            ['%entity%' => $this->translator->trans('entity.tag.plural', [], 'messages')],
            'messages'
        ),
    ],
]);
```

### 5.3 OneToOne - Unique Reference + Add Button

**Special Constraint:** Must filter out already-related objects to maintain OneToOne integrity.

**Generated Code:**

```php
$builder->add('profile', EntityType::class, [
    'class' => Profile::class,
    'choice_label' => '__toString',
    'required' => !$property->isNullable(),
    'attr' => [
        'class' => 'form-input-modern entity-select',
        'data-controller' => 'relation-select',
        'data-relation-select-entity-value' => 'Profile',
        'data-relation-select-route-value' => 'profile_api_search_unrelated',  // Special route
        'data-relation-select-add-route-value' => 'profile_new_modal',
        'data-relation-select-multiple-value' => 'false',
        'data-relation-select-one-to-one-value' => 'true',
        'data-relation-select-current-id-value' => $options['data']?->getProfile()?->getId()?->toString() ?? '',
        'placeholder' => $this->translator->trans(
            'form.select.placeholder',
            ['%entity%' => $this->translator->trans('entity.profile.singular', [], 'messages')],
            'messages'
        ),
    ],
]);
```

**New Route Required:** `{entity}_api_search_unrelated`

This route must be **generated by FormGenerator** for each OneToOne relationship:

```php
// Example: ProfileController
#[Route('/api/profiles/unrelated', name: 'profile_api_search_unrelated', methods: ['GET'])]
public function apiSearchUnrelated(Request $request): JsonResponse
{
    $criteria = SearchCriteria::fromRequest($request->query->all());

    // Create query builder
    $qb = $this->repository->createQueryBuilder('p');

    // Exclude profiles already related to a user
    $qb->leftJoin('App\Entity\User', 'u', 'WITH', 'u.profile = p')
       ->where('u.id IS NULL');  // Only unrelated profiles

    // Allow current profile if editing
    if ($currentId = $request->query->get('currentId')) {
        $qb->orWhere('p.id = :currentId')
           ->setParameter('currentId', $currentId);
    }

    // Apply search criteria
    $result = $this->repository->apiSearchWithQueryBuilder($criteria, $qb);

    return $this->json($result->toArray(fn($p) => $this->entityToArray($p)));
}
```

**Note:** FormGenerator must also update BaseRepository to add `apiSearchWithQueryBuilder()` method.

### 5.4 OneToMany - Collection with Live Component

**Technology:** Symfony UX Live Component (zero JavaScript solution)

**Features:**
- ✅ Add new items dynamically
- ✅ Remove items
- ✅ Minimum 1 item (validation)
- ✅ Maximum items configurable
- ✅ Zero JavaScript required

**Generated Code:**

```php
$builder->add('dealStages', CollectionType::class, [
    'entry_type' => DealStageType::class,
    'entry_options' => [
        'label' => false,
    ],
    'allow_add' => $property->isCollectionAllowAdd() ?? true,
    'allow_delete' => $property->isCollectionAllowDelete() ?? true,
    'by_reference' => false,
    'prototype' => true,
    'attr' => [
        'data-controller' => 'live-collection',
        'data-live-collection-allow-add-value' => $property->isCollectionAllowAdd() ?? true,
        'data-live-collection-allow-delete-value' => $property->isCollectionAllowDelete() ?? true,
        'data-live-collection-max-items-value' => $property->getDtoNestedMaxItems() ?? 99,
    ],
    'label' => $this->translator->trans(
        'entity.deal_stage.plural',
        [],
        'messages'
    ),
    'constraints' => [
        new Count(['min' => 1, 'minMessage' => 'At least one item is required']),
        new Count(['max' => $property->getDtoNestedMaxItems() ?? 99]),
    ],
]);
```

### 5.5 Expanded Mode (Radio/Checkboxes)

For ManyToOne/ManyToMany with **small, static options**, use expanded mode:

```sql
-- Example: Status field with 3 options
UPDATE generator_property
SET form_expanded = TRUE
WHERE property_name = 'status'
  AND relationship_type = 'ManyToOne';
```

**Generated:** Radio buttons instead of select dropdown (no autocomplete).

---

## 6. Translation Strategy

### 6.1 Zero Hardcoded Text

**Every text string MUST be translated:**

```php
// ❌ WRONG
'placeholder' => 'Select a company'

// ✅ CORRECT
'placeholder' => $this->translator->trans(
    'form.select.placeholder',
    ['%entity%' => $this->translator->trans('entity.company.singular', [], 'messages')],
    'messages'
)
```

### 6.2 Translation Keys Structure

```yaml
# translations/messages.en.yaml

# Generic form labels
form:
    field:
        placeholder: "Enter %field%"
    select:
        placeholder: "Select %entity%"
        multiple:
            placeholder: "Select one or more %entity%"
        add_new: "Add new %entity%"
    textarea:
        placeholder: "Enter %field%"

# Entity-specific labels (auto-generated by Genmax)
entity:
    company:
        singular: "company"
        plural: "companies"
    contact:
        singular: "contact"
        plural: "contacts"
    deal_stage:
        singular: "deal stage"
        plural: "deal stages"
    # ... generated for all entities
```

### 6.3 Auto-Generation of Translation Keys

FormGenerator automatically generates/updates translation keys:

```php
// In FormGenerator::generate()
$this->generateTranslationKeys($entity);

// Implementation
protected function generateTranslationKeys(GeneratorEntity $entity): void
{
    $entityName = $this->genmaxExtension->toSnakeCase($entity->getEntityName());
    $entityLabel = $entity->getEntityLabel();
    $pluralLabel = $entity->getPluralLabel();

    $translations = [
        "entity.{$entityName}.singular" => strtolower($entityLabel),
        "entity.{$entityName}.plural" => strtolower($pluralLabel),
    ];

    // Write to translations/messages.en.yaml
    $this->translationWriter->addKeys('messages', 'en', $translations);
}
```

---

## 7. Frontend Components

### 7.1 Relation Select Controller (NEW)

**File:** `app/assets/controllers/relation_select_controller.js`

**Purpose:** Handle entity selection with ajax search + "Add" button

**Implementation:**

```javascript
import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static values = {
        entity: String,       // 'Company'
        route: String,        // 'company_api_search'
        addRoute: String,     // 'company_new_modal'
        multiple: Boolean,    // false for ManyToOne, true for ManyToMany
        oneToOne: Boolean,    // true for OneToOne
        currentId: String,    // For OneToOne: current related ID
    };

    connect() {
        this.initializeSelect();
        this.addCreateButton();
    }

    initializeSelect() {
        const config = {
            plugins: ['remove_button', 'clear_button'],
            valueField: 'id',
            labelField: 'display',
            searchField: ['display'],
            multiple: this.multipleValue,
            maxItems: this.multipleValue ? null : 1,
            load: (query, callback) => {
                this.searchEntities(query, callback);
            },
            render: {
                option: (data, escape) => {
                    return `<div class="py-2 px-3">${escape(data.display)}</div>`;
                },
                item: (data, escape) => {
                    return `<div>${escape(data.display)}</div>`;
                },
            },
        };

        this.tomSelect = new TomSelect(this.element, config);
    }

    async searchEntities(query, callback) {
        const params = new URLSearchParams({ q: query });

        // For OneToOne, include current ID to allow editing
        if (this.oneToOneValue && this.currentIdValue) {
            params.append('currentId', this.currentIdValue);
        }

        const url = `${this.routeValue}?${params.toString()}`;

        try {
            const response = await fetch(url);
            const data = await response.json();

            // Extract items array (key varies by entity)
            const itemsKey = Object.keys(data).find(k => Array.isArray(data[k]));
            const items = data[itemsKey] || [];

            // Transform to Tom Select format
            const transformed = items.map(item => ({
                id: item.id,
                display: item.display || item.name || item.title || String(item),
            }));

            callback(transformed);
        } catch (error) {
            console.error('Entity search failed:', error);
            callback();
        }
    }

    addCreateButton() {
        // Add "Add new" button next to select
        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex gap-2 align-items-start';

        // Move select into wrapper
        this.element.parentNode.insertBefore(wrapper, this.element);
        wrapper.appendChild(this.element.parentNode.querySelector('.ts-wrapper'));

        // Create add button
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn luminai-btn-secondary btn-sm';
        button.innerHTML = `<i class="bi bi-plus-circle me-1"></i>Add`;
        button.addEventListener('click', () => this.openCreateModal());

        wrapper.appendChild(button);
    }

    async openCreateModal() {
        // Use Turbo to load modal
        const response = await fetch(this.addRouteValue);
        const html = await response.text();

        // Insert modal into DOM
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = html;
        document.body.appendChild(modalContainer);

        // Listen for successful creation
        const handler = (event) => {
            if (event.detail.entityType === this.entityValue) {
                // Add new entity to select
                this.tomSelect.addOption({
                    id: event.detail.entity.id,
                    display: event.detail.entity.display,
                });
                this.tomSelect.setValue(event.detail.entity.id);

                // Remove modal
                modalContainer.remove();
                document.removeEventListener('modal:entity:created', handler);
            }
        };

        document.addEventListener('modal:entity:created', handler);
    }
}
```

### 7.2 Modal Event Dispatching

**Modify existing `crud_modal_controller.js`:**

```javascript
// In submit() method, after successful save
submit(event) {
    // ... existing submit logic ...

    // After successful creation, dispatch event
    if (response.success && response.entity) {
        const customEvent = new CustomEvent('modal:entity:created', {
            detail: {
                entityType: this.element.dataset.entityType,  // e.g., 'Company'
                entity: {
                    id: response.entity.id,
                    display: response.entity.display || response.entity.name,
                },
            },
            bubbles: true,
        });
        document.dispatchEvent(customEvent);
    }
}
```

### 7.3 Fullscreen Textarea (Already Exists!)

**File:** `app/assets/controllers/fullscreen_textarea_controller.js`

This controller is **already implemented** and automatically:
- ✅ Adds fullscreen button to ALL textareas
- ✅ Opens fullscreen modal on click
- ✅ Syncs content back to original textarea
- ✅ Handles ESC key

**No changes needed** - just output standard `TextareaType` and it works!

---

## 8. Form Theme

### 8.1 Custom Luminai Form Theme

**File:** `app/templates/genmax/twig/form_theme.html.twig`

```twig
{# Custom Luminai Form Theme - Light/Dark Responsive #}

{% use 'bootstrap_5_layout.html.twig' %}

{# Form row with modern Luminai styling #}
{% block form_row -%}
    <div class="form-group-modern mb-3{% if errors|length > 0 %} has-error{% endif %}">
        {{- form_label(form) -}}
        {{- form_widget(form) -}}
        {{- form_errors(form) -}}
        {% if help is defined and help is not empty %}
            <small class="form-text text-muted mt-1 d-block">
                <i class="bi bi-info-circle me-1"></i>
                {{ help|trans({}, translation_domain) }}
            </small>
        {% endif %}
    </div>
{%- endblock form_row %}

{# Form label #}
{% block form_label -%}
    {% if label is not same as(false) -%}
        <label class="form-label-modern{% if required %} required{% endif %}"
               {% if label_attr %}{% with { attr: label_attr } %}{{ block('attributes') }}{% endwith %}{% endif %}>
            {% if translation_domain is same as(false) -%}
                {{ label }}
            {%- else -%}
                {{ label|trans(label_translation_parameters, translation_domain) }}
            {%- endif %}
        </label>
    {%- endif %}
{%- endblock form_label %}

{# Text inputs #}
{% block form_widget_simple -%}
    {% set attr = attr|merge({
        'class': (attr.class|default('') ~ ' form-input-modern')|trim
    }) %}
    {{- parent() -}}
{%- endblock form_widget_simple %}

{# Textarea #}
{% block textarea_widget -%}
    {% set attr = attr|merge({
        'class': (attr.class|default('') ~ ' form-input-modern')|trim,
        'rows': attr.rows|default(3)
    }) %}
    {{- parent() -}}
{%- endblock textarea_widget %}

{# Select #}
{% block choice_widget_collapsed -%}
    {% set attr = attr|merge({
        'class': (attr.class|default('') ~ ' form-input-modern')|trim
    }) %}
    {{- parent() -}}
{%- endblock choice_widget_collapsed %}

{# Checkbox #}
{% block checkbox_widget -%}
    <div class="form-check">
        <input type="checkbox" {{ block('widget_attributes') }}
               class="form-check-input{% if errors|length > 0 %} is-invalid{% endif %}"
               {% if value is defined %}value="{{ value }}"{% endif %}
               {% if checked %}checked="checked"{% endif %} />
        <label class="form-check-label" for="{{ id }}">
            {{ label|trans({}, translation_domain) }}
        </label>
    </div>
{%- endblock checkbox_widget %}

{# Error display #}
{% block form_errors -%}
    {% if errors|length > 0 -%}
        <ul class="form-errors list-unstyled mb-0 mt-1">
            {%- for error in errors -%}
                <li class="text-danger small">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    {{ error.message }}
                </li>
            {%- endfor -%}
        </ul>
    {%- endif %}
{%- endblock form_errors %}
```

### 8.2 Form Theme CSS

**File:** `app/assets/styles/form.css` (extend existing)

```css
/* Form group modern */
.form-group-modern {
    margin-bottom: 1.5rem;
    position: relative;
}

/* Form label modern */
.form-label-modern {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--luminai-text-primary);
    margin-bottom: 0.5rem;
    display: block;
}

.form-label-modern.required::after {
    content: ' *';
    color: var(--luminai-danger);
}

/* Form input modern */
.form-input-modern {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    line-height: 1.5;
    color: var(--luminai-text-primary);
    background-color: var(--luminai-input-bg);
    border: 1px solid var(--luminai-border-color);
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-input-modern:focus {
    border-color: var(--luminai-primary);
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(var(--luminai-primary-rgb), 0.25);
}

.form-input-modern.input-error {
    border-color: var(--luminai-danger);
}

/* Form errors */
.form-errors {
    margin-top: 0.25rem;
}

.form-errors li {
    font-size: 0.875rem;
}

/* Light/Dark theme variables */
:root {
    --luminai-text-primary: #2c3e50;
    --luminai-input-bg: #ffffff;
    --luminai-border-color: #dee2e6;
    --luminai-primary: #0d6efd;
    --luminai-primary-rgb: 13, 110, 253;
    --luminai-danger: #dc3545;
}

[data-theme="dark"] {
    --luminai-text-primary: #e9ecef;
    --luminai-input-bg: #1e2330;
    --luminai-border-color: #495057;
    --luminai-primary: #4dabf7;
    --luminai-primary-rgb: 77, 171, 247;
    --luminai-danger: #ff6b6b;
}
```

---

## 9. FormGenerator Service

### 9.1 Service Implementation

**File:** `app/src/Service/Genmax/FormGenerator.php`

```php
<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Form Generator for Genmax
 *
 * Generates Symfony forms using Base/Extension pattern.
 */
class FormGenerator
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
        protected readonly TranslatorInterface $translator,
        protected readonly LoggerInterface $logger
    ) {}

    /**
     * Generate form files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        $this->logger->info('[GENMAX] Generating form', [
            'entity' => $entity->getEntityName()
        ]);

        $generatedFiles = [];

        // Generate base form (always regenerated)
        $generatedFiles[] = $this->generateBaseForm($entity);

        // Generate extension form (once only)
        $extensionFile = $this->generateExtensionForm($entity);
        if ($extensionFile) {
            $generatedFiles[] = $extensionFile;
        }

        // Generate translation keys
        $this->generateTranslationKeys($entity);

        return array_filter($generatedFiles);
    }

    /**
     * Generate base form class: src/Form/Generated/{Entity}TypeGenerated.php
     */
    protected function generateBaseForm(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sTypeGenerated.php',
            $this->projectDir,
            $this->paths['form_generated_dir'],
            $entity->getEntityName()
        );

        try {
            $context = $this->buildTemplateContext($entity);
            $content = $this->twig->render($this->templates['form_generated'], $context);
            $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated form base class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);

            return $filePath;
        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate form base class', [
                'entity' => $entity->getEntityName(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate form base class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate extension form class: src/Form/{Entity}Type.php
     */
    protected function generateExtensionForm(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%sType.php',
            $this->projectDir,
            $this->paths['form_dir'],
            $entity->getEntityName()
        );

        // Skip if exists (user may have customized)
        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping extension form (already exists)', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);
            return null;
        }

        try {
            $context = $this->buildTemplateContext($entity);
            $content = $this->twig->render($this->templates['form_extension'], $context);
            $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated form extension class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);

            return $filePath;
        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate form extension class', [
                'entity' => $entity->getEntityName(),
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate form extension class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Build template context with all variables needed for form generation
     */
    protected function buildTemplateContext(GeneratorEntity $entity): array
    {
        return [
            'entity' => $entity,
            'entityName' => $entity->getEntityName(),
            'formFields' => $this->getFormFields($entity),
            'formTypeImports' => $this->getFormTypeImports($entity),
            'namespace' => $this->paths['form_namespace'],
            'generatedNamespace' => $this->paths['form_generated_namespace'],
        ];
    }

    /**
     * Get properties to include in form
     */
    protected function getFormFields(GeneratorEntity $entity): array
    {
        $fields = [];

        foreach ($entity->getProperties() as $property) {
            // Skip if not shown in form
            if (!$property->isShowInForm()) {
                continue;
            }

            // Skip auto-generated fields
            if (in_array($property->getPropertyName(), ['id', 'createdAt', 'updatedAt', 'organization'])) {
                continue;
            }

            $fields[] = [
                'name' => $property->getPropertyName(),
                'label' => $property->getPropertyLabel(),
                'type' => $this->determineFormType($property),
                'options' => $this->buildFormOptions($property, $entity),
            ];
        }

        return $fields;
    }

    /**
     * Determine form type for a property
     */
    protected function determineFormType(GeneratorProperty $property): string
    {
        // Manual override
        if ($property->getFormType()) {
            return $property->getFormType();
        }

        // Relationship handling
        if ($relationshipType = $property->getRelationshipType()) {
            return match($relationshipType) {
                'ManyToOne', 'ManyToMany', 'OneToOne' => 'EntityType',
                'OneToMany' => 'CollectionType',
            };
        }

        // Enum handling
        if ($property->isEnum()) {
            return 'EnumType';
        }

        // Type mapping
        return match($property->getPropertyType()) {
            'string' => $property->getLength() > 255 ? 'TextareaType' : 'TextType',
            'text' => 'TextareaType',
            'integer', 'smallint', 'bigint' => 'IntegerType',
            'float', 'decimal' => 'NumberType',
            'boolean' => 'CheckboxType',
            'datetime', 'datetime_immutable' => 'DateTimeType',
            'date' => 'DateType',
            'time' => 'TimeType',
            'json' => 'TextareaType',
            default => 'TextType',
        };
    }

    /**
     * Build form options for a property
     */
    protected function buildFormOptions(GeneratorProperty $property, GeneratorEntity $entity): array
    {
        $options = [
            'label' => $property->getPropertyLabel(),
            'required' => $property->isFormRequired() ?? !$property->isNullable(),
        ];

        // Help text
        if ($help = $property->getFormHelp()) {
            $options['help'] = $help;
        }

        // Read-only
        if ($property->isFormReadOnly()) {
            $options['disabled'] = true;
        }

        // Relationship-specific options
        if ($relationshipType = $property->getRelationshipType()) {
            $options = array_merge($options, $this->buildRelationshipOptions($property, $entity));
        }

        // Enum-specific options
        if ($property->isEnum()) {
            $options['class'] = $property->getEnumClass();
            $options['choice_label'] = 'getLabel';
        }

        // HTML attributes
        if ($widgetAttr = $property->getFormWidgetAttr()) {
            $options['attr'] = $widgetAttr;
        } else {
            $options['attr'] = [];
        }

        // Always add base CSS class
        $options['attr']['class'] = ($options['attr']['class'] ?? '') . ' form-input-modern';

        // Translated placeholder for text fields
        $formType = $this->determineFormType($property);
        if (in_array($formType, ['TextType', 'TextareaType'])) {
            $options['attr']['placeholder'] = $this->translator->trans(
                'form.field.placeholder',
                ['%field%' => strtolower($property->getPropertyLabel())],
                'messages'
            );
        }

        // Label attributes
        if ($labelAttr = $property->getFormLabelAttr()) {
            $options['label_attr'] = $labelAttr;
        }

        // Row attributes
        if ($rowAttr = $property->getFormRowAttr()) {
            $options['row_attr'] = $rowAttr;
        }

        // Merge manual options (overrides everything)
        if ($manualOptions = $property->getFormOptions()) {
            $options = array_merge($options, $manualOptions);
        }

        return $options;
    }

    /**
     * Build relationship-specific options
     */
    protected function buildRelationshipOptions(GeneratorProperty $property, GeneratorEntity $entity): array
    {
        $relationshipType = $property->getRelationshipType();
        $targetEntity = $property->getTargetEntity();
        $targetEntityName = basename(str_replace('\\', '/', $targetEntity));
        $entityRoute = $this->genmaxExtension->toSnakeCase($targetEntityName);

        $options = [
            'class' => $targetEntity,
            'choice_label' => '__toString',  // ALWAYS use __toString()
        ];

        // ManyToOne / ManyToMany / OneToOne
        if (in_array($relationshipType, ['ManyToOne', 'ManyToMany', 'OneToOne'])) {

            // Multiple (for ManyToMany only)
            if ($relationshipType === 'ManyToMany') {
                $options['multiple'] = true;
            }

            // Expanded (radio/checkboxes)
            if ($property->isFormExpanded()) {
                $options['expanded'] = true;
            } else {
                // Use relation-select controller for dropdown
                $searchRoute = $relationshipType === 'OneToOne'
                    ? "{$entityRoute}_api_search_unrelated"
                    : "{$entityRoute}_api_search";

                $options['attr'] = array_merge($options['attr'] ?? [], [
                    'data-controller' => 'relation-select',
                    'data-relation-select-entity-value' => $targetEntityName,
                    'data-relation-select-route-value' => $searchRoute,
                    'data-relation-select-add-route-value' => "{$entityRoute}_new_modal",
                    'data-relation-select-multiple-value' => $relationshipType === 'ManyToMany' ? 'true' : 'false',
                    'data-relation-select-one-to-one-value' => $relationshipType === 'OneToOne' ? 'true' : 'false',
                ]);

                // Translated placeholder
                $entityKey = $relationshipType === 'ManyToMany' ? 'plural' : 'singular';
                $placeholderKey = $relationshipType === 'ManyToMany'
                    ? 'form.select.multiple.placeholder'
                    : 'form.select.placeholder';

                $options['attr']['placeholder'] = $this->translator->trans(
                    $placeholderKey,
                    ['%entity%' => $this->translator->trans("entity.{$entityRoute}.{$entityKey}", [], 'messages')],
                    'messages'
                );
            }
        }

        // OneToMany - Collection
        if ($relationshipType === 'OneToMany') {
            $options = [
                'entry_type' => "App\\Form\\{$targetEntityName}Type",
                'entry_options' => ['label' => false],
                'allow_add' => $property->isCollectionAllowAdd() ?? true,
                'allow_delete' => $property->isCollectionAllowDelete() ?? true,
                'by_reference' => false,
                'prototype' => true,
                'attr' => [
                    'data-controller' => 'live-collection',
                    'data-live-collection-allow-add-value' => $property->isCollectionAllowAdd() ?? true,
                    'data-live-collection-allow-delete-value' => $property->isCollectionAllowDelete() ?? true,
                    'data-live-collection-max-items-value' => $property->getDtoNestedMaxItems() ?? 99,
                ],
                'label' => $this->translator->trans(
                    "entity.{$entityRoute}.plural",
                    [],
                    'messages'
                ),
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Count(['min' => 1]),
                ],
            ];

            // Add max constraint if configured
            if ($max = $property->getDtoNestedMaxItems()) {
                $options['constraints'][] = new \Symfony\Component\Validator\Constraints\Count(['max' => $max]);
            }
        }

        return $options;
    }

    /**
     * Get form type imports needed for this entity
     */
    protected function getFormTypeImports(GeneratorEntity $entity): array
    {
        $imports = [
            'Symfony\Component\Form\AbstractType',
            'Symfony\Component\Form\FormBuilderInterface',
            'Symfony\Component\OptionsResolver\OptionsResolver',
        ];

        // Collect all form types used
        $types = [];
        foreach ($this->getFormFields($entity) as $field) {
            $types[$field['type']] = true;
        }

        // Add form type imports
        foreach (array_keys($types) as $type) {
            if ($type === 'EntityType') {
                $imports[] = 'Symfony\Bridge\Doctrine\Form\Type\EntityType';
            } elseif ($type === 'CollectionType') {
                $imports[] = 'Symfony\Component\Form\Extension\Core\Type\CollectionType';
                $imports[] = 'Symfony\Component\Validator\Constraints\Count';
            } elseif ($type === 'EnumType') {
                $imports[] = 'Symfony\Component\Form\Extension\Core\Type\EnumType';
            } else {
                $imports[] = "Symfony\\Component\\Form\\Extension\\Core\\Type\\{$type}";
            }
        }

        return array_unique($imports);
    }

    /**
     * Generate translation keys for entity
     */
    protected function generateTranslationKeys(GeneratorEntity $entity): void
    {
        $entityName = $this->genmaxExtension->toSnakeCase($entity->getEntityName());
        $entityLabel = $entity->getEntityLabel();
        $pluralLabel = $entity->getPluralLabel();

        // TODO: Implement translation key generation to messages.en.yaml
        // This will be handled by a TranslationWriter service

        $this->logger->info('[GENMAX] Translation keys to generate', [
            'entity' => $entityName,
            'keys' => [
                "entity.{$entityName}.singular" => strtolower($entityLabel),
                "entity.{$entityName}.plural" => strtolower($pluralLabel),
            ]
        ]);
    }
}
```

---

## 10. Templates

### 10.1 Base Form Template

**File:** `app/templates/genmax/php/form_generated.php.twig`

```twig
<?php

declare(strict_types=1);

namespace {{ generatedNamespace }};

use {{ namespace }}\{{ entityName }};
{% for import in formTypeImports %}
use {{ import }};
{% endfor %}
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Generated Base Form for {{ entityName }}
 *
 * ⚠️ WARNING: This file is ALWAYS regenerated by Genmax
 * DO NOT EDIT THIS FILE - Edit {{ entityName }}Type instead
 *
 * @generated by Genmax
 * @codeCoverageIgnore
 */
abstract class {{ entityName }}TypeGenerated extends AbstractType
{
    public function __construct(
        protected readonly TranslatorInterface $translator
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
{% for field in formFields %}
        $builder->add('{{ field.name }}', {{ field.type }}::class, [
{% for key, value in field.options %}
{% if key == 'attr' or key == 'label_attr' or key == 'row_attr' or key == 'entry_options' %}
            '{{ key }}' => {{ value|json_encode|raw }},
{% elseif key == 'constraints' %}
            '{{ key }}' => [
                {{ value|join(',\n                ')|raw }}
            ],
{% elseif value is iterable %}
            '{{ key }}' => {{ value|json_encode|raw }},
{% elseif value is same as(true) %}
            '{{ key }}' => true,
{% elseif value is same as(false) %}
            '{{ key }}' => false,
{% elseif value is same as(null) %}
            '{{ key }}' => null,
{% elseif value is number %}
            '{{ key }}' => {{ value }},
{% elseif key == 'class' or key == 'entry_type' %}
            '{{ key }}' => {{ value }}::class,
{% else %}
            '{{ key }}' => '{{ value|raw }}',
{% endif %}
{% endfor %}
        ]);

{% endfor %}
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => {{ entityName }}::class,
        ]);
    }
}
```

### 10.2 Extension Form Template

**File:** `app/templates/genmax/php/form_extension.php.twig`

```twig
<?php

declare(strict_types=1);

namespace {{ namespace }};

use {{ generatedNamespace }}\{{ entityName }}TypeGenerated;

/**
 * {{ entityName }} Form Type
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom form fields, transformers, and event listeners here.
 *
 * @generated once by Genmax
 */
class {{ entityName }}Type extends {{ entityName }}TypeGenerated
{
    // Override buildForm() to add custom fields or modify generated ones

    // Example:
    // public function buildForm(FormBuilderInterface $builder, array $options): void
    // {
    //     parent::buildForm($builder, $options);
    //
    //     // Add custom field
    //     $builder->add('customField', TextType::class, [
    //         'label' => 'Custom Field',
    //         'required' => false,
    //     ]);
    //
    //     // Or modify existing field
    //     $builder->get('existingField')->setRequired(false);
    // }
}
```

---

## 11. Implementation Phases

### Phase 1: Foundation (Week 1)
- [x] Review and finalize plan
- [ ] Create database migration for 6 new fields
- [ ] Update GeneratorProperty entity with getters/setters
- [ ] Create FormGenerator service skeleton
- [ ] Create form Twig templates (base + extension)
- [ ] Create custom Luminai form theme template
- [ ] Add form theme CSS to assets

### Phase 2: Basic Form Generation (Week 1-2)
- [ ] Implement `determineFormType()` method
- [ ] Implement `buildFormOptions()` method
- [ ] Implement form type imports detection
- [ ] Generate simple forms (no relationships)
- [ ] Test with Contact entity (basic fields only)
- [ ] Verify fullscreen textarea integration (should work automatically)

### Phase 3: Relationship Handling (Week 2-3)
- [ ] Create `relation_select_controller.js`
- [ ] Install and configure Tom Select
- [ ] Implement `buildRelationshipOptions()` for ManyToOne
- [ ] Implement `buildRelationshipOptions()` for ManyToMany
- [ ] Implement `buildRelationshipOptions()` for OneToOne
- [ ] Generate `{entity}_api_search_unrelated` routes for OneToOne
- [ ] Add modal event dispatching to `crud_modal_controller.js`
- [ ] Test all relationship types

### Phase 4: Collection & Advanced Features (Week 3-4)
- [ ] Install and configure Symfony UX Live Component
- [ ] Implement OneToMany collection generation
- [ ] Test collection add/remove functionality
- [ ] Implement enum field handling
- [ ] Implement JSON field handling
- [ ] Test complex nested forms

### Phase 5: Translation & i18n (Week 4)
- [ ] Implement `generateTranslationKeys()` method
- [ ] Create TranslationWriter service
- [ ] Generate translation keys for all entities
- [ ] Verify all placeholders use translator
- [ ] Test with multiple languages

### Phase 6: Integration & Testing (Week 5)
- [ ] Integrate FormGenerator into GenmaxOrchestrator
- [ ] Enable FORM_ACTIVE flag
- [ ] Update backup file collection
- [ ] End-to-end testing with complex entities
- [ ] Performance testing
- [ ] Update GENMAX.md documentation
- [ ] Update QUICK_START.md
- [ ] Create FORM_GENERATOR.md (this document)

---

## 12. Configuration

### 12.1 Required Dependencies

```bash
# Install Symfony UX Live Component
composer require symfony/ux-live-component

# Install Tom Select (for relation fields)
npm install tom-select --save

# Rebuild assets
npm install --force
npm run build
```

### 12.2 Service Configuration

**File:** `app/config/services.yaml`

```yaml
parameters:
    genmax.paths:
        # ... existing paths
        form_dir: 'src/Form'
        form_generated_dir: 'src/Form/Generated'
        form_namespace: 'App\Form'
        form_generated_namespace: 'App\Form\Generated'

    genmax.templates:
        # ... existing templates
        form_generated: 'genmax/php/form_generated.php.twig'
        form_extension: 'genmax/php/form_extension.php.twig'
        form_theme: 'genmax/twig/form_theme.html.twig'

services:
    # ... existing services

    App\Service\Genmax\FormGenerator:
        autowire: true
        autoconfigure: true
```

### 12.3 Form Theme Configuration

**File:** `app/config/packages/twig.yaml`

```yaml
twig:
    # ... existing config
    form_themes:
        - 'genmax/twig/form_theme.html.twig'
```

### 12.4 Feature Flag

**File:** `app/src/Service/Genmax/GenmaxOrchestrator.php`

Update line 39:

```php
private const FORM_ACTIVE = true;  // ✅ Phase 3 - ACTIVE
```

Add to generate() method around line 246:

```php
// Form (ACTIVE)
if (self::FORM_ACTIVE) {
    try {
        $files = $this->formGenerator->generate($entity);
        $generatedFiles = array_merge($generatedFiles, $files);
        $currentStep++;
    } catch (\Throwable $e) {
        $this->logger->error("[GENMAX] Form generation failed", [
            'entity' => $entity->getEntityName(),
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
```

---

## 13. Usage Examples

### 13.1 Simple Entity with Basic Fields

```sql
-- Mark fields for form display
UPDATE generator_property
SET show_in_form = TRUE,
    form_help = 'Enter the contact full name'
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Contact')
  AND property_name = 'fullName';

-- Mark description as textarea (auto-detected but can override)
UPDATE generator_property
SET show_in_form = TRUE,
    form_type = 'TextareaType',
    form_widget_attr = '{"rows": 5}'::json
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Contact')
  AND property_name = 'notes';
```

**Generate:**

```bash
php bin/console genmax:generate Contact
```

**Result:**
- `src/Form/Generated/ContactTypeGenerated.php` (base)
- `src/Form/ContactType.php` (extension)
- Fullscreen textarea button appears automatically

### 13.2 ManyToOne Relationship

```sql
-- Company relationship - automatically gets search + Add button
UPDATE generator_property
SET show_in_form = TRUE
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Contact')
  AND property_name = 'company'
  AND relationship_type = 'ManyToOne';
```

**Generated form:**
- Searchable select using existing `company_api_search` route
- "Add new company" button opens modal
- On successful creation, new company auto-selected
- All text translated

### 13.3 ManyToMany Relationship

```sql
-- Tags relationship
UPDATE generator_property
SET show_in_form = TRUE
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Contact')
  AND property_name = 'tags'
  AND relationship_type = 'ManyToMany';
```

**Generated form:**
- Multi-select searchable dropdown
- "Add new tag" button
- Can select multiple tags

### 13.4 OneToOne Relationship

```sql
-- Profile relationship (OneToOne)
UPDATE generator_property
SET show_in_form = TRUE
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'User')
  AND property_name = 'profile'
  AND relationship_type = 'OneToOne';
```

**Generated:**
- Searchable select showing ONLY unrelated profiles
- Uses special `profile_api_search_unrelated` route
- "Add new profile" button
- Maintains OneToOne integrity

### 13.5 OneToMany Collection

```sql
-- Deal stages collection
UPDATE generator_property
SET show_in_form = TRUE,
    collection_allow_add = TRUE,
    collection_allow_delete = TRUE,
    dto_nested_max_items = 10
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Pipeline')
  AND property_name = 'stages'
  AND relationship_type = 'OneToMany';
```

**Generated:**
- Live Component collection (zero JavaScript)
- Add/Remove buttons
- Min 1 item (validation)
- Max 10 items (validation)
- Each item uses DealStageType form

### 13.6 Expanded Radio Buttons

```sql
-- Status field with radio buttons instead of select
UPDATE generator_property
SET show_in_form = TRUE,
    form_expanded = TRUE
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
  AND property_name = 'status'
  AND relationship_type = 'ManyToOne';
```

**Generated:**
- Radio buttons instead of select dropdown
- No autocomplete (static list)

---

## 14. Testing

### 14.1 Unit Tests

**File:** `app/tests/Service/Genmax/FormGeneratorTest.php`

Test scenarios:
- ✅ Basic field type detection
- ✅ Relationship options generation
- ✅ Translation key generation
- ✅ Form file creation
- ✅ Skip extension if exists
- ✅ Handle validation constraints

### 14.2 Functional Tests

**File:** `app/tests/Functional/FormGenerationTest.php`

Test scenarios:
- ✅ Generate Contact form (basic fields)
- ✅ Generate Company form (with relationships)
- ✅ Generate Pipeline form (with OneToMany collection)
- ✅ Test ManyToOne with Add button
- ✅ Test OneToOne with unrelated filter
- ✅ Test fullscreen textarea integration

### 14.3 Integration Tests

**File:** `app/tests/Integration/GenmaxFormIntegrationTest.php`

Test scenarios:
- ✅ Full generation flow (Entity → Form → Controller)
- ✅ Form submission and validation
- ✅ Relationship persistence
- ✅ Collection add/remove
- ✅ Translation key availability

### 14.4 Manual Testing Checklist

- [ ] Generate simple entity form (Contact)
- [ ] Fill and submit form
- [ ] Test field validation
- [ ] Test fullscreen textarea button
- [ ] Test ManyToOne search
- [ ] Test ManyToOne "Add" button
- [ ] Test ManyToMany multi-select
- [ ] Test OneToOne unrelated filtering
- [ ] Test OneToMany collection add
- [ ] Test OneToMany collection remove
- [ ] Test form in light mode
- [ ] Test form in dark mode
- [ ] Test translations (English)
- [ ] Test with complex nested entity

---

## Appendix A: Key Design Decisions

### A.1 Why Reuse Existing apiSearch?

**Decision:** Use existing `{entity}_api_search` routes instead of custom query builders.

**Rationale:**
- ✅ Already implements UNACCENT + lowercase search
- ✅ Multi-tenant filtering built-in
- ✅ Consistent search behavior across app
- ✅ No code duplication
- ✅ Well-tested infrastructure

### A.2 Why Symfony UX Live Component for Collections?

**Decision:** Use Live Component instead of custom JavaScript.

**Rationale:**
- ✅ Zero JavaScript required (modern 2025 approach)
- ✅ Reactive updates
- ✅ Server-side validation
- ✅ Easier to maintain
- ✅ Better UX with less code

### A.3 Why Always Use __toString()?

**Decision:** Always use `__toString()` for choice labels, no configuration.

**Rationale:**
- ✅ Consistency across all entities
- ✅ Forces good entity design
- ✅ Simpler generation logic
- ✅ No configuration needed
- ✅ Easy to override in extension form if needed

### A.4 Why Minimal New Fields?

**Decision:** Only 6 new fields instead of 15+.

**Rationale:**
- ✅ Most behavior auto-detected
- ✅ Simpler configuration
- ✅ Reuse existing fields (dto_nested_max_items)
- ✅ Less database overhead
- ✅ Easier to maintain

---

## Appendix B: Troubleshooting

### Problem: Form not rendering

**Solution:**
1. Check `show_in_form = true` for properties
2. Verify form theme is configured in twig.yaml
3. Clear cache: `php bin/console cache:clear`

### Problem: Relation select not working

**Solution:**
1. Check `relation_select_controller.js` is loaded
2. Verify Tom Select is installed: `npm list tom-select`
3. Check browser console for errors
4. Verify apiSearch route exists: `php bin/console debug:router | grep api_search`

### Problem: Add button not appearing

**Solution:**
1. Check `{entity}_new_modal` route exists
2. Verify `crud_modal_controller.js` dispatches event
3. Check browser console for JavaScript errors

### Problem: OneToOne showing related entities

**Solution:**
1. Verify `{entity}_api_search_unrelated` route is generated
2. Check route logic excludes related entities
3. Test route directly: `curl https://localhost/api/profiles/unrelated?q=test`

### Problem: Collection not adding items

**Solution:**
1. Check Symfony UX Live Component is installed
2. Verify `collection_allow_add = true`
3. Check browser console for errors
4. Test with `by_reference = false`

---

## Appendix C: Future Enhancements

### Planned Features

1. **Conditional Fields** - Show/hide fields based on other field values
2. **Field Dependencies** - Auto-populate fields based on selections
3. **Custom Validators** - Generate custom validation constraints
4. **File Uploads** - Handle VichUploaderBundle integration
5. **Repeater Groups** - Group collections with headers
6. **Form Wizards** - Multi-step forms
7. **Inline Editing** - Edit collections without full form reload
8. **Custom Widgets** - Plugin system for custom form widgets

### Performance Optimizations

1. Cache form type instances
2. Lazy-load Tom Select for better initial page load
3. Debounce search queries
4. Virtual scrolling for large select lists

---

**Document Version:** 2.0
**Last Updated:** October 2025
**Status:** Ready for Implementation
**Next Review:** After Phase 2 completion
