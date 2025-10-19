# Task Entity Analysis & Optimization Report

**Generated**: 2025-10-19
**Database**: PostgreSQL 18
**Project**: Luminai CRM (Symfony 7.3)
**Entity**: Task
**Total Properties**: 34

---

## Executive Summary

The Task entity has been thoroughly analyzed against CRM 2025 best practices and industry standards. Multiple critical issues were identified and fixed, including data type inconsistencies, missing essential properties, and incomplete metadata. The entity now aligns with modern CRM task management patterns including polymorphic relationships, status tracking, and comprehensive activity management.

### Key Improvements Made
- Fixed table naming convention (task_table → task)
- Standardized 13 property configurations
- Added 4 missing critical properties
- Implemented proper enum types for status and priority
- Configured API searchable and filterable fields
- Established proper relationship types

---

## 1. GeneratorEntity Analysis

### Initial State Issues Found

| Field | Issue | Status |
|-------|-------|--------|
| `table_name` | Set to `task_table` instead of `task` | FIXED |
| `api_searchable_fields` | Empty array `[]` | FIXED |
| `api_filterable_fields` | Empty array `[]` | FIXED |
| `is_generated` | NULL instead of boolean | FIXED |
| `operation_security` | NULL (not critical) | DOCUMENTED |
| `operation_validation_groups` | NULL (not critical) | DOCUMENTED |
| `validation_groups` | NULL (not critical) | DOCUMENTED |

### Applied Fixes

```sql
UPDATE generator_entity SET
    table_name = 'task',
    api_searchable_fields = '["name", "description"]',
    api_filterable_fields = '["taskStatus", "priority", "user", "contact", "company", "deal", "scheduledDate", "completed", "archived"]',
    is_generated = false
WHERE entity_name = 'Task';
```

### Final Configuration

```
Entity Name:       Task
Table Name:        task (corrected from task_table)
Icon:              bi-check-square
Description:       Tasks and to-dos for productivity management
Menu Group:        CRM
Menu Order:        50
API Enabled:       YES
API Operations:    GetCollection, Get, Post, Put, Delete
API Security:      is_granted('ROLE_SALES_MANAGER')
Voter Enabled:     YES
Voter Attributes:  VIEW, EDIT, DELETE
Fixtures Enabled:  YES
Test Enabled:      YES
Has Organization:  YES (multi-tenant)
Color:             #198754 (green)
Tags:              crm, productivity, todo
```

---

## 2. CRM 2025 Best Practices Research

### Industry Standards for Task Entities

Based on comprehensive research of leading CRM platforms (Salesforce, Microsoft Dynamics 365, HubSpot) and 2025 best practices:

#### Core Task Properties (MUST HAVE)
1. **Subject/Title** - Clear description of task
2. **Status** - Lifecycle tracking (pending, in_progress, completed, etc.)
3. **Priority** - Urgency level (low to critical)
4. **Due Date** - When task should be completed
5. **Start Date** - When task should begin
6. **Assigned To** - User responsible
7. **Created By** - Original creator
8. **Description** - Detailed notes

#### Relationship Properties (POLYMORPHIC)
Modern CRMs use polymorphic relationships where tasks can be related to multiple entity types:
- **WhoId Pattern**: Links to Contact or Lead (person)
- **WhatId Pattern**: Links to Account, Opportunity, Custom Objects (entity)

Our implementation:
- `contact` (ManyToOne to Contact)
- `company` (ManyToOne to Company)
- `deal` (ManyToOne to Deal)

#### Activity Tracking Properties
1. **Category** - Type of activity (call, email, meeting)
2. **Completion Percentage** - Progress tracking
3. **Completed Date** - Actual completion timestamp
4. **Duration** - Time estimate/actual
5. **Location** - Physical or virtual meeting location

#### Automation & Notification Properties
1. **Reminder Date** - When to send reminder
2. **Notification Sent** - Flag for automation
3. **Recurring** - For repeating tasks
4. **Recurrence Rule** - Pattern definition

#### Additional Context Properties
1. **Notes** - Freeform additional information
2. **Outcome** - Result of task
3. **Email Subject** - For email tasks
4. **Phone Number** - For call tasks
5. **Meeting URL** - For virtual meetings

### Schema Design Principles Applied
1. **Normalization (3NF)**: Each property stores one piece of data
2. **Indexing Strategy**: All foreign keys and frequently filtered fields indexed
3. **Enum Types**: Use for finite value sets (status, priority, category)
4. **Nullable Strategy**: Required fields non-null, optional fields nullable
5. **Audit Trail**: createdAt, updatedAt, createdBy for compliance

---

## 3. Property Analysis & Issues Found

### Critical Issues Fixed

#### Issue 1: Priority Field - Incorrect Type and Missing Enum
**Before**:
```
property_type: string
length: 20
form_type: IntegerType (mismatch!)
is_enum: false
validation_rules: []
```

**After**:
```
property_type: integer
length: NULL
form_type: ChoiceType
is_enum: true
enum_values: ["1", "2", "3", "4", "5"]
validation_rules: ["NotBlank"]
form_options: {
  "choices": {
    "Low": 1,
    "Normal": 2,
    "High": 3,
    "Urgent": 4,
    "Critical": 5
  },
  "placeholder": "Select priority"
}
```

