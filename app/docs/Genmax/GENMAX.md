# Genmax Code Generator

**Database-driven code generation for Symfony + API Platform**

Version: 2.0 | Status: Production-Ready | Updated: October 2025

---

## Overview

Genmax generates production-ready Symfony code directly from database entities (`generator_entity` and `generator_property` tables). No CSV files, no manual configuration—just define your entities in the database and run one command.

### What It Generates

| Component | Status | Description |
|-----------|--------|-------------|
| Doctrine Entities | ✅ Active | Base + Extension pattern with UUIDv7 |
| API Platform Config | ✅ Active | YAML configuration with operations |
| DTOs | ✅ Active | Input/Output DTOs for API operations |
| State Processors | ✅ Active | Handles DTO → Entity transformations |
| State Providers | ✅ Active | Custom data fetching logic |
| Repositories | ✅ Active | Base + Extension with query methods |
| Controllers | ✅ Active | Web controllers with CRUD operations |
| Security Voters | ✅ Active | RBAC permission checking with role hierarchy |
| Symfony Forms | ✅ Active | Form classes with auto field detection and Stimulus integration |
| Batch Operations | 🔨 Planned | Bulk create/update/delete (future) |

### Architecture Pattern

**Base/Extension Principle:**
- **Generated files** (in `Generated/` folders): ALWAYS regenerated, never edit
- **Extension files**: Created once, safe to customize

---

## Quick Start

```bash
# 1. Define entity in database (via fixtures, admin UI, or direct SQL)
# 2. Run generation
php bin/console genmax:generate

# 3. Generate specific entity only
php bin/console genmax:generate Contact

# 4. Preview without writing files
php bin/console genmax:generate --dry-run
```

**Result:** All code files created in seconds!

---

## Core Concepts

### 1. GeneratorEntity (Database Table)

Defines what entities to generate.

**Essential Fields:**
- `entityName` - PascalCase (e.g., `Contact`, `DealStage`)
- `entityLabel` - Display name (e.g., `Contact`)
- `pluralLabel` - Plural form (e.g., `Contacts`)

**API Configuration:**
- `apiEnabled` - Enable API Platform (bool)
- `apiOperations` - Operations: `['GetCollection', 'Get', 'Post', 'Put', 'Delete']`
- `apiSecurity` - Global security: `"is_granted('ROLE_USER')"`
- `dtoEnabled` - Use DTOs instead of direct entity exposure (bool)

**Security/Voter Configuration:**
- `voterEnabled` - Enable security voter generation (bool, default: true)
- `voterAttributes` - Custom permissions: `['LIST', 'CREATE', 'VIEW', 'EDIT', 'DELETE']`

**Advanced:**
- `operationSecurity` - Per-operation security overrides
- `validationGroups` - Global validation groups
- `operationValidationGroups` - Per-operation validation

### 2. GeneratorProperty (Database Table)

Defines properties within an entity.

**Essential Fields:**
- `propertyName` - camelCase (e.g., `fullName`, `emailAddress`)
- `propertyType` - Doctrine type (e.g., `string`, `integer`, `datetime_immutable`)
- `propertyLabel` - Display name

**Database Options:**
- `length` - String max length
- `nullable` - Allow NULL
- `unique` - Unique constraint
- `defaultValue` - Default value

**Validation:**
```json
{
  "NotBlank": {},
  "Length": {"max": 100},
  "Email": {}
}
```

**API Filters:**
- `filterStrategy` - `'partial'`, `'exact'`, `'start'`, `'end'`, `'word_start'`
- `filterSearchable` - Enable text search
- `sortable` - Enable sorting (both UI and API)
- `filterBoolean` - Boolean filter
- `filterDate` - Date range filter
- `filterNumericRange` - Numeric range filter

**Relationships:**
- `relationshipType` - `'ManyToOne'`, `'OneToMany'`, `'ManyToMany'`, `'OneToOne'`
- `targetEntity` - Target class (e.g., `'App\\Entity\\Organization'`)
- `inversedBy` / `mappedBy` - Relationship mapping
- `cascade` - `['persist', 'remove']`
- `orphanRemoval` - Remove orphaned entities

---

## Generated File Structure

For entity `Contact` with API enabled, DTOs enabled, controllers enabled, voters enabled, and forms enabled:

