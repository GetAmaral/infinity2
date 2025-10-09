# Multi-Tenant Architecture Guide

Complete guide to Luminai's subdomain-based multi-tenant isolation system.

---

## Overview

Luminai implements **complete tenant isolation** using subdomain-based organization access with automatic Doctrine filtering.

### Key Features

- **Subdomain-based isolation**: Each organization has unique subdomain
- **Automatic data filtering**: Doctrine filter ensures complete isolation
- **Secure authentication**: Users can only login to their organization subdomain
- **Admin override**: ROLE_ADMIN/ROLE_SUPER_ADMIN can access all organizations
- **Organization slugs**: URL-friendly identifiers (e.g., "Acme Corporation" → "acme-corporation")

---

## Architecture

### Subdomain Access Pattern

```
Root Domain:              https://localhost
  → Admin access only
  → No organization filter
  → ROLE_ADMIN/ROLE_SUPER_ADMIN required

Organization Tenant:      https://acme-corporation.localhost
  → Tenant-isolated access
  → Automatic data filtering
  → All users in organization can access
  → Only see their organization's data

Wildcard SSL:             *.localhost
  → Supports all organization subdomains
  → Automatic SSL for any subdomain
```

### Request Flow

```
┌─────────────────────────────────────────┐
│ 1. User visits                          │
│    https://acme-corporation.localhost   │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 2. SubdomainOrganizationSubscriber      │
│    - Extracts slug: "acme-corporation"  │
│    - Loads organization from database   │
│    - Sets in OrganizationContext        │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 3. User Authentication                  │
│    - OrganizationAwareAuthenticator     │
│    - Validates user belongs to org      │
│    - OR user is ADMIN/SUPER_ADMIN       │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 4. OrganizationFilterConfigurator       │
│    - Enables Doctrine filter            │
│    - Sets organization_id parameter     │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ 5. All Queries Automatically Filtered   │
│    SELECT * FROM user                   │
│    WHERE organization_id = '01929...'   │
└─────────────────────────────────────────┘
```

---

## Components

### 1. OrganizationContext Service

**File**: `src/Service/OrganizationContext.php`

**Purpose**: Manages active organization in session

**Key Methods**:

```php
class OrganizationContext
{
    // Extract organization slug from subdomain
    public function extractSlugFromHost(string $host): ?string
    {
        // acme-corporation.localhost → "acme-corporation"
        // localhost → null
    }

    // Get current organization from session
    public function getOrganization(): ?Organization

    // Set organization in session
    public function setOrganization(Organization $organization): void

    // Clear organization from session
    public function clearOrganization(): void

    // Get organization ID for filtering
    public function getOrganizationId(): ?string
}
```

### 2. SubdomainOrganizationSubscriber

**File**: `src/EventSubscriber/SubdomainOrganizationSubscriber.php`

**Purpose**: Detects organization from subdomain on every request

**Priority**: 32 (runs early in request lifecycle)

**Flow**:

```php
public function onKernelRequest(RequestEvent $event): void
{
    $request = $event->getRequest();
    $host = $request->getHost();

    // Extract slug from subdomain
    $slug = $this->organizationContext->extractSlugFromHost($host);

    if ($slug) {
        // Load organization from database
        $organization = $this->organizationRepository->findOneBy(['slug' => $slug]);

        if ($organization) {
            // Set in context
            $this->organizationContext->setOrganization($organization);
        }
    } else {
        // Root domain - clear organization
        $this->organizationContext->clearOrganization();
    }
}
```

### 3. OrganizationFilter (Doctrine Filter)

**File**: `src/Doctrine/Filter/OrganizationFilter.php`

**Purpose**: SQL filter for automatic data isolation

**Configuration**:

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        filters:
            organization_filter:
                class: App\Doctrine\Filter\OrganizationFilter
                enabled: false  # Enabled dynamically by OrganizationFilterConfigurator
```

**Filter Logic**:

```php
public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
{
    if (!$this->hasOrganizationField($targetEntity)) {
        return '';  // Don't filter entities without organization field
    }

    return sprintf(
        '%s.organization_id = %s',
        $targetTableAlias,
        $this->getParameter('organization_id')
    );
}
```

**Result**: All queries automatically filtered:

```sql
-- Original query
SELECT * FROM user;

