# LUMINAI - Quick Reference

> **Note**: This is a minimal reference guide. For detailed documentation, see the [/docs folder](#documentation-index).

---

## 🚀 Project Overview

**Luminai** is a production-ready Symfony 7.3 application with enterprise features.

### Technology Stack

- **Backend**: Symfony 7.3 + API Platform 4.1 + PHP 8.4
- **Database**: PostgreSQL 18 (UUIDv7 support)
- **Cache**: Redis 7
- **Server**: FrankenPHP 1.9 (Worker Mode)
- **Frontend**: Bootstrap 5.3 + Stimulus 3.x + Turbo Drive
- **Infrastructure**: Docker 4-service architecture

### Key URLs

- **Frontend**: https://localhost
- **Organization**: https://{slug}.localhost (e.g., https://acme-corporation.localhost)
- **API**: https://localhost/api
- **Health**: https://localhost/health/detailed

### Multi-Tenant Architecture

- Subdomain-based organization isolation
- Automatic Doctrine filtering by organization
- Secure authentication with organization validation
- Admin override for cross-organization access

---

## ⚡ Quick Start

```bash
# Setup (automated)
cd /home/user/inf
chmod +x scripts/setup.sh && ./scripts/setup.sh

# Start services
docker-compose up -d

# Run tests
./scripts/run-tests.sh

# View health
curl -k https://localhost/health/detailed | jq .
```

**📖 See**: [Quick Start Guide](docs/QUICK_START.md)

---

## 📁 Project Structure

```
/home/user/inf/
├── app/                          # Symfony application
│   ├── src/                      # Source code
│   │   ├── Controller/           # Controllers
│   │   ├── Entity/               # Entities (UUIDv7)
│   │   ├── Repository/           # Repositories
│   │   ├── Security/             # Voters, Authenticators
│   │   ├── Service/              # Business logic
│   │   └── EventSubscriber/      # Event listeners
│   ├── tests/                    # PHPUnit tests
│   ├── templates/                # Twig templates
│   ├── assets/                   # Frontend (Stimulus + Bootstrap)
│   └── config/                   # Configuration files
├── docker-compose.yml            # 4-service orchestration
├── nginx/                        # Reverse proxy + SSL
├── database/init/                # PostgreSQL initialization
├── scripts/                      # Automation scripts
├── docs/                         # ⭐ DETAILED DOCUMENTATION
└── CLAUDE.md                     # This file
```

---

## 📚 Documentation Index

### Getting Started

| Document | Description |
|----------|-------------|
| [Quick Start](docs/QUICK_START.md) | Fast setup, testing, common commands |
| [Docker Guide](docs/DOCKER.md) | Container architecture, operations, troubleshooting |

### Development

| Document | Description |
|----------|-------------|
| [Development Workflow](docs/DEVELOPMENT_WORKFLOW.md) | Adding entities, controllers, forms, tests |
| [Database Guide](docs/DATABASE.md) | UUIDv7 patterns, migrations, Doctrine |
| [Frontend Guide](docs/FRONTEND.md) | Twig, Stimulus, Turbo Drive, Bootstrap |
| [Security Guide](docs/SECURITY.md) | Voters (RBAC), authentication, API tokens |

### Architecture

| Document | Description |
|----------|-------------|
| [Multi-Tenant Guide](docs/MULTI_TENANT.md) | Subdomain isolation, organization filtering |
| [Monitoring Guide](docs/MONITORING.md) | Health checks, logs, performance metrics |

### Deployment

| Document | Description |
|----------|-------------|
| [VPS Deployment](docs/VPS_DEPLOYMENT.md) | Production VPS workflow, automated deployment |
| [Production Deployment](docs/ProductionDeployment.md) | Full production setup, CI/CD, rollback |

### Troubleshooting

| Document | Description |
|----------|-------------|
| [Troubleshooting Guide](docs/TROUBLESHOOTING.md) | Common issues and solutions |

### Features

| Document | Description |
|----------|-------------|
| [Canvas Editor](docs/CANVAS_EDITOR.md) | TreeFlow canvas editor feature |
| [Student Portal](docs/STUDENT_PORTAL.md) | Student course enrollment and learning |
| [Navigation RBAC](docs/NAVIGATION_RBAC.md) | Role-based navigation system |

---

## 🔧 Essential Commands

### Docker

```bash
docker-compose up -d              # Start services
docker-compose down               # Stop services
docker-compose restart app        # Restart app
docker-compose logs -f app        # View logs
docker-compose ps                 # Service status
```

### Database

```bash
php bin/console doctrine:migrations:migrate --no-interaction  # Run migrations
php bin/console doctrine:fixtures:load --no-interaction       # Load fixtures
php bin/console doctrine:schema:validate                      # Validate schema
```

### Cache

```bash
php bin/console cache:clear       # Clear cache
php bin/console cache:warmup      # Warm cache
```

### Testing

```bash
php bin/phpunit                   # All tests
php bin/phpunit tests/Entity/     # Unit tests
php bin/phpunit tests/Controller/ # Functional tests
composer audit                    # Security audit
```

---

## 🗄️ Database Patterns

### UUIDv7 Entity Template

```php
use App\Doctrine\UuidV7Generator;
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

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

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

**📖 See**: [Database Guide](docs/DATABASE.md)

---

## 🎨 Frontend Patterns

### Twig Template

```twig
{% extends 'base.html.twig' %}

{% block title %}Page Title{% endblock %}

{% block body %}
    <div class="luminai-card p-4">
        <h1>
            <i class="bi bi-building me-2"></i>
            {{ 'page.title'|trans }}
        </h1>
        {# Content #}
    </div>
{% endblock %}
```

### Stimulus Controller

```javascript
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['output']
    static values = { url: String }

    connect() {
        console.log('Connected');
    }

    refresh() {
        fetch(this.urlValue)
            .then(response => response.text())
            .then(html => {
                this.outputTarget.innerHTML = html;
            });
    }
}
```

**📖 See**: [Frontend Guide](docs/FRONTEND.md)

---

## 🔐 Security Voters

### Using Voters

```php
// In controller
use App\Security\Voter\EntityVoter;

$this->denyAccessUnlessGranted(EntityVoter::VIEW, $entity);
```

```twig
{# In template #}
{% if is_granted(constant('App\\Security\\Voter\\EntityVoter::EDIT'), entity) %}
    <button>Edit</button>
{% endif %}
```

**📖 See**: [Security Guide](docs/SECURITY.md)

---

## 🌐 VPS Deployment

### Quick Deploy

```bash
# Automatic deployment (VPS server: 91.98.137.175)
# Just commit to Git and run:
vps deploy

# Or manually:
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  git pull origin main && \
  docker-compose build app && \
  docker-compose up -d app && \
  docker-compose exec -T app php bin/console make:migration --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console cache:clear --env=prod && \
  docker-compose exec -T app php bin/console cache:warmup --env=prod'
```

**⚠️ IMPORTANT**: Never modify files on VPS. All changes through Git.

**📖 See**: [VPS Deployment Guide](docs/VPS_DEPLOYMENT.md)

---

## 📈 Monitoring

### Health Check

```bash
# Detailed health status
curl -k https://localhost/health/detailed | jq .

# Metrics
curl -k https://localhost/health/metrics | jq .
```

### Logs

```bash
# Application logs
docker-compose exec app tail -f var/log/app.log | jq .

# Security logs
docker-compose exec app tail -f var/log/security.log | jq .

# Performance logs
docker-compose exec app tail -f var/log/performance.log | jq .
```

**📖 See**: [Monitoring Guide](docs/MONITORING.md)

---

## 🚨 Troubleshooting

### Common Issues

| Issue | Quick Fix |
|-------|-----------|
| Port conflicts | `sudo lsof -i :80 :443 :5432 :6379` then stop conflicting services |
| 502 Bad Gateway | `docker-compose restart nginx` |
| Database connection | `docker-compose restart database` |
| Cache issues | `php bin/console cache:clear && docker-compose restart app` |
| Permission denied | `docker-compose exec -u root app chmod -R 777 var/` |

**📖 See**: [Troubleshooting Guide](docs/TROUBLESHOOTING.md)

---

## 💡 Best Practices

### Development

1. Use `--no-interaction` for maker commands
2. Use UUIDv7 for all entities
3. Always add organization field to entities
4. Write tests for new features
5. Use Turbo Drive event listeners (`turbo:load`)

### Security

6. Use Security Voters for permissions
7. Enable rate limiting on public endpoints
8. Run `composer audit` regularly
9. Never commit secrets to Git
10. Use environment variables for configuration

### Performance

11. Use Redis caching in production
12. Enable OPCache
13. Monitor performance metrics
14. Optimize database queries with indexes

**📖 See**: Individual guides for detailed best practices

---

## 🎯 Quick Checklist

### Before Committing

- [ ] Tests pass (`php bin/phpunit`)
- [ ] Code style compliant (`vendor/bin/php-cs-fixer fix`)
- [ ] PHPStan passes (`vendor/bin/phpstan analyse src --level=8`)
- [ ] Security audit clean (`composer audit`)

### Before Deploying

- [ ] All changes tested locally
- [ ] Changes committed and pushed to Git
- [ ] No local migrations created
- [ ] Deployment plan reviewed

### After Deploying

- [ ] Health check passes
- [ ] Logs checked for errors
- [ ] Critical functionality tested
- [ ] Monitor for 10-15 minutes

---

## 📞 Support & Resources

### Documentation

- **Main Docs**: `/home/user/inf/docs/`
- **API Docs**: https://localhost/api
- **Health Check**: https://localhost/health/detailed

### External Resources

- [Symfony 7.3 Docs](https://symfony.com/doc/7.3/)
- [API Platform Docs](https://api-platform.com/docs/)
- [PostgreSQL 18 Docs](https://www.postgresql.org/docs/18/)
- [Redis 7 Docs](https://redis.io/docs/)
- [Turbo Drive Docs](https://turbo.hotwired.dev/)

---

**For detailed information on any topic, see the comprehensive guides in the `/docs` folder.**
