# INFINITY - CLAUDE CODE REFERENCE

## 🚀 PROJECT OVERVIEW

**Infinity** is a complete modern Symfony application with enterprise features including testing, monitoring, security, and CI/CD.

### **Technology Stack**
- **Symfony 7.3** + **API Platform 4.1**
- **PostgreSQL 18** with UUIDv7 + **Redis 7** caching
- **FrankenPHP 1.9** (PHP 8.4) + **Docker** 4-service setup
- **Bootstrap + Stimulus** frontend

### **Key URLs**
- Frontend: https://localhost (root domain, admin access)
- Organization Access: https://{slug}.localhost (tenant-isolated access)
- API: https://localhost/api
- Health: https://localhost/health/detailed
- Metrics: https://localhost/health/metrics

### **Multi-Tenant Architecture**
- **Subdomain-based Isolation**: Each organization has unique subdomain (e.g., `acme-corporation.localhost`)
- **Automatic Filtering**: Doctrine filter ensures data isolation by organization
- **Secure Authentication**: Users can only login to their organization subdomain
- **Admin Override**: ROLE_ADMIN/ROLE_SUPER_ADMIN can access all organizations via switcher
- **Organization Slugs**: URL-friendly identifiers (e.g., "Acme Corporation" → "acme-corporation")

---

## ⚡ QUICK START

### **Development Setup**
```bash
cd /home/user/inf
chmod +x scripts/setup.sh && ./scripts/setup.sh
```

### **Testing**
```bash
# Quick test execution
./scripts/run-tests.sh

# Manual testing
cd /home/user/inf/app
php bin/phpunit                     # All tests
php bin/phpunit tests/Entity/       # Unit tests
php bin/phpunit tests/Controller/   # Functional tests
composer audit                     # Security audit
```

### **Docker Operations**
```bash
# Start all services
docker-compose up -d

# Health checks
docker-compose exec app wget --spider http://localhost:8000/health
docker-compose exec database pg_isready -U infinity_user -d infinity_db
docker-compose exec redis redis-cli ping

# View logs
docker-compose logs -f app

# Stop services
docker-compose down
```

### **Key Commands**
```bash
# Database
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

# Cache
php bin/console cache:clear
php bin/console cache:warmup

# Assets
php bin/console importmap:install
```

---

## 📁 PROJECT STRUCTURE

```
/home/user/inf/
├── .env                          # Environment configuration
├── docker-compose.yml           # 4-service orchestration
├── .github/workflows/            # CI/CD automation
├── scripts/                      # Setup & deployment scripts
├── nginx/                        # Reverse proxy configuration
├── database/init/                # PostgreSQL initialization
└── app/                          # Symfony application
    ├── src/
    │   ├── Controller/           # Controllers (Home, Organization, User, Health, OrganizationSwitcher)
    │   ├── Entity/               # Entities with UUIDv7 (Organization, User, Course, etc.)
    │   ├── Repository/           # Auto-generated repositories
    │   ├── Doctrine/
    │   │   ├── Filter/           # OrganizationFilter for tenant isolation
    │   │   └── UuidV7Generator   # Custom ID generator
    │   ├── DataFixtures/         # Sample data (5 orgs with slugs, 20+ users)
    │   ├── Service/              # OrganizationContext, Performance monitoring
    │   ├── Security/             # OrganizationAwareAuthenticator
    │   ├── Twig/                 # OrganizationExtension
    │   └── EventSubscriber/      # SubdomainOrganizationSubscriber, Security & monitoring
    ├── tests/                    # 9 test files (Entity, Controller, API, Doctrine)
    ├── templates/                # Twig templates
    ├── assets/                   # Frontend assets (Stimulus + Bootstrap)
    ├── config/packages/          # Enhanced configurations
    ├── docker/frankenphp/        # FrankenPHP configuration
    └── migrations/               # Database migrations
```

---

## 🔧 ESSENTIAL CONFIGURATIONS