```
app/
├── config/api_platform/
│   └── Contact.yaml                              # ALWAYS regenerated
│
├── src/Entity/
│   ├── Contact.php                               # Created once, safe to edit
│   └── Generated/
│       └── ContactGenerated.php                  # ALWAYS regenerated
│
├── src/Dto/
│   ├── ContactInputDto.php                       # Created once, safe to edit
│   ├── ContactOutputDto.php                      # Created once, safe to edit
│   └── Generated/
│       ├── ContactInputDtoGenerated.php          # ALWAYS regenerated
│       └── ContactOutputDtoGenerated.php         # ALWAYS regenerated
│
├── src/State/
│   ├── ContactProcessor.php                      # ALWAYS regenerated
│   └── ContactProvider.php                       # ALWAYS regenerated
│
├── src/Repository/
│   ├── ContactRepository.php                     # Created once, safe to edit
│   └── Generated/
│       └── ContactRepositoryGenerated.php        # ALWAYS regenerated
│
├── src/Controller/
│   ├── ContactController.php                     # Created once, safe to edit
│   └── Generated/
│       └── ContactControllerGenerated.php        # ALWAYS regenerated
│
├── src/Security/Voter/
│   ├── ContactVoter.php                          # Created once, safe to edit
│   └── Generated/
│       └── ContactVoterGenerated.php             # ALWAYS regenerated
│
└── src/Form/
    ├── ContactType.php                           # Created once, safe to edit
    └── Generated/
        └── ContactTypeGenerated.php              # ALWAYS regenerated
```

---

## Features in Detail

### Reserved Keyword Protection

Genmax automatically protects against SQL reserved keywords:

**Table Names:** ALL tables get `_table` suffix
- `User` → `user_table`
- `Order` → `order_table`

**Column Names:** Reserved keywords get `_prop` suffix
- Property `default` → column `default_prop`
- Property `user` → column `user_prop`

**400+ keywords protected** from PostgreSQL, MySQL, SQL Server, and PHP.

See `app/src/Twig/ReservedKeywordExtension.php` for full list.

### Security Voters

Genmax automatically generates **Security Voters** for authorization and permission checking using Symfony's Voter pattern with role hierarchy support.

#### What Are Voters?

Security Voters implement fine-grained authorization logic:
- **Instance-based permissions**: Can this user VIEW/EDIT/DELETE **this specific** Contact?
- **Class-based permissions**: Can this user LIST/CREATE Contacts in general?
- **Multi-tenant support**: Automatic organization isolation
- **Role hierarchy**: Respects Symfony's role inheritance (ROLE_ADMIN includes ROLE_USER)

#### Generated Permission Constants

Each voter generates entity-specific permission constants:

```php
// ContactVoter constants
public const LIST = 'CONTACT_LIST';      // Can list contacts
public const CREATE = 'CONTACT_CREATE';  // Can create contacts
public const VIEW = 'CONTACT_VIEW';      // Can view a specific contact
public const EDIT = 'CONTACT_EDIT';      // Can edit a specific contact
public const DELETE = 'CONTACT_DELETE';  // Can delete a specific contact
```

**Naming Convention**: `{ENTITY_UPPER_SNAKE}_{PERMISSION}`

#### Using Voters in Controllers

```php
use App\Security\Voter\ContactVoter;

class ContactController extends AbstractController
{
    #[Route('/contacts/{id}', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        // Instance-based check: Can user view THIS contact?
        $this->denyAccessUnlessGranted(ContactVoter::VIEW, $contact);

        return $this->render('contact/show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/contacts', methods: ['GET'])]
    public function index(): Response
    {
        // Class-based check: Can user list contacts?
        $this->denyAccessUnlessGranted(ContactVoter::LIST);

        // ... fetch contacts
    }

    #[Route('/contacts/{id}/edit', methods: ['POST'])]
    public function edit(Contact $contact, Request $request): Response
    {
        // Instance-based check: Can user edit THIS contact?
        $this->denyAccessUnlessGranted(ContactVoter::EDIT, $contact);

        // ... update contact
    }
}
```

#### Using Voters in Twig Templates

```twig
{% if is_granted(constant('App\\Security\\Voter\\ContactVoter::EDIT'), contact) %}
    <a href="{{ path('contact_edit', {id: contact.id}) }}" class="btn btn-primary">
        Edit Contact
    </a>
{% endif %}

{% if is_granted(constant('App\\Security\\Voter\\ContactVoter::DELETE'), contact) %}
    <button class="btn btn-danger">Delete</button>
{% endif %}
```

#### Default Permission Logic

**Generated voters implement this hierarchy:**

1. **ADMIN/SUPER_ADMIN**: Full access to everything
2. **Organization Check**: User must be in same organization as entity (if `hasOrganization = true`)
3. **Permission-Specific Rules**:
   - **VIEW**: All authenticated users in same organization
   - **EDIT**: ORGANIZATION_ADMIN + owner (if entity has `owner` property)
   - **DELETE**: ORGANIZATION_ADMIN only
   - **LIST/CREATE**: ORGANIZATION_ADMIN only

**Example generated method:**

```php
protected function canEDIT(?Contact $contact, User $user): bool
{
    if (!$contact) {
        return false;
    }

    // ADMIN and SUPER_ADMIN can do anything
    if ($this->hasRole($user, 'ROLE_ADMIN')
        || $this->hasRole($user, 'ROLE_SUPER_ADMIN')) {
        return true;
    }

    // Must be in same organization
    $sameOrganization = $user->getOrganization()
        && $contact->getOrganization()
        && $user->getOrganization()->getId()->equals($contact->getOrganization()->getId());

    if (!$sameOrganization) {
        return false;
    }

    // ORGANIZATION_ADMIN can edit within their organization
    if ($this->hasRole($user, 'ROLE_ORGANIZATION_ADMIN')) {
        return true;
    }

    // Owner can edit their own contact (if entity has owner property)
    if ($contact->getOwner() && $user->getId()->equals($contact->getOwner()->getId())) {
        return true;
    }

    // Regular users can edit (customize as needed)
    return true;
}
```

