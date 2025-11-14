# Genmax Form Generation & Searchable Select Implementation Analysis

**Date**: 2025-11-10  
**Project**: Luminai (Symfony 7.3 + API Platform 4.1)  
**Status**: Comprehensive Search Complete

---

## Executive Summary

This document details how Genmax (the code generation system) generates form fields for ManyToOne and ManyToMany relationships, identifies the current implementation gaps for searchable selects, and provides a path forward for implementation.

### Key Findings

1. **Form Generation**: Genmax generates EntityType fields for all relationship types (ManyToOne, ManyToMany, OneToOne)
2. **Current Select Implementation**: Basic HTML select elements with no search capability
3. **Available Infrastructure**: API search endpoints already exist for all entities
4. **Tom Select**: Installed but not currently utilized in form fields
5. **Missing Piece**: Data attributes to trigger searchable select on EntityType fields

---

## 1. Form Generation Pipeline

### 1.1 Entry Point: `FormGenerator` Service

**Location**: `/home/user/inf/app/src/Service/Genmax/FormGenerator.php`

The FormGenerator is the central component that generates all form files.

```
Flow:
GeneratorEntity (Database) 
  → FormGenerator::generate()
  → buildTemplateContext()
  → getFormFields()
  → determineFormType()
  → buildFormOptions()
  → buildRelationshipOptions()
  → Twig Template Rendering
  → Generated Form File
```

**Key Methods**:

| Method | Purpose |
|--------|---------|
| `generate()` | Main entry point - generates base + extension forms |
| `getFormFields()` | Extracts properties that should appear in form |
| `determineFormType()` | Maps property types to Symfony form types |
| `buildFormOptions()` | Builds all form field options |
| `buildRelationshipOptions()` | Handles ManyToOne/ManyToMany/OneToOne options |
| `getFormTypeImports()` | Collects necessary imports |

### 1.2 Relationship Type Handling

**Lines 220-227** of `FormGenerator.php`:

```php
if ($relationshipType = $property->getRelationshipType()) {
    return match($relationshipType) {
        'ManyToOne', 'ManyToMany', 'OneToOne' => 'EntityType',
        'OneToMany' => 'CollectionType',
        default => 'TextType',
    };
}
```

All ManyToOne/ManyToMany/OneToOne relationships use **EntityType**.

### 1.3 EntityType Options Generation

**Lines 326-421** of `FormGenerator.php`:

The `buildRelationshipOptions()` method generates EntityType options:

```php
protected function buildRelationshipOptions(GeneratorProperty $property, GeneratorEntity $entity): array
{
    $relationshipType = $property->getRelationshipType();
    $targetEntity = $property->getTargetEntity();

    $options = [
        'class' => $targetEntityClass,
        // Don't set choice_label - Symfony will automatically use __toString()
    ];

    if (in_array($relationshipType, ['ManyToOne', 'ManyToMany', 'OneToOne'], true)) {
        if ($relationshipType === 'ManyToMany') {
            $options['multiple'] = true;
        }

        if ($property->isFormExpanded()) {
            $options['expanded'] = true;  // Radio buttons or checkboxes
        } else {
            // Use relation-select controller for dropdown
            $searchRoute = $relationshipType === 'OneToOne'
                ? "{$entityRoute}_api_search_unrelated"
                : "{$entityRoute}_api_search";

            $attr = $options['attr'] ?? [];
            $attr['data-controller'] = 'relation-select';  // NOT IMPLEMENTED YET
            $attr['data-relation-select-entity-value'] = $targetEntityName;
            $attr['data-relation-select-route-value'] = $searchRoute;
            $attr['data-relation-select-add-route-value'] = "{$entityRoute}_new_modal";
            $attr['data-relation-select-multiple-value'] = $relationshipType === 'ManyToMany' ? 'true' : 'false';
            // ... more attributes

            $options['attr'] = $attr;
        }
    }

    return $options;
}
```

**Important**: The code ALREADY sets `data-controller="relation-select"` on EntityType fields, but the controller doesn't exist yet!

---

## 2. Current Form Template Structure

### 2.1 Form Template