### **Technology Stack with Versions**
- **Symfony**: 7.3 (Latest stable)
- **API Platform**: 4.1
- **PostgreSQL**: 18 (with native UUIDv7 support)
- **Redis**: 7 (caching & sessions)
- **FrankenPHP**: 1.9 (Worker Mode)
- **PHP**: 8.4
- **PHPUnit**: 12.3.15
- **Bootstrap**: 5.3
- **Stimulus**: 3.x
- **Docker**: 4-service orchestration
- **GitHub Actions**: CI/CD automation

### **Environment Variables (.env)**
```bash
# Database
DATABASE_URL="postgresql://infinity_user:InfinitySecure2025!@database:5432/infinity_db"

# Application
APP_ENV=dev
FRANKENPHP_NUM_THREADS=4

# Redis
REDIS_URL=redis://redis:6379/0

# Security & Performance
SECURITY_RATE_LIMIT_ENABLED=true
CACHE_ENABLED=true
OPCACHE_ENABLED=true
```

### **Key Configuration Files**
- `config/packages/doctrine.yaml`: PostgreSQL 18 + UUIDv7
- `config/packages/monolog.yaml`: Multi-channel JSON logging
- `config/packages/cache.yaml`: Redis caching pools
- `config/packages/rate_limiter.yaml`: API rate limiting

### **Main Routes**
```php
#[Route('/', name: 'app_home')]
#[Route('/organization', name: 'organization_index')]
#[Route('/user', name: 'user_index')]
#[Route('/health/detailed', name: 'app_health_detailed')]
#[Route('/api', name: 'api_entrypoint')]
```

---

## 🗄️ DATABASE PATTERNS

### **UUIDv7 Entity Template**
```php
use App\Doctrine\UuidV7Generator;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EntityRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Entity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    protected Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
```

---

## 🎨 FRONTEND PATTERNS

### **Twig Template Structure**
```twig
{% extends 'base.html.twig' %}
{% block title %}Page Title{% endblock %}
{% block body %}
    <div class="infinity-card p-4">
        <h1>Content</h1>
    </div>
{% endblock %}
```

### **CSS Classes & Bootstrap Icons**
```css
.infinity-navbar          # Navigation with gradient
.infinity-card            # Card with shadow
.infinity-btn-primary     # Primary button
```

```html
<i class="bi bi-building me-2"></i>Organizations
<i class="bi bi-people me-2"></i>Users
```

### **Stimulus Configuration**
```javascript
// assets/app.js
import { startStimulusApp } from '@symfony/stimulus-bridge';
import 'bootstrap';
import './styles/app.css';
startStimulusApp();
```

---

## 🐳 DOCKER REFERENCE

### **4-Service Architecture**
- **database**: PostgreSQL 18 with UUIDv7
- **redis**: Redis 7 with LRU eviction
- **app**: FrankenPHP 1.9 + PHP 8.4 + Symfony 7.3
- **nginx**: SSL termination & reverse proxy

### **Health Checks**
```bash
docker-compose exec app wget --spider http://localhost:8000/health
docker-compose exec database pg_isready -U infinity_user -d infinity_db
docker-compose exec redis redis-cli ping
```

### **Service Management**
```bash
# Build & restart
docker-compose build app
docker-compose restart app

# Performance monitoring
docker-compose exec redis redis-cli info memory
docker-compose exec app ps aux | grep frankenphp
```

---

## 🚨 TROUBLESHOOTING

### **System Status**
```bash
curl -k https://localhost/health/detailed | jq .
docker-compose ps
docker stats --no-stream
```

### **Common Issues**

**Port Conflicts**
```bash
sudo lsof -i :80 -i :443 -i :5432 -i :6379
sudo systemctl stop apache2 nginx redis-server
```

**Database Issues**
```bash
docker-compose logs database
docker-compose exec database pg_isready -U infinity_user -d infinity_db
docker-compose exec database psql -U infinity_user -d infinity_db -c "SELECT uuidv7();"
```

**Redis Issues**
```bash
docker-compose logs redis
docker-compose exec redis redis-cli ping
docker-compose exec redis redis-cli info memory
```

**Nginx Issues**
```bash
# 502 Bad Gateway after rebuilding app container
# Nginx cached old app IP - restart nginx to refresh DNS
docker-compose restart nginx

# Verify nginx can reach app
docker-compose exec nginx ping -c 2 app
```

