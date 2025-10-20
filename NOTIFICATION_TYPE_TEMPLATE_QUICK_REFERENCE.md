# NotificationTypeTemplate - Quick Reference

**Status:** COMPLETE - Ready for Code Generation
**Last Updated:** 2025-10-19
**Total Properties:** 19 (increased from 2)

---

## Summary

The **NotificationTypeTemplate** entity has been completely redesigned to support modern CRM notification template functionality with:

- Full content management (subject, HTML body, plain text, headers, footers)
- Placeholder/variable system support
- Priority and category configuration
- Multi-channel delivery options
- Active/inactive status control
- Default template selection
- Visual metadata (icons, colors, tags)

---

## Property List (19 Total)

### Core Content (8 properties)
1. **name** - string(255), required - Template name
2. **description** - text, optional - Help text for template usage
3. **subject** - string(500), required - Email/notification subject with placeholders
4. **bodyHtml** - text, optional - HTML version of notification body
5. **bodyPlainText** - text, optional - Plain text version
6. **headerContent** - text, optional - Optional header content
7. **footerContent** - text, optional - Optional footer content
8. **replyToEmail** - string(255), optional - Reply-to email address

### Configuration (4 properties)
9. **notificationType** - ManyToOne → NotificationType, required
10. **communicationMethod** - ManyToOne → CommunicationMethod, optional
11. **priority** - string(50), optional - Choice: information|warning|critical
12. **category** - string(100), optional - Template category for grouping

### Placeholder System (2 properties)
13. **availablePlaceholders** - json, optional - Array of available placeholders
14. **placeholderExamples** - text, optional - Example usage of placeholders

### Status & Control (2 properties)
15. **active** - boolean, default=true - Enable/disable template
16. **default** - boolean, default=false - Mark as default template