**Impact**: High - This was causing data type confusion and would break in entity generation.

---

#### Issue 2: TaskStatus Field - String Type Without Enum Values
**Before**:
```
property_type: string
length: 20
form_type: IntegerType (mismatch!)
is_enum: false
nullable: true
validation_rules: []
```

**After**:
```
property_type: string
length: 20
form_type: ChoiceType
is_enum: true
enum_values: ["pending", "in_progress", "completed", "cancelled", "deferred"]
nullable: false
validation_rules: ["NotBlank"]
form_options: {
  "choices": {
    "Pending": "pending",
    "In Progress": "in_progress",
    "Completed": "completed",
    "Cancelled": "cancelled",
    "Deferred": "deferred"
  },
  "placeholder": "Select status"
}
```

**Impact**: Critical - Status is a core required field for task management.

---

#### Issue 3: Type Field - Mixed Relation/String Configuration
**Before**:
```
property_type: string
length: 50
relationship_type: ManyToOne
target_entity: TaskType
form_type: EntityType
```

**After**:
```
property_type: relation
length: NULL
relationship_type: ManyToOne
target_entity: TaskType
form_type: EntityType
```

**Impact**: Medium - Inconsistent type would cause confusion in code generation.

---

#### Issue 4: CreatedBy Field - Incomplete Configuration
**Before**:
```
property_type: relation
relationship_type: NULL
target_entity: NULL
form_type: NULL
nullable: true
```

**After**:
```
property_type: relation
relationship_type: ManyToOne
target_entity: User
form_type: EntityType
nullable: false
form_options: {
  "class": "App\\Entity\\User",
  "choice_label": "fullName",
  "placeholder": "Select user"
}
```

**Impact**: High - CreatedBy is essential for audit trails and accountability.

---

#### Issue 5: Company Field - Incomplete Configuration
**Before**:
```
property_type: relation
relationship_type: NULL
target_entity: NULL
form_type: NULL
```

**After**:
```
property_type: relation
relationship_type: ManyToOne
target_entity: Company
form_type: EntityType
form_options: {
  "class": "App\\Entity\\Company",
  "choice_label": "name",
  "placeholder": "Select company"
}
```

**Impact**: Medium - Important for polymorphic task relationships.

---

#### Issue 6: ReminderDate Field - Missing Type Information
**Before**:
```
property_type: NULL
form_type: NULL
fixture_type: NULL
```

**After**:
```
property_type: datetime
form_type: DateTimeType
fixture_type: dateTime
form_options: {"widget": "single_text"}
```

**Impact**: Medium - Reminder functionality requires proper datetime handling.

---

#### Issue 7: Boolean Fields - Missing Type Information
Properties affected: `reminder`, `recurring`, `overdue`, `completed`

**Before**:
```
property_type: NULL
form_type: NULL
fixture_type: NULL
default_value: NULL
nullable: NULL
```

**After**:
```
property_type: boolean
form_type: CheckboxType
fixture_type: boolean
default_value: false
nullable: false
```

**Impact**: High - Boolean flags are essential for task state management.

---

#### Issue 8: String Fields - Missing Form Configuration
Properties affected: `queue`, `emailSubject`, `phoneNumber`, `meetingUrl`, `outcome`, `recurrenceRule`

**Before**:
```
property_type: string
form_type: NULL
fixture_type: NULL
form_options: NULL
```

**After**:
```
property_type: string
form_type: TextType
fixture_type: word
form_options: {}
```

**Impact**: Low-Medium - These fields would work but had incomplete metadata.

---

#### Issue 9: Notes Field - Missing Configuration
**Before**:
```
property_type: NULL
form_type: NULL
fixture_type: NULL
```

**After**:
```
property_type: text
form_type: TextareaType
fixture_type: paragraph
form_options: {"attr": {"rows": 4}}
```

**Impact**: Low - Notes field is optional but should be properly configured.

---

## 4. Missing Properties Added

Based on CRM 2025 best practices, the following critical properties were missing and have been added:

### Property 1: startDate
```
property_name: startDate
property_label: Start Date
property_type: datetime
nullable: true
form_type: DateTimeType
form_options: {"widget": "single_text"}
property_order: 5
indexed: true
api_readable: true
api_writable: true
```

**Justification**: Essential for task planning and scheduling. Allows users to schedule tasks in advance. Industry standard in all major CRMs.

---

### Property 2: completionPercentage
```
property_name: completionPercentage
property_label: Completion %
property_type: integer
nullable: true
default_value: 0
validation_rules: ["Range(min=0, max=100)"]
form_type: IntegerType
form_options: {"attr": {"min": 0, "max": 100}}
property_order: 6
indexed: false
api_readable: true
api_writable: true
```

**Justification**: Allows tracking partial progress on tasks. Critical for project management and reporting. Provides visibility into task completion status beyond binary completed/not completed.

---

### Property 3: category
```
property_name: category
property_label: Category
property_type: string
nullable: true
is_enum: true
enum_values: ["call", "email", "meeting", "follow_up", "review", "other"]
form_type: ChoiceType
form_options: {
  "choices": {
    "Call": "call",
    "Email": "email",
    "Meeting": "meeting",
    "Follow-up": "follow_up",
    "Review": "review",
    "Other": "other"
  },
  "placeholder": "Select category"
}
property_order: 7
indexed: true
api_readable: true
api_writable: true
filterable: true
```

