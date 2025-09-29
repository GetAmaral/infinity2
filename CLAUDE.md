# INFINITY - CLAUDE CODE REFERENCE

## 🚀 PROJECT OVERVIEW

**Infinity** is a modern Symfony UX application built with September 2025 standards.

### **Technology Stack**
- **Symfony 7.3** (Latest stable release)
- **PostgreSQL 18** with native UUIDv7 support
- **FrankenPHP 1.9** with Worker Mode and PHP 8.4
- **AssetMapper** with local vendor downloads
- **API Platform 4.1** for REST/GraphQL APIs
- **Docker** with production-ready configuration

### **Application URLs**
- Frontend: https://localhost
- API Documentation: https://localhost/api
- Health Check: https://localhost/health
- Database: localhost:5432

---

## ⚡ QUICK START COMMANDS

### **Initial Setup**
```bash
# Full setup (run from /home/user/inf)
chmod +x scripts/setup.sh && ./scripts/setup.sh

# Manual setup steps
mkdir -p /home/user/inf/{nginx/{ssl,conf},database/init,scripts,app}
cd /home/user/inf
composer create-project symfony/webapp app "7.3.*"
```

### **Development Commands**
```bash
# Navigate to app directory
cd /home/user/inf/app

# Core packages installation
composer require api-platform/api-pack symfony/uid
composer require symfony/ux-turbo symfony/stimulus-bundle symfony/ux-twig-component
composer require --dev symfony/maker-bundle

# Frontend dependencies (AssetMapper)
php bin/console importmap:require bootstrap
php bin/console importmap:require bootstrap/dist/js/bootstrap.bundle.min.js
php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.min.css
```

### **Entity & Database Commands**
```bash
# Generate controllers (non-interactive)
php bin/console make:controller HomeController --no-interaction
php bin/console make:controller OrganizationController --no-interaction
php bin/console make:controller UserController --no-interaction
php bin/console make:controller HealthController --no-interaction

# Generate repositories
php bin/console make:entity --regenerate App --no-interaction

# Database operations
php bin/console doctrine:database:create --if-not-exists
php bin/console make:migration --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction
```

### **Docker Operations**
```bash
# Start all services
docker-compose up -d

# Start specific services
docker-compose up -d database
docker-compose up --build -d app
docker-compose up -d nginx

# View logs
docker-compose logs -f
docker-compose logs -f app

# Enter containers
docker-compose exec app sh
docker-compose exec database psql -U infinity_user infinity_db

# Stop all services
docker-compose down
```

---

## 📁 PROJECT STRUCTURE

```
/home/user/inf/
├── .env                           # Environment configuration
├── docker-compose.yml            # Container orchestration
├── scripts/
│   ├── setup.sh                  # Main setup script
│   └── generate-ssl.sh           # SSL certificate generation
├── nginx/
│   ├── conf/default.conf         # Nginx configuration
│   └── ssl/                      # SSL certificates
├── database/
│   └── init/01-infinity.sql      # PostgreSQL initialization
└── app/                          # Symfony application
    ├── src/
    │   ├── Controller/            # Route controllers
    │   ├── Entity/                # UUIDv7 entities
    │   └── Doctrine/UuidV7Generator.php
    ├── templates/                 # Twig templates
    ├── assets/                    # Frontend assets
    ├── config/packages/           # Symfony configuration
    ├── docker/frankenphp/Caddyfile
    └── Dockerfile
```

---

## 🔧 ESSENTIAL CONFIGURATIONS

### **Environment Variables (.env)**
```bash
POSTGRES_DB=infinity_db
POSTGRES_USER=infinity_user
POSTGRES_PASSWORD=InfinitySecure2025!
DATABASE_URL="postgresql://infinity_user:InfinitySecure2025!@database:5432/infinity_db"
APP_ENV=dev
APP_SECRET=b8f2c9e4a1d6f3e7c2b9a4d8e1f5a2c6b9e3f7a0d4c8b2e6f1a5d9c3f8e2a7b4
FRANKENPHP_NUM_THREADS=4
CORS_ALLOW_ORIGIN="^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$"
```

### **Doctrine Configuration (config/packages/doctrine.yaml)**
```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        driver: pdo_pgsql
        server_version: '18'
        types:
            uuid: Symfony\Bridge\Doctrine\Types\UuidType
    orm:
        auto_generate_proxy_classes: true
        auto_mapping: true
```

### **Route Examples**
```php
#[Route('/', name: 'app_home')]                    # Home page
#[Route('/organization', name: 'organization_index')] # Organization list
#[Route('/organization/{id}', name: 'organization_show')] # Organization detail
#[Route('/user', name: 'user_index')]              # User list
#[Route('/user/{id}', name: 'user_show')]          # User detail
#[Route('/health', name: 'app_health')]            # Health check endpoint
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
    public readonly Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $updatedAt;

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
}
```

### **Database Commands**
```sql
-- Verify UUIDv7 support
SELECT uuidv7();

-- Check database status
\dt  -- List tables
\d+ table_name  -- Describe table
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

### **CSS Classes**
```css
.infinity-navbar          # Navigation bar with gradient
.infinity-card            # Card component with shadow
.infinity-btn-primary     # Primary button with gradient
```

### **Bootstrap Icons Usage**
```html
<i class="bi bi-building me-2"></i>Organizations
<i class="bi bi-people me-2"></i>Users
<i class="bi bi-house me-2"></i>Home
```

### **Stimulus Configuration**
```javascript
// assets/app.js
import { startStimulusApp } from '@symfony/stimulus-bridge';
import 'bootstrap';
import './styles/app.css';

