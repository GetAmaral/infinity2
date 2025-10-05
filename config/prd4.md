# 🚀 INFINITY - MODERN SYMFONY UX APPLICATION

## **ARCHITECTURE**
- **Symfony 7.3** (Latest stable release)
- **PostgreSQL 18** with native UUIDv7 support
- **FrankenPHP 1.9** with Worker Mode and PHP 8.4
- **AssetMapper** with local vendor downloads
- **API Platform 4.1** for REST/GraphQL APIs
- **Docker** with production-ready configuration

**Application URLs**: https://localhost (frontend), https://localhost/api (API)

---

## **IMPLEMENTATION PLAN**

### **PHASE 1: PROJECT FOUNDATION**

#### **Step 1: Initialize Project Structure**
```bash
mkdir -p /home/user/inf/{nginx/{ssl,conf},database/init,scripts,app}
cd /home/user/inf
```

#### **Step 2: Create Environment Configuration**
Create `.env`:
```bash
POSTGRES_DB=luminai_db
POSTGRES_USER=luminai_user
POSTGRES_PASSWORD=LuminaiSecure2025!
DATABASE_URL="postgresql://luminai_user:LuminaiSecure2025!@database:5432/luminai_db"
APP_ENV=dev
APP_SECRET=b8f2c9e4a1d6f3e7c2b9a4d8e1f5a2c6b9e3f7a0d4c8b2e6f1a5d9c3f8e2a7b4
FRANKENPHP_NUM_THREADS=4
CORS_ALLOW_ORIGIN="^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$"
```

#### **Step 3: Generate SSL Certificates**
Create and execute `scripts/generate-ssl.sh`:
```bash
#!/bin/bash
set -e
mkdir -p nginx/ssl
openssl req -x509 -newkey rsa:2048 -nodes \
    -keyout nginx/ssl/localhost.key \
    -out nginx/ssl/localhost.crt \
    -days 365 -subj "/CN=localhost"
openssl dhparam -out nginx/ssl/dhparam.pem 2048
chmod 600 nginx/ssl/localhost.key
chmod 644 nginx/ssl/localhost.crt nginx/ssl/dhparam.pem
```

Execute:
```bash
chmod +x scripts/generate-ssl.sh
./scripts/generate-ssl.sh
```

### **PHASE 2: SYMFONY APPLICATION**

#### **Step 4: Create Symfony Application**
```bash
cd /home/user/inf
composer create-project symfony/webapp app "7.3.*"
cd app
```

#### **Step 5: Install Core Packages**
```bash
# Core packages
composer require api-platform/api-pack symfony/uid
composer require symfony/ux-turbo symfony/stimulus-bundle symfony/ux-twig-component
composer require --dev symfony/maker-bundle

# Frontend dependencies via AssetMapper
php bin/console importmap:require bootstrap
php bin/console importmap:require bootstrap/dist/js/bootstrap.bundle.min.js
php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.min.css
```

#### **Step 6: Configure Database with UUIDv7**
Update `config/packages/doctrine.yaml`:
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

#### **Step 7: Create UUIDv7 Generator**
Create `src/Doctrine/UuidV7Generator.php`:
```php
<?php
declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class UuidV7Generator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, $entity): UuidV7
    {
        return Uuid::v7();
    }

    public function isPostInsertGenerator(): bool
    {
        return false;
    }
}
```

#### **Step 7a: Create Docker Directory Structure**
```bash
mkdir -p app/docker/frankenphp
```

### **PHASE 3: DATABASE ENTITIES**

#### **Step 8: Create Entity Models with UUIDv7**
Create `src/Entity/Organization.php`:
```php
<?php

namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use App\Repository\OrganizationRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Organization
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    public readonly Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    public string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: User::class)]
    public Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getUsers(): Collection { return $this->users; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

Create `src/Entity/User.php`:
```php
<?php

namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    public readonly Uuid $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    public string $name = '';

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    public ?Organization $organization = null;

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

    public function getId(): Uuid { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getOrganization(): ?Organization { return $this->organization; }
    public function setOrganization(?Organization $organization): self { $this->organization = $organization; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

#### **Step 9: Generate Repositories**
```bash
php bin/console make:entity --regenerate App --no-interaction
```

### **PHASE 4: CONTROLLERS AND ROUTES**

#### **Step 10: Create Controllers with Routes**
Generate base controllers:
```bash
php bin/console make:controller HomeController --no-interaction
php bin/console make:controller OrganizationController --no-interaction
php bin/console make:controller UserController --no-interaction
php bin/console make:controller HealthController --no-interaction
```

Update `src/Controller/HomeController.php`:
```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'title' => 'Welcome to Luminai',
        ]);
    }
}
```

Update `src/Controller/OrganizationController.php`:
```php
<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organization')]
class OrganizationController extends AbstractController
{
    #[Route('/', name: 'organization_index')]
    public function index(OrganizationRepository $repository): Response
    {
        return $this->render('organization/index.html.twig', [
            'organizations' => $repository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'organization_show')]
    public function show(Organization $organization): Response
    {
        return $this->render('organization/show.html.twig', [
            'organization' => $organization,
        ]);
    }
}
```

Update `src/Controller/UserController.php`:
```php
<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'user_index')]
    public function index(UserRepository $repository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $repository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'user_show')]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }
}
```

Update `src/Controller/HealthController.php`:
```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health')]
    public function check(): JsonResponse
    {
        return $this->json([
            'status' => 'OK',
            'timestamp' => new \DateTimeImmutable(),
            'version' => '1.0.0'
        ]);
    }
}
```

### **PHASE 5: FRONTEND ASSETS**

#### **Step 11: Create Frontend Assets**
Create `assets/app.js`:
```javascript
import { startStimulusApp } from '@symfony/stimulus-bridge';
import 'bootstrap';
import './styles/app.css';

// Start Stimulus application with automatic controller registration
startStimulusApp();
```

Create `assets/bootstrap.js`:
```javascript
// Stimulus controllers bootstrap file
// This file is automatically processed by the stimulus-bridge
export { Application } from '@hotwired/stimulus';
```


Create `assets/styles/app.css`:
```css
:root {
    --luminai-primary: #4f46e5;
    --luminai-secondary: #7c3aed;
}

.luminai-navbar {
    background: linear-gradient(135deg, var(--luminai-primary), var(--luminai-secondary));
}

.luminai-card {
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-radius: 0.75rem;
}

.luminai-btn-primary {
    background: linear-gradient(135deg, var(--luminai-primary), var(--luminai-secondary));
    border: none;
}
```

### **PHASE 6: TEMPLATES AND VIEWS**

#### **Step 12: Create Template Directories**
```bash
mkdir -p templates/{home,organization,user}
```

#### **Step 12a: Create Base Template**
Create `templates/base.html.twig`:
```twig
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{% block title %}Luminai{% endblock %}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {% block stylesheets %}{{ importmap('app') }}{% endblock %}
</head>
<body>
    <nav class="navbar navbar-expand-lg luminai-navbar">
        <div class="container">
            <a class="navbar-brand text-white" href="{{ path('app_home') }}">🚀 Luminai</a>
            <div class="navbar-nav">
                <a class="nav-link text-white" href="{{ path('app_home') }}">Home</a>
                <a class="nav-link text-white" href="{{ path('organization_index') }}">Organizations</a>
                <a class="nav-link text-white" href="{{ path('user_index') }}">Users</a>
                <a class="nav-link text-white" href="/api">API</a>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        {% for message in app.flashes('success') %}
            <div class="alert alert-success">{{ message }}</div>
        {% endfor %}
        {% block body %}{% endblock %}
    </main>

    {% block javascripts %}{% endblock %}
</body>
</html>
```

#### **Step 13: Create Page Templates**
Create `templates/home/index.html.twig`:
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ title }}{% endblock %}