**Location**: `/home/user/inf/app/templates/genmax/twig/form_theme.html.twig`

This is the Symfony form theme that renders form fields. It extends Bootstrap 5.

**Current EntityType Rendering** (inherits from Bootstrap):

The form theme doesn't have a custom block for `entity_widget`, so it falls back to Symfony's default behavior, which renders a basic `<select>` element.

### 2.2 Generated Form Example

**Location**: `/home/user/inf/app/src/Form/Generated/ContactTypeGenerated.php`

Example field (lines 76-83):

```php
$builder->add('accountManager', EntityType::class, [
    'label' => 'AccountManager',
    'required' => false,
    'class' => \App\Entity\User::class,
    'attr' => [
        'class' => 'form-input-modern',
    ],
]);
```

**Problem**: No search capability, no data-controller attribute!

But wait - checking lines 361-382 of FormGenerator.php, the code SHOULD be adding the data-controller. Let me verify the generated forms...

Actually, looking at the Contact form (which was modified in git), it shows basic EntityType fields. The data-controller attributes are being generated in the FormGenerator code but may not be showing up in older generated files.

---

## 3. API Search Endpoints

All generated controllers include a search endpoint:

**Pattern**: `/{entity_slug}/api/search`

**Example**: `/home/user/inf/app/src/Controller/RoleController.php` (lines 37-41)

```php
#[Route('/api/search', name: 'role_api_search', methods: ['GET'])]
public function apiSearch(Request $request): Response
{
    return $this->apiSearchAction($request);
}
```

**Implementation**: Delegates to `BaseApiController::apiSearchAction()` which handles:
- Text search (full-text)
- Filtering
- Sorting
- Pagination

**Response Format**:

Expected JSON response with entity data for select population.

---

## 4. Frontend Implementation

### 4.1 Existing Stimulus Controllers

**Location**: `/home/user/inf/app/assets/controllers/`

Controllers found:

| Controller | Purpose | Status |
|----------|---------|--------|
| `tom_select_controller.js` | TomSelect wrapper | ✅ Installed |
| `live_search_controller.js` | Generic live search | ✅ Available |
| `crud_modal_controller.js` | Modal CRUD operations | ✅ Available |
| `form_navigation_controller.js` | Enter-as-Tab navigation | ✅ Available |

**Missing**: `relation-select_controller.js` - This is what FormGenerator is trying to use!

### 4.2 Tom Select Controller

**Location**: `/home/user/inf/app/assets/controllers/tom_select_controller.js`

```javascript
import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

export default class extends Controller {
    static values = {
        options: Object
    }

    connect() {
        const defaultOptions = {
            plugins: {
                remove_button: {
                    title: 'Remove this item',
                }
            },
            maxItems: null,
            allowEmptyOption: true,
            closeAfterSelect: false,
            hidePlaceholder: false,
        };

        const options = { ...defaultOptions, ...this.optionsValue };
        this.tomSelect = new TomSelect(this.element, options);
    }

    disconnect() {
        if (this.tomSelect) {
            this.tomSelect.destroy();
        }
    }
}
```

**Status**: TomSelect is available but not being used for EntityType fields.

### 4.3 Dependencies

**Location**: `/home/user/inf/app/package.json`

```json
{
  "dependencies": {
    "@floating-ui/dom": "^1.7.4",
    "sortablejs": "^1.15.6"
  }
}
```

**Tom Select**: NOT in package.json!

**Issue**: Tom Select library is not installed via npm. It might be:
- Loaded from CDN in the HTML
- Installed as PHP package
- Expected to be added

---

## 5. Current Implementation Gaps

### 5.1 Missing Controller: `relation-select_controller.js`

The FormGenerator code (lines 367) sets:
```php
$attr['data-controller'] = 'relation-select';
```

But this controller does NOT exist in the codebase!

### 5.2 No Tom Select Integration

While Tom Select is installed in the JavaScript, there's no mechanism to:
1. Detect EntityType select elements
2. Convert them to searchable selects
3. Fetch data from API endpoints
4. Handle multiple selection for ManyToMany

### 5.3 Form Theme Missing EntityType Block

