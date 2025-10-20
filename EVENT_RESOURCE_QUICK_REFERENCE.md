# EventResource Entity - Quick Reference

**Last Updated:** 2025-10-19
**Entity ID:** 0199cadd-64f5-708e-917f-599e80f17954
**Status:** ✓ Compliant with 2025 CRM Best Practices

---

## Entity Overview

**Purpose:** Manage bookable resources (meeting rooms, equipment, vehicles, facilities)

**Total Properties:** 25
- Required: 8
- Optional: 17
- Relationships: 3 (organization, type, city)
- Collections: 1 (eventBookings)

---

## Quick Property Reference

### Core Identification
| Property | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| name | string | Yes | - | Display name of the resource |
| type | ManyToOne | Yes | - | Resource type (EventResourceType) |
| organization | ManyToOne | Yes | - | Owning organization |

### Status Flags (Boolean)
| Property | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| active | boolean | Yes | true | Resource is active in system |
| available | boolean | Yes | true | Resource is available for booking |
| bookable | boolean | Yes | true | Resource can be booked online/API |
| requiresApproval | boolean | Yes | false | Bookings need approval |
| autoConfirm | boolean | Yes | true | Bookings auto-confirm |

### Location & Description
| Property | Type | Required | Description |
|----------|------|----------|-------------|
| location | string | No | Physical location/address |
| geoCoordinates | string | No | Latitude,longitude coordinates |
| city | ManyToOne | No | City reference |
| description | text | No | Detailed description |

### Capacity & Scheduling
| Property | Type | Required | Description |
|----------|------|----------|-------------|
| capacity | integer | No | Maximum people/items |
| timezone | string | No | Resource timezone |
| minimumBookingDuration | integer | No | Min booking minutes (default: 30) |
| maximumBookingDuration | integer | No | Max booking minutes |
| availabilitySchedule | json | No | Weekly availability schedule |

### Pricing
| Property | Type | Required | Description |
|----------|------|----------|-------------|
| pricePerHour | decimal(10,2) | No | Hourly rental cost |
| pricePerDay | decimal(10,2) | No | Daily rental cost |

### Assets & Metadata
| Property | Type | Required | Description |
|----------|------|----------|-------------|
| imageUrl | string | No | Main image URL |
| thumbnailUrl | string | No | Thumbnail image URL |
| tags | json | No | Flexible categorization tags |
| equipment | json | No | Available equipment/amenities |
| bookingRules | json | No | Custom booking rules |

### Relationships
| Property | Type | Target | Description |
|----------|------|--------|-------------|
| eventBookings | OneToMany | EventResourceBooking | All bookings for this resource |

---

## Naming Conventions

**CORRECT:**
- active, available, bookable ✓
- requiresApproval, autoConfirm ✓

**INCORRECT:**
- ✗ isActive, isAvailable, isBookable
- ✗ is_active, is_available

---

## JSON Schemas

### equipment
```json
{
  "items": ["projector", "whiteboard", "video_conference", "phone"],
  "notes": "All equipment included in booking"
}
```

### bookingRules
```json
{
  "maxAdvanceBookingDays": 90,
  "bufferMinutes": 15,
  "allowWeekends": true,
  "allowedRoles": ["ROLE_EMPLOYEE", "ROLE_MANAGER"]
}
```

### availabilitySchedule
```json
{
  "monday": [{"start": "09:00", "end": "17:00"}],
  "tuesday": [{"start": "09:00", "end": "17:00"}],
  "wednesday": [{"start": "09:00", "end": "17:00"}],
  "thursday": [{"start": "09:00", "end": "17:00"}],
  "friday": [{"start": "09:00", "end": "17:00"}]
}
```

### tags
```json
["projector", "whiteboard", "video-conference", "accessibility"]
```

---

## API Examples

### Get Available Bookable Resources
```http
GET /api/event_resources?active=true&available=true&bookable=true
```

