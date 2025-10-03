# TreeFlow API Documentation

## Overview

The TreeFlow API provides complete access to the TreeFlow guidance system for AI agents. This API supports full CRUD operations with deep nested data structures including Steps, Questions, FewShot Examples, Outputs, and Inputs.

**Base URL:** `/treeflow`
**Authentication:** Required (ROLE_ORGANIZATION_ADMIN or ROLE_ADMIN)
**Format:** JSON

---

## Endpoints

### GET /treeflow/api/search

Returns a paginated list of TreeFlows with complete nested data structure.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `q` | string | - | Search query (searches in name and description) |
| `page` | integer | 1 | Page number for pagination |
| `pageSize` | integer | 20 | Number of items per page (max: 100) |
| `sortBy` | string | name | Field to sort by (name, version, createdAt, updatedAt) |
| `sortOrder` | string | asc | Sort order (asc or desc) |

#### Example Request

```http
GET /treeflow/api/search?q=customer&page=1&pageSize=10&sortBy=name&sortOrder=asc
```

#### Response Structure

```json
{
  "treeflows": [
    {
      "id": "019296b7-55be-72db-8cfd-67fd41234567",
      "name": "Customer Support Flow",
      "slug": "customer_support_flow",
      "version": 3,
      "active": true,
      "organizationId": "019296b7-1234-5678-9abc-def012345678",
      "organizationName": "Acme Corporation",
      "stepsCount": 5,
      "createdAt": "2025-01-15T10:30:00+00:00",
      "createdAtFormatted": "Jan 15, 2025",
      "updatedAtFormatted": "Jan 20, 2025",
      "createdByName": "John Doe",

      "steps": [
        {
          "id": "019296b7-abcd-1234-5678-9abcdef01234",
          "name": "Initial Greeting",
          "slug": "initial_greeting",
          "first": true,
          "objective": "Welcome the customer warmly and identify their issue",
          "prompt": "Greet the customer professionally and ask how you can help them today.",

          "questions": [
            {
              "id": "019296b7-qqqq-1234-5678-9abcdef01234",
              "name": "Customer Sentiment",
              "slug": "customer_sentiment",
              "prompt": "What is the customer's emotional state?",
              "objective": "Identify if customer is calm, frustrated, or angry",
              "importance": 9,
              "viewOrder": 1,

              "examples": [
                {
                  "id": "019296b7-ffff-1234-5678-9abcdef01234",
                  "type": "positive",
                  "name": "Calm Customer",
                  "slug": "calm_customer",
                  "prompt": "Customer: 'Hello, I have a question about my order'",
                  "description": "Example of a calm, straightforward inquiry"
                },
                {
                  "id": "019296b7-nnnn-1234-5678-9abcdef01234",
                  "type": "negative",
                  "name": "Frustrated Customer",
                  "slug": "frustrated_customer",
                  "prompt": "Customer: 'This is the third time I'm calling about this!'",
                  "description": "Example of an escalated, frustrated customer"
                }
              ]
            }
          ],

          "outputs": [
            {
              "id": "019296b7-oooo-1234-5678-9abcdef01234",
              "name": "Route to Support",
              "slug": "route_to_support",
              "description": "Customer needs technical assistance",
              "conditional": "keywords:technical,bug,error,not working",
              "destinationStepId": "019296b7-ssss-1234-5678-9abcdef01234",
              "destinationStepName": "Technical Support"
            },
            {
              "id": "019296b7-pppp-1234-5678-9abcdef01234",
              "name": "Route to Billing",
              "slug": "route_to_billing",
              "description": "Customer has billing or payment questions",
              "conditional": "keywords:payment,invoice,bill,charge",
              "destinationStepId": "019296b7-bbbb-1234-5678-9abcdef01234",
              "destinationStepName": "Billing Support"
            }
          ],

          "inputs": [
            {
              "id": "019296b7-iiii-1234-5678-9abcdef01234",
              "name": "From Previous Escalation",
              "slug": "from_previous_escalation",
              "type": "not_completed_after_attempts",
              "sourceStepId": "019296b7-eeee-1234-5678-9abcdef01234",
              "sourceStepName": "Escalation Step",
              "prompt": "Customer was escalated from another step"
            }
          ]
        }
      ]
    }
  ],
  "total": 42,
  "page": 1,
  "pageSize": 10,
  "totalPages": 5
}
```

---

## Data Models

### TreeFlow

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier (UUIDv7) |
| name | string | TreeFlow name |
| slug | string | URL-friendly identifier |
| version | integer | Auto-incrementing version number |
| active | boolean | Whether the TreeFlow is active |
| organizationId | UUID | Organization that owns this TreeFlow |
| organizationName | string | Organization name |
| stepsCount | integer | Number of steps in this TreeFlow |
| createdAt | ISO8601 | Creation timestamp |
| createdAtFormatted | string | Human-readable creation date |
| updatedAtFormatted | string | Human-readable update date |
| createdByName | string | Name of creator |
| steps | Step[] | Array of Step objects |