**SSL Issues**
```bash
rm -rf nginx/ssl/*
./scripts/generate-ssl.sh
docker-compose restart nginx
```

**Performance Issues**
```bash
docker-compose exec app tail -f var/log/performance.log
docker-compose exec app php -r "print_r(opcache_get_status());"
```

**Cache Issues**
```bash
docker-compose exec app php bin/console cache:clear
docker-compose exec app php bin/console cache:warmup
```

### **Emergency Recovery**
```bash
# Complete reset
docker-compose down -v
docker-compose build --no-cache
chmod +x scripts/setup.sh && ./scripts/setup.sh

# Database backup/restore
docker-compose exec database pg_dump -U infinity_user infinity_db > backup.sql
docker-compose exec -T database psql -U infinity_user infinity_db < backup.sql
```

---

## 📋 DEVELOPMENT WORKFLOW

### **Adding New Entity**
1. `php bin/console make:entity EntityName --no-interaction`
2. Add UUIDv7 configuration using template pattern
3. `php bin/console make:migration --no-interaction`
4. `php bin/console doctrine:migrations:migrate --no-interaction`
5. Create fixtures in `src/DataFixtures/`
6. Write tests in `tests/Entity/`

### **Adding New Controller**
1. `php bin/console make:controller ControllerName --no-interaction`
2. Add `#[Route]` attributes
3. Create templates in `templates/controllername/`
4. Update navigation in `templates/base.html.twig`
5. Add performance monitoring if needed
6. Write tests in `tests/Controller/`

### **Test Development**
```bash
# Create test file
mkdir -p tests/Feature
# Write test class extending WebTestCase
php bin/phpunit tests/Feature/NewFeatureTest.php
```

### **Asset Management**
```bash
# Add dependency
php bin/console importmap:require package-name

# Update assets
echo "import 'package-name';" >> assets/app.js

# Clear cache
php bin/console cache:clear
php bin/console importmap:install
```

### **Performance Monitoring**
```php
class MyService
{
    public function __construct(
        private readonly PerformanceMonitor $performanceMonitor,
        #[Autowire(service: 'monolog.logger.business')]
        private readonly LoggerInterface $businessLogger
    ) {}

    public function businessOperation(): void
    {
        $this->performanceMonitor->startTimer('operation');
        // Business logic
        $this->performanceMonitor->endTimer('operation');
    }
}
```

---

## 🔐 SECURITY FEATURES

### **Security Implementation**
- **SSL/TLS**: Auto-generated certificates, TLS 1.2/1.3, HSTS headers
- **Rate Limiting**: API (100/min), Auth (5/15min), Redis backend
- **Security Headers**: CSP, X-Frame-Options, XSS Protection
- **Attack Detection**: SQL injection, XSS, command injection monitoring
- **Container Security**: Non-root user, network isolation, health checks

### **Security Headers**
```bash
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: strict-origin-when-cross-origin
```

### **Monitoring & Auditing**
```bash
# View security logs
docker-compose exec app tail -f var/log/security.log | jq .

# Security audit
composer audit

# Monitor security events
docker-compose logs -f app | grep -E "(security|attack)"

# Check rate limiting
docker-compose exec redis redis-cli keys "*rate_limit*"
```

---

## 🔄 CI/CD & AUTOMATION

### **GitHub Actions Pipeline**
- **Testing**: PHPUnit with coverage, 9 test suites
- **Security**: CodeQL, Trivy, dependency review
- **Quality**: PHPStan level 8, PHP CS Fixer
- **Deployment**: Staging and production workflows

### **Commands**
```bash
# Local testing
php bin/phpunit
composer audit
composer validate --strict

# Workflow management
gh workflow run ci.yml
gh run list --limit 10
```

---

## 🏭 PRODUCTION

### **Production Setup**
```bash
chmod +x scripts/production-setup.sh && ./scripts/production-setup.sh
```

### **Production Environment**
```bash
APP_ENV=prod
FRANKENPHP_NUM_THREADS=8
OPCACHE_ENABLED=true
REDIS_MAXMEMORY=256mb
```

