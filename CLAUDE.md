# LUMINAI - CLAUDE CODE REFERENCE

## 🚀 PROJECT OVERVIEW

**Luminai** is a complete modern Symfony application with enterprise features including testing, monitoring, security, and CI/CD.

### **Technology Stack**
- **Symfony 7.3** + **API Platform 4.1**
- **PostgreSQL 18** with UUIDv7 + **Redis 7** caching
- **FrankenPHP 1.9** (PHP 8.4) + **Docker** 4-service setup
- **Bootstrap 5.3 + Stimulus 3.x** frontend

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
docker-compose exec database pg_isready -U luminai_user -d luminai_db
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

# Audit & Maintenance
php bin/console app:audit:retention      # Enforce retention policies
php bin/console app:logs:cleanup         # Compress & cleanup logs
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
    │   ├── Controller/           # 24 controllers (CRUD, API, Admin)
    │   ├── Entity/               # 18 entities + 2 traits
    │   ├── Repository/           # Auto-generated repositories
    │   ├── Doctrine/
    │   │   ├── Filter/           # OrganizationFilter for tenant isolation
    │   │   ├── DQL/              # Custom DQL functions (UNACCENT, EXTRACT)
    │   │   └── UuidV7Generator   # Custom ID generator
    │   ├── DataFixtures/         # Sample data (5 orgs, 20+ users)
    │   ├── Service/              # 16 services (Audit, Preferences, Monitoring, etc.)
    │   ├── Security/             # Authenticators (2) + Voters (4)
    │   ├── Twig/                 # Extensions (3) + Components
    │   ├── EventSubscriber/      # 7 subscribers (Organization, Locale, Audit, etc.)
    │   ├── Message/              # Async message classes
    │   ├── MessageHandler/       # Message queue handlers
    │   └── Entity/Trait/         # Reusable traits (Audit, SoftDelete)
    ├── tests/                    # 9 test suites
    ├── templates/                # Twig templates + partials
    ├── assets/                   # Frontend (19 Stimulus controllers, custom CSS)
    ├── config/packages/          # 26 configuration files
    ├── docker/frankenphp/        # FrankenPHP configuration
    ├── translations/en/          # 10 translation domains (928 keys)
    ├── migrations/               # Database migrations
    └── docs/                     # 📚 Comprehensive documentation
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
DATABASE_URL="postgresql://luminai_user:LuminaiSecure2025!@database:5432/luminai_db"

# Application
APP_ENV=dev
FRANKENPHP_NUM_THREADS=4
APP_BASE_DOMAIN=localhost

# Redis
REDIS_URL=redis://redis:6379/0

# Security & Performance
SECURITY_RATE_LIMIT_ENABLED=true
CACHE_ENABLED=true
OPCACHE_ENABLED=true

# Audit & Compliance
AUDIT_ENCRYPTION_KEY=<generate-with-app:generate-audit-key>
AUDIT_INTEGRITY_SALT=<secure-random-salt>
```

### **Key Configuration Files**
- `config/packages/doctrine.yaml` - PostgreSQL 18 + UUIDv7 + Custom DQL Functions
- `config/packages/monolog.yaml` - Multi-channel JSON logging (10 channels)
- `config/packages/cache.yaml` - Redis multi-tier caching
- `config/packages/audit.yaml` - Audit retention policies & encryption
- `config/packages/security.yaml` - Authentication, RBAC, Security Voters
- `config/packages/messenger.yaml` - Async message queue
- `config/packages/rate_limiter.yaml` - API rate limiting

### **Main Routes**
```php
// Core
#[Route('/', name: 'app_home')]                                    # Dashboard
#[Route('/health/detailed', name: 'app_health_detailed')]          # Comprehensive health

// Organizations & Users
#[Route('/organization', name: 'organization_index')]              # Organizations CRUD
#[Route('/user', name: 'user_index')]                              # Users CRUD
#[Route('/organization-switcher', name: 'organization_switcher')]  # Admin org switcher

// Courses & Learning
#[Route('/course', name: 'course_index')]                          # Course management
#[Route('/student/courses', name: 'student_courses')]              # Student portal
#[Route('/student/course/{id}', name: 'student_course_show')]      # Course view
#[Route('/course/{id}/certificate/pdf', name: 'certificate_pdf')]  # PDF certificates