#### Customizing Voter Logic

The **extension voter** is safe to edit and won't be overwritten:

```php
// src/Security/Voter/ContactVoter.php (safe to edit)
namespace App\Security\Voter;

use App\Security\Voter\Generated\ContactVoterGenerated;

final class ContactVoter extends ContactVoterGenerated
{
    // Override VIEW permission: Public contacts visible to everyone
    protected function canVIEW(?Contact $contact, User $user): bool
    {
        if ($contact && $contact->isPublic()) {
            return true;
        }

        // Fall back to base logic
        return parent::canVIEW($contact, $user);
    }

    // Override DELETE: Prevent deletion if contact has deals
    protected function canDELETE(?Contact $contact, User $user): bool
    {
        if ($contact && $contact->getDeals()->count() > 0) {
            return false; // Cannot delete contacts with deals
        }

        return parent::canDELETE($contact, $user);
    }
}
```

#### Configuring Voter Generation

**Enable/disable voter generation:**

```php
$entity = new GeneratorEntity();
$entity->setEntityName('Contact');
$entity->setVoterEnabled(true); // Default: true
```

**Custom permissions (instead of default LIST/CREATE/VIEW/EDIT/DELETE):**

```php
$entity->setVoterAttributes(['LIST', 'CREATE', 'VIEW', 'APPROVE', 'ARCHIVE']);
```

This generates:
- `CONTACT_LIST`
- `CONTACT_CREATE`
- `CONTACT_VIEW`
- `CONTACT_APPROVE` (custom)
- `CONTACT_ARCHIVE` (custom)

**When to disable voters:**

```php
// System/reference data (Country, TimeZone, Currency)
$entity->setVoterEnabled(false);

// Entities with public read access (BlogPost might use different authorization)
$entity->setVoterEnabled(false);
```

#### Role Hierarchy Integration

Voters respect Symfony's role hierarchy via `RoleHierarchyInterface`:

**config/packages/security.yaml:**
```yaml
security:
    role_hierarchy:
        ROLE_ORGANIZATION_ADMIN: ROLE_USER
        ROLE_ADMIN: ROLE_ORGANIZATION_ADMIN
        ROLE_SUPER_ADMIN: ROLE_ADMIN
```

**Voter checks role properly:**

```php
protected function hasRole(User $user, string $role): bool
{
    $userRoles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());
    return in_array($role, $userRoles, true);
}
```

Result: If checking `hasRole($user, 'ROLE_USER')` and user has `ROLE_ADMIN`, it returns `true`.

#### Multi-Tenant Isolation

Voters automatically enforce organization boundaries:

```php
// Organization check is generated automatically for entities with hasOrganization = true
$sameOrganization = $user->getOrganization()
    && $contact->getOrganization()
    && $user->getOrganization()->getId()->equals($contact->getOrganization()->getId());

if (!$sameOrganization) {
    return false; // Access denied across organizations
}
```

**Cross-organization access:**
- ADMIN/SUPER_ADMIN: Can access all organizations
- ORGANIZATION_ADMIN: Limited to their organization
- Regular users: Limited to their organization

### Symfony Forms

Genmax automatically generates **Symfony Form classes** with automatic field type detection, validation, and relationship handling.

#### What Are Forms?

Symfony Forms provide:
- **Type-safe form handling**: Proper field types for text, numbers, dates, enums, relationships
- **Validation integration**: Automatically applies validation rules
- **Relationship widgets**: Smart dropdowns and collections with Stimulus controllers
- **CSRF protection**: Built-in security
- **Custom styling**: Luminai theme with light/dark mode support

#### Form Configuration Fields

**GeneratorProperty fields for form configuration:**

```php
// Basic form control
$property->setShowInForm(true);           // Include in form (default: true)
$property->setFormType('TextType');       // Override auto-detected type
$property->setFormRequired(true);         // Required field (default: follows nullable)
$property->setFormReadOnly(false);        // Read-only field
$property->setFormHelp('Helper text');    // Help text below field

// Relationship display options
$property->setFormExpanded(false);        // Use checkboxes/radios instead of select

// Collection options (OneToMany relationships)
$property->setCollectionAllowAdd(true);   // Allow adding items
$property->setCollectionAllowDelete(true); // Allow deleting items

// HTML attributes (stored as JSON)
$property->setFormWidgetAttr([
    'class' => 'custom-class',
    'placeholder' => 'Enter value',
    'data-controller' => 'custom'
]);
$property->setFormLabelAttr(['class' => 'custom-label']);
$property->setFormRowAttr(['class' => 'custom-row']);

// Advanced: Full form options override
$property->setFormOptions([
    'label' => 'Custom Label',
    'attr' => ['class' => 'special'],
    'help' => 'Custom help'
]);
```