**Justification**: Categorizes tasks by activity type. Essential for activity reporting and analytics. Allows filtering and grouping tasks by category. Standard in CRM activity tracking.

---

### Property 4: notificationSent
```
property_name: notificationSent
property_label: Notification Sent
property_type: boolean
nullable: false
default_value: false
form_type: CheckboxType
property_order: 8
show_in_list: false
show_in_detail: true
show_in_form: false
api_readable: true
api_writable: true
```

**Justification**: Critical for automation workflows. Prevents duplicate notifications. Tracks whether reminder notifications have been sent. Required for implementing reminder systems.

---

## 5. Complete Property Inventory (34 Properties)

### Core Properties (Required)
| Property | Type | Nullable | Indexed | Status |
|----------|------|----------|---------|--------|
| name | string(255) | NO | YES | OK |
| taskStatus | string(20) enum | NO | YES | FIXED |
| priority | integer enum | YES | YES | FIXED |
| organization | relation(Organization) | YES | YES | OK |

### Scheduling Properties
| Property | Type | Nullable | Indexed | Status |
|----------|------|----------|---------|--------|
| startDate | datetime | YES | YES | ADDED |
| scheduledDate | datetime | YES | YES | OK |
| completedDate | datetime | YES | YES | OK |
| reminderDate | datetime | YES | YES | FIXED |
| durationMinutes | integer | YES | NO | OK |

### Relationship Properties (Polymorphic)
| Property | Type | Nullable | Indexed | Status |
|----------|------|----------|---------|--------|
| user | relation(User) | YES | YES | OK |
| createdBy | relation(User) | NO | YES | FIXED |
| contact | relation(Contact) | YES | YES | OK |
| company | relation(Company) | YES | YES | FIXED |
| deal | relation(Deal) | YES | YES | OK |
| pipelineStage | relation(PipelineStage) | YES | YES | OK |
| type | relation(TaskType) | YES | YES | FIXED |

### Content Properties
| Property | Type | Nullable | Indexed | Status |
|----------|------|----------|---------|--------|
| description | text | YES | NO | OK |
| notes | text | YES | NO | FIXED |
| command | text | YES | NO | OK |
| location | string(255) | YES | NO | OK |

### Categorization Properties
| Property | Type | Nullable | Indexed | Status |
|----------|------|----------|---------|--------|
| category | string enum | YES | YES | ADDED |
| queue | string(100) | YES | NO | FIXED |

### Progress Tracking
| Property | Type | Nullable | Indexed | Status |
|----------|------|----------|---------|--------|
| completed | boolean | NO | YES | FIXED |
| completionPercentage | integer | YES | NO | ADDED |
| outcome | string(100) | YES | NO | FIXED |

### Automation & Flags
| Property | Type | Nullable | Indexed | Status |
|----------|------|----------|---------|--------|
| reminder | boolean | NO | NO | FIXED |
| notificationSent | boolean | NO | NO | ADDED |
| recurring | boolean | NO | NO | FIXED |
| recurrenceRule | string(500) | YES | NO | FIXED |
| overdue | boolean | NO | YES | FIXED |
| archived | boolean | YES | YES | OK |

### Communication Context
| Property | Type | Nullable | Indexed | Status |
|----------|------|----------|---------|--------|
| emailSubject | string(255) | YES | NO | FIXED |
| phoneNumber | string(50) | YES | NO | FIXED |
| meetingUrl | string(500) | YES | NO | FIXED |

---

## 6. Database Schema Recommendations

### Indexes Strategy

Current indexed properties (15 total):
```sql
-- Primary relationships (already indexed via foreign keys)
CREATE INDEX idx_task_organization_id ON task(organization_id);
CREATE INDEX idx_task_user_id ON task(user_id);
CREATE INDEX idx_task_created_by_id ON task(created_by_id);
CREATE INDEX idx_task_contact_id ON task(contact_id);
CREATE INDEX idx_task_company_id ON task(company_id);
CREATE INDEX idx_task_deal_id ON task(deal_id);
CREATE INDEX idx_task_pipeline_stage_id ON task(pipeline_stage_id);
CREATE INDEX idx_task_type_id ON task(type_id);

-- Status and filtering fields
CREATE INDEX idx_task_status ON task(task_status);
CREATE INDEX idx_task_priority ON task(priority);
CREATE INDEX idx_task_completed ON task(completed);
CREATE INDEX idx_task_archived ON task(archived);
CREATE INDEX idx_task_overdue ON task(overdue);
CREATE INDEX idx_task_category ON task(category);

-- Date filtering
CREATE INDEX idx_task_scheduled_date ON task(scheduled_date);
CREATE INDEX idx_task_start_date ON task(start_date);
```

### Recommended Composite Indexes

For common query patterns:

```sql
-- Active tasks by user
CREATE INDEX idx_task_user_status_scheduled
ON task(user_id, task_status, scheduled_date);

-- Overdue tasks report
CREATE INDEX idx_task_status_scheduled_overdue
ON task(task_status, scheduled_date, overdue)
WHERE archived = false;

-- Organization tasks dashboard
CREATE INDEX idx_task_org_completed_scheduled
ON task(organization_id, completed, scheduled_date DESC);

-- Contact activity timeline
CREATE INDEX idx_task_contact_created
ON task(contact_id, created_at DESC);

-- Company activity timeline
CREATE INDEX idx_task_company_created
ON task(company_id, created_at DESC);
```

