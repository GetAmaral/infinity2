# Genmax - Code Generator

Database-driven code generator for Symfony entities and API Platform resources.

---

## Quick Start

```bash
# Generate all entities
php bin/console genmax:generate

# Generate specific entity
php bin/console genmax:generate Contact

# Preview without writing files
php bin/console genmax:generate --dry-run
```

---

## How It Works

1. Define entities in database (`generator_entity` table)
2. Define properties in database (`generator_property` table)
3. Run `genmax:generate` command
4. Generated files appear in your project

**What Gets Generated**:
- `src/Entity/Generated/{Name}Generated.php` - Base class (always overwritten)
- `src/Entity/{Name}.php` - Extension class (created once, safe to edit)
- `config/api_platform/{Name}.yaml` - API configuration (always overwritten)

---

## GeneratorEntity

Defines an entity to be generated.

### Required Fields
- `entityName` - PascalCase name (e.g., "Contact")
- `entityLabel` - Display name (e.g., "Contact")
- `pluralLabel` - Plural form (e.g., "Contacts")

### API Configuration
- `apiEnabled` - Enable API Platform (boolean)
- `apiOperations` - Array of operations: `['GetCollection', 'Get', 'Post', 'Put', 'Delete']`
- `apiSecurity` - Global security: `"is_granted('ROLE_USER')"`
- `apiNormalizationContext` - Read groups: `['groups' => ['contact:read']]`
- `apiDenormalizationContext` - Write groups: `['groups' => ['contact:write']]`
- `apiDefaultOrder` - Default sort: `['name' => 'ASC']`

### Advanced API
- `operationSecurity` - Per-operation security:
  ```php
  [
      'Post' => "is_granted('ROLE_ADMIN')",
      'Delete' => "is_granted('ROLE_ADMIN')"
  ]
  ```
- `operationValidationGroups` - Per-operation validation:
  ```php
  [
      'Post' => ['create', 'strict'],
      'Put' => ['update']
  ]
  ```
- `validationGroups` - Global validation groups: `['Default', 'strict']`

### Other
- `hasOrganization` - Multi-tenant isolation (boolean, default: true)
- `menuGroup` - Menu category (e.g., "CRM")
- `menuOrder` - Display order (integer)
- `icon` - Bootstrap icon (e.g., "bi-person")

---

## GeneratorProperty

Defines a property within an entity.

### Required Fields
- `entity` - Parent GeneratorEntity
- `propertyName` - camelCase name (e.g., "fullName")
- `propertyLabel` - Display name (e.g., "Full Name")
- `propertyType` - Doctrine type (e.g., "string", "integer", "datetime_immutable")

### Database
- `length` - String length (e.g., 100)
- `nullable` - Allow NULL (boolean)
- `unique` - Unique constraint (boolean)
- `defaultValue` - Default value (string)

### Validation
- `validationRules` - Constraints as JSON:
  ```php
  [
      'NotBlank' => [],
      'Length' => ['max' => 100],
      'Email' => []
  ]
  ```

### API Filters

Enable filters for API queries:

- `filterStrategy` - Search strategy: `'partial'`, `'exact'`, `'start'`, `'end'`, `'word_start'`
- `filterSearchable` - Enable text search (boolean)
- `filterOrderable` - Enable sorting (boolean)
- `filterBoolean` - Enable boolean filter (boolean)
- `filterDate` - Enable date range filter (boolean)
- `filterNumericRange` - Enable numeric range filter (boolean)
- `filterExists` - Enable null/not-null filter (boolean)

**API Usage**:
```
GET /api/contacts?fullName=john               # Search (if filterSearchable=true)
GET /api/contacts?order[fullName]=asc         # Sort (if filterOrderable=true)
GET /api/contacts?isActive=true               # Boolean (if filterBoolean=true)
GET /api/contacts?createdAt[after]=2024-01-01 # Date range (if filterDate=true)
GET /api/contacts?age[gte]=18                 # Numeric (if filterNumericRange=true)
GET /api/contacts?deletedAt[exists]=false     # Exists (if filterExists=true)
```

### Relationships
- `relationshipType` - `'ManyToOne'`, `'OneToMany'`, `'ManyToMany'`, `'OneToOne'`
- `targetEntity` - Target class (e.g., `'App\\Entity\\Organization'`)
- `inversedBy` - Inverse property name (for owning side)
- `mappedBy` - Owning property name (for inverse side)
- `cascade` - Array: `['persist', 'remove']`
- `orphanRemoval` - Remove orphans (boolean)

---

## Example Usage

### Create Entity

```php
use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;

$entity = new GeneratorEntity();
$entity->setEntityName('Contact');
$entity->setEntityLabel('Contact');
$entity->setPluralLabel('Contacts');
$entity->setIcon('bi-person');
$entity->setHasOrganization(true);

// API Platform
$entity->setApiEnabled(true);
$entity->setApiOperations(['GetCollection', 'Get', 'Post', 'Put', 'Delete']);
$entity->setApiSecurity("is_granted('ROLE_USER')");
$entity->setOperationSecurity([
    'Post' => "is_granted('ROLE_ADMIN')",
    'Delete' => "is_granted('ROLE_ADMIN')"
]);

$em->persist($entity);
```

### Add Properties

