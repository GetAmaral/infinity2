# Proper CSV Migration Analysis

## Original Entity.csv Structure

**Columns:**
- [0] `ID`
- [1] `Entity`
- [2] `Property`
- [3] `Type`
- [4] `Len`
- [5] `Nullable`
- [6] `targetEntity`
- [7] `fkProperty`
- [8] `OrderBy`
- [9] `index`
- [10] `form`
- [11] `detail`
- [12] `list`
- [13] `noSearch`
- [14] `noSort`
- [15] `roles`
- [16] `get`
- [17] `post`
- [18] `put`
- [19] `patch`
- [20] `delete`
- [21] `nav_group`
- [22] `nav_order`

## Index Patterns Extracted

### ix_name (54 properties)
- `Organization.name`
- `Module.name`
- `User.name`
- `Role.name`
- `City.name`
- `Country.name`
- `ProfileTemplate.name`
- `Profile.name`
- `SocialMediaType.name`
- `SocialMedia.name`
- ... and 44 more

### ix_name_slug (2 properties)
- `Organization.name`
- `Organization.slug`

### ix_slug (1 properties)
- `Organization.slug`

### ix_name_organization (2 properties)
- `User.name`
- `User.organization`

### ix_organization (1 properties)
- `User.organization`

### ix_email_organization (2 properties)
- `User.organization`
- `User.email`

### ix_email (1 properties)
- `User.email`

## Role Patterns Extracted

### CLIENT (3 properties)
- `Contact.name`
- `Company.name`
- `Flag.name`

### MANAGER (1 properties)
- `User.name`

### ORGANIZATION_ADMIN (2 properties)
- `Organization.name`
- `TalkType.name`

### STUDENT (1 properties)
- `UserCourse.user`

### SUPER_ADMIN (8 properties)
- `Module.name`
- `Role.name`
- `City.name`
- `Country.name`
- `ProfileTemplate.name`
- `SocialMediaType.name`
- `TalkTypeTemplate.name`
- `AgentType.name`

### TALK (1 properties)
- `Talk.organization`

### TEACHER (2 properties)
- `Course.name`
- `Lecture.name`

### USER (1 properties)
- `Attachment.filename`

## Comprehensive Role Hierarchy (18 Roles)

### ROLE_SUPER_ADMIN

**Description:** System-wide super administrator

**Grants:**
- Full system access
- Cross-organization access
- System configuration

### ROLE_ORGANIZATION_ADMIN

**Description:** Organization administrator

**Grants:**
- Organization management
- User management
- Module configuration

**Primary Entities:** Organization, User, Role, Module

### ROLE_CRM_ADMIN

**Description:** CRM system administrator

**Grants:**
- CRM configuration
- Pipeline management
- Deal stages

**Primary Entities:** Pipeline, PipelineStage, DealStage, DealType, DealCategory, TaskType

### ROLE_SALES_MANAGER

**Description:** Sales team manager

**Grants:**
- Manage deals
- Assign tasks
- View team performance

**Primary Entities:** Deal, Task, Pipeline, Contact, Company

### ROLE_SALES_REP

**Description:** Sales representative

**Grants:**
- Manage own deals
- Create contacts
- Update tasks

**Primary Entities:** Deal, Contact, Task, Talk

### ROLE_ACCOUNT_MANAGER

**Description:** Account/Customer manager

**Grants:**
- Manage customer accounts
- Track interactions

**Primary Entities:** Contact, Company, Deal, Task, Talk

### ROLE_MARKETING_ADMIN

**Description:** Marketing administrator

**Grants:**
- Campaign management
- Lead source configuration

**Primary Entities:** Campaign, LeadSource

### ROLE_MARKETING_MANAGER

**Description:** Marketing campaign manager

**Grants:**
- Create campaigns
- Manage leads

**Primary Entities:** Campaign, LeadSource, Contact

### ROLE_EVENT_ADMIN

**Description:** Events administrator

**Grants:**
- Event configuration
- Resource management

**Primary Entities:** EventCategory, EventResource, EventResourceType, CalendarType

### ROLE_EVENT_MANAGER

**Description:** Event organizer

**Grants:**
- Create events
- Manage attendees
- Book resources

**Primary Entities:** Event, EventAttendee, EventResourceBooking, Calendar

### ROLE_EDUCATION_ADMIN

**Description:** Education administrator

**Grants:**
- Course management
- Module configuration

**Primary Entities:** Course, CourseModule, CourseLecture

### ROLE_INSTRUCTOR

**Description:** Course instructor

**Grants:**
- Create courses
- Manage modules
- Track students

**Primary Entities:** Course, CourseModule, CourseLecture, StudentCourse

### ROLE_STUDENT

**Description:** Student/Learner

**Grants:**
- Enroll in courses
- View lectures
- Track progress

**Primary Entities:** StudentCourse, StudentLecture

### ROLE_SYSTEM_CONFIG

**Description:** System configuration manager

**Grants:**
- Manage templates
- Configure types
- System settings

**Primary Entities:** ProfileTemplate, TalkTypeTemplate, AgentType, CalendarType, NotificationTypeTemplate, TimeZone, CommunicationMethod

### ROLE_ORG_CONFIG

**Description:** Organization configuration manager

**Grants:**
- Configure organization settings
- Manage profiles

**Primary Entities:** Profile, Agent, TalkType, NotificationType

### ROLE_SUPPORT_ADMIN

**Description:** Support administrator

**Grants:**
- Configure support settings
- Manage agents

**Primary Entities:** Agent, AgentType, Talk, TalkType

### ROLE_SUPPORT_AGENT

**Description:** Support agent

**Grants:**
- Handle customer conversations
- Manage tickets

**Primary Entities:** Talk, TalkMessage, Contact

### ROLE_DATA_ADMIN

**Description:** Data administrator

**Grants:**
- Manage system data
- Import/Export
- Data cleanup

**Primary Entities:** City, Country, Product, ProductCategory, Brand, TaxCategory

### ROLE_USER

**Description:** Basic authenticated user

**Grants:**
- View own data
- Basic operations