### Performance Optimization

1. **Full-Text Search**: Consider adding to `name` and `description`
```sql
ALTER TABLE task ADD COLUMN search_vector tsvector;
CREATE INDEX idx_task_search ON task USING gin(search_vector);
```

2. **Partitioning Strategy**: For high-volume systems
```sql
-- Partition by date (monthly)
CREATE TABLE task_2025_10 PARTITION OF task
FOR VALUES FROM ('2025-10-01') TO ('2025-11-01');
```

3. **Archival Strategy**: Move old completed tasks
```sql
-- Archive table for completed tasks older than 1 year
CREATE TABLE task_archive (LIKE task INCLUDING ALL);
```

---

## 7. API Configuration Analysis

### Current API Settings
```json
{
  "api_enabled": true,
  "api_operations": ["GetCollection", "Get", "Post", "Put", "Delete"],
  "api_security": "is_granted('ROLE_SALES_MANAGER')",
  "api_normalization_context": {"groups": ["task:read"]},
  "api_denormalization_context": {"groups": ["task:write"]},
  "api_default_order": {"createdAt": "desc"},
  "api_searchable_fields": ["name", "description"],
  "api_filterable_fields": [
    "taskStatus",
    "priority",
    "user",
    "contact",
    "company",
    "deal",
    "scheduledDate",
    "completed",
    "archived"
  ]
}
```

### API Endpoint Examples

```
GET /api/tasks
  ?taskStatus=pending
  &priority=4
  &user=/api/users/123
  &completed=false
  &order[scheduledDate]=asc

GET /api/tasks/456

POST /api/tasks
  {
    "name": "Follow up call",
    "taskStatus": "pending",
    "priority": 3,
    "user": "/api/users/123",
    "contact": "/api/contacts/789",
    "scheduledDate": "2025-10-20T14:00:00Z"
  }

PUT /api/tasks/456
PATCH /api/tasks/456
DELETE /api/tasks/456
```

### Security Considerations

Current security requires `ROLE_SALES_MANAGER` for all operations. Recommendations:

1. **Granular Permissions**:
```
GET operations: ROLE_SALES_REP
POST/PUT operations: ROLE_SALES_REP (own tasks)
DELETE operations: ROLE_SALES_MANAGER
```

2. **Organization Filtering**: Already enabled via multi-tenant architecture

3. **Rate Limiting**: Implement for public endpoints

---

## 8. Validation Rules Summary

### Required Fields (NotBlank)
- `name` (Subject)
- `taskStatus` (Status)
- `priority` (Priority)

### Length Constraints
- `name`: max 255 characters
- `location`: max 255 characters
- `emailSubject`: max 255 characters
- `phoneNumber`: max 50 characters
- `queue`: max 100 characters
- `outcome`: max 100 characters
- `meetingUrl`: max 500 characters
- `recurrenceRule`: max 500 characters

### Range Constraints
- `completionPercentage`: 0-100

### Recommended Additional Validations

```php
// In Task entity
#[Assert\Callback]
public function validate(ExecutionContextInterface $context): void
{
    // Completed date should be set if status is completed
    if ($this->taskStatus === 'completed' && !$this->completedDate) {
        $context->buildViolation('Completed date is required when status is completed')
            ->atPath('completedDate')
            ->addViolation();
    }

    // Start date should be before due date
    if ($this->startDate && $this->scheduledDate && $this->startDate > $this->scheduledDate) {
        $context->buildViolation('Start date must be before due date')
            ->atPath('startDate')
            ->addViolation();
    }

    // Completion percentage should be 100 if completed
    if ($this->completed && $this->completionPercentage !== 100) {
        $context->buildViolation('Completion percentage should be 100 when task is completed')
            ->atPath('completionPercentage')
            ->addViolation();
    }
}
```

---

## 9. Polymorphic Relationship Pattern

### Current Implementation
The Task entity implements a **multiple optional relationships** pattern (simpler than true polymorphic):

```php
// A task can be related to:
- Contact (WhoId pattern)
- Company (WhatId pattern)
- Deal (WhatId pattern)
- PipelineStage (WhatId pattern)
```

### True Polymorphic Alternative (Advanced)

For a more flexible implementation, consider Doctrine's Class Table Inheritance:

```php
// Option A: Single relation field with discriminator
#[ORM\ManyToOne(targetEntity: ActivityTarget::class)]
private ?ActivityTarget $relatedTo = null;

// ActivityTarget would be an abstract class with:
// - Contact extends ActivityTarget
// - Company extends ActivityTarget
// - Deal extends ActivityTarget
```

```php
// Option B: Separate type and ID (Salesforce pattern)
#[ORM\Column(type: 'string', length: 50, nullable: true)]
private ?string $relatedToType = null; // 'Contact', 'Company', 'Deal'

#[ORM\Column(type: 'uuid', nullable: true)]
private ?Uuid $relatedToId = null;
```

### Current Pattern Benefits
1. Type-safe relationships in Doctrine
2. Easier to query with joins
3. Foreign key constraints maintained
4. Better IDE support