```php
// Full Name - searchable text
$name = new GeneratorProperty();
$name->setEntity($entity);
$name->setPropertyName('fullName');
$name->setPropertyLabel('Full Name');
$name->setPropertyType('string');
$name->setLength(100);
$name->setNullable(false);
$name->setFilterStrategy('partial');
$name->setFilterOrderable(true);
$name->setValidationRules(['NotBlank' => [], 'Length' => ['max' => 100]]);

// Email - exact match only
$email = new GeneratorProperty();
$email->setEntity($entity);
$email->setPropertyName('email');
$email->setPropertyLabel('Email');
$email->setPropertyType('string');
$email->setLength(180);
$email->setUnique(true);
$email->setFilterStrategy('exact');
$email->setFilterOrderable(true);
$email->setValidationRules(['NotBlank' => [], 'Email' => []]);

// Active - boolean filter
$active = new GeneratorProperty();
$active->setEntity($entity);
$active->setPropertyName('isActive');
$active->setPropertyLabel('Active');
$active->setPropertyType('boolean');
$active->setDefaultValue('true');
$active->setFilterBoolean(true);
$active->setFilterOrderable(true);

// Created - date filter
$created = new GeneratorProperty();
$created->setEntity($entity);
$created->setPropertyName('createdAt');
$created->setPropertyLabel('Created At');
$created->setPropertyType('datetime_immutable');
$created->setNullable(false);
$created->setFilterDate(true);
$created->setFilterOrderable(true);

$entity->addProperty($name);
$entity->addProperty($email);
$entity->addProperty($active);
$entity->addProperty($created);

$em->persist($name);
$em->persist($email);
$em->persist($active);
$em->persist($created);
$em->flush();
```

### Generate Code

```bash
php bin/console genmax:generate Contact
```

---

## Generated Output

### Entity Base Class
`src/Entity/Generated/ContactGenerated.php` - Always regenerated

```php
abstract class ContactGenerated
{
    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: false)]
    protected Organization $organization;

    #[ORM\Column(length: 100)]
    protected string $fullName;

    #[ORM\Column(length: 180, unique: true)]
    protected string $email;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    protected bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    // Getters and setters...
}
```

### Entity Extension Class
`src/Entity/Contact.php` - Created once, safe to edit

```php
#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Table(name: 'contact')]
class Contact extends ContactGenerated
{
    // Add custom methods here
}
```

### API Platform YAML
`config/api_platform/Contact.yaml` - Always regenerated

```yaml
resources:
  App\Entity\Contact:
    shortName: Contact

    normalizationContext:
      groups: ["contact:read"]

    denormalizationContext:
      groups: ["contact:write"]

    order:
      name: ASC

    security: "is_granted('ROLE_USER')"

    operations:
      - class: ApiPlatform\Metadata\GetCollection
        security: "is_granted('ROLE_USER')"

      - class: ApiPlatform\Metadata\Get
        security: "is_granted('ROLE_USER')"

      - class: ApiPlatform\Metadata\Post
        security: "is_granted('ROLE_ADMIN')"

      - class: ApiPlatform\Metadata\Put
        security: "is_granted('ROLE_USER')"

      - class: ApiPlatform\Metadata\Delete
        security: "is_granted('ROLE_ADMIN')"

    properties:
      fullName:
        filters:
          - type: SearchFilter
            strategy: partial
          - type: OrderFilter

      email:
        filters:
          - type: SearchFilter
            strategy: exact
          - type: OrderFilter

      isActive:
        filters:
          - type: BooleanFilter
          - type: OrderFilter

      createdAt:
        filters:
          - type: DateFilter
          - type: OrderFilter
```

---

## Filter Reference

| Filter | Property Field | API Usage |
|--------|----------------|-----------|
| Search | `filterStrategy` | `?fullName=john` |
| Order | `filterOrderable` | `?order[fullName]=asc` |
| Boolean | `filterBoolean` | `?isActive=true` |
| Date | `filterDate` | `?createdAt[after]=2024-01-01` |
| Numeric | `filterNumericRange` | `?price[gte]=100` |
| Exists | `filterExists` | `?deletedAt[exists]=false` |

### Search Strategies

- `partial` - Contains substring
- `exact` - Exact match
- `start` - Starts with
- `end` - Ends with
- `word_start` - Word starts with

---

## Best Practices

### Entity Names
- Use PascalCase: `Contact`, `Organization`, `SalesOrder`
- Use singular form for entity name
- Use plural form for pluralLabel

### Property Names
- Use camelCase: `fullName`, `emailAddress`, `isActive`
- Use `is` prefix for booleans: `isActive`, `isDeleted`
- Use `At` suffix for dates: `createdAt`, `updatedAt`

### Filters
- Enable `filterOrderable` on sortable columns
- Use `filterStrategy='exact'` for unique fields (email, username)
- Use `filterStrategy='partial'` for text search (name, description)
- Enable `filterBoolean` for boolean fields
- Enable `filterDate` for datetime fields
- Enable `filterNumericRange` for numeric fields

### Security
- Set `hasOrganization=true` for tenant-isolated entities
- Use operation-level security for sensitive operations
- Admin-only for `Post` and `Delete` operations
- User + ownership check for `Put` operations

### Validation
- Define validation in `validationRules` (JSON)
- Use stricter validation for `Post` (create)
- Use lenient validation for `Put` (update)

---

## Troubleshooting

**Generated files have errors**
- Check `lastGenerationLog` field in `generator_entity` table
- Run with `--dry-run` to preview

**Filters not working**
- Regenerate: `php bin/console genmax:generate`
- Clear cache: `php bin/console cache:clear`

**Validation not applied**
- Check `validationRules` is valid JSON
- Verify validation groups match operation configuration

---

## Configuration

Config file: `config/packages/genmax.yaml`

```yaml
parameters:
    genmax.paths:
        entity_dir: 'src/Entity'
        entity_generated_dir: 'src/Entity/Generated'
        api_platform_config_dir: 'config/api_platform'
```

Templates: `templates/genmax/php/` and `templates/genmax/yaml/`