#### Automatic Form Type Detection

Genmax automatically selects appropriate form types based on property types:

| Property Type | Form Type | Notes |
|--------------|-----------|-------|
| `string` (≤255) | `TextType` | Standard text input |
| `string` (>255) / `text` | `TextareaType` | Multi-line text |
| `integer` / `smallint` / `bigint` | `IntegerType` | Number input |
| `float` / `decimal` | `NumberType` | Decimal input |
| `boolean` | `CheckboxType` | Checkbox |
| `datetime` / `datetime_immutable` | `DateTimeType` | Date + time picker |
| `date` | `DateType` | Date picker |
| `time` | `TimeType` | Time picker |
| Enum | `EnumType` | Dropdown with enum values |
| ManyToOne / ManyToMany / OneToOne | `EntityType` | Relationship selector with AJAX search |
| OneToMany | `CollectionType` | Dynamic collection with add/delete |

#### Generated Form Structure

**Base form (always regenerated):**
```php
// src/Form/Generated/ContactTypeGenerated.php
abstract class ContactTypeGenerated extends AbstractType
{
    public function __construct(
        protected readonly TranslatorInterface $translator
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('fullName', TextType::class, [
            'label' => 'Full Name',
            'required' => true,
            'attr' => [
                'class' => 'form-input-modern',
                'placeholder' => 'Enter full name',
            ],
        ]);

        $builder->add('email', TextType::class, [
            'label' => 'Email',
            'required' => true,
            'attr' => [
                'class' => 'form-input-modern',
                'placeholder' => 'Enter email',
            ],
        ]);

        $builder->add('company', EntityType::class, [
            'label' => 'Company',
            'required' => true,
            'class' => App\Entity\Company::class,
            'choice_label' => '__toString',
            'attr' => [
                'class' => 'form-input-modern',
                'data-controller' => 'relation-select',
                'data-relation-select-entity-value' => 'Company',
                'data-relation-select-route-value' => 'company_api_search',
            ],
        ]);
    }
}
```

**Extension form (created once, safe to customize):**
```php
// src/Form/ContactType.php
namespace App\Form;

use App\Form\Generated\ContactTypeGenerated;

class ContactType extends ContactTypeGenerated
{
    // Add custom fields or override base behavior
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // Add custom field
        $builder->add('notes', TextareaType::class, [
            'label' => 'Internal Notes',
            'required' => false,
            'mapped' => false,  // Not mapped to entity
        ]);
    }
}
```

#### Relationship Handling

**ManyToOne / OneToOne Relationships:**

Generated with AJAX search via Stimulus `relation-select` controller:

```php
$builder->add('company', EntityType::class, [
    'class' => App\Entity\Company::class,
    'choice_label' => '__toString',
    'attr' => [
        'data-controller' => 'relation-select',
        'data-relation-select-entity-value' => 'Company',
        'data-relation-select-route-value' => 'company_api_search',
        'data-relation-select-add-route-value' => 'company_new_modal',
        'data-relation-select-multiple-value' => 'false',
        'placeholder' => 'Select company',
    ],
]);
```

**ManyToMany Relationships:**

Multiple selection with AJAX search:

```php
$builder->add('tags', EntityType::class, [
    'class' => App\Entity\Tag::class,
    'choice_label' => '__toString',
    'multiple' => true,
    'attr' => [
        'data-controller' => 'relation-select',
        'data-relation-select-multiple-value' => 'true',
        'placeholder' => 'Select one or more tags',
    ],
]);
```

**OneToMany Collections:**

Dynamic add/delete with Stimulus `live-collection` controller:

```php
$builder->add('phoneNumbers', CollectionType::class, [
    'entry_type' => App\Form\PhoneNumberType::class,
    'entry_options' => ['label' => false],
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
    'prototype' => true,
    'attr' => [
        'data-controller' => 'live-collection',
        'data-live-collection-allow-add-value' => true,
        'data-live-collection-allow-delete-value' => true,
        'data-live-collection-max-items-value' => 5,
    ],
    'constraints' => [
        new \Symfony\Component\Validator\Constraints\Count(['min' => 1]),
    ],
]);
```

#### Enum Support

Enums automatically generate dropdowns with proper labeling:

```php
// Enum class
enum LeadSourceCategory: string
{
    case ORGANIC = 'organic';
    case PAID = 'paid';
    case REFERRAL = 'referral';

    public function getLabel(): string
    {
        return match($this) {
            self::ORGANIC => 'Organic',
            self::PAID => 'Paid Advertising',
            self::REFERRAL => 'Referral',
        };
    }
}

// Generated form field
$builder->add('category', EnumType::class, [
    'class' => App\Enum\LeadSourceCategory::class,
    'choice_label' => 'getLabel',
    'attr' => [
        'class' => 'form-input-modern',
    ],
]);
```