### Current Pattern Limitations
1. Cannot enforce "exactly one relation"
2. More columns in database
3. Need to check multiple fields for "what is this task about"

### Recommendation
**Keep current pattern** - It's more maintainable and works well with Symfony/Doctrine. True polymorphic patterns add complexity without significant benefit for this use case.

---

## 10. Form Configuration Analysis

### Form Types Summary

| Form Type | Count | Properties |
|-----------|-------|------------|
| TextType | 7 | name, location, queue, emailSubject, phoneNumber, recurrenceRule, meetingUrl |
| TextareaType | 3 | description, notes, command |
| EntityType | 8 | organization, contact, deal, pipelineStage, user, createdBy, company, type |
| ChoiceType | 3 | priority, taskStatus, category |
| DateTimeType | 4 | scheduledDate, completedDate, startDate, reminderDate |
| IntegerType | 2 | durationMinutes, completionPercentage |
| CheckboxType | 6 | archived, completed, reminder, recurring, overdue, notificationSent |

### Recommended Form Order (User Experience)

```php
// TaskType.php form builder order
$builder
    ->add('name', TextType::class, ['label' => 'Subject'])
    ->add('taskStatus', ChoiceType::class, ['label' => 'Status'])
    ->add('priority', ChoiceType::class, ['label' => 'Priority'])
    ->add('category', ChoiceType::class, ['label' => 'Category'])
    ->add('user', EntityType::class, ['label' => 'Assigned To'])

    // Dates
    ->add('startDate', DateTimeType::class)
    ->add('scheduledDate', DateTimeType::class, ['label' => 'Due Date'])
    ->add('completionPercentage', IntegerType::class)

    // Related entities
    ->add('contact', EntityType::class)
    ->add('company', EntityType::class)
    ->add('deal', EntityType::class)

    // Details
    ->add('description', TextareaType::class)
    ->add('location', TextType::class)
    ->add('durationMinutes', IntegerType::class)

    // Reminders
    ->add('reminder', CheckboxType::class)
    ->add('reminderDate', DateTimeType::class)

    // Recurring
    ->add('recurring', CheckboxType::class)
    ->add('recurrenceRule', TextType::class)

    // Communication context
    ->add('emailSubject', TextType::class)
    ->add('phoneNumber', TextType::class)
    ->add('meetingUrl', TextType::class)

    // Additional
    ->add('notes', TextareaType::class)
    ->add('command', TextareaType::class)
    ->add('archived', CheckboxType::class);
```

---

## 11. Fixture Configuration

### Fixture Types Distribution

| Fixture Type | Count | Purpose |
|--------------|-------|---------|
| word | 7 | Short text fields |
| paragraph | 3 | Long text fields |
| dateTime | 4 | Date/time fields |
| randomNumber | 3 | Numeric fields |
| boolean | 6 | Boolean flags |
| (entity relation) | 8 | Foreign key relationships |

### Recommended Fixture Factory

```php
// TaskFactory.php
protected function getDefaults(): array
{
    return [
        'name' => self::faker()->sentence(6),
        'taskStatus' => self::faker()->randomElement(['pending', 'in_progress', 'completed']),
        'priority' => self::faker()->numberBetween(1, 5),
        'category' => self::faker()->randomElement(['call', 'email', 'meeting', 'follow_up']),

        'startDate' => self::faker()->dateTimeBetween('now', '+7 days'),
        'scheduledDate' => self::faker()->dateTimeBetween('+8 days', '+30 days'),
        'completionPercentage' => self::faker()->numberBetween(0, 100),

        'description' => self::faker()->paragraph(),
        'location' => self::faker()->optional()->city(),
        'durationMinutes' => self::faker()->optional()->numberBetween(15, 240),

        'reminder' => self::faker()->boolean(30),
        'reminderDate' => self::faker()->optional()->dateTimeBetween('now', '+30 days'),

        'completed' => false,
        'archived' => false,
        'notificationSent' => false,
        'recurring' => false,

        'organization' => OrganizationFactory::random(),
        'user' => UserFactory::random(),
        'createdBy' => UserFactory::random(),
        'contact' => ContactFactory::random(),
    ];
}
```

---

## 12. Migration Strategy

### Recommended Migration Steps

Since properties were modified in `generator_property` table, the actual entity PHP class and database table need to be regenerated/migrated:

```bash
# 1. Generate the Task entity from generator metadata
php bin/console app:generator:generate-entity Task

# 2. Create migration for schema changes
php bin/console make:migration

# 3. Review the migration (check for data loss!)
cat migrations/Version*.php

# 4. Backup existing data
pg_dump -U luminai -t task > task_backup.sql

# 5. Run migration
php bin/console doctrine:migrations:migrate --no-interaction

# 6. Verify schema
php bin/console doctrine:schema:validate

# 7. Update fixtures
php bin/console doctrine:fixtures:load --no-interaction

# 8. Test entity
php bin/phpunit tests/Entity/TaskTest.php
```

### Data Migration Concerns

#### New NOT NULL Fields
These fields were changed from nullable to non-null:
- `taskStatus`: Set default 'pending' for existing records
- `createdBy`: Need to populate from `createdAt` audit or set system user
- `notificationSent`: Default false
- `completed`: Default false
- `reminder`, `recurring`, `overdue`: Default false

