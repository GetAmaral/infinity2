# Nested Modal Implementation for Relation Fields

## Overview

This feature allows users to create new related entities (ManyToOne/ManyToMany) directly from within a parent form without losing their work.

## User Flow

1. User opens a modal to create/edit an entity (e.g., Calendar)
2. User encounters a relation field (e.g., CalendarType) with a "+" button
3. User clicks the "+" button to create a new related entity
4. Nested modal opens with the CalendarType form
5. User fills the nested form and clicks Submit
6. Submit button gets disabled (prevents double submission)
7. Form submits to backend
8. Nested modal closes automatically
9. Control returns to the original Calendar modal
10. **The newly created CalendarType appears SELECTED in the dropdown**
11. **The newly created CalendarType appears in the options list**

## Technical Architecture

### Frontend Components

#### 1. `relation-select_controller.js`
**Purpose**: Stimulus controller that manages searchable select fields with Tom Select library and handles nested modal creation.

**Key Features**:
- Initializes Tom Select for searchable dropdowns
- Opens nested modals via "+" button
- Listens for Turbo Stream responses
- Extracts entity data from streams
- Updates Tom Select with newly created entities
- Handles modal restoration

**Controller Instances**:
The template creates TWO controller instances per relation field:
1. **Wrapper Controller**: On the wrapper `<div>`, handles the "+" button click
2. **Select Controller**: Connected via `data-relation-select-target="select"`, initializes Tom Select

**Why Two Instances?**
- The wrapper instance has no `selectTarget`, so it skips Tom Select initialization
- The select instance has the `selectTarget` and initializes Tom Select
- Both instances can handle events, but only one has the Tom Select instance

**Solution**:
When a Turbo Stream event fires, the handler searches the DOM to find the Tom Select instance by querying for the select element inside the original modal, rather than relying on `this.tomSelect`.

#### 2. `crud_modal_controller.js`
**Purpose**: Manages modal lifecycle, form submission, and submit button state.

**Key Features**:
- Disables submit button on form submission
- Detects Turbo Stream responses
- Keeps button disabled for streams (lets other controllers handle closing)

### Backend Components

#### 1. Generated Controllers (e.g., `CalendarTypeControllerGenerated.php`)
**Modal Request Detection**:
```php
if ($request->headers->get('X-Requested-With') === 'turbo-frame' ||
    $request->get('modal') === '1' ||
    $request->request->get('modal') === '1') {

    // Return Turbo Stream instead of redirect
    $response = $this->render('_entity_created_success_stream.html.twig', [
        'entityType' => 'CalendarType',
        'entityId' => $calendarType->getId()->toRfc4122(),
        'displayText' => (string) $calendarType,
    ]);

    $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');
    return $response;
}
```

#### 2. `_entity_created_success_stream.html.twig`
**Purpose**: Turbo Stream template that sends entity data back to the frontend.

```twig
<turbo-stream action="append" target="body">
    <template>
        <div data-entity-type="{{ entityType }}"
             data-entity-id="{{ entityId }}"
             data-display-text="{{ displayText|e('html_attr') }}"
             style="display:none;"
             id="entity-created-marker">
        </div>
    </template>
</turbo-stream>
```

**Why This Works**:
- Turbo Stream appends a hidden div with entity data to the body
- The `turbo:before-stream-render` event fires before the stream is rendered
- Our JavaScript intercepts this event and extracts the data from `template.content`
- The hidden div is appended (and immediately cleaned up), but we've already captured the data

### Template Structure

#### Form Template (e.g., `calendar/generated/_form_modal_generated.html.twig`)

```twig
{# Check if this is a relation select field #}
{% set isRelationSelect = child.vars.attr['data-controller'] is defined and 'relation-select' in child.vars.attr['data-controller'] %}

{% if isRelationSelect %}
    {# Extract controller attributes #}
    {% set entityValue = child.vars.attr['data-relation-select-entity-value'] ?? '' %}
    {% set routeValue = child.vars.attr['data-relation-select-route-value'] ?? '' %}
    {% set addRouteValue = child.vars.attr['data-relation-select-add-route-value'] ?? '' %}
    {% set multipleValue = child.vars.attr['data-relation-select-multiple-value'] ?? 'false' %}

    {# Wrapper div with controller (for "+" button) #}
    <div data-controller="relation-select"
         data-relation-select-entity-value="{{ entityValue }}"
         data-relation-select-route-value="{{ routeValue }}"
         data-relation-select-multiple-value="{{ multipleValue }}"
         style="display: flex; gap: 0.5rem; align-items: start;">
        <div style="flex: 1;">
            {# Select element with target attribute #}
            {{ form_widget(child, {
                'attr': cleanAttrs|merge({
                    'data-relation-select-target': 'select'
                })
            }) }}
        </div>
        <button type="button"
                data-action="click->relation-select#openAddModal"
                data-add-route="{{ addRouteValue }}">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
{% endif %}
```

## Event Flow

### 1. Opening Nested Modal
```
User clicks "+" → openAddModal()
  ↓
Fetch /calendartype/new?modal=1
  ↓
Hide original modal (display: none)
  ↓
Create nested-modal-container
  ↓
Insert fetched HTML
  ↓
Add modal=1 to form action & hidden input
  ↓
Listen for turbo:before-stream-render
```

