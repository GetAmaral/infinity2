# Event Table - Quick Reference Card

## Table Information

**Table Name:** `event`
**Total Columns:** 61
**Total Indexes:** 22
**Total Constraints:** 38 (9 CHECK + 4 FK + 24 NOT NULL + 1 PK)

---

## Core Fields

| Field | Type | Required | Default | Notes |
|-------|------|----------|---------|-------|
| `id` | UUID | ✅ | - | Primary key (UUIDv7) |
| `name` | VARCHAR(255) | ✅ | - | Event title - INDEXED |
| `subject` | VARCHAR(255) | ❌ | - | Alias for Salesforce/Outlook - INDEXED |
| `description` | TEXT | ❌ | - | Event details |
| `organization_id` | UUID | ✅ | - | Multi-tenant (FK → organization) |

---

## Time Fields ⏰ (CRITICAL - ALL INDEXED)

| Field | Type | Required | Default | Index |
|-------|------|----------|---------|-------|
| `start_time` | TIMESTAMP | ✅ | - | ✅ **CRITICAL** |
| `end_time` | TIMESTAMP | ✅ | - | ✅ **CRITICAL** |
| `is_all_day` | BOOLEAN | ✅ | false | - |
| `timezone` | VARCHAR(100) | ❌ | 'UTC' | IANA format |
| `start_timezone` | VARCHAR(100) | ❌ | - | Outlook pattern |
| `end_timezone` | VARCHAR(100) | ❌ | - | Outlook pattern |
| `duration` | INTEGER | ❌ | - | Duration in minutes |

---

## Status Fields

| Field | Type | Required | Default | Values |
|-------|------|----------|---------|--------|
| `status` | VARCHAR(50) | ✅ | 'Planned' | Planned, Confirmed, Completed, Cancelled |
| `event_type` | VARCHAR(50) | ❌ | - | Meeting, Call, Task, Email, Demo, Conference, Training, Interview |
| `show_as` | VARCHAR(50) | ✅ | 'Busy' | **Busy, Free, Tentative, OutOfOffice, WorkingElsewhere** |
| `importance` | VARCHAR(50) | ✅ | 'Normal' | Low, Normal, High |
| `sensitivity` | VARCHAR(50) | ✅ | 'Normal' | Normal, Personal, Private, Confidential |
| `is_cancelled` | BOOLEAN | ✅ | false | ✅ **INDEXED** |
| `is_draft` | BOOLEAN | ✅ | false | - |

---

## Online Meeting Fields 💻

| Field | Type | Required | Default | Notes |
|-------|------|----------|---------|-------|
| `is_online_meeting` | BOOLEAN | ✅ | false | - |
| `online_meeting_provider` | VARCHAR(50) | ❌ | - | Unknown, Zoom, Teams, GoogleMeet, Webex, SkypeForBusiness, SkypeForConsumer |
| `meeting_url` | VARCHAR(500) | ❌ | - | Meeting join URL |
| `meeting_id` | VARCHAR(255) | ❌ | - | Meeting ID |
| `meeting_password` | VARCHAR(255) | ❌ | - | Meeting password |
| `conference_data` | JSONB | ❌ | - | Google conferenceData structure |

---

## Recurrence Fields 🔁 (RFC 5545)

| Field | Type | Required | Default | Notes |
|-------|------|----------|---------|-------|
| `is_recurring` | BOOLEAN | ✅ | false | ✅ INDEXED |
| `recurrence_rule` | TEXT | ❌ | - | RFC 5545 RRULE string |
| `recurrence_exceptions` | JSONB | ❌ | - | Array of exception dates |
| `original_start_time` | TIMESTAMP | ❌ | - | For modified instances |
| `parent_event_id` | UUID | ❌ | - | FK → event (self-ref) |

**RRULE Example:**
```
FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20251231
```

---

## Location Fields 📍

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `location` | VARCHAR(500) | ❌ | Physical location string |
| `location_display_name` | VARCHAR(255) | ❌ | Formatted name |
| `location_url` | VARCHAR(500) | ❌ | Directions URL |
| `location_coordinates` | JSONB | ❌ | `{"latitude": 40.7128, "longitude": -74.0060}` |

---

## External Calendar Sync 🔄