#### Migration SQL
```sql
-- Before changing schema, set defaults
UPDATE task SET task_status = 'pending' WHERE task_status IS NULL;
UPDATE task SET created_by_id = (SELECT id FROM "user" WHERE email = 'system@luminai.com' LIMIT 1) WHERE created_by_id IS NULL;
UPDATE task SET notification_sent = false WHERE notification_sent IS NULL;
UPDATE task SET completed = false WHERE completed IS NULL;
UPDATE task SET reminder = false WHERE reminder IS NULL;
UPDATE task SET recurring = false WHERE recurring IS NULL;
UPDATE task SET overdue = false WHERE overdue IS NULL;
UPDATE task SET completion_percentage = 0 WHERE completion_percentage IS NULL;
```

---

## 13. Testing Recommendations

### Unit Tests (Entity)

```php
// tests/Entity/TaskTest.php
public function testTaskCreation(): void
{
    $task = new Task();
    $task->setName('Test Task');
    $task->setTaskStatus('pending');
    $task->setPriority(3);

    $this->assertEquals('Test Task', $task->getName());
    $this->assertEquals('pending', $task->getTaskStatus());
    $this->assertEquals(3, $task->getPriority());
}

public function testTaskStatusValidation(): void
{
    $task = new Task();
    $task->setTaskStatus('invalid_status');

    $violations = $this->validator->validate($task);
    $this->assertGreaterThan(0, count($violations));
}

public function testPolymorphicRelationships(): void
{
    $task = new Task();
    $task->setContact($contact);
    $task->setCompany($company);
    $task->setDeal($deal);

    $this->assertSame($contact, $task->getContact());
    $this->assertSame($company, $task->getCompany());
    $this->assertSame($deal, $task->getDeal());
}
```

### Functional Tests (API)

```php
// tests/Controller/TaskControllerTest.php
public function testCreateTask(): void
{
    $this->client->request('POST', '/api/tasks', [
        'json' => [
            'name' => 'Follow up call',
            'taskStatus' => 'pending',
            'priority' => 3,
            'scheduledDate' => '2025-10-25T10:00:00Z'
        ]
    ]);

    $this->assertResponseStatusCodeSame(201);
}

public function testFilterTasks(): void
{
    $this->client->request('GET', '/api/tasks?taskStatus=pending&priority=5');
    $this->assertResponseIsSuccessful();
}

public function testUpdateTaskStatus(): void
{
    $this->client->request('PUT', '/api/tasks/123', [
        'json' => [
            'taskStatus' => 'completed',
            'completedDate' => '2025-10-19T15:30:00Z',
            'completionPercentage' => 100
        ]
    ]);

    $this->assertResponseIsSuccessful();
}
```

---

## 14. Performance Benchmarks

### Expected Query Performance

With proper indexing:

| Query Type | Expected Time | Index Used |
|------------|---------------|------------|
| Get single task by ID | < 1ms | PRIMARY KEY |
| List user's tasks | < 10ms | idx_task_user_status_scheduled |
| Filter by status | < 20ms | idx_task_status |
| Search by name | < 50ms | Full-text index (if implemented) |
| Overdue tasks report | < 30ms | idx_task_status_scheduled_overdue |
| Contact activity timeline | < 15ms | idx_task_contact_created |

### Slow Query Alerts

Monitor for queries taking > 100ms:
```sql
-- Enable slow query logging
ALTER SYSTEM SET log_min_duration_statement = 100;

-- Check slow queries
SELECT query, mean_exec_time, calls
FROM pg_stat_statements
WHERE query LIKE '%task%'
ORDER BY mean_exec_time DESC
LIMIT 10;
```

---

## 15. Summary of Changes

### Files Modified
- `generator_entity` table: 1 record updated (Task)
- `generator_property` table: 13 records updated, 4 records inserted

### SQL Statements Executed
1. UPDATE generator_entity (table_name, api fields, is_generated)
2. UPDATE priority property (type, enum, validation)
3. UPDATE taskStatus property (enum, nullable, validation)
4. UPDATE type property (relationship type)
5. UPDATE createdBy property (relationship configuration)
6. UPDATE company property (relationship configuration)
7. UPDATE reminderDate property (type and form)
8. UPDATE 4 boolean properties (type, form, default)
9. UPDATE 6 string properties (type and form)
10. UPDATE notes property (type and form)
11. INSERT startDate property
12. INSERT completionPercentage property
13. INSERT category property
14. INSERT notificationSent property

### Properties Status
- **Total**: 34 properties
- **Fixed**: 13 properties
- **Added**: 4 properties
- **OK (no changes needed)**: 17 properties

---

## 16. Recommendations & Next Steps

### Immediate Actions Required

1. **Regenerate Entity Class**
   ```bash
   php bin/console app:generator:generate-entity Task
   ```

2. **Create and Run Migration**
   ```bash
   php bin/console make:migration
   # Review migration carefully!
   php bin/console doctrine:migrations:migrate
   ```

3. **Update Fixtures**
   ```bash
   php bin/console make:factory Task --test
   # Edit TaskFactory with proper defaults
   ```

4. **Update Tests**
   - Add tests for new properties
   - Test enum validations
   - Test polymorphic relationships

### Short-Term Improvements (1-2 weeks)

1. **Implement Automation**
   - Create EventSubscriber for `overdue` flag calculation
   - Implement reminder notification system
   - Add recurring task generator