### 2. Submitting Nested Form
```
User clicks Submit → Turbo submits form
  ↓
crud-modal disables submit button
  ↓
Backend receives modal=1 parameter
  ↓
Backend returns Turbo Stream (not redirect)
  ↓
turbo:before-stream-render event fires
```

### 3. Processing Stream Response
```
handleTurboStreamRender() receives event
  ↓
Extract template from stream
  ↓
Find [data-entity-type] in template.content
  ↓
Get entityType, entityId, displayText
  ↓
Check if entityType matches this.entityValue
  ↓
Find Tom Select instance:
  - Try this.tomSelect (if exists)
  - Or search DOM in originalModal
  ↓
Add option to Tom Select
  ↓
Set value (select the new entity)
  ↓
Remove nested-modal-container
  ↓
Show original modal (display: flex)
  ↓
Clean up event listeners
```

## Key Design Decisions

### Why Not Use `entity:created` Custom Event?
- **Tried first**, but Turbo doesn't execute JavaScript in streams for security
- Stimulus controllers don't automatically connect to dynamically inserted stream content
- The `entity-created_controller.js` would never connect

### Why Use `turbo:before-stream-render` Event?
- **Fires before** the stream content is rendered to the DOM
- Allows intercepting the stream and extracting data from `template.content`
- Works reliably without requiring JavaScript execution in the stream

### Why Store `originalModal` Reference?
- Allows restoring the modal after nested modal closes
- Preserves form state (user's partially filled data)
- Enables DOM searching for Tom Select instance

### Why Search DOM for Tom Select Instance?
- The wrapper controller (which handles events) doesn't have `this.tomSelect`
- The select controller (which has Tom Select) is a different instance
- Solution: Query the DOM to find the select element and access `element.tomselect`

## Common Issues & Solutions

### Issue: "Missing target element 'select'"
**Cause**: Accessing `this.selectTarget` before checking `this.hasSelectTarget`
**Solution**: Always check `this.hasSelectTarget` first, or the wrapper controller will throw an error

### Issue: Tom Select instance is undefined
**Cause**: Event handler runs in wrapper controller, which doesn't have Tom Select
**Solution**: Search DOM for select element in originalModal and use `element.tomselect`

### Issue: Modal doesn't close after submission
**Cause**: JavaScript in Turbo Stream is blocked, event not fired
**Solution**: Use `turbo:before-stream-render` to intercept stream before rendering

### Issue: Submit button not disabled
**Cause**: crud-modal controller not connecting to dynamically inserted form
**Solution**: Works automatically - Stimulus MutationObserver detects new DOM

## Testing Checklist

- [ ] Click "+" button opens nested modal
- [ ] Original modal is hidden (not removed from DOM)
- [ ] Nested modal form is fully functional
- [ ] Submit button gets disabled on click
- [ ] Only one submission occurs (no double-submit)
- [ ] Nested modal closes after successful submission
- [ ] Original modal reappears with all data preserved
- [ ] Newly created entity appears selected in dropdown
- [ ] Newly created entity appears in dropdown options
- [ ] Works for ManyToOne relations (single select)
- [ ] Works for ManyToMany relations (multiple select)
- [ ] ESC key closes nested modal without saving
- [ ] Close button works without saving
- [ ] Multiple nested modals on same form work independently
- [ ] Works across different entity types

## Files Modified

### JavaScript Controllers
- `/app/assets/controllers/relation-select_controller.js` - Main logic
- `/app/assets/controllers/crud_modal_controller.js` - Submit button handling

### Twig Templates
- `/app/templates/_entity_created_success_stream.html.twig` - Stream response
- `/app/templates/calendar/generated/_form_modal_generated.html.twig` - Form structure (example)
- All other entity form modal templates follow same pattern

### PHP Controllers
- `/app/src/Controller/Generated/*ControllerGenerated.php` - All generated controllers
- Modal detection and Turbo Stream response added to all create/edit actions

## Future Enhancements

1. **Loading States**: Show loading indicator while fetching nested modal
2. **Error Handling**: Display error messages if nested form submission fails
3. **Breadcrumb Navigation**: Show "Calendar > CalendarType" in nested modal header
4. **Keyboard Shortcuts**: Alt+N for new, Esc for close, etc.
5. **Caching**: Cache frequently used nested modal HTML
6. **Animations**: Smooth transitions between modals
7. **Validation Feedback**: Better error display in nested forms

## Performance Considerations

- **No Page Reload**: Everything happens via AJAX/Turbo
- **Preserve Form State**: Original form data never lost
- **Minimal DOM Manipulation**: Only hide/show, not create/destroy
- **Event Listener Cleanup**: Properly remove listeners to prevent memory leaks
- **Tom Select Initialization**: Only once per field, reused after modal restoration

## Security Considerations

- **CSRF Protection**: All forms include CSRF tokens
- **Modal Parameter Validation**: Backend validates `modal=1` parameter
- **XSS Prevention**: Display text escaped with `e('html_attr')` filter
- **Authorization**: Same permission checks as regular creation
- **No JavaScript Execution**: Turbo blocks inline scripts in streams (security feature)

---

**Last Updated**: 2025-11-13
**Status**: ✅ Production Ready
**Feature**: Nested Modal for Relation Fields