// Start Stimulus application with automatic controller registration
startStimulusApp();

// assets/bootstrap.js
// Stimulus controllers bootstrap file
export { Application } from '@hotwired/stimulus';
```

---

## 🐳 DOCKER REFERENCE

### **Service Architecture**
- **database**: PostgreSQL 18 with UUIDv7 support
- **app**: FrankenPHP 1.9 with Symfony 7.3
- **nginx**: SSL termination and reverse proxy

### **Container Commands**
```bash
# Health checks
docker-compose exec app wget --spider http://localhost:8000/health
docker-compose exec database pg_isready -U infinity_user -d infinity_db

# Build specific service
docker-compose build app

# Restart services
docker-compose restart app nginx
```

### **Volume Locations**
- `postgres_data`: Database persistent storage
- `./app:/app`: Application code (development)
- `./nginx/ssl:/etc/nginx/ssl`: SSL certificates

---

## 🚨 TROUBLESHOOTING

### **Common Issues & Solutions**

**1. Port Already in Use**
```bash
# Check what's using the port
sudo lsof -i :80 -i :443 -i :5432 -i :8000
# Stop conflicting services
sudo systemctl stop apache2 nginx
```

**2. Database Connection Failed**
```bash
# Check database status
docker-compose logs database
# Verify environment variables
docker-compose exec app env | grep DATABASE_URL
```

**3. SSL Certificate Issues**
```bash
# Regenerate certificates
rm -rf nginx/ssl/*
./scripts/generate-ssl.sh
docker-compose restart nginx
```

**4. Asset Loading Problems**
```bash
# Clear Symfony cache
docker-compose exec app php bin/console cache:clear
# Check importmap
docker-compose exec app php bin/console importmap:install
```

**5. Migration Errors**
```bash
# Check migration status
docker-compose exec app php bin/console doctrine:migrations:status
# Reset database (development only)
docker-compose exec app php bin/console doctrine:database:drop --force
docker-compose exec app php bin/console doctrine:database:create
```

### **Debug Commands**
```bash
# View application logs
docker-compose logs -f app

# Check Symfony environment
docker-compose exec app php bin/console about

# Verify routes
docker-compose exec app php bin/console debug:router

# Check database connection
docker-compose exec app php bin/console doctrine:query:sql "SELECT version()"
```

---

## 📋 DEVELOPMENT WORKFLOW

### **Adding New Entity**
1. Create entity: `php bin/console make:entity EntityName --no-interaction`
2. Add UUIDv7 configuration (see template above)
3. Add `#[ORM\HasLifecycleCallbacks]` and `#[ApiResource]` attributes
4. Generate migration: `php bin/console make:migration --no-interaction`
5. Run migration: `php bin/console doctrine:migrations:migrate --no-interaction`
6. Generate repository: `php bin/console make:entity --regenerate App --no-interaction`

### **Adding New Controller**
1. Generate controller: `php bin/console make:controller ControllerName --no-interaction`
2. Add routes with `#[Route]` attributes
3. Create corresponding templates in `templates/`
4. Update navigation in `templates/base.html.twig`

### **Frontend Asset Management**
1. Add dependency: `php bin/console importmap:require package-name`
2. Import in `assets/app.js`: `import 'package-name'`
3. Add styles in `assets/styles/app.css`
4. Clear cache: `php bin/console cache:clear`

### **Directory Structure Setup**
```bash
# Create Docker directory structure
mkdir -p app/docker/frankenphp

# Create template directories
mkdir -p templates/{home,organization,user}
```

---

## 🔐 SECURITY NOTES

### **SSL Configuration**
- Certificates auto-generated for localhost
- Modern TLS 1.2/1.3 protocols
- HSTS headers enabled
- Secure cipher suites configured

### **Docker Security**
- Non-root user (www-data) in containers
- Read-only volume mounts where possible
- Health checks for service monitoring
- Network isolation via custom bridge

### **Symfony Security**
- CORS configured for localhost
- CSRF protection enabled
- XSS protection headers
- Content type sniffing prevention

---

## 📚 USEFUL LINKS

- **Symfony Docs**: https://symfony.com/doc/current/
- **API Platform**: https://api-platform.com/docs/
- **FrankenPHP**: https://frankenphp.dev/docs/
- **PostgreSQL 18**: https://www.postgresql.org/docs/18/
- **Bootstrap Icons**: https://icons.getbootstrap.com/

---

## 💡 TIPS & BEST PRACTICES

1. **Always use `--no-interaction`** for maker commands in automation
2. **Use Bootstrap Icons** instead of FontAwesome for modern approach
3. **Leverage AssetMapper** - no build process needed
4. **UUIDs in URLs** are automatically converted by Symfony
5. **Health checks** ensure robust container orchestration
6. **FrankenPHP Worker Mode** provides excellent performance
7. **PostgreSQL 18 UUIDv7** offers better performance than UUIDv4
8. **Always add `#[ORM\HasLifecycleCallbacks]`** when using lifecycle methods like `#[ORM\PreUpdate]`
9. **Use single `startStimulusApp()`** call to avoid Stimulus application conflicts
10. **Create template/docker directories** before referencing them in configurations

---

*This reference is optimized for Claude Code development sessions.*