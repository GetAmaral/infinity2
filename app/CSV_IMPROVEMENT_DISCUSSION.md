# CSV Improvement Discussion Plan

**Date:** 2025-10-08 20:05:52
**Entities:** 67
**Properties:** 729

---

## ✅ Clarifications Applied

- ✅ **Audit Fields**: Handled by AuditTrait in generator (no CSV changes needed)
- ✅ **Pagination**: Handled by server configuration (no CSV changes needed)
- ⚠️ **EXTRA_LAZY**: Must be configured in CSV and generator must apply it
- ⚠️ **AuditLog**: Already implemented as PHP entity, needs CSV entry

---

## 1️⃣ DATABASE INDEXES (PropertyNew.csv)

### Current Issue
PropertyNew.csv has no index definitions. Generator must create ORM indexes.

### Proposed Solution
Add columns to PropertyNew.csv:
```csv
entityName,propertyName,...,indexed,indexType,compositeIndexGroup
```

**Index Types:**
- `simple` - Single column index
- `composite` - Part of multi-column index
- `unique` - Unique constraint (already has 'unique' column)

### Index Summary

| Type | Count | Priority |
|------|-------|----------|
| Foreign Keys (ManyToOne) | 132 | 🔴 CRITICAL |
| Status Fields | 17 | 🟡 HIGH |
| Email/Slug (unique) | ~67 | 🔴 CRITICAL |
| Date Fields | ~200 | 🟢 MEDIUM |

### Top Entities Needing Indexes

#### Deal (11 critical, 11 total)
- 🔴 `organization` (simple) - Foreign key (ManyToOne)
- 🔴 `manager` (simple) - Foreign key (ManyToOne)
- 🔴 `company` (simple) - Foreign key (ManyToOne)
- 🔴 `primaryContact` (simple) - Foreign key (ManyToOne)
- 🔴 `currentStage` (simple) - Foreign key (ManyToOne)
- 🔴 `dealType` (simple) - Foreign key (ManyToOne)
- 🔴 `leadSource` (simple) - Foreign key (ManyToOne)
- 🔴 `campaign` (simple) - Foreign key (ManyToOne)
- 🔴 `category` (simple) - Foreign key (ManyToOne)
- 🔴 `lostReason` (simple) - Foreign key (ManyToOne)
- 🔴 `winReason` (simple) - Foreign key (ManyToOne)

#### Contact (6 critical, 7 total)
- 🔴 `organization` (simple) - Foreign key (ManyToOne)
- 🔴 `company` (simple) - Foreign key (ManyToOne)
- 🔴 `accountManager` (simple) - Foreign key (ManyToOne)
- 🔴 `email` (unique) - Email (login/search)
- 🔴 `city` (simple) - Foreign key (ManyToOne)
- 🟡 `status` (simple) - Status field (WHERE clauses)
- 🔴 `billingCity` (simple) - Foreign key (ManyToOne)

#### Task (6 critical, 7 total)
- 🔴 `organization` (simple) - Foreign key (ManyToOne)
- 🔴 `pipelineStage` (simple) - Foreign key (ManyToOne)
- 🔴 `deal` (simple) - Foreign key (ManyToOne)
- 🔴 `contact` (simple) - Foreign key (ManyToOne)
- 🔴 `user` (simple) - Foreign key (ManyToOne)
- 🟡 `active` (simple) - Status field (WHERE clauses)
- 🔴 `type` (simple) - Foreign key (ManyToOne)

#### Product (6 critical, 7 total)
- 🔴 `organization` (simple) - Foreign key (ManyToOne)
- 🔴 `category` (simple) - Foreign key (ManyToOne)
- 🔴 `productLine` (simple) - Foreign key (ManyToOne)
- 🔴 `brand` (simple) - Foreign key (ManyToOne)
- 🔴 `taxCategory` (simple) - Foreign key (ManyToOne)
- 🟡 `active` (simple) - Status field (WHERE clauses)
- 🔴 `billingFrequency` (simple) - Foreign key (ManyToOne)