{% block body %}
<div class="row">
    <div class="col-12">
        <div class="luminai-card p-4 mb-4">
            <h1 class="h2 mb-3">{{ title }}</h1>
            <p class="lead">Modern Symfony UX application with PostgreSQL 18 and UUIDv7 support.</p>
            <div class="row mt-4">
                <div class="col-md-6">
                    <a href="{{ path('organization_index') }}" class="btn luminai-btn-primary w-100 mb-2">
                        <i class="bi bi-building me-2"></i>Organizations
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ path('user_index') }}" class="btn luminai-btn-primary w-100 mb-2">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

Create `templates/organization/index.html.twig`:
```twig
{% extends 'base.html.twig' %}

{% block title %}Organizations{% endblock %}

{% block body %}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Organizations</h1>
    <a href="/api/organizations" class="btn btn-outline-primary">API</a>
</div>

<div class="row">
    {% for org in organizations %}
        <div class="col-md-6 mb-3">
            <div class="luminai-card p-3">
                <h5>{{ org.name }}</h5>
                {% if org.description %}
                    <p class="text-muted">{{ org.description }}</p>
                {% endif %}
                <small class="text-muted">{{ org.users|length }} users</small>
                <div class="mt-2">
                    <a href="{{ path('organization_show', {id: org.id}) }}" class="btn btn-sm btn-primary">View</a>
                </div>
            </div>
        </div>
    {% else %}
        <div class="col-12">
            <div class="alert alert-info">No organizations found.</div>
        </div>
    {% endfor %}
</div>
{% endblock %}
```

Create `templates/organization/show.html.twig`:
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ organization.name }}{% endblock %}

{% block body %}
<div class="mb-3">
    <a href="{{ path('organization_index') }}" class="btn btn-outline-secondary">← Back to Organizations</a>
</div>

<div class="luminai-card p-4">
    <h1>{{ organization.name }}</h1>
    {% if organization.description %}
        <p class="lead">{{ organization.description }}</p>
    {% endif %}

    <hr>

    <h3>Users ({{ organization.users|length }})</h3>
    {% if organization.users %}
        <div class="row">
            {% for user in organization.users %}
                <div class="col-md-4 mb-2">
                    <div class="card">
                        <div class="card-body">
                            <h6>{{ user.name }}</h6>
                            <small class="text-muted">{{ user.email }}</small>
                        </div>
                    </div>
                </div>
            {% endfor %}
        </div>
    {% else %}
        <div class="alert alert-info">No users in this organization.</div>
    {% endif %}
</div>
{% endblock %}
```

Create `templates/user/index.html.twig`:
```twig
{% extends 'base.html.twig' %}

{% block title %}Users{% endblock %}

{% block body %}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Users</h1>
    <a href="/api/users" class="btn btn-outline-primary">API</a>
</div>

<div class="row">
    {% for user in users %}
        <div class="col-md-6 mb-3">
            <div class="luminai-card p-3">
                <h5>{{ user.name }}</h5>
                <p class="text-muted">{{ user.email }}</p>
                {% if user.organization %}
                    <small class="text-muted">{{ user.organization.name }}</small>
                {% endif %}
                <div class="mt-2">
                    <a href="{{ path('user_show', {id: user.id}) }}" class="btn btn-sm btn-primary">View</a>
                </div>
            </div>
        </div>
    {% else %}
        <div class="col-12">
            <div class="alert alert-info">No users found.</div>
        </div>
    {% endfor %}
</div>
{% endblock %}
```

Create `templates/user/show.html.twig`:
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ user.name }}{% endblock %}

{% block body %}
<div class="mb-3">
    <a href="{{ path('user_index') }}" class="btn btn-outline-secondary">← Back to Users</a>
</div>

<div class="luminai-card p-4">
    <h1>{{ user.name }}</h1>
    <p class="lead">{{ user.email }}</p>

    {% if user.organization %}
        <hr>
        <h5>Organization</h5>
        <p><a href="{{ path('organization_show', {id: user.organization.id}) }}">{{ user.organization.name }}</a></p>
    {% endif %}

    <hr>
    <small class="text-muted">Created: {{ user.createdAt|date('Y-m-d H:i') }}</small>
</div>
{% endblock %}
```

### **PHASE 7: CONTAINERIZATION**