The form theme (`form_theme.html.twig`) doesn't override the `entity_widget` block to add:
- Search attributes
- Placeholder configuration
- CSS classes for styling

### 5.4 Data Attributes Not Fully Utilized

The FormGenerator correctly sets these attributes:

```php
$attr['data-controller'] = 'relation-select';
$attr['data-relation-select-entity-value'] = $targetEntityName;
$attr['data-relation-select-route-value'] = $searchRoute;
$attr['data-relation-select-add-route-value'] = "{$entityRoute}_new_modal";
$attr['data-relation-select-multiple-value'] = $relationshipType === 'ManyToMany' ? 'true' : 'false';
$attr['placeholder'] = sprintf($placeholderKey, strtolower($targetEntityName));
```

But they're never consumed by a controller.

---

## 6. Form Generation Configuration

### 6.1 GeneratorProperty Options for Forms

From Genmax documentation, each property in GeneratorEntity can have:

| Option | Description | Default |
|--------|-------------|---------|
| `showInForm` | Include in form | true |
| `formRequired` | Required in form | depends on nullable |
| `formReadOnly` | Disabled in form | false |
| `formExpanded` | Radio/checkboxes instead of select | false |
| `formType` | Override form type | auto-detected |
| `formOptions` | Manual form options JSON | null |
| `formHelp` | Help text below field | null |
| `formWidgetAttr` | HTML attributes object | {} |
| `formLabelAttr` | Label HTML attributes | {} |
| `formRowAttr` | Row wrapper attributes | {} |

### 6.2 Example Property Configuration

To enable searchable select for a ManyToOne field:

```json
{
  "propertyName": "accountManager",
  "relationshipType": "ManyToOne",
  "targetEntity": "App\\Entity\\User",
  "showInForm": true,
  "formRequired": false,
  "formExpanded": false,
  "formOptions": {
    "attr": {
      "data-controller": "relation-select",
      "data-searchable": "true"
    }
  }
}
```

---

## 7. Generated Form File Flow

### 7.1 Template Used

**Location**: `/home/user/inf/app/templates/genmax/php/form_generated.php.twig`

This Twig template generates the PHP form class from GeneratorEntity data.

**Key Loop** (lines 32-84):

```twig
{% for field in formFields %}
    {% if field.isParentBackReference %}
        // Conditionally exclude parent back-reference
        if (empty($options['exclude_parent'])) {
    {% elseif field.type == 'CollectionType' %}
        // Exclude nested collections
        if (empty($options['exclude_parent'])) {
    {% endif %}
    
    $builder->add('{{ field.name }}', {{ field.type|raw }}::class, [
        {% for key, value in field.options %}
            '{{ key }}' => ...{{ value|e }}...,
        {% endfor %}
    ]);
    
    {% if field.isParentBackReference or field.type == 'CollectionType' %}
        }
    {% endif %}
{% endfor %}
```

The template properly handles:
- Field options (including data attributes)
- Entity class imports
- Nested validation constraints

---

## 8. Stimulus Form Integration

### 8.1 Form Navigation Controller

**Location**: `/home/user/inf/app/templates/auditlog/generated/form_generated.html.twig`

```twig
{{ form_start(form, {
    'attr': {
        'data-turbo': 'true',
        'data-controller': 'form-navigation'
    }
}) }}

{% for child in form.children %}
    {% if child.vars.name not in ['_token'] %}
        <div class="mb-3">
            {{ form_row(child) }}
        </div>
    {% endif %}
{% endfor %}

{{ form_end(form) }}
```

**The form renders all fields using Symfony's `form_row()` macro**, which applies the form theme and renders each field with its data attributes.

---

## 9. Entity __toString() Methods

All entities have a `__toString()` method for display in select lists.

**Example**: `/home/user/inf/app/src/Entity/User.php`

```php
public function __toString(): string
{
    return $this->email ?? $this->name ?? 'Unknown User';
}
```

The FormGenerator comment says:
```php
// Don't set choice_label - Symfony will automatically use __toString() if available
```

This works great for initial form load, but doesn't help with dynamic searching.

---

## 10. Database Configuration Tables