#### TalkMessage (6 critical, 6 total)
- 🔴 `organization` (simple) - Foreign key (ManyToOne)
- 🔴 `talk` (simple) - Foreign key (ManyToOne)
- 🔴 `fromContact` (simple) - Foreign key (ManyToOne)
- 🔴 `fromUser` (simple) - Foreign key (ManyToOne)
- 🔴 `fromAgent` (simple) - Foreign key (ManyToOne)
- 🔴 `parentMessage` (simple) - Foreign key (ManyToOne)

### Composite Indexes (Multi-Tenancy)

**Critical for multi-tenant queries:**
```sql
-- Pattern: organization_id + commonly filtered/sorted field
CREATE INDEX idx_deal_org_created ON deal (organization_id, created_at);
CREATE INDEX idx_task_org_status ON task (organization_id, status);
CREATE INDEX idx_contact_org_name ON contact (organization_id, name);
```

**Entities needing composite indexes (51):**
```
Organization, User, Profile, SocialMedia, Contact, Company
Flag, Talk, TalkType, TalkMessage, Attachment, Agent
Deal, DealStage, DealCategory, DealType, Pipeline, PipelineTemplate
PipelineStage, PipelineStageTemplate, Task, TaskTemplate, TaskType, LeadSource
Product, ProductBatch, ProductCategory, ProductLine, Brand, TaxCategory
Competitor, LostReason, Tag, BillingFrequency, Campaign, Calendar
Event, EventAttendee, Reminder, Notification, NotificationType, EventCategory
EventResource, EventResourceBooking, MeetingData, WorkingHour, Holiday, Course
CourseLecture, StudentCourse, StudentLecture
```

### Implementation Approach

**Option A: Add to PropertyNew.csv (Recommended)**
```csv
Contact,organization,Organization,,false,...,indexed=true,compositeWith=createdAt
Contact,name,Name,string,false,255,...,indexed=true
```

**Option B: Generator Auto-Detection**
- Generator automatically indexes all ManyToOne relationships
- Generator automatically indexes unique fields
- Generator automatically creates composite (organization_id, created_at)

**Decision Needed:** Which approach? 🤔

---

## 2️⃣ FETCH STRATEGIES (EXTRA_LAZY)

### Current State
All relationships use `fetch='LAZY'` (default).

### Fetch Strategy Guide

```php
// EAGER - Small collections, always needed (5-20 items)
#[ORM\OneToMany(fetch: 'EAGER')]
protected Collection $courseModules; // Course has 5-15 modules

// LAZY - Default (most cases)
#[ORM\ManyToOne(fetch: 'LAZY')] // Default - fine

// EXTRA_LAZY - Large collections, rarely fully iterated (100+ items)
#[ORM\OneToMany(fetch: 'EXTRA_LAZY')]
protected Collection $contacts; // Organization has 1000s
```

### EXTRA_LAZY Candidates

#### Organization
- `contacts` - Can have 1000s of contacts
- `companies` - Can have 1000s of companies
- `deals` - Can have 1000s of deals
- `tasks` - Can have 1000s of tasks
- `events` - Can have 1000s of events
- `users` - Can have 100s of users
- `products` - Can have 1000s of products
- `campaigns` - Can have 100s of campaigns

#### User
- `managedContacts` - Manager can have 100s of contacts
- `managedDeals` - Manager can have 100s of deals
- `tasks` - User can have 100s of tasks
- `contacts` - Team member in many contacts

#### Contact
- `talks` - Can have 100s of conversations
- `deals` - Can be involved in many deals
- `tasks` - Can have many tasks

#### Company
- `contacts` - Can have 100s of employees
- `deals` - Can have many deals

#### Deal
- `tasks` - Can have many tasks

#### Course
- `studentCourses` - Can have 1000s of enrollments

**Total EXTRA_LAZY needed:** 19

### CSV Implementation

Update PropertyNew.csv `fetch` column:
```csv
Organization,contacts,Contacts,,true,,,,false,,OneToMany,Contact,organization,,,false,EXTRA_LAZY
Organization,companies,Companies,,true,,,,false,,OneToMany,Company,organization,,,false,EXTRA_LAZY
User,managedContacts,ManagedContacts,,true,,,,false,,OneToMany,Contact,accountManager,,,false,EXTRA_LAZY
```