#### Using Forms in Controllers

**In generated controllers:**

Forms are automatically integrated in create/edit actions:

```php
// src/Controller/Generated/ContactControllerGenerated.php
#[Route('/contacts/new', name: 'contact_new', methods: ['GET', 'POST'])]
public function new(Request $request): Response
{
    $contact = new Contact();
    $form = $this->createForm(ContactType::class, $contact);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $this->redirectToRoute('contact_show', ['id' => $contact->getId()]);
    }

    return $this->render('contact/new.html.twig', [
        'form' => $form,
    ]);
}
```

**In custom controllers:**

```php
use App\Form\ContactType;

class ContactController extends AbstractController
{
    #[Route('/contacts/{id}/edit', name: 'contact_edit')]
    public function edit(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Contact updated successfully');

            return $this->redirectToRoute('contact_show', ['id' => $contact->getId()]);
        }

        return $this->render('contact/edit.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }
}
```

#### Form Theming

Genmax includes a custom form theme with Luminai styling:

**Configuration (already set in `config/packages/twig.yaml`):**
```yaml
twig:
    form_themes:
        - 'genmax/twig/form_theme.html.twig'
```

**Features:**
- Modern input styling with `form-input-modern` class
- Light/Dark theme support via CSS variables
- Error messages with proper styling
- Checkbox and radio button custom styles
- Proper label formatting

**Custom CSS classes (in `assets/styles/app.css`):**
- `.form-input-modern` - Styled input fields
- `.form-label-modern` - Styled labels
- `.form-error-modern` - Error messages
- `.form-checkbox-modern` - Checkbox styling

#### Customizing Forms

**Override specific field:**
```php
// src/Form/ContactType.php
class ContactType extends ContactTypeGenerated
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // Override email field to add custom validation message
        $builder->add('email', TextType::class, [
            'label' => 'Email Address',
            'help' => 'We will never share your email',
            'attr' => [
                'placeholder' => 'you@example.com',
                'autocomplete' => 'email',
            ],
        ]);
    }
}
```

**Add conditional fields:**
```php
class ContactType extends ContactTypeGenerated
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // Add field based on options
        if ($options['show_internal_notes'] ?? false) {
            $builder->add('internalNotes', TextareaType::class, [
                'label' => 'Internal Notes',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'show_internal_notes' => false,
        ]);
    }
}
```

**Add form events:**
```php
class ContactType extends ContactTypeGenerated
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // Add event listener
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $contact = $event->getData();
            $form = $event->getForm();

            // Modify form based on data
            if ($contact && $contact->isVip()) {
                $form->add('vipNotes', TextareaType::class, [
                    'label' => 'VIP Notes',
                ]);
            }
        });
    }
}
```

#### Form Configuration Best Practices

✅ **DO:**
- Use `showInForm = true` for fields that should appear in forms
- Configure `formExpanded = true` for small enum/relationship sets (shows as radios/checkboxes)
- Set `collectionAllowAdd/Delete` appropriately for OneToMany relationships
- Use `formHelp` for complex fields that need explanation
- Leverage `formWidgetAttr` for custom Stimulus controllers

❌ **DON'T:**
- Include auto-generated fields like `id`, `createdAt`, `updatedAt` in forms
- Forget to set `formRequired` for mandatory business fields
- Use collections without setting max items limit
- Edit generated form base classes (edit extension class instead)

### API Filter Examples

```bash
# Text search (partial match)
GET /api/contacts?fullName=john

# Exact match
GET /api/contacts?email=john@example.com

# Sorting
GET /api/contacts?order[createdAt]=desc

# Boolean filter
GET /api/contacts?isActive=true

# Date range
GET /api/contacts?createdAt[after]=2024-01-01

# Numeric range
GET /api/contacts?age[gte]=18&age[lte]=65

# Null check
GET /api/contacts?deletedAt[exists]=false
```

### Security Configuration

**Global security:**
```php
$entity->setApiSecurity("is_granted('ROLE_USER')");
```

**Per-operation security:**
```php
$entity->setOperationSecurity([
    'Post' => "is_granted('ROLE_ADMIN')",
    'Delete' => "is_granted('ROLE_ADMIN')"
]);
```

### Multi-Tenant Support

All generated entities automatically:
- Include `organization` relationship
- Filter by organization in State Providers
- Validate organization ownership in Processors

Disable with: `$entity->setHasOrganization(false)`

---

## Practical Examples

### Example 1: Simple Contact Entity