| Field | Type | Required | Index | Notes |
|-------|------|----------|-------|-------|
| `external_calendar_id` | VARCHAR(255) | ❌ | ✅ | Google/Outlook Event ID |
| `external_calendar_provider` | VARCHAR(50) | ❌ | - | Google, Outlook, Apple, Other |
| `ical_uid` | VARCHAR(255) | ❌ | ✅ | iCalendar UID |
| `sequence` | INTEGER | ❌ | - | iCal sequence number |
| `web_link` | VARCHAR(500) | ❌ | - | View in external calendar |
| `html_link` | VARCHAR(500) | ❌ | - | HTML link |
| `extended_properties` | JSONB | ❌ | - | Custom metadata |
| `source` | JSONB | ❌ | - | External source info |

---

## Relationships (Foreign Keys)

| Field | Type | Required | FK Target | Notes |
|-------|------|----------|-----------|-------|
| `organization_id` | UUID | ✅ | organization(id) | CASCADE delete |
| `calendar_id` | UUID | ❌ | *pending* | Will add FK when calendar table exists |
| `organizer_id` | UUID | ❌ | user(id) | Event creator |
| `assigned_to_id` | UUID | ❌ | user(id) | Primary owner (Salesforce pattern) |
| `contact_id` | UUID | ❌ | *pending* | Contact reference (WhoID) |
| `company_id` | UUID | ❌ | *pending* | Company/Account reference |
| `deal_id` | UUID | ❌ | *pending* | Deal/Opportunity reference (WhatID) |
| `parent_event_id` | UUID | ❌ | event(id) | Recurring event parent |

---

## Attendee Management

| Field | Type | Required | Default | Notes |
|-------|------|----------|---------|-------|
| `response_status` | VARCHAR(50) | ❌ | - | NeedsAction, Accepted, Declined, Tentative |
| `response_requested` | BOOLEAN | ✅ | true | Request RSVP |
| `allow_new_time_proposals` | BOOLEAN | ✅ | true | Outlook feature |
| `hide_attendees` | BOOLEAN | ✅ | false | Hide attendee list |

---

## Google Calendar Features

| Field | Type | Required | Default | Notes |
|-------|------|----------|---------|-------|
| `guests_can_modify` | BOOLEAN | ✅ | false | Attendees can edit |
| `guests_can_invite_others` | BOOLEAN | ✅ | true | Invite permissions |
| `guests_can_see_other_guests` | BOOLEAN | ✅ | true | Visibility |
| `transparency` | VARCHAR(50) | ✅ | 'Opaque' | Opaque, Transparent |
| `color_id` | VARCHAR(20) | ❌ | - | Google color ID (1-11) |
| `locked` | BOOLEAN | ✅ | false | Cannot be modified |

---

## Other Fields

| Field | Type | Required | Default | Notes |
|-------|------|----------|---------|-------|
| `reminder_minutes` | INTEGER | ❌ | 15 | Default reminder time |
| `created_at` | TIMESTAMP | ✅ | NOW() | Auto-set |
| `updated_at` | TIMESTAMP | ✅ | NOW() | Auto-update |

---

## Critical Indexes 🚀

### Performance-Critical (Calendar Queries)
- ✅ `idx_event_start_time` - **CRITICAL**
- ✅ `idx_event_end_time` - **CRITICAL**
- ✅ `idx_event_is_cancelled` - **CRITICAL**

### Composite Indexes (Multi-column)
- ✅ `idx_event_calendar_time_range` - (calendar_id, start_time, end_time) WHERE is_cancelled = false
- ✅ `idx_event_organization_time_range` - (organization_id, start_time, end_time) WHERE is_cancelled = false
- ✅ `idx_event_busy_times` - (assigned_to_id, start_time, end_time, show_as) WHERE is_cancelled = false

### Sync Indexes
- ✅ `idx_event_external_calendar_id`
- ✅ `idx_event_ical_uid`

### Filter Indexes
- ✅ `idx_event_status`
- ✅ `idx_event_event_type`
- ✅ `idx_event_is_recurring`
- ✅ `idx_event_name`
- ✅ `idx_event_subject`

---

## Common Query Patterns

### 1. Get Calendar Events (Date Range)
```sql
SELECT * FROM event
WHERE calendar_id = 'uuid'
  AND start_time >= '2025-10-01'
  AND end_time <= '2025-10-31'
  AND is_cancelled = false
ORDER BY start_time;
```
**Uses:** `idx_event_calendar_time_range` (composite)

### 2. Get User's Busy Times
```sql
SELECT start_time, end_time, name FROM event
WHERE assigned_to_id = 'user-uuid'
  AND show_as IN ('Busy', 'OutOfOffice')
  AND is_cancelled = false
ORDER BY start_time;
```
**Uses:** `idx_event_busy_times` (composite)

