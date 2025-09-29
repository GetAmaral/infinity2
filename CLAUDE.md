# INFINITY - CLAUDE CODE REFERENCE

## 🚀 PROJECT OVERVIEW

**Infinity** is a complete modern Symfony application with enterprise features including testing, monitoring, security, and CI/CD.

### **Technology Stack**
- **Symfony 7.3** + **API Platform 4.1**
- **PostgreSQL 18** with UUIDv7 + **Redis 7** caching
- **FrankenPHP 1.9** (PHP 8.4) + **Docker** 4-service setup
- **Bootstrap + Stimulus** frontend

### **Key URLs**
- Frontend: https://localhost
- API: https://localhost/api
- Health: https://localhost/health/detailed
- Metrics: https://localhost/health/metrics

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
    │   ├── Controller/           # 4 controllers (Home, Organization, User, Health)
    │   ├── Entity/               # 2 entities with UUIDv7
    │   ├── Repository/           # Auto-generated repositories
    │   ├── Doctrine/             # UuidV7Generator
    │   ├── DataFixtures/         # Sample data (5 orgs, 20+ users)
    │   ├── Service/              # Performance monitoring
    │   └── EventSubscriber/      # Security & monitoring
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

## 📈 MONITORING

### **Health Checks**
```bash
curl -k https://localhost/health
curl -k https://localhost/health/detailed | jq .
curl -k https://localhost/health/metrics | jq .
```

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