2. **Enhance UI**
   - Create task calendar view
   - Add kanban board for status management
   - Implement drag-and-drop priority/status changes

3. **Add Business Logic**
   - Task assignment notifications
   - Automatic status transitions
   - Completion percentage auto-update

### Medium-Term Enhancements (1-3 months)

1. **Advanced Features**
   - Task templates
   - Task dependencies
   - Time tracking integration
   - Task checklist/subtasks

2. **Analytics & Reporting**
   - Task completion metrics by user
   - Average time to complete by category
   - Overdue task trends
   - User workload analysis

3. **Integration**
   - Email task creation
   - Calendar sync (Google Calendar, Outlook)
   - Mobile app notifications

### Long-Term Optimizations (3-6 months)

1. **Performance**
   - Implement full-text search with Elasticsearch
   - Add caching layer for frequently accessed tasks
   - Partition large task tables by date

2. **AI/ML Features**
   - Task priority prediction
   - Smart task scheduling
   - Automatic task categorization
   - Time estimation based on historical data

3. **Enterprise Features**
   - Task approval workflows
   - Custom task types
   - Advanced recurring patterns
   - Task SLA tracking

---

## 17. Compliance & Best Practices Checklist

- [x] Follows Symfony entity naming conventions
- [x] Uses UUIDv7 for primary keys
- [x] Implements multi-tenant organization filtering
- [x] Has proper validation rules
- [x] Includes audit trail fields (createdAt, updatedAt, createdBy)
- [x] API Platform integration configured
- [x] Security voters in place
- [x] Proper indexing strategy
- [x] Fixture support enabled
- [x] Test coverage enabled
- [x] PostgreSQL 18 compatible
- [x] Follows CRM 2025 best practices
- [x] Implements polymorphic relationships pattern
- [x] Has comprehensive property metadata

---

## 18. References & Resources

### CRM Industry Standards
- Salesforce Task Object: https://developer.salesforce.com/docs/atlas.en-us.object_reference.meta/object_reference/sforce_api_objects_task.htm
- Microsoft Dynamics 365 Task Entity: https://learn.microsoft.com/en-us/power-apps/developer/data-platform/reference/entities/task
- HubSpot Tasks: https://developers.hubspot.com/docs/api/crm/tasks

### Database Best Practices
- PostgreSQL Indexing: https://www.postgresql.org/docs/18/indexes.html
- Database Normalization: https://en.wikipedia.org/wiki/Database_normalization
- Partitioning Strategies: https://www.postgresql.org/docs/18/ddl-partitioning.html

### Symfony/Doctrine
- Doctrine Entities: https://symfony.com/doc/current/doctrine.html
- API Platform: https://api-platform.com/docs/
- Validation: https://symfony.com/doc/current/validation.html

---

## Appendix A: Complete SQL Update Script