### 3. Get Today's Meetings
```sql
SELECT * FROM event
WHERE organization_id = 'org-uuid'
  AND start_time >= CURRENT_DATE
  AND start_time < CURRENT_DATE + INTERVAL '1 day'
  AND is_cancelled = false
  AND event_type = 'Meeting'
ORDER BY start_time;
```
**Uses:** `idx_event_organization_time_range`, `idx_event_event_type`

### 4. Get Recurring Events (Master Records)
```sql
SELECT * FROM event
WHERE is_recurring = true
  AND parent_event_id IS NULL
ORDER BY start_time;
```
**Uses:** `idx_event_is_recurring`

### 5. Sync Events from Google Calendar
```sql
SELECT * FROM event
WHERE external_calendar_provider = 'Google'
  AND external_calendar_id IS NOT NULL
ORDER BY updated_at DESC
LIMIT 100;
```
**Uses:** `idx_event_external_calendar_id`

### 6. Get Online Meetings with Zoom
```sql
SELECT * FROM event
WHERE is_online_meeting = true
  AND online_meeting_provider = 'Zoom'
  AND meeting_url IS NOT NULL
  AND start_time > NOW()
ORDER BY start_time;
```

---

## Enum Values Reference

### status
- `Planned` (default)
- `Confirmed`
- `Completed`
- `Cancelled`

### event_type
- `Meeting`
- `Call`
- `Task`
- `Email`
- `Demo`
- `Conference`
- `Training`
- `Interview`

### show_as (Free/Busy Status)
- `Busy` (default) - Blocks calendar
- `Free` - Available
- `Tentative` - Maybe busy
- `OutOfOffice` - OOO
- `WorkingElsewhere` - Working remotely

### importance
- `Low`
- `Normal` (default)
- `High`

### sensitivity
- `Normal` (default)
- `Personal`
- `Private`
- `Confidential`

### online_meeting_provider
- `Unknown`
- `Zoom`
- `Teams`
- `GoogleMeet`
- `Webex`
- `SkypeForBusiness`
- `SkypeForConsumer`

### response_status
- `NeedsAction`
- `Accepted`
- `Declined`
- `Tentative`

### external_calendar_provider
- `Google`
- `Outlook`
- `Apple`
- `Other`

### transparency
- `Opaque` (default) - Blocks time
- `Transparent` - Doesn't block time

---

## API Compatibility

### Google Calendar API Mapping
| Google Field | Event Field |
|--------------|-------------|
| `summary` | `name` |
| `start.dateTime` | `start_time` |
| `end.dateTime` | `end_time` |
| `start.timeZone` | `start_timezone` |
| `hangoutLink` | `meeting_url` |
| `conferenceData` | `conference_data` (JSONB) |
| `recurrence` | `recurrence_rule` |
| `id` | `external_calendar_id` |
| `iCalUID` | `ical_uid` |
| `transparency` | `transparency` |

### Outlook API Mapping
| Outlook Field | Event Field |
|---------------|-------------|
| `subject` | `name` or `subject` |
| `start.dateTime` | `start_time` |
| `start.timeZone` | `start_timezone` |
| `isOnlineMeeting` | `is_online_meeting` |
| `onlineMeetingProvider` | `online_meeting_provider` |
| `showAs` | `show_as` |
| `importance` | `importance` |
| `sensitivity` | `sensitivity` |
| `isCancelled` | `is_cancelled` |

### Salesforce Mapping
| Salesforce Field | Event Field |
|------------------|-------------|
| `Subject` | `name` |
| `StartDateTime` | `start_time` |
| `EndDateTime` | `end_time` |
| `WhoId` | `contact_id` |
| `WhatId` | `company_id` or `deal_id` |
| `OwnerId` | `assigned_to_id` |
| `EventSubtype` | `event_type` |
| `ShowAs` | `show_as` |

---

## Next Steps

1. **Create Symfony Entity** - `src/Entity/Event.php`
2. **Create Repository** - `src/Repository/EventRepository.php`
3. **Create API Resource** - Add API Platform annotations
4. **Create Security Voter** - `src/Security/Voter/EventVoter.php`
5. **Create Form Type** - `src/Form/EventType.php`
6. **Add supporting tables**:
   - Calendar
   - EventAttendee
   - Reminder
   - Contact (if not exists)
   - Company (if not exists)
   - Deal (if not exists)

---

**Database:** PostgreSQL 18
**Location:** `/home/user/inf/app/migrations/`
**Files:**
- `event_migration.sql` (full version)
- `event_migration_safe.sql` (executed version)
- `event_migration_report.md` (detailed report)
- `EVENT_TABLE_QUICK_REFERENCE.md` (this file)