#### **Step 14: Create Application Dockerfile**
Create `app/Dockerfile`:
```dockerfile
FROM dunglas/frankenphp:1.9-php8.4-alpine

# Install dependencies
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo_pgsql opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for layer caching
COPY composer.json composer.lock symfony.lock ./

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-scripts --classmap-authoritative

# Copy application code
COPY . .

# Create required directories and set permissions
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/ \
    && chmod -R 755 var/

# Copy FrankenPHP configuration
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# Run final composer scripts
RUN composer run-script post-install-cmd

# Set user
USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:8000/health || exit 1

EXPOSE 8000
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
```

#### **Step 15: FrankenPHP Configuration**
Create `app/docker/frankenphp/Caddyfile`:
```caddyfile
{
    frankenphp {
        worker ./public/index.php
        num_threads 4
    }
    auto_https off
}

:8000 {
    root * public

    # Enable FrankenPHP
    php_server {
        resolve_root_symlink
    }

    # Security headers
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options SAMEORIGIN
        X-XSS-Protection "1; mode=block"
        Referrer-Policy "strict-origin-when-cross-origin"
    }

    # Asset optimization
    @static {
        path *.css *.js *.ico *.png *.jpg *.jpeg *.gif *.svg *.woff *.woff2 *.ttf *.eot
    }
    header @static {
        Cache-Control "public, max-age=31536000, immutable"
    }

    # Enable compression
    encode gzip

    # File server for static assets
    file_server

    # Logging
    log {
        level INFO
    }
}
```

#### **Step 16: Docker Compose Configuration**
Create `docker-compose.yml`:
```yaml
version: '3.8'

services:
  database:
    image: postgres:18-alpine
    container_name: luminai_database
    environment:
      POSTGRES_DB: ${POSTGRES_DB}
      POSTGRES_USER: ${POSTGRES_USER}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./database/init:/docker-entrypoint-initdb.d:ro
    ports:
      - "5432:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER} -d ${POSTGRES_DB}"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - luminai_network

  app:
    build: ./app
    container_name: luminai_app
    depends_on:
      database:
        condition: service_healthy
    environment:
      DATABASE_URL: ${DATABASE_URL}
      APP_ENV: ${APP_ENV}
      APP_SECRET: ${APP_SECRET}
      FRANKENPHP_NUM_THREADS: ${FRANKENPHP_NUM_THREADS}
    volumes:
      - ./app:/app
    ports:
      - "8000:8000"
    healthcheck:
      test: ["CMD", "wget", "--no-verbose", "--tries=1", "--spider", "http://localhost:8000/health"]
      interval: 30s
      timeout: 10s
      retries: 3
    networks:
      - luminai_network

  nginx:
    image: nginx:1.27-alpine
    container_name: luminai_nginx
    depends_on:
      app:
        condition: service_healthy
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/conf/default.conf:/etc/nginx/conf.d/default.conf:ro
      - ./nginx/ssl:/etc/nginx/ssl:ro
    networks:
      - luminai_network

volumes:
  postgres_data:

networks:
  luminai_network:
    driver: bridge
```

#### **Step 17: Nginx Configuration**
Create `nginx/conf/default.conf`:
```nginx
server {
    listen 80;
    server_name localhost;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name localhost;

    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/localhost.crt;
    ssl_certificate_key /etc/nginx/ssl/localhost.key;
    ssl_dhparam /etc/nginx/ssl/dhparam.pem;

    # Modern SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security headers
    add_header X-Frame-Options SAMEORIGIN always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Compression
    gzip on;
    gzip_vary on;
    gzip_types text/css text/javascript application/javascript application/json;

    # Main application
    location / {
        proxy_pass http://app:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
    }

    # Static assets with caching
    location ~* \.(css|js|ico|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$ {
        proxy_pass http://app:8000;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### **PHASE 8: DATABASE INITIALIZATION**

#### **Step 18: Database Initialization Script**
Create `database/init/01-luminai.sql`:
```sql
-- Luminai Database Initialization
-- PostgreSQL 18 with UUIDv7 Support

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Verify UUIDv7 support (PostgreSQL 18 feature)
DO $$
BEGIN
    PERFORM uuidv7();
    RAISE NOTICE 'PostgreSQL 18 UUIDv7 support confirmed ✅';