```php
use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;

$entity = new GeneratorEntity();
$entity->setEntityName('Contact');
$entity->setEntityLabel('Contact');
$entity->setPluralLabel('Contacts');
$entity->setApiEnabled(true);
$entity->setDtoEnabled(true);
$entity->setApiOperations(['GetCollection', 'Get', 'Post', 'Put', 'Delete']);
$entity->setApiSecurity("is_granted('ROLE_USER')");

$em->persist($entity);

// Full Name Property
$fullName = new GeneratorProperty();
$fullName->setEntity($entity);
$fullName->setPropertyName('fullName');
$fullName->setPropertyLabel('Full Name');
$fullName->setPropertyType('string');
$fullName->setLength(100);
$fullName->setNullable(false);
$fullName->setFilterStrategy('partial');
$fullName->setSortable(true);
$fullName->setValidationRules([
    ['constraint' => 'NotBlank'],
    ['constraint' => 'Length', 'max' => 100]
]);

$em->persist($fullName);

// Email Property
$email = new GeneratorProperty();
$email->setEntity($entity);
$email->setPropertyName('email');
$email->setPropertyLabel('Email');
$email->setPropertyType('string');
$email->setLength(180);
$email->setUnique(true);
$email->setFilterStrategy('exact');
$email->setSortable(true);
$email->setValidationRules([
    ['constraint' => 'NotBlank'],
    ['constraint' => 'Email']
]);

$em->persist($email);
$em->flush();
```

**Generate:**
```bash
php bin/console genmax:generate Contact
```

### Example 2: Relationship Property

```php
// Company relationship (ManyToOne)
$company = new GeneratorProperty();
$company->setEntity($entity);
$company->setPropertyName('company');
$company->setPropertyLabel('Company');
$company->setRelationshipType('ManyToOne');
$company->setTargetEntity('App\\Entity\\Company');
$company->setInversedBy('contacts');
$company->setNullable(false);

$em->persist($company);
```

### Example 3: Collection Relationship

```php
// Deal Stages (OneToMany)
$stages = new GeneratorProperty();
$stages->setEntity($entity);
$stages->setPropertyName('stages');
$stages->setPropertyLabel('Stages');
$stages->setRelationshipType('OneToMany');
$stages->setTargetEntity('App\\Entity\\DealStage');
$stages->setMappedBy('pipeline');
$stages->setCascade(['persist', 'remove']);
$stages->setOrphanRemoval(true);
$stages->setOrderBy(['position' => 'ASC']);

$em->persist($stages);
```

### Example 4: Configuring Voter Settings

```php
// Entity with custom voter permissions
$entity = new GeneratorEntity();
$entity->setEntityName('Invoice');
$entity->setEntityLabel('Invoice');
$entity->setPluralLabel('Invoices');
$entity->setVoterEnabled(true);

// Custom permissions instead of default LIST/CREATE/VIEW/EDIT/DELETE
$entity->setVoterAttributes(['LIST', 'CREATE', 'VIEW', 'APPROVE', 'CANCEL', 'SEND']);

$em->persist($entity);
$em->flush();

// Generate voter
// Result: InvoiceVoter with constants:
//   - INVOICE_LIST
//   - INVOICE_CREATE
//   - INVOICE_VIEW
//   - INVOICE_APPROVE (custom)
//   - INVOICE_CANCEL (custom)
//   - INVOICE_SEND (custom)
```

**Using custom permissions in controller:**

```php
use App\Security\Voter\InvoiceVoter;

class InvoiceController extends AbstractController
{
    #[Route('/invoices/{id}/approve', methods: ['POST'])]
    public function approve(Invoice $invoice): Response
    {
        // Check custom APPROVE permission
        $this->denyAccessUnlessGranted(InvoiceVoter::APPROVE, $invoice);

        // Approve invoice logic...
    }

    #[Route('/invoices/{id}/send', methods: ['POST'])]
    public function send(Invoice $invoice): Response
    {
        // Check custom SEND permission
        $this->denyAccessUnlessGranted(InvoiceVoter::SEND, $invoice);

        // Send invoice logic...
    }
}
```

**Customizing permission logic in extension voter:**

```php
// src/Security/Voter/InvoiceVoter.php
namespace App\Security\Voter;

use App\Security\Voter\Generated\InvoiceVoterGenerated;
use App\Entity\Invoice;
use App\Entity\User;

final class InvoiceVoter extends InvoiceVoterGenerated
{
    // Only accountants can approve invoices
    protected function canAPPROVE(?Invoice $invoice, User $user): bool
    {
        if (!$invoice) {
            return false;
        }

        // Must have accountant role
        if (!$this->hasRole($user, 'ROLE_ACCOUNTANT')) {
            return false;
        }

        // Cannot approve your own invoice
        if ($invoice->getCreatedBy() && $user->getId()->equals($invoice->getCreatedBy()->getId())) {
            return false;
        }

        // Fall back to organization check
        return parent::canAPPROVE($invoice, $user);
    }

    // Only send approved invoices
    protected function canSEND(?Invoice $invoice, User $user): bool
    {
        if (!$invoice || !$invoice->isApproved()) {
            return false;
        }

        return parent::canSEND($invoice, $user);
    }
}
```

---

## Best Practices

### Naming Conventions

✅ **DO:**
- Entity names: PascalCase, singular (`Contact`, `DealStage`)
- Property names: camelCase (`fullName`, `isActive`, `createdAt`)
- Boolean properties: `is` prefix (`isActive`, `isDeleted`)
- Date properties: `At` suffix (`createdAt`, `updatedAt`)