### **Performance Monitoring**
```bash
# Cache optimization
docker-compose exec app php bin/console cache:warmup --env=prod
docker-compose exec redis redis-cli info memory

# Database performance
docker-compose exec database psql -U infinity_user -d infinity_db -c "
SELECT schemaname, tablename, n_live_tup FROM pg_stat_user_tables;"
```

---

## 🌐 VPS DEPLOYMENT

### **VPS Server Details**
- **IP Address**: `91.98.137.175`
- **SSH User**: `root`
- **SSH Key**: `/home/user/.ssh/infinity_vps`
- **Application Path**: `/opt/infinity`
- **Access Method**: SSH key-based authentication only

### **IMPORTANT: VPS File Modification Rules**
⚠️ **NEVER modify ANY files directly on the VPS server**
- VPS is production environment - ALL changes must come through Git
- Never edit, write, or delete files manually on VPS
- Never modify Dockerfile, docker-compose.yml, or any config on VPS
- All development happens locally at `/home/user/inf`, then deployed via Git
- **ONLY EXCEPTION**: Migration files are created on VPS (migrations folder in `.gitignore`)

### **Docker Configuration Changes**
When Docker configuration needs updates:
1. **Local**: Modify Dockerfile/docker-compose.yml at `/home/user/inf`
2. **Local**: Test changes locally with `docker-compose build && docker-compose up -d`
3. **Local**: Commit and push to Git
4. **VPS**: Pull changes and rebuild containers
5. **Never modify Docker files on VPS directly**

### **VPS Deployment Workflow**

**STEP 1: Local Development**
```bash
# Make entity/schema changes locally
# Edit entities, add fields, etc.

# Commit entity changes (NOT migrations)
git add src/Entity/
git commit -m "Add new fields to Entity"
git push origin main

# DO NOT create or run migrations locally
# Migrations are created on VPS only
```

**STEP 2: VPS Deployment**
```bash
# Connect to VPS as root
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175

# Navigate to application directory
cd /opt/infinity

# Pull latest changes from Git
git pull origin main

# Rebuild Docker containers if Dockerfile/docker-compose.yml changed
docker-compose build app
docker-compose up -d app

# Create migration inside Docker container (ONLY exception to file creation rule)
docker-compose exec -T app php bin/console make:migration --no-interaction --env=prod

# Execute migration inside Docker container
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Clear and warm cache inside Docker container
docker-compose exec -T app php bin/console cache:clear --env=prod
docker-compose exec -T app php bin/console cache:warmup --env=prod
```

### **VPS Deployment Command**
When you ask Claude to "deploy to vps" or "vps deploy", Claude will automatically:
1. **VPS**: Connect via SSH as `root` using `/home/user/.ssh/infinity_vps` key
2. **VPS**: Navigate to `/opt/infinity`
3. **VPS**: Execute `git pull origin main` to get latest code
4. **VPS**: Rebuild Docker containers if needed (`docker-compose build app`)
5. **VPS**: Run `make:migration` inside app container (CREATE migration on VPS)
6. **VPS**: Run `doctrine:migrations:migrate` inside app container (EXECUTE migration)
7. **VPS**: Clear and warm production cache inside app container
8. **VPS**: Restart nginx to refresh DNS cache
9. **VPS**: Verify deployment with `/health/detailed` endpoint
10. Report deployment status with comprehensive health metrics

**Example Usage:**
```bash
# User asks:
"deploy to vps" or "vps deploy" or "vps"

# Claude executes on VPS:
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 'cd /opt/infinity && \
  git pull origin main && \
  docker-compose build app && \
  docker-compose up -d app && \
  docker-compose exec -T app php bin/console make:migration --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console cache:clear --env=prod && \
  docker-compose exec -T app php bin/console cache:warmup --env=prod && \
  docker-compose restart nginx && \
  sleep 3 && \
  curl -s http://localhost/health/detailed'
```

### **VPS Deployment Checklist**
- ✅ Local entity/schema changes committed
- ✅ Entity changes pushed to Git
- ✅ Run `/vps` command to deploy
- ✅ VPS creates migration automatically
- ✅ VPS executes migration automatically
- ✅ Verify deployment via health endpoint
- ✅ Monitor logs for errors

