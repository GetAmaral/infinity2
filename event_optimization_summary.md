# Event Entity Optimization - Summary Report

## Executive Summary

The Event entity has been analyzed and optimized for modern calendar/meeting management with full compatibility for Google Calendar, Microsoft Outlook, and Salesforce Event objects. The optimization focuses on:

1. **Calendar API Integration** - Full support for Google Calendar and Outlook Graph API
2. **Online Meeting Support** - Zoom, Teams, Google Meet integration
3. **CRM Integration** - Links to Contact, Company, Deal (Salesforce pattern)
4. **Multi-timezone Support** - Proper timezone handling for global teams
5. **Performance** - Critical indexes for calendar queries

---

## Current State Analysis

### Strengths ✓
- Has calendar relationship
- Supports recurrence (interval, frequency, end date)
- Has attendee relationship (OneToMany)
- Conference data support (Google Hangout)
- Extended properties for API sync

### Critical Gaps ✗
- **NO INDEXES on startTime/endTime** - Calendar queries will be slow
- Missing timezone support for multi-timezone handling
- No showAs/free-busy status (Busy, Free, Tentative, OutOfOffice)
- No eventType categorization (Meeting, Call, Task, Demo)
- Missing CRM relationships (Contact, Company, Deal)
- No assignedTo user (primary owner)
- No meeting provider enum (Zoom, Teams, Google Meet)
- Recurrence not RFC 5545 compliant
- Missing isCancelled, isDraft status flags

---

## Key Recommendations

### CRITICAL (Must Fix Immediately)

1. **Add Indexes to startTime and endTime**
   - Calendar queries filter by date range
   - Without indexes, performance will degrade rapidly
   - **Action**: Add composite index on (startTime, endTime, isCancelled)

2. **Add Status Field**
   - Replace integer eventStatus with enum
   - Values: Planned, Confirmed, Completed, Cancelled
   - **Action**: Add indexed string field with enum constraint

3. **Add ShowAs Field**
   - Free/busy status for calendar availability
   - Values: Busy, Free, Tentative, OutOfOffice, WorkingElsewhere
   - **Action**: Add string field with enum constraint

4. **Add Timezone Support**
   - timezone (IANA timezone name: America/New_York)
   - startTimezone, endTimezone (Outlook pattern)
   - **Action**: Add 3 string fields for timezone handling

5. **Add Online Meeting Fields**
   - isOnlineMeeting (boolean flag)
   - onlineMeetingProvider (enum: Zoom, Teams, GoogleMeet, Webex, etc.)
   - meetingUrl (rename from hangoutLink)
   - meetingId, meetingPassword
   - **Action**: Add 5 fields for modern online meetings

6. **Add CRM Relationships**
   - contact (ManyToOne to Contact) - Salesforce WhoID pattern
   - company (ManyToOne to Company) - Salesforce WhatID pattern
   - deal (ManyToOne to Deal) - Salesforce WhatID pattern
   - **Action**: Add 3 relationship fields

7. **Replace Recurrence Fields**
   - Current: recurrenceInterval, recurrenceFrequency, recurrenceEndDate, recurrenceCount
   - Replace with: recurrenceRule (RFC 5545 RRULE string)
   - Add: isRecurring (boolean flag), recurrenceExceptions (JSON array)
   - **Action**: Migrate to standard RRULE format

8. **Add Cancellation/Draft Flags**
   - isCancelled (indexed boolean)
   - isDraft (boolean)
   - **Action**: Add 2 boolean fields

9. **Add External Calendar Sync**
   - externalCalendarId (indexed string) - Google/Outlook event ID
   - externalCalendarProvider (enum: Google, Outlook, Apple, Other)
   - icalUid (indexed string) - cross-platform sync
   - **Action**: Add 3 fields with 2 indexes

---

### HIGH Priority

10. **Add Event Type**
    - Values: Meeting, Call, Task, Email, Demo, Conference, Training, Interview
    - **Action**: Add indexed string field

11. **Add AssignedTo Relationship**
    - Primary owner/participant (Salesforce pattern)
    - **Action**: Add ManyToOne to User

12. **Add Importance and Sensitivity**
    - importance: Low, Normal, High (Outlook standard)
    - sensitivity: Normal, Personal, Private, Confidential
    - **Action**: Add 2 enum fields

13. **Add Location Enhancements**
    - locationDisplayName (formatted name)
    - locationUrl (directions/details)
    - locationCoordinates (JSON: {latitude, longitude})
    - **Action**: Add 3 fields

14. **Add Attendee Control**
    - hideAttendees, responseRequested, allowNewTimeProposals
    - **Action**: Add 3 boolean fields

---

### MEDIUM Priority