### Visual Metadata (3 properties)
17. **iconUrl** - string(255), optional - Icon URL for UI
18. **colorCode** - string(7), optional - Hex color (#RRGGBB)
19. **tags** - json, optional - Searchable tags

---

## Relationships

### Owned By NotificationTypeTemplate
- **notificationType** → NotificationType (ManyToOne)
- **communicationMethod** → CommunicationMethod (ManyToOne)

### Inverse Relationships Added
- **NotificationType.notificationTypeTemplates** (OneToMany)
- **CommunicationMethod.notificationTypeTemplates** (OneToMany)

---

## Validation Rules

| Field | Validation |
|-------|------------|
| name | NotBlank, Length(max=255) |
| description | Length(max=2000) |
| subject | NotBlank, Length(max=500) |
| bodyHtml | Length(max=10000) |
| bodyPlainText | Length(max=10000) |
| headerContent | Length(max=2000) |
| footerContent | Length(max=2000) |
| replyToEmail | Email format |
| notificationType | NotBlank |
| priority | Choice(information, warning, critical) |
| category | Length(max=100) |
| iconUrl | Url, Length(max=255) |
| colorCode | Regex(/^#[0-9A-Fa-f]{6}$/) |

---

## Boolean Naming Convention

✓ **CORRECT (Luminai Convention):**
```php
private bool $active = true;
private bool $default = false;
```

✗ **WRONG:**
```php
private bool $isActive = true;
private bool $isDefault = false;
```

---

## API Configuration

**Endpoint:** `/api/notification_type_templates`

**Operations:** GetCollection, Get, Post, Put, Delete

**Security:** `is_granted('ROLE_SUPER_ADMIN')`

**Serialization Groups:**
- Read: `notificationtypetemplate:read`
- Write: `notificationtypetemplate:write`

**All 19 properties are API-enabled**

---

## Priority Levels

| Level | Use Case |
|-------|----------|
| **information** | General updates, FYI notifications |
| **warning** | Needs attention soon |
| **critical** | Requires immediate action |

---

## Placeholder System

### Syntax
```
Subject: Welcome to {organization_name}, {user_name}!
Body: Your event "{event_title}" starts at {event_start_time} on {event_date}.
```

### Example Placeholders
- `{user_name}` - Recipient name
- `{organization_name}` - Organization name
- `{event_title}` - Event title
- `{event_start_time}` - Event start time
- `{event_date}` - Event date
- `{case_number}` - Case number
- `{priority}` - Priority level
- `{due_date}` - Due date

### Storage
Stored in `availablePlaceholders` as JSON array:
```json
["user_name", "organization_name", "event_title", "event_start_time"]
```

---

## Form Widgets

| Field | Widget | Options |
|-------|--------|---------|
| name | TextType | - |
| description | TextareaType | - |
| subject | TextType | - |
| bodyHtml | TextareaType | rows: 10 |
| bodyPlainText | TextareaType | rows: 10 |
| headerContent | TextareaType | - |
| footerContent | TextareaType | - |
| replyToEmail | EmailType | - |
| notificationType | EntityType | - |
| communicationMethod | EntityType | - |
| priority | ChoiceType | information/warning/critical |
| category | TextType | - |
| availablePlaceholders | TextareaType | rows: 5 |
| placeholderExamples | TextareaType | - |
| active | CheckboxType | default: true |
| default | CheckboxType | - |
| iconUrl | TextType | - |
| colorCode | ColorType | - |
| tags | TextareaType | - |

---

## Database Schema

### Table: notification_type_template

**Primary Key:** id (UUID v7)

**Foreign Keys:**
- notification_type_id → notification_type(id)
- communication_method_id → communication_method(id)

**Indexes:**
- idx_notification_type_template_active (active)
- idx_notification_type_template_type (notification_type_id)
- idx_notification_type_template_priority (priority)
- idx_notification_type_template_name (name)
- idx_notification_type_template_active_type (active, notification_type_id)

**Default Values:**
- active: true
- default: false

---

## Common Queries

### Find Active Templates by Type
```php
$templates = $repository->createQueryBuilder('ntt')
    ->where('ntt.active = :active')
    ->andWhere('ntt.notificationType = :type')
    ->setParameter('active', true)
    ->setParameter('type', $notificationType)
    ->orderBy('ntt.name', 'ASC')
    ->getQuery()
    ->getResult();
```

### Find Default Template
```php
$template = $repository->createQueryBuilder('ntt')
    ->where('ntt.active = :active')
    ->andWhere('ntt.default = :default')
    ->andWhere('ntt.notificationType = :type')
    ->setParameter('active', true)
    ->setParameter('default', true)
    ->setParameter('type', $notificationType)
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();
```

### Search Templates
```php
$templates = $repository->createQueryBuilder('ntt')
    ->where('ntt.active = :active')
    ->andWhere('LOWER(ntt.name) LIKE :search OR LOWER(ntt.subject) LIKE :search')
    ->setParameter('active', true)
    ->setParameter('search', '%' . strtolower($searchTerm) . '%')
    ->orderBy('ntt.priority', 'DESC')
    ->addOrderBy('ntt.name', 'ASC')
    ->getQuery()
    ->getResult();
```

---

## API Example

### Create Template

```bash
POST /api/notification_type_templates
Content-Type: application/json

{
  "name": "Event Reminder Template",
  "description": "Template for event reminder notifications",
  "subject": "Reminder: {event_title}",
  "bodyHtml": "<p>Hello {user_name},</p><p>Your event <strong>{event_title}</strong> starts at {event_start_time}</p>",
  "bodyPlainText": "Hello {user_name},\n\nYour event {event_title} starts at {event_start_time}",
  "notificationType": "/api/notification_types/1",
  "communicationMethod": "/api/communication_methods/1",
  "priority": "information",
  "active": true,
  "default": false,
  "availablePlaceholders": ["user_name", "event_title", "event_start_time"],
  "placeholderExamples": "Use {user_name} for recipient name, {event_title} for event title",
  "iconUrl": "https://example.com/icon.png",
  "colorCode": "#007BFF",
  "tags": ["event", "reminder"]
}
```

### Response (201 Created)

```json
{
  "@context": "/api/contexts/NotificationTypeTemplate",
  "@id": "/api/notification_type_templates/01923456-7890-7abc-def0-123456789abc",
  "@type": "NotificationTypeTemplate",
  "id": "01923456-7890-7abc-def0-123456789abc",
  "name": "Event Reminder Template",
  "description": "Template for event reminder notifications",
  "subject": "Reminder: {event_title}",
  "bodyHtml": "<p>Hello {user_name},</p><p>Your event <strong>{event_title}</strong> starts at {event_start_time}</p>",
  "bodyPlainText": "Hello {user_name},\n\nYour event {event_title} starts at {event_start_time}",
  "notificationType": "/api/notification_types/1",
  "communicationMethod": "/api/communication_methods/1",
  "priority": "information",
  "category": null,
  "availablePlaceholders": ["user_name", "event_title", "event_start_time"],
  "placeholderExamples": "Use {user_name} for recipient name, {event_title} for event title",
  "active": true,
  "default": false,
  "iconUrl": "https://example.com/icon.png",
  "colorCode": "#007BFF",
  "tags": ["event", "reminder"],
  "createdAt": "2025-10-19T10:30:00+00:00",
  "updatedAt": "2025-10-19T10:30:00+00:00"
}
```

---

## Implementation Steps

### 1. Generate Entity
```bash
cd /home/user/inf/app
php bin/console app:generate:entity NotificationTypeTemplate
```

### 2. Create Migration
```bash
php bin/console make:migration --no-interaction
```

### 3. Review Migration
Check that migration includes:
- All 19 properties
- Foreign keys to notification_type and communication_method
- Default values: active=true, default=false
- Indexes on active, notification_type_id, priority

### 4. Execute Migration
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Validate Schema
```bash
php bin/console doctrine:schema:validate
```

### 6. Clear Cache
```bash
php bin/console cache:clear
```

### 7. Test API
```bash
# Get all templates
curl -k https://localhost/api/notification_type_templates

# Get single template
curl -k https://localhost/api/notification_type_templates/{id}

# Create template (requires ROLE_SUPER_ADMIN)
curl -k -X POST https://localhost/api/notification_type_templates \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","subject":"Test Subject","notificationType":"/api/notification_types/1"}'
```

---

## Files Modified

1. `/home/user/inf/app/config/PropertyNew.csv`
   - Line 625-643: NotificationTypeTemplate properties (19 total)
   - Line 625: Added inverse to NotificationType
   - Line 712: Added inverse to CommunicationMethod

2. `/home/user/inf/app/config/EntityNew.csv`
   - Line 53: Updated description, searchableFields, filterableFields

---

## Key Features

✓ **Content Management:** Subject, HTML body, plain text, headers, footers
✓ **Placeholder System:** Dynamic variable replacement
✓ **Priority Levels:** Information, Warning, Critical
✓ **Multi-Channel:** Email, SMS, Push, In-App
✓ **Status Control:** Active/Inactive toggle
✓ **Default Templates:** Mark preferred template per type
✓ **Visual Metadata:** Icons, colors, tags for UI
✓ **Full API Support:** All CRUD operations
✓ **Validation:** Comprehensive validation on critical fields
✓ **PostgreSQL 18:** Optimized data types and indexes

---

## Best Practices

1. **Template Naming:** Use descriptive names (e.g., "Welcome Email", "Event Reminder")
2. **Placeholder Documentation:** Always fill `placeholderExamples` field
3. **Priority Selection:** Use `critical` sparingly, only for urgent actions
4. **HTML + Plain Text:** Provide both versions for email compatibility
5. **Default Templates:** Only one default per notification type
6. **Testing:** Always test placeholder replacement before activating
7. **Categories:** Use consistent category names for easy grouping
8. **Icons:** Use SVG or small PNG for better performance
9. **Colors:** Use brand colors for consistency
10. **Tags:** Use for searchability and filtering

---

## Troubleshooting

### Template Not Appearing
- Check `active` field is true
- Verify `notificationType` is set correctly
- Check user has ROLE_SUPER_ADMIN

### Placeholders Not Replacing
- Verify placeholder is in `availablePlaceholders`
- Check syntax: `{variable_name}` not `{{variable_name}}`
- Ensure variable exists in context

### Default Template Not Working
- Only one template can be default per type
- Check both `active=true` AND `default=true`
- Query: `WHERE active AND default AND notification_type_id = ?`

---

**Report Location:** `/home/user/inf/notification_type_template_entity_analysis_report.md`
**Status:** READY FOR CODE GENERATION
**Next Step:** Run entity generator command

---
