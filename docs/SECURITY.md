# SECURITY & RBAC GUIDE

Comprehensive security documentation for Luminai including Security Voters, authentication, account protection, and security best practices.

---

## Table of Contents

- [Security Voters (RBAC 2.0)](#security-voters-rbac-20)
- [API Token Authentication](#api-token-authentication)
- [Account Security Features](#account-security-features)
- [CSRF Protection](#csrf-protection)
- [Login Throttling](#login-throttling)
- [Remember Me Functionality](#remember-me-functionality)
- [Security Headers](#security-headers)
- [SSL/TLS Configuration](#ssltls-configuration)
- [Rate Limiting](#rate-limiting)
- [Security Monitoring](#security-monitoring)
- [Security Best Practices](#security-best-practices)

---

## Security Voters (RBAC 2.0)

Luminai uses **Symfony Security Voters** for granular, type-safe permission control at the entity level.

### **Overview**

**Security Voters** provide fine-grained access control beyond simple role checks:
- **Entity-level permissions**: `LIST`, `CREATE`, `VIEW`, `EDIT`, `DELETE`
- **Type-safe constants**: No magic strings
- **Reusable logic**: Permission logic in one place
- **Flexible decisions**: Based on user, entity, organization, ownership, etc.

### **Available Voters**

Luminai includes 4 comprehensive security voters:

| Voter | File | Entities | Permissions |
|-------|------|----------|-------------|
| OrganizationVoter | `/src/Security/Voter/OrganizationVoter.php` | Organization | LIST, CREATE, VIEW, EDIT, DELETE |
| UserVoter | `/src/Security/Voter/UserVoter.php` | User | LIST, CREATE, VIEW, EDIT, DELETE |
| CourseVoter | `/src/Security/Voter/CourseVoter.php` | Course | LIST, CREATE, VIEW, EDIT, DELETE, MANAGE_ENROLLMENTS |
| TreeFlowVoter | `/src/Security/Voter/TreeFlowVoter.php` | TreeFlow | LIST, CREATE, VIEW, EDIT, DELETE |

---

### **OrganizationVoter**

**File:** `/src/Security/Voter/OrganizationVoter.php`

**Permission Constants:**

```php
namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrganizationVoter extends Voter
{
    public const LIST = 'ORGANIZATION_LIST';
    public const CREATE = 'ORGANIZATION_CREATE';
    public const VIEW = 'ORGANIZATION_VIEW';
    public const EDIT = 'ORGANIZATION_EDIT';
    public const DELETE = 'ORGANIZATION_DELETE';

    // ... implementation
}
```

**Permission Logic:**

| Permission | Who Can Access |
|------------|----------------|
| `LIST` | ROLE_ADMIN, ROLE_SUPER_ADMIN |
| `CREATE` | ROLE_ADMIN, ROLE_SUPER_ADMIN |
| `VIEW` | ADMIN/SUPER_ADMIN (all orgs) OR user's own organization |
| `EDIT` | ADMIN/SUPER_ADMIN (all orgs) OR ORGANIZATION_ADMIN (own org only) |
| `DELETE` | ROLE_ADMIN, ROLE_SUPER_ADMIN |

**Usage in Controller:**

```php
use App\Security\Voter\OrganizationVoter;

class OrganizationController extends AbstractController
{
    #[Route('/organization', name: 'organization_index')]
    public function index(): Response
    {
        // Deny access unless user can list organizations
        $this->denyAccessUnlessGranted(OrganizationVoter::LIST);

        $organizations = $organizationRepository->findAll();
        return $this->render('organization/index.html.twig', [
            'organizations' => $organizations,
        ]);
    }

    #[Route('/organization/{id}', name: 'organization_show')]
    public function show(Organization $organization): Response
    {
        // Check permission on specific organization
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $organization);

        return $this->render('organization/show.html.twig', [
            'organization' => $organization,
        ]);
    }

    #[Route('/organization/{id}/edit', name: 'organization_edit')]
    public function edit(Organization $organization): Response
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::EDIT, $organization);

        // ... edit logic
    }

    #[Route('/organization/{id}/delete', name: 'organization_delete', methods: ['POST', 'DELETE'])]
    public function delete(Organization $organization): Response
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::DELETE, $organization);

        // ... delete logic
    }
}
```

**Usage in Templates:**

```twig
{# Check if user can create organizations #}
{% if is_granted(constant('App\\Security\\Voter\\OrganizationVoter::CREATE')) %}
    {{ button_create(path('organization_new'), 'organization.button.create', 'organization', null, 'Create Organization'|trans, 'ORGANIZATION_CREATE') }}
{% endif %}

{# Check if user can edit specific organization #}
{% if is_granted(constant('App\\Security\\Voter\\OrganizationVoter::EDIT'), organization) %}
    {{ button_edit(organization.id, path('organization_edit', {id: organization.id}), null, 'messages', 'Edit'|trans, null, 'ORGANIZATION_EDIT', 'sm') }}
{% endif %}
```

---

### **UserVoter**

**File:** `/src/Security/Voter/UserVoter.php`

**Permission Constants:**

```php
public const LIST = 'USER_LIST';
public const CREATE = 'USER_CREATE';
public const VIEW = 'USER_VIEW';
public const EDIT = 'USER_EDIT';
public const DELETE = 'USER_DELETE';
```

**Permission Logic:**

| Permission | Who Can Access |
|------------|----------------|
| `LIST` | ROLE_ADMIN, ROLE_SUPER_ADMIN |
| `CREATE` | ROLE_ADMIN, ROLE_SUPER_ADMIN |
| `VIEW` | ADMIN/SUPER_ADMIN (all users) OR self OR ORGANIZATION_ADMIN (same org) |
| `EDIT` | ADMIN/SUPER_ADMIN (all users) OR self OR ORGANIZATION_ADMIN (same org, non-admins only) |
| `DELETE` | ROLE_ADMIN, ROLE_SUPER_ADMIN (cannot delete self) |

**Key Feature - Cannot Delete Self:**

```php
protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
{
    $user = $token->getUser();

    if ($attribute === self::DELETE) {
        // Prevent self-deletion
        if ($subject->getId()->equals($user->getId())) {
            return false;
        }

        return $this->security->isGranted('ROLE_ADMIN');
    }

    // ...
}
```

**Usage:**

```php
use App\Security\Voter\UserVoter;

// In controller
$this->denyAccessUnlessGranted(UserVoter::LIST);
$this->denyAccessUnlessGranted(UserVoter::VIEW, $user);
$this->denyAccessUnlessGranted(UserVoter::EDIT, $user);
$this->denyAccessUnlessGranted(UserVoter::DELETE, $user);

// In template
{% if is_granted(constant('App\\Security\\Voter\\UserVoter::EDIT'), user) %}
    {# Show edit button #}
{% endif %}
```

---

### **CourseVoter**

**File:** `/src/Security/Voter/CourseVoter.php`

**Permission Constants:**

```php
public const LIST = 'COURSE_LIST';
public const CREATE = 'COURSE_CREATE';
public const VIEW = 'COURSE_VIEW';
public const EDIT = 'COURSE_EDIT';
public const DELETE = 'COURSE_DELETE';
public const MANAGE_ENROLLMENTS = 'COURSE_MANAGE_ENROLLMENTS';
```

**Permission Logic:**

| Permission | Who Can Access |
|------------|----------------|
| `LIST` | All authenticated users (organization-filtered) |
| `CREATE` | ROLE_ADMIN, ROLE_SUPER_ADMIN, ROLE_ORGANIZATION_ADMIN |
| `VIEW` | ADMIN/SUPER_ADMIN (all) OR ORGANIZATION_ADMIN/owner (same org) OR active courses |
| `EDIT` | ADMIN/SUPER_ADMIN (all) OR ORGANIZATION_ADMIN/owner (same org) |
| `DELETE` | ADMIN/SUPER_ADMIN (all) OR ORGANIZATION_ADMIN/owner (same org) |
| `MANAGE_ENROLLMENTS` | ROLE_ADMIN, ROLE_SUPER_ADMIN, ROLE_ORGANIZATION_ADMIN |

**Special Feature - Ownership Check:**

```php
protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
{
    $user = $token->getUser();

    if ($attribute === self::EDIT || $attribute === self::DELETE) {
        // Course owner can edit/delete
        if ($subject->getOwner()->getId()->equals($user->getId())) {
            return true;
        }

        // Organization admin can manage courses in their org
        if ($this->security->isGranted('ROLE_ORGANIZATION_ADMIN')) {
            return $subject->getOrganization()->getId()->equals($user->getOrganization()->getId());
        }

        // Global admin can manage all courses
        return $this->security->isGranted('ROLE_ADMIN');
    }

    // ...
}
```

**Usage:**

```php
use App\Security\Voter\CourseVoter;

// In controller
$this->denyAccessUnlessGranted(CourseVoter::LIST);
$this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);
$this->denyAccessUnlessGranted(CourseVoter::MANAGE_ENROLLMENTS, $course);

// In template
{% if is_granted(constant('App\\Security\\Voter\\CourseVoter::MANAGE_ENROLLMENTS'), course) %}
    {# Show enrollment management UI #}
{% endif %}
```

---

### **TreeFlowVoter**

**File:** `/src/Security/Voter/TreeFlowVoter.php`

**Permission Constants:**

```php
public const LIST = 'TREEFLOW_LIST';
public const CREATE = 'TREEFLOW_CREATE';
public const VIEW = 'TREEFLOW_VIEW';
public const EDIT = 'TREEFLOW_EDIT';
public const DELETE = 'TREEFLOW_DELETE';
```

**Permission Logic:**

| Permission | Who Can Access |
|------------|----------------|
| `LIST` | ROLE_ADMIN, ROLE_SUPER_ADMIN, ROLE_ORGANIZATION_ADMIN |
| `CREATE` | ROLE_ADMIN, ROLE_SUPER_ADMIN, ROLE_ORGANIZATION_ADMIN |
| `VIEW` | ADMIN/SUPER_ADMIN (all) OR ORGANIZATION_ADMIN (same org only) |
| `EDIT` | ADMIN/SUPER_ADMIN (all) OR ORGANIZATION_ADMIN (same org only) |
| `DELETE` | ROLE_ADMIN, ROLE_SUPER_ADMIN, ROLE_ORGANIZATION_ADMIN (same org) |

**Usage:**

```php
use App\Security\Voter\TreeFlowVoter;

// In controller
$this->denyAccessUnlessGranted(TreeFlowVoter::LIST);
$this->denyAccessUnlessGranted(TreeFlowVoter::VIEW, $treeFlow);
$this->denyAccessUnlessGranted(TreeFlowVoter::EDIT, $treeFlow);

// In template
{% if is_granted(constant('App\\Security\\Voter\\TreeFlowVoter::EDIT'), treeflow) %}
    {# Show canvas editor #}
{% endif %}
```

---

### **Adding a New Voter**

**Step 1: Generate Voter**

```bash
php bin/console make:voter MyEntityVoter
```

**Step 2: Define Permission Constants**

```php
<?php
namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MyEntityVoter extends Voter
{
    public const LIST = 'MY_ENTITY_LIST';
    public const CREATE = 'MY_ENTITY_CREATE';
    public const VIEW = 'MY_ENTITY_VIEW';
    public const EDIT = 'MY_ENTITY_EDIT';
    public const DELETE = 'MY_ENTITY_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::LIST,
            self::CREATE,
            self::VIEW,
            self::EDIT,
            self::DELETE,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::LIST:
            case self::CREATE:
                return $this->security->isGranted('ROLE_ADMIN');

            case self::VIEW:
            case self::EDIT:
            case self::DELETE:
                // Check ownership, organization, etc.
                return $this->canManage($user, $subject);
        }

        return false;
    }

    private function canManage(User $user, MyEntity $entity): bool
    {
        // Custom permission logic
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // Organization-based access
        if ($this->security->isGranted('ROLE_ORGANIZATION_ADMIN')) {
            return $entity->getOrganization()->getId()->equals($user->getOrganization()->getId());
        }

        return false;
    }
}
```

**Step 3: Use in Controllers**

```php
use App\Security\Voter\MyEntityVoter;

$this->denyAccessUnlessGranted(MyEntityVoter::VIEW, $myEntity);
```

---

## API Token Authentication

Stateless bearer token authentication for API endpoints.

### **Architecture**

```
┌──────────────────────────────────┐
│ Request: Authorization: Bearer TOKEN │
└────────────────┬─────────────────┘
                 │
                 ▼
┌──────────────────────────────────┐
│ ApiTokenAuthenticator            │
│ - Extract token from header      │
│ - Find user by token             │
│ - Validate expiration            │
│ - Check user verified/not locked │
└────────────────┬─────────────────┘
                 │
                 ▼
┌──────────────────────────────────┐
│ Grant access or return 401       │
└──────────────────────────────────┘
```

### **Configuration**

**File:** `config/packages/security.yaml`

```yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - App\Security\ApiTokenAuthenticator
```

### **ApiTokenAuthenticator Implementation**

**File:** `/src/Security/ApiTokenAuthenticator.php`

```php
public function authenticate(Request $request): Passport
{
    $token = $this->extractToken($request);

    if (!$token) {
        throw new CustomUserMessageAuthenticationException('No API token provided');
    }

    $user = $this->userRepository->findOneBy(['apiToken' => $token]);

    if (!$user) {
        throw new CustomUserMessageAuthenticationException('Invalid API token');
    }

    if (!$user->isApiTokenValid()) {
        throw new CustomUserMessageAuthenticationException('API token expired');
    }

    if (!$user->isVerified()) {
        throw new CustomUserMessageAuthenticationException('User not verified');
    }

    if ($user->isLocked()) {
        throw new CustomUserMessageAuthenticationException('User account locked');
    }

    return new SelfValidatingPassport(
        new UserBadge($user->getUserIdentifier())
    );
}
```

### **Token Management (User Entity)**

**File:** `/src/Entity/User.php`

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $apiTokenExpiresAt = null;

    public function generateApiToken(int $validityDays = 30): void
    {
        $this->apiToken = bin2hex(random_bytes(32)); // 64-char hex string
        $this->apiTokenExpiresAt = new \DateTimeImmutable("+{$validityDays} days");
    }

    public function isApiTokenValid(): bool
    {
        if (!$this->apiToken) {
            return false;
        }

        if (!$this->apiTokenExpiresAt) {
            return false;
        }

        return $this->apiTokenExpiresAt > new \DateTimeImmutable();
    }

    public function revokeApiToken(): void
    {
        $this->apiToken = null;
        $this->apiTokenExpiresAt = null;
    }
}
```

### **Token Generation UI**

**Controller:** `/src/Controller/ApiTokenController.php`

**Routes:**
- `GET /api-tokens` - View token status
- `POST /api-tokens/generate` - Generate new 30-day token
- `POST /api-tokens/revoke` - Revoke token

**Usage:**

```php
#[Route('/api-tokens/generate', name: 'api_token_generate', methods: ['POST'])]
public function generate(Request $request): Response
{
    // CSRF protection
    if (!$this->isCsrfTokenValid('generate_token', $request->request->get('_token'))) {
        throw new InvalidCsrfTokenException();
    }

    $user = $this->getUser();
    $user->generateApiToken(30);

    $this->entityManager->flush();

    $this->addFlash('success', $this->translator->trans('token.flash.generated', [], 'token'));

    return $this->redirectToRoute('api_token_index');
}
```

### **Using API Tokens**

```bash
# Generate token via UI at /api-tokens
# Then use in requests:

curl -H "Authorization: Bearer YOUR_64_CHAR_TOKEN_HERE" \
     https://localhost/api/endpoint
```

---

## Account Security Features

Comprehensive account protection against brute force and unauthorized access.

### **Account Locking**

**Automatic Lock After Failed Logins:**

```php
// File: src/Entity/User.php

#[ORM\Column]
private int $failedLoginAttempts = 0;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
private ?\DateTimeImmutable $lockedUntil = null;

public function incrementFailedLoginAttempts(): void
{
    $this->failedLoginAttempts++;

    // Lock account after 5 failed attempts
    if ($this->failedLoginAttempts >= 5) {
        $this->lock(15); // Lock for 15 minutes
    }
}

public function lock(int $minutes = 15): void
{
    $this->lockedUntil = new \DateTimeImmutable("+{$minutes} minutes");
}

public function unlock(): void
{
    $this->lockedUntil = null;
    $this->failedLoginAttempts = 0;
}

public function isLocked(): bool
{
    if (!$this->lockedUntil) {
        return false;
    }

    // Auto-unlock if lock period expired
    if ($this->lockedUntil < new \DateTimeImmutable()) {
        $this->unlock();
        return false;
    }

    return true;
}

public function resetFailedLoginAttempts(): void
{
    $this->failedLoginAttempts = 0;
    $this->lockedUntil = null;
}
```

**Usage in Authenticator:**

```php
public function authenticate(Request $request): Passport
{
    // ... load user

    if ($user->isLocked()) {
        throw new CustomUserMessageAuthenticationException(
            'Account locked. Try again later.'
        );
    }

    return new Passport(
        new UserBadge($email),
        new PasswordCredentials($password),
        [new CsrfTokenBadge('authenticate', $csrfToken)]
    );
}

public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
    $user = $token->getUser();
    $user->resetFailedLoginAttempts();
    $user->updateLastLogin();
    $this->entityManager->flush();

    // ... redirect
}

public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
{
    $email = $request->request->get('email');
    $user = $this->userRepository->findOneBy(['email' => $email]);

    if ($user) {
        $user->incrementFailedLoginAttempts();
        $this->entityManager->flush();
    }

    // ... show error
}
```

### **Email Verification**

```php
#[ORM\Column]
private bool $isVerified = false;

#[ORM\Column(length: 255, nullable: true)]
private ?string $verificationToken = null;

public function generateVerificationToken(): string
{
    $this->verificationToken = bin2hex(random_bytes(32));
    return $this->verificationToken;
}

public function verify(): void
{
    $this->isVerified = true;
    $this->verificationToken = null;
}
```

### **Last Login Tracking**

```php
#[ORM\Column(type: 'datetime_immutable', nullable: true)]
private ?\DateTimeImmutable $lastLoginAt = null;

public function updateLastLogin(): void
{
    $this->lastLoginAt = new \DateTimeImmutable();
}
```

---

## CSRF Protection

**Automatic in Forms:**

```twig
{{ form_start(form) }}
    {# CSRF token automatically included #}
    {{ form_widget(form) }}
{{ form_end(form) }}
```

**Manual CSRF for AJAX/API:**

```php
// Controller
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

public function delete(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
{
    $token = $request->request->get('_token');

    if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_item', $token))) {
        throw new InvalidCsrfTokenException();
    }

    // Proceed with deletion
}
```

```html
<!-- In template -->
<form method="post" action="{{ path('item_delete', {id: item.id}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('delete_item') }}">
    <button type="submit">Delete</button>
</form>
```

---

## Login Throttling

**Configuration:**

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            login_throttling:
                max_attempts: 5          # Maximum attempts
                interval: '15 minutes'   # Within this time period
```

**Behavior:**
- After 5 failed login attempts within 15 minutes, user is temporarily blocked
- IP-based throttling (not user-based)
- Automatic unblock after interval expires

---

## Remember Me Functionality

**Configuration:**

```yaml
# config/packages/security.yaml
security:
    firewalls:
        main:
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800  # 1 week in seconds
                path: /
                always_remember_me: false
```

**Usage:**

```twig
{{ form_start(form) }}
    {{ form_row(form.email) }}
    {{ form_row(form.password) }}

    <div class="form-check">
        <input type="checkbox" name="_remember_me" id="remember_me" class="form-check-input">
        <label for="remember_me" class="form-check-label">Remember me</label>
    </div>

    <button type="submit">Login</button>
{{ form_end(form) }}
```

---

## Security Headers

**Nginx Configuration:**

**File:** `/nginx/conf/default.conf`

```nginx
# Security Headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# TODO: Add these for enhanced security
# add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
# add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
```

---

## SSL/TLS Configuration

**Nginx TLS Settings:**

```nginx
# SSL Configuration
ssl_protocols TLSv1.2 TLSv1.3;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;

# DH parameters for forward secrecy
ssl_dhparam /etc/nginx/ssl/dhparam.pem;

# HTTP to HTTPS redirect
server {
    listen 80;
    server_name localhost *.localhost;
    return 301 https://$host$request_uri;
}
```

---

## Rate Limiting

**Status:** ⚠️ Configured but requires `symfony/lock` package

**Configuration:** `config/packages/rate_limiter.yaml`

```yaml
framework:
    rate_limiter:
        auth_login:
            policy: 'sliding_window'
            limit: 5
            interval: '15 minutes'
            storage_service: 'cache.app'

        auth_register:
            policy: 'sliding_window'
            limit: 3
            interval: '1 hour'
            storage_service: 'cache.app'

        api_anonymous:
            policy: 'sliding_window'
            limit: 100
            interval: '1 hour'
            storage_service: 'cache.app'

        api_authenticated:
            policy: 'sliding_window'
            limit: 1000
            interval: '1 hour'
            storage_service: 'cache.app'
```

**To Enable:**

```bash
composer require symfony/lock
```

**Usage in Controllers:**

```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

public function __construct(
    private readonly RateLimiterFactory $apiAnonymousLimiter
) {}

public function apiEndpoint(Request $request): Response
{
    $limiter = $this->apiAnonymousLimiter->create($request->getClientIp());

    if (!$limiter->consume(1)->isAccepted()) {
        throw new TooManyRequestsHttpException('Rate limit exceeded');
    }

    // Process request
}
```

---

## Security Monitoring

**View Security Logs:**

```bash
# Real-time security events
docker-compose exec app tail -f var/log/security.log | jq .

# Failed login attempts
docker-compose exec app grep -i "authentication failure" var/log/security.log | jq .

# Account lockouts
docker-compose exec app grep -i "account locked" var/log/security.log | jq .
```

**Monitor Attack Attempts:**

```bash
# SQL injection attempts
docker-compose logs -f app | grep -i "sql.*injection"

# XSS attempts
docker-compose logs -f app | grep -i "xss\|script.*alert"

# Command injection
docker-compose logs -f app | grep -i "cmd\|shell\|exec"
```

---

## Security Best Practices

### **1. Always Use Voters for Complex Permissions**

❌ **Bad:**
```php
if ($this->isGranted('ROLE_ADMIN')) {
    // Too broad
}
```

✅ **Good:**
```php
$this->denyAccessUnlessGranted(UserVoter::EDIT, $user);
```

### **2. Never Trust User Input**

✅ **Always sanitize and validate:**
```php
use Symfony\Component\Validator\Constraints as Assert;

class MyDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[Assert\Regex('/^[a-zA-Z0-9\s\-]+$/')]
    public string $name;
}
```

### **3. Use Parameterized Queries**

✅ **Doctrine handles this automatically:**
```php
$qb = $repository->createQueryBuilder('u')
    ->where('u.email = :email')
    ->setParameter('email', $userInput);  // Safe from SQL injection
```

### **4. Hash Passwords Properly**

✅ **Symfony handles this automatically:**
```php
// In User entity
public function getPassword(): string
{
    return $this->password;  // Hashed with Argon2id
}
```

### **5. Enable HTTPS Everywhere**

```nginx
# Redirect all HTTP to HTTPS
server {
    listen 80;
    return 301 https://$host$request_uri;
}
```

### **6. Regularly Update Dependencies**

```bash
# Check for security vulnerabilities
composer audit

# Update dependencies
composer update

# Check for outdated packages
composer outdated
```

### **7. Monitor Security Logs**

```bash
# Daily security review
docker-compose exec app tail -100 var/log/security.log | jq .

# Setup alerts for critical events
# (integrate with monitoring system)
```

### **8. Use Environment Variables for Secrets**

✅ **Never commit secrets to Git:**
```bash
# .env.local (not in Git)
DATABASE_PASSWORD=SecurePassword123!
APP_SECRET=RandomSecret456!
AUDIT_ENCRYPTION_KEY=EncryptionKey789!
```

---

## Quick Reference

### **Security Checklist**

- [ ] Security Voters implemented for all entities
- [ ] CSRF protection enabled on all forms
- [ ] Login throttling configured
- [ ] Account locking after 5 failed attempts
- [ ] API tokens expire after 30 days
- [ ] SSL/TLS certificates valid
- [ ] Security headers configured
- [ ] Rate limiting enabled (requires symfony/lock)
- [ ] Security logs monitored
- [ ] Regular `composer audit` runs
- [ ] No secrets in Git repository
- [ ] Password hashing with Argon2id
- [ ] Email verification implemented

### **Common Commands**

```bash
# Security audit
composer audit

# Check Security Voters
php bin/console debug:voter

# View firewall configuration
php bin/console debug:firewall

# Test authentication
php bin/console security:check

# View security logs
docker-compose exec app tail -f var/log/security.log | jq .
```

---

**For more information:**
- [Audit & Compliance System](AUDIT_SYSTEM.md)
- [Multi-Tenant Architecture](MULTI_TENANT.md)
- [Navigation & RBAC](NAVIGATION_RBAC.md)
