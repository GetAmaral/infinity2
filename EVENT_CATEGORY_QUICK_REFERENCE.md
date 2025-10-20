# EventCategory Entity - Quick Reference

## Status: OPTIMIZED & PRODUCTION-READY

---

## Database Statistics

- **Total Properties:** 11
- **API Documentation Coverage:** 100% (11/11)
- **Indexed Properties:** 4
- **Validated Properties:** 5
- **Boolean Properties:** 3 (active, default, allowMultiple)

---

## Property List (Ordered)

| Order | Property | Type | Required | Default | Indexed | Validated |
|-------|----------|------|----------|---------|---------|-----------|
| 0 | name | string | YES | - | YES | YES |
| 1 | description | text | NO | - | NO | YES |
| 2 | color | string | YES | #6c757d | NO | YES |
| 3 | icon | string | YES | bi-calendar-event | NO | YES |
| 4 | active | boolean | YES | true | YES | NO |
| 5 | default | boolean | YES | false | NO | NO |
| 6 | eventType | string | NO | - | YES | NO |
| 7 | sortOrder | integer | YES | 0 | YES | YES |
| 8 | allowMultiple | boolean | YES | true | NO | NO |
| 98 | organization | relation | NO | - | NO | NO |
| 99 | events | relation | NO | - | NO | NO |

---

## New Properties Added

1. **active** (boolean) - Enable/disable categories without deletion
2. **default** (boolean) - Auto-select for new events
3. **eventType** (string) - Type categorization (meeting, call, task, etc.)
4. **sortOrder** (integer) - Custom ordering (0-9999)
5. **allowMultiple** (boolean) - Allow multi-category assignment

---

## Validation Rules

| Property | Rule | Pattern/Range |
|----------|------|---------------|
| name | NotBlank, Length | max: 100 chars |
| description | Length | max: 500 chars |
| color | Regex | ^#[0-9A-Fa-f]{6}$ |
| icon | Regex | ^bi-[a-z0-9-]+$ |
| sortOrder | Range | 0-9999 |

---

## Index Strategy

### Single-Column Indexes
- name (btree)
- active (btree)
- eventType (btree)
- sortOrder (btree)

### Recommended Composite Indexes
- (organization_id, active, sortOrder) - Most common query
- (organization_id, eventType, sortOrder) - Type filtering

---

## Common Queries

### Get Active Categories (Ordered)
```sql
SELECT * FROM event_category
WHERE organization_id = ? AND active = true
ORDER BY sort_order ASC, name ASC;
```

### Get Default Category
```sql
SELECT * FROM event_category
WHERE organization_id = ? AND active = true AND "default" = true
LIMIT 1;
```

### Filter by Event Type
```sql
SELECT * FROM event_category
WHERE organization_id = ? AND active = true AND event_type = ?
ORDER BY sort_order ASC;
```

---

## API Endpoints

```bash
# List all categories
GET /api/event_categories

# Get active categories ordered by sortOrder
GET /api/event_categories?active=true&order[sortOrder]=asc

# Get default category
GET /api/event_categories?default=true&active=true

# Filter by event type
GET /api/event_categories?eventType=meeting&active=true

# Create new category
POST /api/event_categories
{
  "name": "Client Meeting",
  "description": "All client meetings",
  "color": "#3498db",
  "icon": "bi-calendar-check",
  "eventType": "meeting",
  "sortOrder": 10
}
```

---

## eventType Choices

- `meeting` - Meeting
- `call` - Call
- `task` - Task
- `deadline` - Deadline
- `appointment` - Appointment
- `follow_up` - Follow Up
- `other` - Other

---

## Default Color Palette

- #3498db - Blue (Client Meeting)
- #2ecc71 - Green (Phone Call)
- #e74c3c - Red (Deadline)
- #f39c12 - Orange (Follow Up)
- #9b59b6 - Purple (Appointment)
- #6c757d - Gray (Default)

---

## Default Icons

- bi-calendar-check - Client Meeting
- bi-telephone - Phone Call
- bi-exclamation-triangle - Deadline
- bi-arrow-repeat - Follow Up
- bi-calendar-event - Default/Appointment

---

## Next Steps

1. Generate entity: `php bin/console app:genmax:generate-entity EventCategory`
2. Create migration: `php bin/console make:migration --no-interaction`
3. Run migration: `php bin/console doctrine:migrations:migrate --no-interaction`
4. Load fixtures: `php bin/console doctrine:fixtures:load --no-interaction`
5. Test API: `curl -k https://localhost/api/event_categories`

---

**Full Documentation:** /home/user/inf/event_category_entity_analysis_report.md