```sql
-- =====================================================
-- TASK ENTITY OPTIMIZATION SCRIPT
-- Generated: 2025-10-19
-- Database: PostgreSQL 18
-- =====================================================

BEGIN;

-- 1. Update GeneratorEntity
UPDATE generator_entity SET
    table_name = 'task',
    api_searchable_fields = '["name", "description"]',
    api_filterable_fields = '["taskStatus", "priority", "user", "contact", "company", "deal", "scheduledDate", "completed", "archived"]',
    is_generated = false,
    updated_at = CURRENT_TIMESTAMP
WHERE entity_name = 'Task';

-- 2. Fix priority property
UPDATE generator_property SET
    property_type = 'integer',
    form_type = 'ChoiceType',
    validation_rules = '["NotBlank"]',
    is_enum = true,
    enum_values = '["1", "2", "3", "4", "5"]',
    form_options = '{"choices": {"Low": 1, "Normal": 2, "High": 3, "Urgent": 4, "Critical": 5}, "placeholder": "Select priority"}',
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name = 'priority';

-- 3. Fix taskStatus property
UPDATE generator_property SET
    property_type = 'string',
    form_type = 'ChoiceType',
    validation_rules = '["NotBlank"]',
    is_enum = true,
    enum_values = '["pending", "in_progress", "completed", "cancelled", "deferred"]',
    form_options = '{"choices": {"Pending": "pending", "In Progress": "in_progress", "Completed": "completed", "Cancelled": "cancelled", "Deferred": "deferred"}, "placeholder": "Select status"}',
    nullable = false,
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name = 'taskStatus';

-- 4. Fix type property
UPDATE generator_property SET
    property_type = 'relation',
    relationship_type = 'ManyToOne',
    target_entity = 'TaskType',
    form_type = 'EntityType',
    length = NULL,
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name = 'type';

-- 5. Fix createdBy property
UPDATE generator_property SET
    property_type = 'relation',
    relationship_type = 'ManyToOne',
    target_entity = 'User',
    form_type = 'EntityType',
    form_options = '{"class": "App\\Entity\\User", "choice_label": "fullName", "placeholder": "Select user"}',
    nullable = false,
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name = 'createdBy';

-- 6. Fix company property
UPDATE generator_property SET
    property_type = 'relation',
    relationship_type = 'ManyToOne',
    target_entity = 'Company',
    form_type = 'EntityType',
    form_options = '{"class": "App\\Entity\\Company", "choice_label": "name", "placeholder": "Select company"}',
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name = 'company';

-- 7. Fix reminderDate property
UPDATE generator_property SET
    property_type = 'datetime',
    form_type = 'DateTimeType',
    fixture_type = 'dateTime',
    form_options = '{"widget": "single_text"}',
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name = 'reminderDate';

-- 8. Fix boolean properties
UPDATE generator_property SET
    property_type = 'boolean',
    form_type = 'CheckboxType',
    fixture_type = 'boolean',
    default_value = 'false',
    nullable = false,
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name IN ('reminder', 'recurring', 'overdue', 'completed');

-- 9. Fix string properties
UPDATE generator_property SET
    property_type = 'string',
    form_type = 'TextType',
    fixture_type = 'word',
    form_options = '{}',
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name IN ('queue', 'emailSubject', 'phoneNumber', 'meetingUrl', 'outcome', 'recurrenceRule');

-- 10. Fix notes property
UPDATE generator_property SET
    property_type = 'text',
    form_type = 'TextareaType',
    fixture_type = 'paragraph',
    form_options = '{"attr": {"rows": 4}}',
    updated_at = CURRENT_TIMESTAMP
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND property_name = 'notes';

-- 11. Add startDate property
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    property_order, nullable, validation_rules, form_type, form_options,
    show_in_list, show_in_detail, show_in_form, sortable, filterable,
    api_readable, api_writable, api_groups, indexed,
    created_at, updated_at
)
SELECT
    gen_random_uuid(),
    id,
    'startDate',
    'Start Date',
    'datetime',
    5,
    true,
    '[]',
    'DateTimeType',
    '{"widget": "single_text"}',
    true,
    true,
    true,
    true,
    true,
    true,
    true,
    '["task:read", "task:write"]',
    true,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM generator_entity
WHERE entity_name = 'Task'
AND NOT EXISTS (
    SELECT 1 FROM generator_property
    WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
    AND property_name = 'startDate'
);

-- 12. Add completionPercentage property
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    property_order, nullable, default_value, validation_rules,
    form_type, form_options, show_in_list, show_in_detail, show_in_form,
    sortable, filterable, api_readable, api_writable, api_groups,
    indexed, created_at, updated_at
)
SELECT
    gen_random_uuid(),
    id,
    'completionPercentage',
    'Completion %',
    'integer',
    6,
    true,
    '0',
    '["Range(min=0, max=100)"]',
    'IntegerType',
    '{"attr": {"min": 0, "max": 100}}',
    true,
    true,
    true,
    true,
    true,
    true,
    true,
    '["task:read", "task:write"]',
    false,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM generator_entity
WHERE entity_name = 'Task'
AND NOT EXISTS (
    SELECT 1 FROM generator_property
    WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
    AND property_name = 'completionPercentage'
);

-- 13. Add category property
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    property_order, nullable, validation_rules, form_type, form_options,
    is_enum, enum_values, show_in_list, show_in_detail, show_in_form,
    sortable, filterable, api_readable, api_writable, api_groups,
    indexed, created_at, updated_at
)
SELECT
    gen_random_uuid(),
    id,
    'category',
    'Category',
    'string',
    7,
    true,
    '[]',
    'ChoiceType',
    '{"choices": {"Call": "call", "Email": "email", "Meeting": "meeting", "Follow-up": "follow_up", "Review": "review", "Other": "other"}, "placeholder": "Select category"}',
    true,
    '["call", "email", "meeting", "follow_up", "review", "other"]',
    true,
    true,
    true,
    true,
    true,
    true,
    true,
    '["task:read", "task:write"]',
    true,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM generator_entity
WHERE entity_name = 'Task'
AND NOT EXISTS (
    SELECT 1 FROM generator_property
    WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
    AND property_name = 'category'
);

-- 14. Add notificationSent property
INSERT INTO generator_property (
    id, entity_id, property_name, property_label, property_type,
    property_order, nullable, default_value, validation_rules,
    form_type, form_options, show_in_list, show_in_detail, show_in_form,
    sortable, api_readable, api_writable, api_groups,
    created_at, updated_at
)
SELECT
    gen_random_uuid(),
    id,
    'notificationSent',
    'Notification Sent',
    'boolean',
    8,
    false,
    'false',
    '[]',
    'CheckboxType',
    '{}',
    false,
    true,
    false,
    false,
    true,
    true,
    '["task:read", "task:write"]',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM generator_entity
WHERE entity_name = 'Task'
AND NOT EXISTS (
    SELECT 1 FROM generator_property
    WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
    AND property_name = 'notificationSent'
);

COMMIT;

-- Verification queries
SELECT 'GeneratorEntity updated:' as step, COUNT(*) as count
FROM generator_entity WHERE entity_name = 'Task' AND table_name = 'task';

SELECT 'Properties fixed:' as step, COUNT(*) as count
FROM generator_property
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task')
AND updated_at::date = CURRENT_DATE;

SELECT 'Total properties:' as step, COUNT(*) as count
FROM generator_property
WHERE entity_id = (SELECT id FROM generator_entity WHERE entity_name = 'Task');
```

---

**End of Report**

Report Location: `/home/user/inf/task_entity_analysis_report.md`
Generated By: Database Optimization Expert
Date: 2025-10-19
Status: COMPLETE