**Generator Must:**
- Read `fetch` column from CSV
- Apply to `#[ORM\OneToMany(fetch: '{value}')]`
- Default to 'LAZY' if empty

---

## 3️⃣ CASCADE OPERATIONS & ORPHAN REMOVAL

### Cascade Strategy

```php
// OneToMany (Parent owns children)
#[ORM\OneToMany(mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
// Example: Course -> CourseModules, Deal -> DealStages

// ManyToMany (Association only)
#[ORM\ManyToMany(cascade: ['persist'])]
// Example: User <-> Roles, Deal <-> Tags

// ManyToOne (Child references parent)
// No cascade needed on this side
```

### Owned Relationships (orphanRemoval=true)

**Pattern:** Parent fully owns children. If removed from collection, delete from database.

#### Course
- `modules` → `CourseModule`
  - `cascade: ['persist', 'remove']`
  - `orphanRemoval: true`

#### CourseModule
- `lectures` → `CourseLecture`
  - `cascade: ['persist', 'remove']`
  - `orphanRemoval: true`

#### Pipeline
- `stages` → `PipelineStage`
  - `cascade: ['persist', 'remove']`
  - `orphanRemoval: true`

#### Talk
- `messages` → `TalkMessage`
  - `cascade: ['persist', 'remove']`
  - `orphanRemoval: true`

#### Event
- `attendees` → `EventAttendee`
  - `cascade: ['persist', 'remove']`
  - `orphanRemoval: true`

#### EventResource
- `bookings` → `EventResourceBooking`
  - `cascade: ['persist', 'remove']`
  - `orphanRemoval: true`

### CSV Implementation

Update PropertyNew.csv columns:
```csv
Course,modules,Modules,,true,,,,false,,OneToMany,CourseModule,course,,"persist,remove",true,LAZY
Pipeline,stages,Stages,,true,,,,false,,OneToMany,PipelineStage,pipeline,,"persist,remove",true,LAZY
```

---

## 4️⃣ SECURITY & ACCESS CONTROL

### Current Issue
All entities use: `is_granted('ROLE_USER')`

### Proposed Role Hierarchy

```yaml
ROLE_SUPER_ADMIN:
  - Full system access
  - Cross-organization access

ROLE_ADMIN:
  - Organization admin
  - Can manage: Organization, Module, Role, System entities

ROLE_MANAGER:
  - Team manager
  - Can manage: Pipelines, Campaigns, Templates, Configuration

ROLE_USER:
  - Regular user
  - Can manage: Contacts, Deals, Tasks, Events (with voter)
```

### Security by Menu Group

| Menu Group | Entities | Suggested Security |
|------------|----------|-------------------|
| System | 14 | `ROLE_ADMIN` |
| Configuration | 29 | `ROLE_MANAGER` |
| CRM | 8 | `ROLE_USER` + Voter |
| Marketing | 2 | `ROLE_MANAGER` + Voter |
| Calendar | 9 | `ROLE_USER` + Voter |
| Education | 4 | `ROLE_USER` + Voter |

### Entities Requiring Security Changes

#### System Entities (ROLE_ADMIN)
```
Organization
Role
City
Country
ProfileTemplate
SocialMediaType
TalkTypeTemplate
AgentType
NotificationTypeTemplate
EventResourceType
HolidayTemplate
TimeZone
CommunicationMethod
Module
```

#### Configuration (ROLE_MANAGER)
```
User, Profile, SocialMedia, TalkType, Attachment, Agent, DealStage, DealCategory, DealType, PipelineTemplate
... and 19 more
```

---

## 5️⃣ ADD AUDITLOG ENTITY TO CSV

### Current State
AuditLog exists as PHP entity but NOT in CSV files.

### Required CSV Entry

**EntityNew.csv:**
```csv
AuditLog,Audit Log,Audit Logs,bi-journal-text,"System audit trail",false,true,"GetCollection,Get","is_granted('ROLE_ADMIN')",audit:read,audit:write,true,50,"{""createdAt"": ""desc""}","entityType,action","user,entityType",false,VIEW,bootstrap_5_layout.html.twig,,,,System,99,true
```