❌ **DON'T:**
- Use plural entity names (`Contacts` ❌)
- Use reserved keywords without letting Genmax handle them
- Manually set table names (auto-generated as `{entity}_table`)

### API Configuration

✅ **DO:**
- Enable DTOs for all entities with write operations
- Use per-operation security for sensitive actions
- Set appropriate filter strategies (exact for IDs/emails, partial for names)
- Enable `sortable` on fields that need sorting (both UI and API)

❌ **DON'T:**
- Expose entities directly without DTOs
- Use global ROLE_ADMIN security (use operation-level instead)
- Forget to set validation rules

### Enum Properties (String-Backed Enums)

⚠️ **CRITICAL: Understanding Enum Storage**

Genmax stores PHP enum-backed properties as **plain strings** in the database and entity properties:

**Example:** `InputType` enum property
```php
// PHP Enum Definition
enum InputType: string
{
    case FULLY_COMPLETED = 'fully_completed';
    case ANY = 'any';
}

// Database Storage
type_prop VARCHAR - stores "fully_completed" (string value)

// Generated Entity Property
protected string $type = 'ANY';  // String, NOT InputType enum

// Generated Getter
public function getType(): string  // Returns string, NOT InputType
{
    return $this->type;  // "fully_completed" string
}
```

**In Controllers and Serialization:**

✅ **CORRECT:**
```php
'type' => $entity->getType()  // Returns "fully_completed" (string)
```

❌ **WRONG:**
```php
'type' => $entity->getType()->value
// ERROR: "Attempt to read property 'value' on string"
// getType() already returns a string, not an enum object!
```

**When You Need the Enum Object:**

If business logic requires the actual enum (for match expressions, methods, etc.):

```php
// Convert string to enum
$typeEnum = InputType::from($entity->getType());

// Now you can use enum features
match ($typeEnum) {
    InputType::FULLY_COMPLETED => '...',
    InputType::ANY => '...',
};

// Or use enum methods
$label = $typeEnum->getLabel();
```

**Why This Design?**

1. **Database Portability**: Strings work across all database systems
2. **Validation Flexibility**: Easy to validate with constraints
3. **API Simplicity**: JSON-friendly (enums serialize to strings automatically)
4. **Migration Safety**: Changing enum cases doesn't break existing data

**Custom entityToArray Override:**

When overriding `entityToArray()` with deep nested serialization:

```php
protected function entityToArray(object $entity): array
{
    return [
        // ✅ CORRECT: getType() returns string directly
        'type' => $input->getType(),

        // ❌ WRONG: Don't add ->value
        'type' => $input->getType()->value,  // ERROR!
    ];
}
```

**Generated Controllers:**

Genmax-generated controllers handle this correctly. The issue only appears when manually writing deep nested serialization or custom array conversions.

### Validation

✅ **DO:**
- Define validation in `validationRules` JSON
- Use stricter validation for create operations
- Combine multiple constraints when needed

❌ **DON'T:**
- Rely on database constraints alone
- Skip validation on optional fields that have format requirements

### Security & Voters

✅ **DO:**
- Enable voters for all user-manageable entities (`voterEnabled = true`)
- Use voters in controllers with `$this->denyAccessUnlessGranted()`
- Customize voter logic in extension class for business rules
- Test permission logic with different roles
- Use entity-specific permission constants (e.g., `ContactVoter::VIEW`)

❌ **DON'T:**
- Disable voters for entities that users can create/edit/delete
- Bypass voter checks with direct repository queries in controllers
- Edit generated voter files (edit extension voter instead)
- Hardcode role checks in controllers (use voters instead)
- Forget to check permissions before allowing sensitive operations

---

## Troubleshooting

### Problem: Generated files have errors

**Solution:**
1. Check `lastGenerationLog` in `generator_entity` table
2. Run with `--dry-run` to preview
3. Check logs: `docker-compose exec app tail -f var/log/app.log`

### Problem: Filters not working in API

**Solution:**
1. Regenerate: `php bin/console genmax:generate`
2. Clear cache: `php bin/console cache:clear`
3. Check API Platform config: `app/config/api_platform/Entity.yaml`

### Problem: Validation not applied

**Solution:**
1. Verify `validationRules` is valid JSON
2. Check validation groups match operation configuration
3. Ensure DTO is enabled

### Problem: Relationship not generated

**Solution:**
1. Check `targetEntity` is fully qualified class name
2. Verify `inversedBy`/`mappedBy` are correct
3. Ensure target entity exists and is generated

### Problem: Access denied errors with voters

**Solution:**
1. Check voter is registered in Symfony (should auto-register via `_defaults: autowire: true`)
2. Verify user is authenticated: `$this->getUser()` returns User instance
3. Check role hierarchy in `config/packages/security.yaml`
4. Debug voter decision: `bin/console debug:autowiring Voter`
5. Add custom logic in extension voter if base logic is too restrictive