### **VPS Troubleshooting**
```bash
# Check Docker containers status
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 'cd /opt/infinity && docker-compose ps'

# View application logs
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 'cd /opt/infinity && docker-compose logs -f app'

# Check Git status on VPS
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 'cd /opt/infinity && git status'

# Restart containers
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 'cd /opt/infinity && docker-compose restart app'

# Manual rollback (if needed) - requires rebuild
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 'cd /opt/infinity && git reset --hard HEAD^ && docker-compose build app && docker-compose up -d app'

# Check Redis PHP extension
ssh -i /home/user/.ssh/infinity_vps root@91.98.137.175 'cd /opt/infinity && docker-compose exec -T app php -m | grep redis'
```

---

## 📈 MONITORING

### **Health Checks**
```bash
# Simple health check
curl -k https://localhost/health

# Comprehensive health monitoring (recommended)
curl -k https://localhost/health/detailed | jq .

# Database and performance metrics
curl -k https://localhost/health/metrics | jq .
```

**Comprehensive Health Monitoring (`/health/detailed`) includes:**
1. **Database**: PostgreSQL connectivity + UUIDv7 support verification
2. **Redis**: Connectivity, version, memory usage, clients, uptime
3. **Messenger Queue**: Pending messages, failed message count
4. **Disk Space**: Total, used, free, percentage with warnings (>80%: WARNING, >90%: ERROR)
5. **Storage Directories**: Existence, writability, size for:
   - `var/videos/originals` - Original video uploads
   - `var/videos/hls` - HLS transcoded videos
   - `public/uploads` - General file uploads
   - `var/cache` - Application cache
   - `var/log` - Application logs
6. **PHP Extensions**: Verify all required extensions loaded (pdo_pgsql, opcache, intl, gd, redis)
7. **Environment Variables**: Verify critical env vars configured
8. **System Metrics**: Memory usage, load averages, PHP version

**Status Levels:**
- `OK`: All systems operational
- `WARNING`: Non-critical issues detected (e.g., >10 failed messages, >80% disk usage)
- `ERROR`: Critical issues requiring immediate attention (e.g., missing extensions, >90% disk, unwritable directories)

### **Log Analysis**
```bash
# Performance logs
docker-compose exec app tail -f var/log/performance.log | jq .

# Security events
docker-compose exec app tail -f var/log/security.log | jq .

# Error analysis
docker-compose exec app grep -c "ERROR" var/log/app.log
```

---

## 🧪 TESTING

### **Test Execution**
```bash
cd /home/user/inf/app
php bin/phpunit --coverage-html coverage/
php bin/phpunit tests/Entity/       # Unit tests
php bin/phpunit tests/Controller/   # Functional tests
php bin/phpunit tests/Api/          # API tests
```

### **Quality Assurance**
```bash
vendor/bin/phpstan analyse src --level=8
vendor/bin/php-cs-fixer fix --dry-run
composer audit
```

---

## 📚 REFERENCE LINKS

### **Core Documentation**
- **Symfony 7.3**: https://symfony.com/doc/7.3/
- **API Platform 4.1**: https://api-platform.com/docs/
- **PostgreSQL 18**: https://www.postgresql.org/docs/18/
- **Redis 7**: https://redis.io/docs/
- **Bootstrap 5**: https://getbootstrap.com/docs/5.3/

---

## 🏢 MULTI-TENANT ORGANIZATION ISOLATION

### **Overview**
Infinity implements complete tenant isolation using subdomain-based organization access with automatic Doctrine filtering.

### **Architecture**

**Subdomain Access Pattern:**
- Root domain: `https://localhost` (admin access only)
- Organization tenant: `https://acme-corporation.localhost` (tenant-isolated)
- Wildcard SSL: Supports `*.localhost` for all organization subdomains

**Components:**

1. **OrganizationContext Service** (`src/Service/OrganizationContext.php`)
   - Manages active organization in session
   - Extracts organization slug from subdomain
   - Provides organization ID for filtering