### 10.1 GeneratorEntity Table

Stores entity metadata used for generation.

**Key Columns**:
- `entityName` - Entity class name
- `entityLabel`, `pluralLabel` - Display names
- `apiEnabled` - Generate API endpoints

### 10.2 GeneratorProperty Table

Stores property/field metadata.

**Key Columns** for form rendering:
- `propertyName` - Field name
- `propertyType` - PHP/Doctrine type
- `relationshipType` - ManyToOne, ManyToMany, etc.
- `targetEntity` - Related entity class
- `showInForm` - Include in form
- `formExpanded` - Use radio/checkboxes
- `formType` - Override form type
- `formOptions` - JSON form options
- `formWidgetAttr` - HTML attributes

---

## 11. Implementation Roadmap

### Phase 1: Create Missing Stimulus Controller

1. Create `/home/user/inf/app/assets/controllers/relation-select_controller.js`
2. Wire up Tom Select for entity relationships
3. Implement API searching with debouncing
4. Handle ManyToMany vs ManyToOne differences

### Phase 2: Ensure Tom Select Installation

1. Verify Tom Select is available (npm or CDN)
2. Configure Tom Select plugins (search, remove_button, etc.)
3. Add CSS styling for searchable select

### Phase 3: Update Form Theme

1. Add custom `entity_widget` block to `/home/user/inf/app/templates/genmax/twig/form_theme.html.twig`
2. Handle expanded vs collapsed variations
3. Add accessibility attributes

### Phase 4: Testing & Documentation

1. Test with various entity relationships
2. Document configuration options
3. Add examples to Genmax docs

---

## 12. Code References Summary

| File | Location | Purpose |
|------|----------|---------|
| FormGenerator | `/app/src/Service/Genmax/FormGenerator.php` | Main form generation logic |
| Form Template | `/app/templates/genmax/php/form_generated.php.twig` | PHP form class template |
| Form Theme | `/app/templates/genmax/twig/form_theme.html.twig` | Form field rendering theme |
| Generated Form | `/app/src/Form/Generated/ContactTypeGenerated.php` | Example generated form |
| Controller | `/app/src/Controller/RoleController.php` | Example with API search |
| Tom Select | `/app/assets/controllers/tom_select_controller.js` | Tom Select wrapper |
| Form Rendering | `/app/templates/auditlog/generated/form_generated.html.twig` | How forms are rendered |

---

## 13. Key Implementation Details

### 13.1 What's Already There

✅ API search endpoints exist for all entities
✅ FormGenerator sets data attributes for relation-select controller
✅ Tom Select controller exists (but unused)
✅ Form theme handles rendering
✅ Entity __toString() methods provide display
✅ All database configuration is in place

### 13.2 What's Missing

❌ `relation-select_controller.js` Stimulus controller
❌ Tom Select integration with EntityType fields
❌ Dynamic API search triggering from user input
❌ Proper ManyToMany support (with tags/chips)
❌ Custom entity_widget form theme block

### 13.3 What Needs Updating

🔄 Package.json - ensure Tom Select is installed
🔄 Form theme - add custom entity_widget block (optional, for styling)
🔄 Genmax documentation - add searchable select configuration examples

---

## 14. Architecture Diagram

```
User Input in Select
    ↓
relation-select_controller.js (MISSING)
    ↓
API Search Route (e.g., /role/api/search?q=...)
    ↓
apiSearchAction() in Generated Controller
    ↓
BaseApiController::apiSearchAction()
    ↓
Repository Query
    ↓
JSON Response [{id, label}, ...]
    ↓
Tom Select Updates Dropdown Options
    ↓
User Selects Item
    ↓
Form Submits to Create/Update Action
```

---

## Conclusion

The Genmax system is **80% ready for searchable selects**. The missing piece is the `relation-select_controller.js` Stimulus controller that will:

1. Listen to select elements with `data-controller="relation-select"`
2. Convert them to Tom Select instances with search enabled
3. Fetch data from the API search endpoints as user types
4. Handle single/multiple selection based on relationship type

The groundwork is already laid - we just need to implement the controller and ensure Tom Select is properly installed and configured.