// TreeFlow AI Workflows
#[Route('/treeflow', name: 'treeflow_index')]                      # TreeFlow management
#[Route('/treeflow/{id}/canvas', name: 'treeflow_canvas')]         # Visual canvas editor

// Admin & Audit
#[Route('/admin/audit', name: 'admin_audit_index')]                # Audit logs
#[Route('/admin/audit/analytics', name: 'admin_audit_analytics')]  # Predictive analytics

// API & Tokens
#[Route('/api', name: 'api_entrypoint')]                           # API Platform
#[Route('/api-tokens', name: 'api_token_index')]                   # API token management

// Media & Streaming
#[Route('/videos/hls/{lectureId}/{filename}', name: 'video_hls')]  # HLS video streaming

// Settings
#[Route('/settings', name: 'app_settings')]                        # User settings & preferences
```

---

## 📚 DOCUMENTATION INDEX

### **Core Architecture**
- **[Database & Doctrine](app/docs/DATABASE.md)** - UUIDv7 entities, custom DQL functions, soft delete, audit trails, migrations
- **[Frontend & Assets](app/docs/FRONTEND.md)** - Twig extensions, Stimulus controllers (19), custom CSS theme, preference system
- **[Translations](app/docs/TRANSLATIONS.md)** - i18n system, 928 keys across 10 domains, multi-language support
- **[Multi-Tenant Architecture](app/docs/MULTI_TENANT.md)** - Subdomain-based organization isolation, authentication flow, data filtering

### **Features & Systems**
- **[Student Learning Portal](app/docs/STUDENT_PORTAL.md)** - Course enrollment, progress tracking, video player, certificates
- **[TreeFlow Canvas Editor](app/docs/CANVAS_EDITOR.md)** - Visual workflow builder with 1,905 lines of interactive canvas code
- **[Video Processing & HLS](app/docs/VIDEO_SYSTEM.md)** - Upload, async transcoding, HLS streaming, Plyr player, progress tracking
- **[API Search System](app/docs/API_SEARCH.md)** - Advanced search with pagination, filters, sorting across all major entities
- **[Buttons System](app/docs/BUTTONS.md)** - 18 standardized button functions, permission-aware, automatic tooltips

### **Security & Compliance**
- **[Security & RBAC](app/docs/SECURITY.md)** - Security Voters (4), API tokens, account locking, CSRF protection, login throttling
- **[Audit & Compliance](app/docs/AUDIT_SYSTEM.md)** - Enterprise audit with encryption, retention policies, GDPR compliance, analytics
- **[Navigation & RBAC](app/docs/NAVIGATION_RBAC.md)** - Permission-based menus, centralized navigation config, automatic filtering

### **DevOps & Operations**
- **[Docker Infrastructure](app/docs/DOCKER.md)** - 4-service architecture, health checks, nginx reverse proxy, SSL certificates
- **[VPS Deployment](app/docs/VPS.md)** - Production deployment workflow, SSH automation, migration management
- **[Monitoring & Logging](app/docs/MONITORING.md)** - Multi-channel logging (10 channels), performance monitoring, health endpoints
- **[Troubleshooting](app/docs/TROUBLESHOOTING.md)** - Common issues, emergency recovery, debugging guides

### **Development**
- **[Development Workflows](app/docs/DEVELOPMENT.md)** - Adding entities, controllers, voters, tests, drag-drop reordering
- **[Testing Guide](app/docs/TESTING.md)** - Unit tests, functional tests, API tests, quality assurance
- **[CI/CD Pipeline](app/docs/CI_CD.md)** - GitHub Actions, automated testing, security scanning, deployments

---

## 💡 BEST PRACTICES

### **Development**
1. Use `--no-interaction` for maker commands
2. Leverage AssetMapper for frontend assets
3. Use UUIDv7 for all new entities (extends EntityBase)
4. Add `AuditTrait` for user tracking, `SoftDeletableTrait` for compliance
5. Implement structured JSON logging with appropriate channels
6. Use Security Voters for complex permissions (not simple role checks)

### **Translations**
7. **NEVER hardcode text** - Always use translation keys from `/translations/en/`
8. **Check existing keys first** - Search all domains before creating new keys
9. **Use correct domain** - Follow domain mapping (organization, user, course, treeflow, etc.)
10. **Clear cache after changes** - Run `php bin/console cache:clear`

### **Security**
11. Use Security Voters for entity-level permissions (LIST, CREATE, VIEW, EDIT, DELETE)
12. Enable rate limiting on public endpoints (requires `symfony/lock`)
13. Monitor security logs regularly (`var/log/security.log`)
14. Run regular security audits with `composer audit`
15. Use `$this->denyAccessUnlessGranted(VoterClass::PERMISSION, $entity)` in controllers

### **Performance**
16. Use Redis caching in production (multi-tier with separate databases)
17. Enable OPCache with optimized settings
18. Monitor performance metrics via `/health/detailed` endpoint
19. Use async processing (Messenger) for heavy operations (videos, emails, audit)
20. Leverage Doctrine query/result caching in production

### **Testing**
21. Write tests for all new features (unit + functional)
22. Use fixtures for consistent test data
23. Test both success and error scenarios
24. Run tests before commits: `./scripts/run-tests.sh`

### **Audit & Compliance**
25. Review audit logs regularly via `/admin/audit`
26. Configure retention policies in `config/packages/audit.yaml`
27. Run `app:audit:retention` and `app:logs:cleanup` via cron
28. Monitor audit analytics for anomalies at `/admin/audit/analytics`

---

## 📊 REFERENCE LINKS

### **Core Documentation**
- **Symfony 7.3**: https://symfony.com/doc/7.3/
- **API Platform 4.1**: https://api-platform.com/docs/
- **PostgreSQL 18**: https://www.postgresql.org/docs/18/
- **Redis 7**: https://redis.io/docs/
- **Bootstrap 5**: https://getbootstrap.com/docs/5.3/
- **Stimulus**: https://stimulus.hotwired.dev/

### **Luminai Documentation**
All comprehensive documentation is available in `/home/user/inf/app/docs/`:
- 15 detailed topic guides
- ~11,000 lines of production-ready documentation
- Complete feature coverage with examples
- Troubleshooting guides and best practices

---

## 🎯 SUMMARY

**Luminai** is a complete, production-ready Symfony 7.3 application featuring:

✅ **Modern Stack**: PostgreSQL 18, Redis 7, FrankenPHP 1.9, PHP 8.4
✅ **Enterprise Features**: Audit system, multi-tenant isolation, RBAC, soft delete
✅ **Advanced UI**: TreeFlow canvas editor, video streaming, progress tracking
✅ **Security**: Security Voters, API tokens, account locking, audit encryption
✅ **Performance**: Multi-tier caching, async processing, OPCache
✅ **Compliance**: GDPR-ready audit system with retention policies
✅ **DevOps**: Docker orchestration, CI/CD, comprehensive monitoring
✅ **i18n**: Multi-language support with 928 translation keys

**Total Codebase:**
- 24 Controllers
- 18 Entities + 2 Traits
- 16 Services
- 4 Security Voters
- 7 Event Subscribers
- 19 Stimulus Controllers
- 10 Translation Domains
- 26 Configuration Files

*Optimized for maximum Claude Code development efficiency.*

---

## 🚀 QUICK ACCESS

**Start Development:**
```bash
chmod +x scripts/setup.sh && ./scripts/setup.sh
```

**Run Tests:**
```bash
./scripts/run-tests.sh
```

**Deploy to VPS:**
```bash
# Push changes to Git first
git add . && git commit -m "Update" && git push

# Then run VPS deployment (connects via SSH, runs migrations, clears cache)
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  git pull origin main && \
  docker-compose build app && \
  docker-compose up -d app && \
  docker-compose exec -T app php bin/console make:migration --no-interaction && \
  docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console cache:clear --env=prod && \
  docker-compose exec -T app php bin/console cache:warmup --env=prod && \
  docker-compose exec -T app php bin/console importmap:install && \
  docker-compose restart nginx && \
  sleep 3 && \
  curl -k https://localhost/health/detailed'
```

**View Health Status:**
```bash
curl -k https://localhost/health/detailed | jq .
```

**For detailed guidance on any topic, see the comprehensive documentation in `/app/docs/`**