-- With filter enabled
SELECT * FROM user WHERE organization_id = '019296b7-55be-72db-8cfd...';
```

### 4. OrganizationFilterConfigurator

**File**: `src/EventSubscriber/OrganizationFilterConfigurator.php`

**Purpose**: Enables/disables filter based on context

**Priority**: 7 (runs after SubdomainOrganizationSubscriber)

**Flow**:

```php
public function onKernelRequest(RequestEvent $event): void
{
    $organizationId = $this->organizationContext->getOrganizationId();
    $filters = $this->entityManager->getFilters();

    if ($organizationId && !$this->security->isGranted('ROLE_SUPER_ADMIN')) {
        // Enable filter for regular users
        $filter = $filters->enable('organization_filter');
        $filter->setParameter('organization_id', $organizationId, 'string');
    } else {
        // Disable filter for admins or root domain
        if ($filters->has('organization_filter')) {
            $filters->disable('organization_filter');
        }
    }
}
```

### 5. OrganizationAwareAuthenticator

**File**: `src/Security/OrganizationAwareAuthenticator.php`

**Purpose**: Custom authenticator for organization validation

**Validation Logic**:

```php
public function authenticate(Request $request): Passport
{
    // ... load user from credentials ...

    // Check if user is locked or not verified
    if ($user->isLocked() || !$user->isVerified()) {
        throw new CustomUserMessageAuthenticationException('...');
    }

    // Get organization from context (subdomain)
    $contextOrganization = $this->organizationContext->getOrganization();

    // Admins can login anywhere
    if ($this->security->isGranted('ROLE_ADMIN')) {
        return new Passport(...);  // Allow
    }

    // Regular users can only login to their organization subdomain
    if ($contextOrganization && $user->getOrganization()->getId()->equals($contextOrganization->getId())) {
        return new Passport(...);  // Allow
    }

    // User trying to access wrong organization
    throw new CustomUserMessageAuthenticationException('Invalid organization for user');
}
```

### 6. OrganizationSwitcherController

**File**: `src/Controller/OrganizationSwitcherController.php`

**Purpose**: Allow admins to switch organizations

**Routes**:

- `POST /organization-switcher/switch/{id}` - Switch to specific organization
- `POST /organization-switcher/clear` - Clear organization (root access)

**Access**: ROLE_ADMIN or ROLE_SUPER_ADMIN only

```php
#[Route('/organization-switcher/switch/{id}', name: 'app_organization_switcher_switch', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function switch(Organization $organization): Response
{
    $this->organizationContext->setOrganization($organization);

    return $this->redirectToRoute('app_home');
}

