# Navigation Menu RBAC System

## Overview

This document describes the centralized Role-Based Access Control (RBAC) system for navigation menus, implemented following Symfony 2025 best practices.

## Architecture

### Single Source of Truth

The navigation system uses **NavigationConfig** (`src/Service/NavigationConfig.php`) as the single source of truth for:
- Menu structure and hierarchy
- Required permissions for each menu item
- Route names, icons, and translation labels
- Visibility rules based on roles and permissions

### Key Components

1. **NavigationConfig Service** (`src/Service/NavigationConfig.php`) - Centralized menu configuration
2. **MenuExtension** (`src/Twig/MenuExtension.php`) - Twig extension for rendering menus with automatic permission checks
3. **Menu Templates** (`templates/_partials/menu/`) - Reusable Twig templates for menu rendering
   - `_menu_item.html.twig` - Individual menu item template
   - `_menu_divider.html.twig` - Divider and section title template
   - `_main_menu.html.twig` - Main navigation menu template
   - `_user_menu.html.twig` - User profile menu template
4. **Security Voters** - Define granular permissions (OrganizationVoter, UserVoter, CourseVoter, TreeFlowVoter)
5. **Role Hierarchy** - Defined in `config/packages/security.yaml`

## Permission Checking Flow

```
User clicks menu → Twig renders menu → MenuExtension filters items → NavigationConfig checks permissions → Security Voters evaluate → Menu item visible/hidden
```

## Adding a New Menu Item

### Step 1: Add to NavigationConfig

Edit `src/Service/NavigationConfig.php`:

```php
public function getMainMenu(): array
{
    return [
        // ... existing items ...
        'my_new_feature' => [
            'label' => 'nav.my.feature',           // Translation key
            'route' => 'my_feature_index',         // Route name
            'icon' => 'bi-star',                   // Bootstrap Icon class
            'permission' => MyFeatureVoter::LIST,  // Required permission (optional)
            'role' => 'ROLE_ADMIN',                // Required role (optional)
        ],
    ];
}
```

### Step 2: Create/Update Voter (if using custom permission)

Create or update a voter in `src/Security/Voter/`:

```php
final class MyFeatureVoter extends Voter
{
    public const LIST = 'MYFEATURE_LIST';
    public const CREATE = 'MYFEATURE_CREATE';
    public const VIEW = 'MYFEATURE_VIEW';
    public const EDIT = 'MYFEATURE_EDIT';
    public const DELETE = 'MYFEATURE_DELETE';

    // ... implement supports() and voteOnAttribute() ...
}
```

### Step 3: Add Translation

Add translation key to `translations/en/messages.en.yaml`:

```yaml
nav.my.feature: My Feature
```

### Step 4: Clear Cache

```bash
docker-compose exec -T app php bin/console cache:clear
```

### Step 5: Test

The menu item will automatically appear/disappear based on user permissions. No changes needed to templates!

## Customizing Menu Templates

Menu rendering is handled by reusable Twig templates in `templates/_partials/menu/`:

### Menu Item Template (`_menu_item.html.twig`)

Customize how individual menu items are rendered:

```twig
<li>
    <a class="dropdown-item{{ css_class is defined and css_class ? ' ' ~ css_class : '' }}"
       href="{{ path(route) }}"
       {{ id is defined and id ? 'id="' ~ id ~ '"' : '' }}>
        <i class="bi {{ icon }} me-2"></i>{{ label|trans({}, translation_domain|default('messages')) }}
    </a>
</li>
```

### Menu Divider Template (`_menu_divider.html.twig`)

Customize dividers and section titles:

```twig
<li>
    <hr class="dropdown-divider" style="border-color: rgba(255, 255, 255, 0.1);">
</li>
{% if section_title is defined and section_title %}
<li class="px-3 py-1">
    <small class="text-muted text-uppercase">{{ section_title|trans }}</small>
</li>
{% endif %}
```

### Benefits of Template-Based Rendering

✅ **Separation of Concerns** - Logic in PHP, presentation in Twig
✅ **Easy Customization** - Modify templates without touching PHP code
✅ **Reusable Components** - Same templates used across all menus
✅ **Designer-Friendly** - Frontend developers can modify without PHP knowledge
✅ **Better Testing** - Template rendering can be tested independently

## Permission Strategies

### Role-Based Access (Simple)

Use when permission is based solely on user role:

```php
'admin_users' => [
    'label' => 'nav.users',
    'route' => 'user_index',
    'icon' => 'bi-people',
    'role' => 'ROLE_ADMIN',  // Only ROLE_ADMIN can see this
],
```

### Attribute-Based Access (Voter)

Use for complex permissions (e.g., organization-scoped):

```php
'organizations' => [
    'label' => 'nav.organizations',
    'route' => 'organization_index',
    'icon' => 'bi-building',
    'permission' => OrganizationVoter::LIST,  // Uses voter logic
],
```

### Combined (Role + Permission)

Use both for granular control:

```php
'advanced_feature' => [
    'label' => 'nav.advanced',
    'route' => 'advanced_index',
    'icon' => 'bi-lightning',
    'role' => 'ROLE_ADMIN',                    // Must be admin
    'permission' => AdvancedVoter::ACCESS,     // AND must have specific permission
],
```

### Public (No Permission)

Omit both `role` and `permission` for items visible to all authenticated users:

```php
'home' => [
    'label' => 'nav.home',
    'route' => 'app_home',
    'icon' => 'bi-house',
    // No permission required - all authenticated users can access
],
```

## Menu Sections and Dividers

### Section Divider with Title

```php
'admin_section_divider' => [
    'divider_before' => true,
    'section_title' => 'nav.admin.section',
    'role' => 'ROLE_ADMIN',  // Only visible if user has role
],
```