2. **SubdomainOrganizationSubscriber** (`src/EventSubscriber/SubdomainOrganizationSubscriber.php`)
   - Runs on every request (priority 32)
   - Detects organization from subdomain
   - Sets organization in context

3. **OrganizationFilter** (`src/Doctrine/Filter/OrganizationFilter.php`)
   - SQL filter for automatic data isolation
   - Applies to all entities with `organization` relation
   - Filters queries: `WHERE organization_id = :activeOrgId`

4. **OrganizationFilterConfigurator** (`src/EventSubscriber/OrganizationFilterConfigurator.php`)
   - Enables/disables filter based on context
   - Runs after SubdomainOrganizationSubscriber (priority 7)
   - Sets organization_id parameter

5. **OrganizationAwareAuthenticator** (`src/Security/OrganizationAwareAuthenticator.php`)
   - Custom authenticator for organization validation
   - Non-admin users can only login to their org subdomain
   - Admins can login to any org or root domain

6. **OrganizationSwitcherController** (`src/Controller/OrganizationSwitcherController.php`)
   - POST `/organization-switcher/switch/{id}` - Switch organization
   - POST `/organization-switcher/clear` - Clear org (root access)
   - Only accessible to ROLE_ADMIN/ROLE_SUPER_ADMIN

7. **OrganizationExtension** (`src/Twig/OrganizationExtension.php`)
   - `current_organization()` - Get active organization
   - `has_active_organization()` - Check if org is active
   - `can_switch_organization()` - Check admin permission
   - `available_organizations()` - Get all orgs for switcher

### **Database Schema**

```sql
-- Organization table with slug
CREATE TABLE organization (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,  -- URL-friendly identifier
    description TEXT,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

-- Example organizations with slugs
INSERT INTO organization (name, slug) VALUES
    ('Acme Corporation', 'acme-corporation'),
    ('Globex Corporation', 'globex-corporation'),
    ('Wayne Enterprises', 'wayne-enterprises'),
    ('Stark Industries', 'stark-industries'),
    ('Umbrella Corporation', 'umbrella-corporation');

-- Users belong to organizations
CREATE TABLE "user" (
    id UUID PRIMARY KEY,
    organization_id UUID REFERENCES organization(id),
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    -- ... other fields
);
```

### **Authentication Flow**

1. User visits `https://acme-corporation.localhost/login`
2. SubdomainOrganizationSubscriber extracts slug "acme-corporation"
3. Loads organization from database and sets in OrganizationContext
4. User submits login credentials
5. OrganizationAwareAuthenticator validates:
   - User exists with correct email/password
   - User's organization matches subdomain organization
   - OR user has ROLE_ADMIN/ROLE_SUPER_ADMIN
6. If valid, login succeeds and session is created
7. OrganizationFilterConfigurator enables Doctrine filter
8. All subsequent queries automatically filtered by organization

### **Data Isolation Examples**

```php
// Without filter: Returns all users
$users = $userRepository->findAll();

// With filter enabled (automatic via subdomain):
// SQL: SELECT * FROM user WHERE organization_id = '019296b7-55be-72db-8cfd...'
// Returns only users from active organization
$users = $userRepository->findAll();

// Filter applies to all entities with organization relation:
$courses = $courseRepository->findAll(); // Only courses from active org
$lectures = $lectureRepository->findAll(); // Only lectures from active org
```

### **Admin Organization Switcher**

Admins and Super Admins see an organization dropdown in the navbar:

```twig
{% if can_switch_organization() %}
<div class="dropdown">
    <!-- Current organization display -->
    <a href="#" data-bs-toggle="dropdown">
        {{ current_organization() ? current_organization().name : 'All Organizations' }}
    </a>
    <ul class="dropdown-menu">
        <!-- Clear organization (root access) -->
        <form method="post" action="{{ path('app_organization_switcher_clear') }}">
            <button>All Organizations (Root)</button>
        </form>

        <!-- Switch to specific organization -->
        {% for org in available_organizations() %}
        <form method="post" action="{{ path('app_organization_switcher_switch', {'id': org.id}) }}">
            <button>{{ org.name }}</button>
        </form>
        {% endfor %}
    </ul>
</div>
{% endif %}
```