### Create New Resource
```http
POST /api/event_resources
Content-Type: application/json

{
  "name": "Conference Room A",
  "description": "Large meeting room with video conferencing",
  "location": "Building 2, Floor 3, Room 301",
  "geoCoordinates": "40.7128,-74.0060",
  "capacity": 20,
  "active": true,
  "available": true,
  "bookable": true,
  "requiresApproval": false,
  "autoConfirm": true,
  "timezone": "America/Sao_Paulo",
  "minimumBookingDuration": 30,
  "maximumBookingDuration": 480,
  "pricePerHour": 50.00,
  "pricePerDay": 300.00,
  "equipment": {
    "items": ["projector", "whiteboard", "video_conference"],
    "notes": "All equipment included"
  },
  "tags": ["projector", "whiteboard", "video-conference"],
  "type": "/api/event_resource_types/xxx",
  "organization": "/api/organizations/yyy"
}
```

### Filter by Type and City
```http
GET /api/event_resources?type=/api/event_resource_types/xxx&city=/api/cities/yyy
```

---

## Validation Rules

### Required Fields
- name (NotBlank)
- active (NotNull, default: true)
- available (NotNull, default: true)
- bookable (NotNull, default: true)
- requiresApproval (NotNull, default: false)
- autoConfirm (NotNull, default: true)
- organization (NotNull)
- type (NotNull)

### Optional Validations
- capacity: GreaterThanOrEqual 1
- geoCoordinates: Regex pattern for lat,lng
- minimumBookingDuration: GreaterThanOrEqual 15
- maximumBookingDuration: GreaterThan 0
- pricePerHour: GreaterThanOrEqual 0
- pricePerDay: GreaterThanOrEqual 0
- imageUrl: Valid URL
- thumbnailUrl: Valid URL
- timezone: Valid timezone identifier

---

## Common Queries

### Find All Available Meeting Rooms in a City
```sql
SELECT er.*
FROM event_resource er
JOIN event_resource_type ert ON er.type_id = ert.id
WHERE er.active = true
  AND er.available = true
  AND er.bookable = true
  AND ert.name = 'Meeting Room'
  AND er.city_id = 'xxx'
ORDER BY er.capacity DESC;
```

### Check Resource Availability
```sql
SELECT COUNT(*) = 0 as is_available
FROM event_resource_booking
WHERE resource_id = 'xxx'
  AND status IN ('confirmed', 'pending')
  AND start_time < '2025-10-19 17:00:00'
  AND end_time > '2025-10-19 15:00:00';
```

---

## Best Practices

### 1. Always Check Availability Flags
```php
if ($resource->isActive() && $resource->isAvailable() && $resource->isBookable()) {
    // Allow booking
}
```

### 2. Respect Booking Duration Constraints
```php
$duration = $endTime->diff($startTime)->i; // minutes
if ($duration < $resource->getMinimumBookingDuration()) {
    throw new ValidationException('Booking too short');
}
if ($resource->getMaximumBookingDuration() &&
    $duration > $resource->getMaximumBookingDuration()) {
    throw new ValidationException('Booking too long');
}
```

### 3. Handle Approval Workflow
```php
if ($resource->isRequiresApproval()) {
    $booking->setStatus('pending_approval');
} elseif ($resource->isAutoConfirm()) {
    $booking->setStatus('confirmed');
} else {
    $booking->setStatus('pending');
}
```

### 4. Use Timezone-Aware Scheduling
```php
$resourceTz = new DateTimeZone($resource->getTimezone() ?? $org->getTimezone());
$bookingStart = new DateTime($startTime, $resourceTz);
```

---

## Files

**Report:** `/home/user/inf/event_resource_entity_analysis_report.md`
**SQL Script:** `/home/user/inf/scripts/fix_event_resource_entity.sql`
**This Reference:** `/home/user/inf/EVENT_RESOURCE_QUICK_REFERENCE.md`

---

**Status:** ✓ Production Ready - Compliant with 2025 CRM Best Practices