### Simple Divider

```php
'logout_divider' => [
    'divider_before' => true,
],
```

## Voter Best Practices

### 1. Use Constants for Permissions

```php
public const LIST = 'ENTITY_LIST';
public const CREATE = 'ENTITY_CREATE';
public const VIEW = 'ENTITY_VIEW';
public const EDIT = 'ENTITY_EDIT';
public const DELETE = 'ENTITY_DELETE';
```

### 2. Implement CacheableVoterInterface

Extend Symfony's `Voter` class (already implements CacheableVoterInterface):

```php
final class MyVoter extends Voter
{
    // Automatically cacheable for performance
}
```

### 3. Support NULL Subjects for LIST/CREATE

```php
protected function supports(string $attribute, mixed $subject): bool
{
    if (!in_array($attribute, [self::LIST, self::CREATE, self::VIEW, self::EDIT, self::DELETE])) {
        return false;
    }

    // For CREATE and LIST, subject can be null (not tied to specific entity)
    if (in_array($attribute, [self::CREATE, self::LIST])) {
        return true;
    }

    // For VIEW, EDIT, DELETE, subject must be an Entity
    return $subject instanceof MyEntity;
}
```

### 4. Use Role Hierarchy

Define in `config/packages/security.yaml`:

```yaml
role_hierarchy:
    ROLE_ADMIN: [ROLE_USER]
    ROLE_SUPER_ADMIN: [ROLE_ADMIN]
```

This means ROLE_ADMIN automatically has ROLE_USER permissions.

## Testing Permissions

### In Controller

```php
$this->denyAccessUnlessGranted(MyFeatureVoter::VIEW, $entity);
```

### In Twig Template

```twig
{% if is_granted(constant('App\\Security\\Voter\\MyFeatureVoter::VIEW'), entity) %}
    <a href="{{ path('my_feature_show', {id: entity.id}) }}">View</a>
{% endif %}
```

### In Service

```php
if ($this->authorizationChecker->isGranted(MyFeatureVoter::VIEW, $entity)) {
    // User can view entity
}
```

## Benefits of This Approach

✅ **Single Source of Truth** - All menu configuration in one place
✅ **Automatic Permission Filtering** - No manual `is_granted()` checks in templates
✅ **Type-Safe** - Uses voter constants instead of magic strings
✅ **Reusable** - Same voters used in controllers, templates, and services
✅ **Maintainable** - Easy to add/remove menu items without touching templates
✅ **Testable** - Permission logic centralized in voters
✅ **Performance** - Voters implement CacheableVoterInterface
✅ **Symfony 2025 Best Practice** - Follows official Symfony recommendations

## Common Patterns

### Menu Item Only for Organization Admins

```php
'org_settings' => [
    'label' => 'nav.org.settings',
    'route' => 'organization_settings',
    'icon' => 'bi-gear',
    'role' => 'ROLE_ORGANIZATION_ADMIN',
],
```

### Menu Item with Complex Organization-Scoped Logic

```php
'org_users' => [
    'label' => 'nav.org.users',
    'route' => 'organization_users',
    'icon' => 'bi-people',
    'permission' => OrganizationVoter::MANAGE_USERS,  // Voter checks org scope
],
```

### Different Menus for Different User Types

Main menu items are automatically filtered based on permissions. No need for separate menus!

## Migration from Old System

**Before (manual permission checks):**
```twig
{% if is_granted('ROLE_ADMIN') %}
    <li><a href="{{ path('admin_users') }}">Users</a></li>
{% endif %}
```

**After (automatic filtering):**
```php
// In NavigationConfig.php
'users' => [
    'label' => 'nav.users',
    'route' => 'user_index',
    'icon' => 'bi-people',
    'permission' => UserVoter::LIST,
],

// In template - automatically filtered!
{{ render_main_menu()|raw }}
```

## Troubleshooting

### Menu item not appearing

1. Check if user has required role/permission
2. Verify voter is registered (auto-registered if extends Voter)
3. Clear cache: `php bin/console cache:clear`
4. Check voter logic with debug: `php bin/console debug:security`

### Permission always denied

1. Check role hierarchy in `security.yaml`
2. Verify voter `supports()` method returns true
3. Check voter `voteOnAttribute()` logic
4. Use Symfony 7.3+ explain feature to debug voter decisions

## Examples from Codebase

### Organizations Menu (Voter-Based)

```php
'organizations' => [
    'label' => 'nav.organizations',
    'route' => 'organization_index',
    'icon' => 'bi-building',
    'permission' => OrganizationVoter::LIST,  // Only ADMIN/SUPER_ADMIN
],
```

### Student Courses (All Users)

```php
'student_courses' => [
    'label' => 'nav.my.courses',
    'route' => 'student_courses',
    'icon' => 'bi-mortarboard',
    // No permission - all authenticated users can see their courses
],
```

### Admin Section (Role-Based)

```php
'admin_section_divider' => [
    'divider_before' => true,
    'section_title' => 'nav.admin.section',
    'role' => 'ROLE_ADMIN',  // Divider only shows for admins
],
'admin_audit' => [
    'label' => 'nav.admin.audit.log',
    'route' => 'admin_audit_index',
    'icon' => 'bi-clipboard-data',
    'role' => 'ROLE_ADMIN',
],
```

## References

- [Symfony Security Voters Documentation](https://symfony.com/doc/current/security/voters.html)
- [Symfony 7.3 Voter Debugging](https://symfony.com/blog/new-in-symfony-7-3-explaining-security-voter-decisions)
- [API Platform Security](https://api-platform.com/docs/symfony/security/)