**PropertyNew.csv entries needed:**
- `entityType` (string, indexed)
- `entityId` (string, indexed)
- `action` (string: CREATE, UPDATE, DELETE)
- `oldValues` (json, nullable)
- `newValues` (json, nullable)
- `user` (ManyToOne → User)
- `ipAddress` (string, nullable)
- `userAgent` (text, nullable)

---

## 🤔 DECISIONS NEEDED

### 1. Index Management
- [ ] **Option A:** Add indexed/compositeIndexGroup columns to PropertyNew.csv
- [ ] **Option B:** Generator auto-detects and creates indexes (foreign keys, unique, etc.)
- [ ] **Hybrid:** Auto-detect common patterns + manual overrides in CSV

**Recommendation:** Option B (Generator auto-detection) for simplicity.

### 2. EXTRA_LAZY Implementation
- [ ] Update PropertyNew.csv `fetch` column for 19 relationships
- [ ] Verify generator reads and applies `fetch` attribute
- [ ] Test performance impact on large collections

**Recommendation:** Proceed immediately - critical for performance.

### 3. Cascade Operations
- [ ] Update PropertyNew.csv `cascade` and `orphanRemoval` columns
- [ ] Document ownership patterns (which relationships own children)
- [ ] Test cascade behavior in development

**Recommendation:** Conservative approach - only clear ownership patterns.

### 4. Security Roles
- [ ] Update EntityNew.csv `security` column for System entities
- [ ] Create role hierarchy in security.yaml
- [ ] Implement voters for business entities

**Recommendation:** Phase 1 - System entities only. Phase 2 - Full voter system.

### 5. AuditLog CSV Entry
- [ ] Add AuditLog to EntityNew.csv
- [ ] Add AuditLog properties to PropertyNew.csv
- [ ] Verify generator doesn't regenerate existing entity

**Recommendation:** Add immediately - simple addition.

---

## 🚀 PROPOSED IMPLEMENTATION PLAN

### Phase 1: Quick Wins (This Week)
1. ✅ Add AuditLog to CSV files (1 hour)
2. ✅ Update EXTRA_LAZY for 19 relationships (2 hours)
3. ✅ Fix security for System entities (1 hour)
4. ✅ Verify generator handles fetch/cascade from CSV (2 hours)

**Total Time:** ~6 hours
**Impact:** 🔴 HIGH - Performance + Security

### Phase 2: Index Strategy (Next Week)
5. ⚠️ Implement generator auto-indexing for:
   - All ManyToOne foreign keys
   - All unique fields
   - Composite (organization_id, created_at)
6. ⚠️ Add index annotations to generated entities
7. ⚠️ Generate migration with all indexes

**Total Time:** ~8 hours
**Impact:** 🔴 CRITICAL - Performance

### Phase 3: Cascade & Validation (Following Week)
8. 🟡 Update cascade for owned relationships
9. 🟡 Enable orphanRemoval for specific patterns
10. 🟡 Add enhanced validation rules

**Total Time:** ~6 hours
**Impact:** 🟡 MEDIUM - Data Integrity

### Phase 4: Advanced Security (Month 2)
11. 🟢 Implement voter system for all business entities
12. 🟢 Field-level security for sensitive data
13. 🟢 Role hierarchy testing

**Total Time:** ~16 hours
**Impact:** 🟢 HIGH - Security

---

## 📊 SUMMARY FOR DISCUSSION

| Action | Complexity | Impact | Priority |
|--------|-----------|--------|----------|
| EXTRA_LAZY (19 relations) | 🟢 Easy | 🔴 High | ⚠️ NOW |
| AuditLog CSV entry | 🟢 Easy | 🟡 Medium | ⚠️ NOW |
| Security roles (System) | 🟢 Easy | 🔴 High | ⚠️ NOW |
| Generator auto-indexing | 🟡 Medium | 🔴 Critical | 📅 Week 2 |
| Cascade operations | 🟡 Medium | 🟡 Medium | 📅 Week 3 |
| Full voter system | 🔴 Complex | 🟢 High | 📅 Month 2 |

**Questions for Discussion:**
1. Should generator auto-create indexes or read from CSV?
2. Which relationships need EXTRA_LAZY? (see list above)
3. Approve security role hierarchy?
4. Timeline approval for 4 phases?