15. **Add Google-Specific Fields**
    - transparency (Opaque/Transparent)
    - colorId (1-11)
    - guestsCanModify, guestsCanInviteOthers, guestsCanSeeOtherGuests
    - **Action**: Add 5 fields

16. **Add Calendar Links**
    - webLink (view in external calendar)
    - htmlLink (HTML link)
    - **Action**: Add 2 string fields

17. **Add Duration Field**
    - Duration in minutes (useful for reporting)
    - **Action**: Add integer field

18. **Rename allDay to isAllDay**
    - Consistency with boolean naming
    - **Action**: Rename field

---

### LOW Priority

19. **Add Locked Field**
    - Google pattern - event cannot be modified
    - **Action**: Add boolean field

20. **Consider Subject Field**
    - Salesforce/Outlook use "subject" instead of "name"
    - Options: (a) Keep name only, (b) Add subject as alias
    - **Action**: Decide on naming strategy

---

## Properties to Remove

| Property | Reason |
|----------|--------|
| geo | Too vague - use locationCoordinates instead |
| eventStatus (integer) | Replace with status enum |
| visibility (boolean) | Replace with sensitivity enum |
| priority (integer) | Replace with importance enum |
| recurrenceInterval | Consolidate into recurrenceRule |
| recurrenceFrequency | Consolidate into recurrenceRule |
| recurrenceEndDate | Consolidate into recurrenceRule |
| recurrenceCount | Consolidate into recurrenceRule |
| notifications | Duplicate of reminders |
| meetingDatas | Consolidate into conferenceData JSON |
| workingHours | Belongs to User/Calendar, not Event |
| holidays | Belongs to Calendar/Organization |
| notes | Duplicate of description |

---

## Properties to Rename

| From | To | Reason |
|------|-----|--------|
| allDay | isAllDay | Boolean naming consistency |
| hangoutLink | meetingUrl | Generic meeting URL |

---

## Index Strategy

### Critical Indexes (Performance)
```sql
CREATE INDEX idx_event_date_range ON event (start_time, end_time, is_cancelled);
CREATE INDEX idx_event_external_id ON event (external_calendar_id);
CREATE INDEX idx_event_ical_uid ON event (ical_uid);
```

### Important Indexes (Filtering)
```sql
CREATE INDEX idx_event_status ON event (status);
CREATE INDEX idx_event_type ON event (event_type);
CREATE INDEX idx_event_cancelled ON event (is_cancelled);
CREATE INDEX idx_event_recurring ON event (is_recurring);
```

---

## Calendar Integration Compatibility

### Google Calendar API ✓
- **Event ID**: externalCalendarId
- **iCalendar UID**: icalUid
- **Recurrence**: recurrenceRule (RRULE array)
- **Conference**: conferenceData (JSON)
- **Transparency**: transparency field
- **Guest Permissions**: guestsCanModify, guestsCanInviteOthers, guestsCanSeeOtherGuests
- **Extended Properties**: extendedProperties (JSON)

### Microsoft Outlook Graph API ✓
- **Event ID**: externalCalendarId
- **Subject**: name/subject
- **ShowAs**: showAs field
- **Importance**: importance field
- **Sensitivity**: sensitivity field
- **Online Meeting**: isOnlineMeeting, onlineMeetingProvider, meetingUrl
- **Time Proposals**: allowNewTimeProposals
- **Attendee Privacy**: hideAttendees
- **Timezone**: startTimezone, endTimezone

### Salesforce Event Object ✓
- **Subject**: name/subject
- **WhoID**: contact (Contact reference)
- **WhatID**: company/deal (Account/Opportunity)
- **OwnerId**: assignedTo
- **EventSubtype**: eventType
- **ShowAs**: showAs

---

## Data Migration Strategy

### Step 1: Add New Fields
- Add all new fields with nullable=true initially
- Add indexes to startTime, endTime

### Step 2: Data Transformation
```php
// eventStatus (integer) -> status (enum)
$statusMap = [
    0 => 'Planned',
    1 => 'Confirmed',
    2 => 'Completed',
    3 => 'Cancelled'
];

// visibility (boolean) -> sensitivity (enum)
$sensitivityMap = [
    true => 'Private',
    false => 'Normal'
];

// priority (integer) -> importance (enum)
$importanceMap = [
    1 => 'Low',
    2 => 'Normal',
    3 => 'High'
];

// recurrence fields -> recurrenceRule (RRULE)
// Example: FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR;UNTIL=20251231
if ($recurrenceFrequency && $recurrenceInterval) {
    $freq = ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'][$recurrenceFrequency];
    $rrule = "FREQ={$freq};INTERVAL={$recurrenceInterval}";
    if ($recurrenceEndDate) {
        $rrule .= ";UNTIL=" . $recurrenceEndDate->format('Ymd');
    } elseif ($recurrenceCount) {
        $rrule .= ";COUNT={$recurrenceCount}";
    }
    $event->setRecurrenceRule($rrule);
    $event->setIsRecurring(true);
}

// hangoutLink -> meetingUrl
if ($hangoutLink) {
    $event->setMeetingUrl($hangoutLink);
    $event->setIsOnlineMeeting(true);
    $event->setOnlineMeetingProvider('GoogleMeet');
}

// allDay -> isAllDay
$event->setIsAllDay($event->getAllDay());
```