#[Route('/organization-switcher/clear', name: 'app_organization_switcher_clear', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function clear(): Response
{
    $this->organizationContext->clearOrganization();

    return $this->redirectToRoute('app_home');
}
```

### 7. OrganizationExtension (Twig)

**File**: `src/Twig/OrganizationExtension.php`

**Purpose**: Twig functions for organization display

**Functions**:

```twig
{# Get current organization #}
{% if current_organization() %}
    {{ current_organization().name }}
{% endif %}

{# Check if organization is active #}
{% if has_active_organization() %}
    <span>Viewing: {{ current_organization().name }}</span>
{% endif %}

{# Check if user can switch organizations #}
{% if can_switch_organization() %}
    <div class="organization-switcher">...</div>
{% endif %}

{# Get all organizations (for admin switcher) #}
{% for org in available_organizations() %}
    <option value="{{ org.id }}">{{ org.name }}</option>
{% endfor %}
```

---

## Database Schema

### Organization Table

```sql
CREATE TABLE organization (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,  -- URL-friendly identifier
    description TEXT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

-- Example data
INSERT INTO organization (id, name, slug) VALUES
    (uuidv7(), 'Acme Corporation', 'acme-corporation'),
    (uuidv7(), 'Globex Corporation', 'globex-corporation'),
    (uuidv7(), 'Wayne Enterprises', 'wayne-enterprises'),
    (uuidv7(), 'Stark Industries', 'stark-industries'),
    (uuidv7(), 'Umbrella Corporation', 'umbrella-corporation');
```

### User Table (Multi-Tenant)

```sql
CREATE TABLE "user" (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL REFERENCES organization(id),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_user_organization ON "user"(organization_id);
```

### Any Organization-Scoped Entity

```sql
CREATE TABLE entity_name (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL REFERENCES organization(id),
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_entity_name_organization ON entity_name(organization_id);
```

---

## Authentication Flow

### Step-by-Step Flow

1. **User visits** `https://acme-corporation.localhost/login`

2. **SubdomainOrganizationSubscriber** extracts slug "acme-corporation"

3. **Loads organization** from database and sets in OrganizationContext

4. **User submits** login credentials

5. **OrganizationAwareAuthenticator validates**:
   - User exists with correct email/password
   - User's organization matches subdomain organization
   - OR user has ROLE_ADMIN/ROLE_SUPER_ADMIN

6. **If valid**, login succeeds and session is created

7. **OrganizationFilterConfigurator** enables Doctrine filter

8. **All subsequent queries** automatically filtered by organization

---

## Data Isolation Examples

### Without Filter (Admin on Root Domain)

```php
// Admin at https://localhost (no organization context)
$users = $userRepository->findAll();

// SQL: SELECT * FROM user
// Returns ALL users from ALL organizations
```

### With Filter (User on Organization Subdomain)

```php
// User at https://acme-corporation.localhost
$users = $userRepository->findAll();

// SQL: SELECT * FROM user WHERE organization_id = '019296b7-55be-72db-8cfd...'
// Returns ONLY users from Acme Corporation
```

### Filter Applies to All Entities

```php
// All these queries are automatically filtered
$courses = $courseRepository->findAll();
// SQL: SELECT * FROM course WHERE organization_id = '019296b7...'

$lectures = $lectureRepository->findAll();
// SQL: SELECT * FROM lecture WHERE organization_id = '019296b7...'

$users = $userRepository->findBy(['active' => true]);
// SQL: SELECT * FROM user WHERE active = true AND organization_id = '019296b7...'
```

---

## Admin Organization Switcher

### Navbar Dropdown (Admins Only)

```twig
{% if can_switch_organization() %}
<div class="dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        {% if current_organization() %}
            <i class="bi bi-building me-1"></i>
            {{ current_organization().name }}
        {% else %}
            <i class="bi bi-globe me-1"></i>
            All Organizations (Root)
        {% endif %}
    </a>

    <ul class="dropdown-menu">
        {# Clear organization (root access) #}
        <li>
            <form method="post" action="{{ path('app_organization_switcher_clear') }}">
                <input type="hidden" name="_csrf_token" value="{{ csrf_token('switch_org') }}">
                <button type="submit" class="dropdown-item">
                    <i class="bi bi-globe me-2"></i>
                    All Organizations (Root)
                </button>
            </form>
        </li>

        <li><hr class="dropdown-divider"></li>

        {# Switch to specific organization #}
        {% for org in available_organizations() %}
        <li>
            <form method="post" action="{{ path('app_organization_switcher_switch', {'id': org.id}) }}">
                <input type="hidden" name="_csrf_token" value="{{ csrf_token('switch_org') }}">
                <button type="submit" class="dropdown-item">
                    <i class="bi bi-building me-2"></i>
                    {{ org.name }}
                </button>
            </form>
        </li>
        {% endfor %}
    </ul>
</div>
{% endif %}
```

---

## Security Rules

### Regular Users (ROLE_USER)

- ✅ Can ONLY login to their organization subdomain
- ❌ Login fails if they try wrong subdomain
- ❌ Cannot access root domain
- ❌ Cannot switch organizations
- ✅ All data queries automatically filtered

### Admins (ROLE_ADMIN, ROLE_SUPER_ADMIN)

- ✅ Can login to ANY organization subdomain
- ✅ Can login to root domain (no organization)
- ✅ Can switch organizations via dropdown
- ✅ When no org selected, filter is disabled (see all data)
- ✅ When org selected, filter applies (see only that org's data)

---

## Configuration

### Doctrine Filter Registration

**File**: `config/packages/doctrine.yaml`

```yaml
doctrine:
    orm:
        filters:
            organization_filter:
                class: App\Doctrine\Filter\OrganizationFilter
                enabled: false  # Enabled dynamically by OrganizationFilterConfigurator
```

### Nginx Wildcard Subdomain

**File**: `nginx/conf/default.conf`

```nginx
server {
    listen 443 ssl http2;
    server_name localhost *.localhost;  # Wildcard for all subdomains

    ssl_certificate /etc/nginx/ssl/localhost.crt;
    ssl_certificate_key /etc/nginx/ssl/localhost.key;

    location / {
        proxy_pass http://app:8000;
        proxy_set_header Host $host;
        # ...
    }
}
```

### SSL Certificate with SAN

**File**: `scripts/generate-ssl.sh`

```bash
# Generate cert with Subject Alternative Names for wildcard
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout nginx/ssl/localhost.key \
    -out nginx/ssl/localhost.crt \
    -config <(cat <<EOF
[req]
distinguished_name = req_distinguished_name
x509_extensions = v3_req

[req_distinguished_name]

[v3_req]
subjectAltName = @alt_names

[alt_names]
DNS.1 = localhost
DNS.2 = *.localhost
EOF
)
```

---

## Adding Organization to New Entity

When creating new entities that should be organization-scoped:

```php
use App\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MyEntity extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

    // Getters and setters...

    // The OrganizationFilter will automatically apply to this entity!
    // No additional code needed - filtering happens at Doctrine level
}
```

**Important**: Add index for performance:

```php
#[ORM\Index(name: 'idx_my_entity_organization', columns: ['organization_id'])]
```

---

## Testing Tenant Isolation

### Test Subdomain Extraction

```php
use App\Service\OrganizationContext;

$context = new OrganizationContext($requestStack);
$slug = $context->extractSlugFromHost('acme-corporation.localhost');

$this->assertEquals('acme-corporation', $slug);
```

### Test Filter Registration

```php
$filters = $entityManager->getFilters();
$this->assertTrue($filters->has('organization_filter'));
```

### Test Filter Application

```php
$filter = $filters->enable('organization_filter');
$filter->setParameter('organization_id', $orgId, 'string');

$users = $userRepository->findAll();

// Verify all users belong to the organization
foreach ($users as $user) {
    $this->assertEquals($orgId, $user->getOrganization()->getId()->toString());
}
```

---

## Troubleshooting

### Filter Not Working

```bash
# Check if filter is registered
php bin/console debug:config doctrine orm filters

# Check if filter is enabled in logs
docker-compose logs -f app | grep "Organization filter"

# Check filter status
php bin/console doctrine:query:dql "
    SELECT f FROM App\Entity\User u WHERE u.id = '01234...'"
```

### Subdomain Not Detected

```bash
# Verify nginx wildcard config
cat nginx/conf/default.conf | grep server_name

# Test SSL certificate SAN
openssl x509 -in nginx/ssl/localhost.crt -text -noout | grep DNS

# Check DNS resolution
ping acme-corporation.localhost
```

### User Can't Login

```bash
# Check organization slug
docker-compose exec app php bin/console doctrine:query:sql "
    SELECT id, name, slug FROM organization"

# Check user organization
docker-compose exec app php bin/console doctrine:query:sql "
    SELECT u.email, o.slug
    FROM \"user\" u
    JOIN organization o ON u.organization_id = o.id"

# Check logs
docker-compose logs -f app | grep -i "organization\|authentication"
```

---

## Best Practices

### 1. Always Add Organization Field to Entities

```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
#[ORM\JoinColumn(nullable: false)]
private Organization $organization;
```

### 2. Add Indexes for Performance

```php
#[ORM\Index(name: 'idx_entity_organization', columns: ['organization_id'])]
```

### 3. Set Organization in Fixtures

```php
foreach ($organizations as $organization) {
    $entity = new Entity();
    $entity->setOrganization($organization);  // Always set organization
    $manager->persist($entity);
}
```

### 4. Test Multi-Tenant Isolation

```php
// Test that users only see their organization's data
public function testUserOnlySees OwnOrganizationData(): void
{
    // Login as user from Organization A
    // Verify can only see data from Organization A
    // Verify cannot see data from Organization B
}
```

---

For more information:
- [Security Guide](SECURITY.md)
- [Database Guide](DATABASE.md)
- [Development Workflow](DEVELOPMENT_WORKFLOW.md)