### Step

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier |
| name | string | Step name |
| slug | string | URL-friendly identifier |
| first | boolean | Whether this is the first step |
| objective | string | Step objective description |
| prompt | string | AI prompt for this step |
| questions | Question[] | Array of Question objects |
| outputs | StepOutput[] | Array of output routing rules |
| inputs | StepInput[] | Array of input entry rules |

### Question

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier |
| name | string | Question name |
| slug | string | URL-friendly identifier |
| prompt | string | Question prompt for AI |
| objective | string | Question objective |
| importance | integer | Importance level (1-10) |
| viewOrder | integer | Display order |
| examples | FewShotExample[] | Array of example answers |

### FewShotExample

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier |
| type | enum | "positive" or "negative" |
| name | string | Example name |
| slug | string | URL-friendly identifier |
| prompt | string | Example prompt/response |
| description | string | Example description |

### StepOutput

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier |
| name | string | Output name |
| slug | string | URL-friendly identifier |
| description | string | Output description |
| conditional | string | Routing condition (regex or keywords) |
| destinationStepId | UUID | Next step ID (nullable) |
| destinationStepName | string | Next step name (nullable) |

### StepInput

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier |
| name | string | Input name |
| slug | string | URL-friendly identifier |
| type | enum | "fully_completed", "not_completed_after_attempts", or "any" |
| sourceStepId | UUID | Previous step ID (nullable) |
| sourceStepName | string | Previous step name (nullable) |
| prompt | string | Entry prompt |

---

## Conditional Expressions

Outputs support conditional routing using the following formats:

### Keyword Matching
```
keywords:urgent,high-priority,escalate
```
Matches if any of the keywords are found in the response.

### Regex Matching
```
regex:/\b(urgent|critical|emergency)\b/i
```
Matches using regular expressions (case-insensitive with `/i` flag).

### Custom Expressions
Any freeform expression can be stored and evaluated by your application logic.

---

## Input Types

StepInput supports three entry types:

- **`fully_completed`**: Step must be 100% completed before routing here
- **`not_completed_after_attempts`**: Step failed after multiple attempts
- **`any`**: Any status allows routing to this step

---

## Security

### Authentication
All endpoints require authentication. Users must have one of the following roles:
- `ROLE_ORGANIZATION_ADMIN` - Can manage TreeFlows for their organization
- `ROLE_ADMIN` - Can manage TreeFlows for any organization
- `ROLE_SUPER_ADMIN` - Full system access

### Authorization
- Organization admins can only access TreeFlows for their own organization
- System admins can access all TreeFlows
- The API enforces organization-based data isolation via Doctrine filters

---

## Performance

### Query Optimization
The API uses eager loading to prevent N+1 query problems:
- Single optimized query loads TreeFlow with all nested relations
- Left joins for: steps → questions → fewShotExamples, outputs, inputs
- Recommended for large TreeFlows (10+ steps, 50+ questions)

### Pagination
- Default page size: 20 items
- Maximum page size: 100 items
- Use pagination for large result sets to maintain performance

---

## Error Responses

### 400 Bad Request
```json
{
  "error": "Invalid parameter",
  "message": "pageSize must be between 1 and 100"
}
```

### 401 Unauthorized
```json
{
  "error": "Authentication required",
  "message": "You must be logged in to access this resource"
}
```

### 403 Forbidden
```json
{
  "error": "Access denied",
  "message": "You do not have permission to access this TreeFlow"
}
```

### 404 Not Found
```json
{
  "error": "Not found",
  "message": "TreeFlow not found"
}
```

---

## Examples

### Search for Active TreeFlows
```http
GET /treeflow/api/search?active=true&sortBy=updatedAt&sortOrder=desc
```

### Get Recently Updated TreeFlows
```http
GET /treeflow/api/search?sortBy=updatedAt&sortOrder=desc&pageSize=5
```

### Search by Name
```http
GET /treeflow/api/search?q=customer%20support
```

---

## Version History

The TreeFlow version is auto-incremented on every update:
- Initial creation: version = 1
- Each edit: version automatically increments
- Version is immutable and cannot be manually set

---

## Best Practices

1. **Use Pagination**: Always paginate large result sets
2. **Cache Responses**: Cache TreeFlow data when possible (check `updatedAt` for changes)
3. **Handle Null Values**: destinationStepId and sourceStepId can be null
4. **Validate Conditionals**: Ensure conditional expressions are valid before saving
5. **Monitor Performance**: Large TreeFlows (100+ questions) may require optimization

---

## Support

For API support or to report issues:
- GitHub: https://github.com/anthropics/claude-code/issues
- Documentation: /home/user/inf/CLAUDE.md

---

**Last Updated:** Phase 6 - January 2025
**API Version:** 1.0