### Step 3: Remove Old Fields
- Drop: geo, eventStatus, visibility, priority
- Drop: recurrenceInterval, recurrenceFrequency, recurrenceEndDate, recurrenceCount
- Drop: notifications, meetingDatas, workingHours, holidays, notes
- Drop: allDay, hangoutLink

---

## Example Calendar Queries

### Get events for date range
```php
$events = $repository->createQueryBuilder('e')
    ->where('e.startTime >= :start')
    ->andWhere('e.endTime <= :end')
    ->andWhere('e.isCancelled = false')
    ->setParameter('start', $startDate)
    ->setParameter('end', $endDate)
    ->getQuery()
    ->getResult();
```

### Get user's busy times
```php
$busyEvents = $repository->createQueryBuilder('e')
    ->where('e.assignedTo = :user')
    ->andWhere('e.showAs IN (:busyStatuses)')
    ->andWhere('e.isCancelled = false')
    ->andWhere('e.startTime >= :start')
    ->andWhere('e.endTime <= :end')
    ->setParameter('user', $user)
    ->setParameter('busyStatuses', ['Busy', 'OutOfOffice'])
    ->setParameter('start', $startDate)
    ->setParameter('end', $endDate)
    ->getQuery()
    ->getResult();
```

### Get online meetings
```php
$onlineMeetings = $repository->createQueryBuilder('e')
    ->where('e.isOnlineMeeting = true')
    ->andWhere('e.meetingUrl IS NOT NULL')
    ->andWhere('e.isCancelled = false')
    ->getQuery()
    ->getResult();
```

### Sync with Google Calendar
```php
$googleEvents = $repository->createQueryBuilder('e')
    ->where('e.externalCalendarProvider = :provider')
    ->andWhere('e.externalCalendarId IS NOT NULL')
    ->setParameter('provider', 'Google')
    ->getQuery()
    ->getResult();
```

---

## Implementation Checklist

### Phase 1: Critical Performance (Week 1)
- [ ] Add indexes to startTime and endTime
- [ ] Add status field (replace eventStatus)
- [ ] Add showAs field
- [ ] Add timezone fields (timezone, startTimezone, endTimezone)
- [ ] Add isCancelled and isDraft flags
- [ ] Test calendar query performance

### Phase 2: Online Meetings (Week 2)
- [ ] Add isOnlineMeeting, onlineMeetingProvider
- [ ] Rename hangoutLink to meetingUrl
- [ ] Add meetingId, meetingPassword
- [ ] Test Zoom/Teams integration

### Phase 3: CRM Integration (Week 3)
- [ ] Add contact relationship
- [ ] Add company relationship
- [ ] Add deal relationship
- [ ] Add assignedTo relationship
- [ ] Test Salesforce sync

### Phase 4: Recurrence & External Sync (Week 4)
- [ ] Replace recurrence fields with recurrenceRule
- [ ] Add isRecurring flag
- [ ] Add recurrenceExceptions JSON
- [ ] Add externalCalendarId with index
- [ ] Add icalUid with index
- [ ] Add externalCalendarProvider
- [ ] Test Google Calendar sync
- [ ] Test Outlook sync

### Phase 5: Additional Features (Week 5)
- [ ] Add eventType field
- [ ] Add importance and sensitivity
- [ ] Add location enhancements
- [ ] Add attendee control flags
- [ ] Add Google-specific fields
- [ ] Add duration field
- [ ] Rename allDay to isAllDay

### Phase 6: Cleanup (Week 6)
- [ ] Migrate data from old fields to new fields
- [ ] Remove deprecated fields
- [ ] Update all forms and templates
- [ ] Update API endpoints
- [ ] Update tests
- [ ] Deploy to production

---

## File Locations

- **Optimization JSON**: `/home/user/inf/event_optimization.json`
- **Summary Report**: `/home/user/inf/event_optimization_summary.md`

---

## Next Steps

1. Review the optimization JSON for complete field details
2. Prioritize implementation based on business needs
3. Create database migrations for Phase 1 (critical performance)
4. Update Event entity class
5. Update forms and controllers
6. Test calendar functionality
7. Deploy incrementally by phase

---

**Generated**: 2025-10-18
**Version**: 1.0