### **Security Rules**

**Regular Users (ROLE_USER):**
- Can ONLY login to their organization subdomain
- Login fails if they try wrong subdomain
- Cannot access root domain
- Cannot switch organizations
- All data queries automatically filtered

**Admins (ROLE_ADMIN, ROLE_SUPER_ADMIN):**
- Can login to ANY organization subdomain
- Can login to root domain (no organization)
- Can switch organizations via dropdown
- When no org selected, filter is disabled (see all data)
- When org selected, filter applies (see only that org's data)

### **Configuration**

**Doctrine Filter Registration** (`config/packages/doctrine.yaml`):
```yaml
doctrine:
    orm:
        filters:
            organization_filter:
                class: App\Doctrine\Filter\OrganizationFilter
                enabled: false  # Enabled dynamically by OrganizationFilterConfigurator
```

**Nginx Wildcard Subdomain** (`nginx/conf/default.conf`):
```nginx
server {
    listen 443 ssl http2;
    server_name localhost *.localhost;  # Wildcard for all subdomains
    # ... SSL and proxy configuration
}
```

**SSL Certificate with SAN** (`scripts/generate-ssl.sh`):
```bash
# Generate cert with Subject Alternative Names for wildcard
[alt_names]
DNS.1 = localhost
DNS.2 = *.localhost
```

### **Testing Tenant Isolation**

```php
// Test subdomain extraction
$context = new OrganizationContext($requestStack);
$slug = $context->extractSlugFromHost('acme-corporation.localhost');
$this->assertEquals('acme-corporation', $slug);

// Test filter registration
$filters = $entityManager->getFilters();
$this->assertTrue($filters->has('organization_filter'));

// Test filter parameter
$filter = $filters->enable('organization_filter');
$filter->setParameter('organization_id', $orgId, 'string');
```

### **Adding Organization to New Entity**

When creating new entities that should be organization-scoped:

```php
#[ORM\Entity]
class MyEntity extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

    // The OrganizationFilter will automatically apply to this entity!
    // No additional code needed - filtering happens at Doctrine level
}
```

### **Troubleshooting**

**Filter not working:**
```bash
# Check if filter is registered
docker-compose exec app php bin/console debug:config doctrine orm filters

# Check if filter is enabled in logs
docker-compose logs -f app | grep "Organization filter"
```

**Subdomain not detected:**
```bash
# Verify nginx wildcard config
cat nginx/conf/default.conf | grep server_name

# Test SSL certificate SAN
openssl x509 -in nginx/ssl/localhost.crt -text -noout | grep DNS
```

**User can't login:**
```bash
# Check organization slug
docker-compose exec app php bin/console doctrine:query:sql "SELECT id, name, slug FROM organization"

# Check user organization
docker-compose exec app php bin/console doctrine:query:sql "SELECT u.email, o.slug FROM \"user\" u JOIN organization o ON u.organization_id = o.id"
```

---

## 💡 BEST PRACTICES

### **Development**
1. Use `--no-interaction` for maker commands
2. Leverage AssetMapper for frontend assets
3. Use UUIDv7 for better database performance
4. Implement structured JSON logging
5. Add performance monitoring to services

### **Security**
6. Enable rate limiting on public endpoints
7. Monitor security logs regularly
8. Use security headers (CSP, HSTS)
9. Run regular security audits with `composer audit`

### **Performance**
10. Use Redis caching in production
11. Enable OPCache with optimized settings
12. Monitor performance metrics regularly
13. Optimize database queries

### **Testing**
14. Write tests for all new features
15. Use fixtures for consistent test data
16. Test both success and error scenarios
17. Run tests before commits

---

## 🎯 SUMMARY

**Infinity** is a complete, production-ready Symfony 7.3 application featuring:

✅ **Modern Stack**: PostgreSQL 18, Redis 7, FrankenPHP 1.9, PHP 8.4
✅ **Enterprise Features**: Testing, monitoring, security, CI/CD
✅ **Performance**: Redis caching, OPCache, Worker Mode
✅ **Security**: Rate limiting, attack detection, security headers

*Optimized for maximum Claude Code development efficiency.*