EXCEPTION WHEN OTHERS THEN
    RAISE EXCEPTION 'UUIDv7 not supported. Ensure PostgreSQL 18 is installed.';
END $$;

-- Performance optimizations for development
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET maintenance_work_mem = '64MB';

-- Apply configuration
SELECT pg_reload_conf();
```


### **PHASE 9: AUTOMATION AND DEPLOYMENT**

#### **Step 19: Setup Script**
Create `scripts/setup.sh`:
```bash
#!/bin/bash
set -e

echo "🚀 Setting up Luminai - Modern Symfony UX Application"
echo "================================================="

# Check requirements
command -v docker >/dev/null 2>&1 || { echo "❌ Docker is required"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "❌ Docker Compose is required"; exit 1; }

# Navigate to project root
cd "$(dirname "$0")/.."

# Generate SSL certificates
echo "🔐 Generating SSL certificates..."
chmod +x scripts/generate-ssl.sh
./scripts/generate-ssl.sh

# Verify .env file exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please create it first."
    exit 1
fi

# Start database first and wait for it to be ready
echo "🗄️ Starting database..."
docker-compose up -d database

echo "⏳ Waiting for database to be ready..."
timeout 60 bash -c 'until docker-compose exec -T database pg_isready -U ${POSTGRES_USER:-luminai_user} -d ${POSTGRES_DB:-luminai_db} >/dev/null 2>&1; do sleep 2; done'

# Build and start application
echo "🏗️ Building and starting application..."
docker-compose up --build -d app

echo "⏳ Waiting for application to be ready..."
timeout 120 bash -c 'until docker-compose exec -T app wget --no-verbose --tries=1 --spider http://localhost:8000/health >/dev/null 2>&1; do sleep 5; done'

# Setup database
echo "🗄️ Setting up database..."
docker-compose exec -T app php bin/console doctrine:database:create --if-not-exists
docker-compose exec -T app php bin/console make:migration --no-interaction
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction

# Start nginx
echo "🌐 Starting nginx..."
docker-compose up -d nginx

# Final check
echo "🏥 Running health checks..."
sleep 10
if curl -sf https://localhost/health >/dev/null 2>&1; then
    echo "✅ Setup completed successfully!"
    echo ""
    echo "🌐 Application URLs:"
    echo "   Frontend:  https://localhost"
    echo "   API:       https://localhost/api"
    echo "   Health:    https://localhost/health"
    echo ""
    echo "📋 Useful commands:"
    echo "   View logs:    docker-compose logs -f"
    echo "   Stop all:     docker-compose down"
    echo "   Enter app:    docker-compose exec app sh"
else
    echo "⚠️ Setup completed but health check failed"
    echo "Check logs: docker-compose logs"
fi
```

## **IMPLEMENTATION SUMMARY**

**Total Steps**: 19 steps across 9 phases
**Estimated Time**: 3-4 hours
**Technologies**: Symfony 7.3, PostgreSQL 18, FrankenPHP 1.9, API Platform 4.1

### **🚀 Quick Start**
```bash
cd /home/user/inf
chmod +x scripts/setup.sh
./scripts/setup.sh
```

### **🌐 Application URLs**
- **Frontend**: https://localhost
- **API Documentation**: https://localhost/api
- **Health Check**: https://localhost/health
- **Database**: localhost:5432

### **✅ Verification Checklist**
- [ ] https://localhost loads successfully with navigation
- [ ] Home page displays welcome message with Bootstrap Icons
- [ ] Organizations page shows empty state or test data
- [ ] Users page shows empty state or test data
- [ ] API documentation accessible at /api
- [ ] Health endpoint responds at /health
- [ ] All routes functional (no 404 errors)
- [ ] Bootstrap styling applied correctly
- [ ] Bootstrap Icons display properly

**Ready for implementation with corrected order of operations and current September 2025 standards.**