### Problem: Voter not generated or skipped

**Solution:**
1. Check `voterEnabled = true` in `generator_entity` table
2. Verify `VOTER_ACTIVE = true` in `GenmaxOrchestrator.php`
3. Regenerate: `php bin/console genmax:generate EntityName`
4. Check generation logs for errors

### Problem: Permission constants not found

**Solution:**
1. Clear cache: `php bin/console cache:clear`
2. Verify voter file exists: `src/Security/Voter/EntityVoter.php`
3. Check namespace in voter class matches controller import
4. Use fully qualified constant: `\App\Security\Voter\EntityVoter::VIEW`

---

## Configuration

**File:** `app/config/services.yaml`

```yaml
parameters:
    genmax.paths:
        entity_dir: 'src/Entity'
        entity_generated_dir: 'src/Entity/Generated'
        dto_dir: 'src/Dto'
        dto_generated_dir: 'src/Dto/Generated'
        processor_dir: 'src/State'
        provider_dir: 'src/State'
        repository_dir: 'src/Repository'
        repository_generated_dir: 'src/Repository/Generated'
        controller_dir: 'src/Controller'
        controller_generated_dir: 'src/Controller/Generated'
        voter_dir: 'src/Security/Voter'
        voter_generated_dir: 'src/Security/Voter/Generated'
        form_dir: 'src/Form'
        form_generated_dir: 'src/Form/Generated'
        api_platform_config_dir: 'config/api_platform'

    genmax.templates:
        entity_generated: 'genmax/php/entity_generated.php.twig'
        entity_extension: 'genmax/php/entity_extension.php.twig'
        dto_input_generated: 'genmax/php/dto_input_generated.php.twig'
        dto_input_extension: 'genmax/php/dto_input_extension.php.twig'
        dto_output_generated: 'genmax/php/dto_output_generated.php.twig'
        dto_output_extension: 'genmax/php/dto_output_extension.php.twig'
        state_processor: 'genmax/php/state_processor.php.twig'
        state_provider: 'genmax/php/state_provider.php.twig'
        repository_generated: 'genmax/php/repository_generated.php.twig'
        repository_extension: 'genmax/php/repository_extension.php.twig'
        controller_generated: 'genmax/php/controller_generated.php.twig'
        controller_extension: 'genmax/php/controller_extension.php.twig'
        voter_generated: 'genmax/php/voter_generated.php.twig'
        voter_extension: 'genmax/php/voter_extension.php.twig'
        form_generated: 'genmax/php/form_generated.php.twig'
        form_extension: 'genmax/php/form_extension.php.twig'
        api_platform: 'genmax/yaml/api_platform.yaml.twig'
```

---

## Service Architecture

```
GenmaxOrchestrator (Main Controller)
├── EntityGenerator → Entities (base + extension)
├── ApiGenerator → API Platform YAML configs
├── DtoGenerator → Input/Output DTOs (base + extension)
├── StateProcessorGenerator → DTO → Entity processors
├── StateProviderGenerator → Custom data providers
├── RepositoryGenerator → Repositories (base + extension)
├── ControllerGenerator → Web controllers (base + extension)
├── VoterGenerator → Security voters (base + extension)
└── FormGenerator → Symfony forms (base + extension)
```

**Feature Flags:** See `GenmaxOrchestrator.php:28-43`

---

## Migration & Updates

### Regenerate All Entities

```bash
php bin/console genmax:generate
```

### Regenerate Single Entity

```bash
php bin/console genmax:generate Contact
```

### After Database Schema Changes

```bash
# Update GeneratorEntity or GeneratorProperty in database
# Then regenerate
php bin/console genmax:generate Entity

# Create migration
php bin/console make:migration

# Apply migration
php bin/console doctrine:migrations:migrate
```

---

## Future Features

### Planned (Not Yet Implemented)

- ✨ **Batch Operations** - Bulk create/update/delete API endpoints
- ✨ **Templates** - Twig templates for CRUD pages
- ✨ **Tests** - Automated PHPUnit tests

See `app/docs/Genmax/old/BATCH_OPERATIONS_IMPLEMENTATION_PLAN.md` for batch operations roadmap.
See `app/docs/Genmax/FORM_GENERATOR.md` for complete form generation documentation.
See `app/docs/Genmax/CONTROLLER_GENERATOR.md` for complete controller generation documentation.

---

## Resources

**Documentation:**
- API Platform: https://api-platform.com/docs/
- Doctrine ORM: https://www.doctrine-project.org/
- Symfony Validation: https://symfony.com/doc/current/validation.html

**Project Files:**
- Service Code: `/app/src/Service/Genmax/`
- Templates: `/app/templates/genmax/`
- Configuration: `/app/config/services.yaml`
- Old Docs: `/app/docs/Genmax/old/`

**Quick Start Guide:** See `QUICK_START.md` in this directory.

---

**Last Updated:** October 2025
**Version:** 2.0
**Maintainer:** Luminai Development Team
