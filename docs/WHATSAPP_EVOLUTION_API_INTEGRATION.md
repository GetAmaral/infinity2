# WhatsApp Integration via Evolution API - Implementation Plan

**Version:** 1.0
**Date:** November 13, 2025
**Status:** Ready for Implementation

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Architecture Overview](#architecture-overview)
3. [Prerequisites](#prerequisites)
4. [Database Changes](#database-changes)
5. [Component Specifications](#component-specifications)
6. [Implementation Steps](#implementation-steps)
7. [Configuration Guide](#configuration-guide)
8. [Testing Guide](#testing-guide)
9. [Troubleshooting](#troubleshooting)
10. [Limitations & Future Enhancements](#limitations--future-enhancements)

---

## Executive Summary

This plan implements WhatsApp messaging integration using Evolution API v2, enabling AI agents (powered by TreeFlow SDR system) to have automated conversations via WhatsApp.

### Key Features

✅ **Bidirectional Communication** - Receive and send WhatsApp messages
✅ **Automatic Contact Creation** - Creates contacts from WhatsApp with NO dummy emails
✅ **Talk Continuity** - Maintains conversation context across sessions
✅ **Multi-Tenant Support** - Full organization isolation
✅ **AI Integration** - Seamless integration with existing TreeFlow AI agents
✅ **Retry Logic** - Automatic retry with exponential backoff for failed sends
✅ **Simple Architecture** - Agent entity handles WhatsApp config (no extra entities)

### Architectural Decisions

1. **No WhatsAppInstance Entity** - Agent entity extended with WhatsApp fields
2. **Email Nullable** - Contact.email is optional, no dummy emails
3. **Simple Routing** - One WhatsApp number per Agent (1:1 mapping by instance name)
4. **Retry Mechanism** - 3 attempts with exponential backoff
5. **Phone Uniqueness** - Database constraints ensure unique phone numbers per organization

### Implementation Effort

**Total Time: ~3.5 hours**

- Database migrations & constraints: 15 min
- WhatsAppService: 1 hour
- EvolutionApiClient: 30 min
- WhatsAppWebhookController: 30 min
- TalkMessageSubscriber update: 15 min
- Services configuration: 10 min
- Manual testing: 1 hour

---

## Architecture Overview

### High-Level Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    EXTERNAL LAYER                            │
│                                                              │
│  ┌──────────────┐         Webhook          ┌─────────────┐ │
│  │  WhatsApp    │────MESSAGES_UPSERT──────▶│ Luminai     │ │
│  │  User        │◀──────Send Text API──────│ Server      │ │
│  └──────────────┘                          └─────────────┘ │
│         ▲                                         │         │
│         │                Evolution API            │         │
│         │                                         ▼         │
│  ┌──────┴──────────────────────────────────────────────┐   │
│  │            Evolution API Server                      │   │
│  │  • Instance: "sdr-agent-1"                          │   │
│  │  • Phone: +5511999999999                            │   │
│  │  • Webhook URL configured                           │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                 LUMINAI APPLICATION LAYER                    │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  POST /api/webhooks/whatsapp/evolution               │  │
│  │  WhatsAppWebhookController                           │  │
│  │  • Validates payload                                  │  │
│  │  • Dispatches to WhatsAppService                     │  │
│  └────────────────────┬─────────────────────────────────┘  │
│                       │                                     │
│                       ▼                                     │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  WhatsAppService (Core Business Logic)              │  │
│  │                                                       │  │
│  │  processIncomingMessage():                           │  │
│  │  1. Identify Agent by instanceName                   │  │
│  │  2. Normalize phone number                           │  │
│  │  3. Find/Create Contact (no dummy email!)           │  │
│  │  4. Find/Create Talk                                 │  │
│  │  5. Create TalkMessage (inbound)                     │  │
│  │                                                       │  │
│  │  sendOutgoingMessage():                              │  │
│  │  1. Validate Agent WhatsApp config                   │  │
│  │  2. Get contact phone                                │  │
│  │  3. Send via EvolutionApiClient (with retry)        │  │
│  │  4. Update TalkMessage status                        │  │
│  └────────────────────┬─────────────────────────────────┘  │
│                       │                                     │
│                       ▼                                     │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  EvolutionApiClient (HTTP Client)                    │  │
│  │  • sendTextMessage()                                  │  │
│  │  • getInstanceStatus()                                │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                     DATA LAYER                               │
│                                                              │
│  ┌─────────────────────────────────────────────┐           │
│  │  Agent (Enhanced with WhatsApp Fields)      │           │
│  │                                              │           │
│  │  Core Fields:                                │           │
│  │  • id, organization, treeFlow                │           │
│  │  • name, prompt, active                      │           │
│  │                                              │           │
│  │  WhatsApp Fields (NEW):                      │           │
│  │  • whatsappInstanceName ← Lookup key         │           │
│  │  • whatsappPhone                             │           │
│  │  • whatsappServerUrl                         │           │
│  │  • whatsappApiKey                            │           │
│  │  • whatsappActive                            │           │
│  │  • whatsappStatus                            │           │
│  │  • whatsappWebhookToken                      │           │
│  │  • whatsappLastConnectedAt                   │           │
│  │  • whatsappMetadata (JSON)                   │           │
│  └──────────────────┬──────────────────────────┘           │
│                     │                                       │
│  ┌──────────────────▼──────────────────────────┐           │
│  │  Contact (Email Now Nullable)               │           │
│  │                                              │           │
│  │  • id, organization, company                 │           │
│  │  • name, firstName, lastName                 │           │
│  │  • phone, mobilePhone (unique per org)       │           │
│  │  • email (NULLABLE - no dummy emails!)       │           │
│  │  • leadSource, origin                        │           │
│  └──────────────────┬──────────────────────────┘           │
│                     │                                       │
│  ┌──────────────────▼──────────────────────────┐           │
│  │  Talk                                        │           │
│  │                                              │           │
│  │  • id, organization, contact                 │           │
│  │  • agents (many-to-many)                     │           │
│  │  • talkFlow (JSON - TreeFlow state)          │           │
│  │  • channel (integer - see note below)        │           │
│  │  • status, paused                            │           │
│  └──────────────────┬──────────────────────────┘           │
│                     │                                       │
│  ┌──────────────────▼──────────────────────────┐           │
│  │  TalkMessage                                 │           │
│  │                                              │           │
│  │  • talk, fromContact, fromAgent              │           │
│  │  • body, direction (inbound/outbound)        │           │
│  │  • channel (string)                          │           │
│  │  • messageType, sentAt, deliveredAt          │           │
│  │  • metadata (WhatsApp message ID, etc.)      │           │
│  └──────────────────────────────────────────────┘           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   AI PROCESSING LAYER                        │
│                  (Existing System - No Changes)              │
│                                                              │
│  TalkMessageSubscriber → ProcessTalkMessageCommand           │
│  → ProcessTalkMessageHandler → OpenAI + TreeFlow            │
│  → GenerateAgentResponseCommand → Response TalkMessage       │
│  → TalkMessageSubscriber → WhatsAppService.sendOutgoing()   │
└─────────────────────────────────────────────────────────────┘
```

### Complete Message Flow (Step by Step)

```
1. WhatsApp User (+5511999999999) sends: "I'm interested in your product"
   ↓
2. Evolution API instance "sdr-agent-1" receives message
   ↓
3. Evolution API → POST /api/webhooks/whatsapp/evolution
   Payload:
   {
     "event": "messages.upsert",
     "instance": "sdr-agent-1",
     "data": {
       "key": { "remoteJid": "5511999999999@s.whatsapp.net", "fromMe": false, "id": "ABC123" },
       "pushName": "John Doe",
       "message": { "conversation": "I'm interested in your product" },
       "messageTimestamp": 1699876543
     }
   }
   ↓
4. WhatsAppWebhookController receives & validates payload
   ↓
5. WhatsAppService.processIncomingMessage()
   │
   ├─→ A. Identify Agent
   │    Query: Agent WHERE whatsappInstanceName = "sdr-agent-1"
   │                    AND whatsappActive = true
   │    Returns: Agent (id, organization, treeFlow, whatsappServerUrl, whatsappApiKey, etc.)
   │
   ├─→ B. Normalize Phone Number
   │    Input: "5511999999999@s.whatsapp.net"
   │    Output: "+5511999999999"
   │
   ├─→ C. Identify or Create Contact
   │    Query: Contact WHERE (phone = "+5511999999999" OR mobilePhone = "+5511999999999")
   │                      AND organization = agent.organization
   │
   │    If NOT EXISTS:
   │    Create Contact:
   │      • name: "John Doe" (from pushName)
   │      • firstName: "John", lastName: "Doe" (parsed)
   │      • phone: "+5511999999999"
   │      • mobilePhone: "+5511999999999"
   │      • email: NULL ✅ (no dummy email!)
   │      • company: Default company (auto-created)
   │      • leadSource: "WhatsApp"
   │      • origin: "WhatsApp - Evolution API"
   │      • organization: agent.organization
   │
   │    Returns: Contact (existing or new)
   │
   ├─→ D. Find or Create Talk
   │    Query: Talk WHERE contact = {contact}
   │                    AND {agent} IN agents
   │                    AND channel = 2 (WhatsApp constant)
   │                    AND status = 0 (Active)
   │                    AND archived = false
   │                    AND closedAt IS NULL
   │           ORDER BY dateLastMessage DESC
   │           LIMIT 1
   │
   │    If NOT EXISTS:
   │    Create Talk:
   │      • organization: agent.organization
   │      • subject: "WhatsApp - John Doe"
   │      • contact: {contact}
   │      • talkType: "WhatsApp" type (auto-created if needed)
   │      • owner: agent.user (or default org user)
   │      • agents: [agent]
   │      • channel: 2 (WhatsApp)
   │      • status: 0 (Active)
   │      • dateStart: now
   │
   │    Initialize TalkFlow:
   │      TalkFlowService.initializeTalkFlow(talk)
   │      • Copies TreeFlow template to talk.talkFlow
   │
   │    Returns: Talk (existing or new)
   │
   └─→ E. Create TalkMessage (Inbound)
        TalkMessage:
          • talk: {talk}
          • organization: agent.organization
          • fromContact: {contact}
          • body: "I'm interested in your product"
          • direction: "inbound"
          • channel: "whatsapp"
          • messageType: "text"
          • sentAt: now
          • metadata: {
              "whatsappMessageId": "ABC123",
              "instanceName": "sdr-agent-1",
              "remoteJid": "5511999999999@s.whatsapp.net",
              "pushName": "John Doe"
            }

        Save to database → Triggers TalkMessageSubscriber
   ↓
6. TalkMessageSubscriber.postPersist() detects inbound message
   ↓
7. Dispatch ProcessTalkMessageCommand (Existing System)
   ↓
8. ProcessTalkMessageHandler (Existing):
   • Check if Talk is paused → Skip if true
   • Extract structured data from message using OpenAI
   • Update talk.talkFlow with extracted data
   • Evaluate if current TreeFlow step is complete
   • If complete → Move to next step
   ↓
9. Dispatch GenerateAgentResponseCommand (Existing)
   ↓
10. GenerateAgentResponseHandler (Existing):
    • Build context from TreeFlow, Talk history, Agent prompt
    • Generate response using OpenAI
    • Create TalkMessage (Outbound):
        • talk: {talk}
        • organization: agent.organization
        • fromAgent: {agent}
        • body: "Great! I'd love to help you. Can you tell me more about your needs?"
        • direction: "outbound"
        • channel: "whatsapp"
        • messageType: "text"
        • sentAt: now

    Save to database → Triggers TalkMessageSubscriber again
   ↓
11. TalkMessageSubscriber.postPersist() detects outbound + channel=whatsapp
    ↓
12. WhatsAppService.sendOutgoingMessage() with Retry Logic:

    Attempt 1 (max 3 attempts):
    ├─→ Validate: Agent.whatsappActive = true
    ├─→ Get: contact.phone → "+5511999999999"
    ├─→ Normalize for Evolution: "5511999999999" (strip + and @)
    ├─→ Call EvolutionApiClient.sendTextMessage():
    │     POST https://{agent.whatsappServerUrl}/message/sendText/{agent.whatsappInstanceName}
    │     Headers: { "apikey": agent.whatsappApiKey, "Content-Type": "application/json" }
    │     Body: {
    │       "number": "5511999999999",
    │       "textMessage": { "text": "Great! I'd love to help you..." }
    │     }
    │
    ├─→ If SUCCESS:
    │    • Update TalkMessage:
    │        deliveredAt = now
    │        metadata.whatsappSent = true
    │        metadata.whatsappResponse = {response}
    │        metadata.whatsappAttempts = 1
    │    • Log success
    │    • DONE
    │
    └─→ If FAILURE:
         • Log warning
         • If attempt < 3: sleep(2 * attempt) seconds, then retry
         • If attempt = 3:
             - Update TalkMessage:
                 metadata.whatsappSent = false
                 metadata.whatsappError = error message
                 metadata.whatsappAttempts = 3
                 metadata.whatsappFailedAt = now
             - Log error
             - DONE (don't throw exception)
   ↓
13. Evolution API sends message to WhatsApp infrastructure
    ↓
14. WhatsApp User receives: "Great! I'd love to help you..."
    ↓
15. Conversation continues... (back to step 1)
```

---

## Prerequisites

### Required Software

- **Luminai Application**: Symfony 7.3 with existing TreeFlow AI system
- **Evolution API Server**: v2.x running and accessible
- **Database**: PostgreSQL 18 (with UUIDv7 support)
- **PHP**: 8.4+
- **Composer**: 2.x

### Evolution API Setup

1. **Install Evolution API** (if not already):
   ```bash
   # Using Docker (recommended)
   docker pull atendai/evolution-api:latest
   docker run -d \
     -p 8080:8080 \
     -e AUTHENTICATION_API_KEY=your-global-api-key \
     --name evolution-api \
     atendai/evolution-api:latest
   ```

2. **Create WhatsApp Instance**:
   ```bash
   curl -X POST http://your-evolution-server:8080/instance/create \
     -H "apikey: your-global-api-key" \
     -H "Content-Type: application/json" \
     -d '{
       "instanceName": "sdr-agent-1",
       "token": "your-instance-specific-token",
       "qrcode": true,
       "integration": "WHATSAPP-BUSINESS"
     }'
   ```

3. **Scan QR Code**: Get QR code and scan with WhatsApp mobile app

4. **Verify Connection**:
   ```bash
   curl -X GET http://your-evolution-server:8080/instance/connectionState/sdr-agent-1 \
     -H "apikey: your-global-api-key"
   ```

---

## Database Changes

### 1. Agent Table Enhancement ✅ (Already Applied)

The following fields were added to the `agent` table:

```sql
ALTER TABLE agent ADD whatsapp_instance_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE agent ADD whatsapp_phone VARCHAR(25) DEFAULT NULL;
ALTER TABLE agent ADD whatsapp_server_url VARCHAR(500) DEFAULT NULL;
ALTER TABLE agent ADD whatsapp_api_key TEXT DEFAULT NULL;
ALTER TABLE agent ADD whatsapp_active BOOLEAN DEFAULT FALSE NOT NULL;
ALTER TABLE agent ADD whatsapp_status VARCHAR(50) DEFAULT NULL;
ALTER TABLE agent ADD whatsapp_webhook_token VARCHAR(255) DEFAULT NULL;
ALTER TABLE agent ADD whatsapp_last_connected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
ALTER TABLE agent ADD whatsapp_metadata JSON DEFAULT NULL;
```

**Field Descriptions:**

- `whatsappInstanceName`: Evolution API instance name (e.g., "sdr-agent-1") - **globally unique**
- `whatsappPhone`: WhatsApp phone number in E.164 format (e.g., "+5511999999999")
- `whatsappServerUrl`: Evolution API server URL (e.g., "https://evolution.mydomain.com")
- `whatsappApiKey`: Evolution API instance token (plain text, not encrypted per user decision)
- `whatsappActive`: Whether WhatsApp is enabled for this agent (boolean)
- `whatsappStatus`: Connection status (e.g., "connected", "disconnected", "connecting")
- `whatsappWebhookToken`: Token for webhook validation (future use)
- `whatsappLastConnectedAt`: Last successful connection timestamp
- `whatsappMetadata`: Additional configuration as JSON (flexible for future features)

### 2. Contact Table Enhancement ✅ (Already Applied)

```sql
ALTER TABLE contact ALTER email DROP NOT NULL;
```

**Impact:** Contact.email is now **nullable**. No more dummy emails for WhatsApp-only contacts!

### 3. Required Indexes and Constraints

**IMPORTANT:** These must be created before implementation:

```sql
-- 1. Agent: Globally unique WhatsApp instance name
--    (NOT per organization - instance names must be unique across all orgs)
CREATE UNIQUE INDEX idx_agent_whatsapp_instance
ON agent(whatsapp_instance_name)
WHERE whatsapp_instance_name IS NOT NULL;

-- 2. Agent: Fast lookup for active WhatsApp agents
CREATE INDEX idx_agent_whatsapp_active
ON agent(whatsapp_active, whatsapp_status)
WHERE whatsapp_active = true;

-- 3. Contact: Unique phone per organization (prevent duplicates)
CREATE UNIQUE INDEX idx_contact_phone_org
ON contact(phone, organization_id)
WHERE phone IS NOT NULL;

-- 4. Contact: Unique mobile phone per organization
CREATE UNIQUE INDEX idx_contact_mobile_org
ON contact(mobile_phone, organization_id)
WHERE mobile_phone IS NOT NULL;
```

**Rationale:**
- **Global instance name uniqueness**: Evolution API instance names are global identifiers. Each instance receives webhooks independently, so we need to map instance → agent without organization context in the webhook.
- **Phone number uniqueness per org**: Prevents duplicate contacts from being created for the same person within an organization.

### 4. Channel Field Inconsistency Resolution

**Problem Identified:**
- `Talk.channel` is currently `INTEGER` (values: 0, 1)
- `TalkMessage.channel` is currently `VARCHAR`

**Two Options:**

#### Option A: Use Integer Constants (Quick Fix)

Define constants in your code:

```php
// In Talk entity or a ChannelType enum
const CHANNEL_UNKNOWN = 0;
const CHANNEL_WEB = 1;
const CHANNEL_WHATSAPP = 2;
const CHANNEL_EMAIL = 3;
```

Update code to use:
```php
$talk->setChannel(Talk::CHANNEL_WHATSAPP);
```

**Query updates:**
```php
// In findOrCreateTalk()
->andWhere('t.channel = :channel')
->setParameter('channel', Talk::CHANNEL_WHATSAPP)  // Integer 2
```

#### Option B: Migrate to String (Recommended for Consistency)

Create a migration to convert Talk.channel from integer to string:

```php
// Migration
public function up(Schema $schema): void
{
    // Convert existing integer values to strings
    $this->addSql("
        UPDATE talk SET channel =
        CASE channel
            WHEN 0 THEN 'unknown'
            WHEN 1 THEN 'web'
            ELSE channel::text
        END
    ");

    // Change column type
    $this->addSql('ALTER TABLE talk ALTER COLUMN channel TYPE VARCHAR(50)');
    $this->addSql("ALTER TABLE talk ALTER COLUMN channel SET DEFAULT 'web'");
}

public function down(Schema $schema): void
{
    // Reverse: string to integer
    $this->addSql("
        UPDATE talk SET channel =
        CASE channel
            WHEN 'unknown' THEN '0'
            WHEN 'web' THEN '1'
            WHEN 'whatsapp' THEN '2'
            WHEN 'email' THEN '3'
            ELSE '0'
        END
    ");

    $this->addSql('ALTER TABLE talk ALTER COLUMN channel TYPE INTEGER USING channel::integer');
    $this->addSql('ALTER TABLE talk ALTER COLUMN channel SET DEFAULT 0');
}
```

**Then use consistently:**
```php
$talk->setChannel('whatsapp');  // String
```

**Recommendation:** Choose **Option A** (integer constants) for faster implementation. Option B can be done later if needed.

---

## Component Specifications

### 1. WhatsAppService

**File:** `/app/src/Service/WhatsApp/WhatsAppService.php`

**Purpose:** Core business logic for WhatsApp message processing

**Dependencies:**
- `AgentRepository`
- `ContactRepository`
- `TalkRepository`
- `TalkTypeRepository`
- `UserRepository`
- `TalkFlowService`
- `EvolutionApiClient`
- `EntityManagerInterface`
- `LoggerInterface`

**Key Methods:**

#### 1.1 processIncomingMessage(array $webhookPayload): void

Processes incoming WhatsApp messages from Evolution API webhook.

**Parameters:**
- `$webhookPayload`: Raw webhook payload from Evolution API

**Logic:**
1. Validate event type (`messages.upsert`)
2. Ignore messages sent by us (`fromMe = true`)
3. Extract data (instance, phone, name, message text, message ID)
4. Identify Agent by `whatsappInstanceName`
5. Normalize phone number to E.164 format
6. Find or create Contact (with no dummy email)
7. Find or create Talk (with channel = WhatsApp)
8. Create inbound TalkMessage
9. Let TalkMessageSubscriber trigger AI processing

**Example Usage:**
```php
$service->processIncomingMessage([
    'event' => 'messages.upsert',
    'instance' => 'sdr-agent-1',
    'data' => [
        'key' => [
            'remoteJid' => '5511999999999@s.whatsapp.net',
            'fromMe' => false,
            'id' => 'ABC123XYZ',
        ],
        'pushName' => 'John Doe',
        'message' => [
            'conversation' => 'Hello, I need help',
        ],
    ],
]);
```

#### 1.2 sendOutgoingMessage(TalkMessage $talkMessage): void

Sends outbound WhatsApp messages with retry logic.

**Parameters:**
- `$talkMessage`: The outbound TalkMessage to send

**Logic:**
1. Validate message is outbound and channel is WhatsApp
2. Get Agent and validate WhatsApp is active
3. Get Contact phone number
4. Retry loop (max 3 attempts):
   - Call EvolutionApiClient.sendTextMessage()
   - On success: Update message metadata and return
   - On failure: Log warning, wait (exponential backoff), retry
5. If all retries fail: Mark message as failed, log error

**Retry Strategy:**
- Attempt 1: Immediate
- Attempt 2: Wait 2 seconds
- Attempt 3: Wait 4 seconds
- After 3 failures: Mark as failed, don't throw exception

**Example Usage:**
```php
// Called automatically by TalkMessageSubscriber when outbound message is created
$service->sendOutgoingMessage($talkMessage);
```

#### 1.3 identifyAgentByInstance(string $instanceName): ?Agent

Finds Agent by Evolution API instance name.

**Parameters:**
- `$instanceName`: Evolution API instance identifier (e.g., "sdr-agent-1")

**Returns:** Agent entity or null

**Query:**
```php
SELECT a FROM Agent a
WHERE a.whatsappInstanceName = :instanceName
  AND a.whatsappActive = true
  AND a.active = true
```

**Note:** No organization filtering here. Instance names are globally unique across all organizations.

#### 1.4 identifyOrCreateContact(string $phone, string $name, Organization $org): Contact

Finds existing contact or creates new one.

**Parameters:**
- `$phone`: Normalized phone number (E.164 format)
- `$name`: Contact name from WhatsApp (pushName)
- `$org`: Organization entity

**Returns:** Contact entity (existing or newly created)

**Logic:**
1. Search by `phone` field
2. If not found, search by `mobilePhone` field
3. If found, update `lastContactDate` and return
4. If not found, create new Contact:
   - Parse name into firstName/lastName
   - Set phone and mobilePhone to same value
   - Set email to NULL (no dummy email!)
   - Get or create default Company for organization
   - Set leadSource = "WhatsApp"
   - Set origin = "WhatsApp - Evolution API"
5. Handle UniqueConstraintViolationException (race condition)

**Example:**
```php
$contact = $service->identifyOrCreateContact(
    phone: '+5511999999999',
    name: 'John Doe',
    org: $agent->getOrganization()
);
// Returns: Contact with email = null, leadSource = "WhatsApp"
```

#### 1.5 findOrCreateTalk(Contact $contact, Agent $agent): Talk

Finds active Talk or creates new one.

**Parameters:**
- `$contact`: Contact entity
- `$agent`: Agent entity

**Returns:** Talk entity (existing or newly created)

**Logic:**
1. Search for existing active Talk:
   - WHERE contact = {contact}
   - AND agent IN agents collection
   - AND channel = CHANNEL_WHATSAPP (use constant based on Option A/B choice)
   - AND status = 0 (Active)
   - AND archived = false
   - AND closedAt IS NULL
   - ORDER BY dateLastMessage DESC
2. If found, update `dateLastMessage` and return
3. If not found, create new Talk:
   - Get or create "WhatsApp" TalkType
   - Set all required fields
   - Add agent to agents collection
   - Initialize TalkFlow via TalkFlowService

**Example:**
```php
$talk = $service->findOrCreateTalk($contact, $agent);
// Returns: Talk with TreeFlow initialized, ready for AI processing
```

#### 1.6 Helper Methods

**extractMessageText(array $message): string**
- Handles various WhatsApp message formats:
  - `message.conversation` (simple text)
  - `message.extendedTextMessage.text` (rich text)
  - `message.imageMessage.caption` (image with caption)
  - `message.videoMessage.caption` (video with caption)

**normalizePhoneNumber(string $remoteJid): string**
- Converts WhatsApp JID to E.164 format
- Input: `"5511999999999@s.whatsapp.net"`
- Output: `"+5511999999999"`

**normalizePhoneNumberForEvolution(string $phone): string**
- Strips formatting for Evolution API send request
- Input: `"+55 11 99999-9999"`
- Output: `"5511999999999"`

**parseFullName(string $fullName): array**
- Splits name into first and last name
- Input: `"John Doe"`
- Output: `['firstName' => 'John', 'lastName' => 'Doe']`

**getOrCreateDefaultCompany(Organization $org): Company**
- Gets or creates "Default Company" for organization
- Required because Contact.company is not nullable

**getDefaultOwner(Organization $org): User**
- Gets first user in organization for Talk.owner
- Required because Talk.owner is not nullable

---

### 2. EvolutionApiClient

**File:** `/app/src/Service/WhatsApp/EvolutionApiClient.php`

**Purpose:** HTTP client for Evolution API operations

**Dependencies:**
- `HttpClientInterface` (Symfony)
- `LoggerInterface`

**Methods:**

#### 2.1 sendTextMessage(...): array

Sends text message via Evolution API.

**Parameters:**
- `string $serverUrl`: Evolution API base URL
- `string $apiKey`: Instance API key
- `string $instanceName`: Instance identifier
- `string $phoneNumber`: Recipient phone (no formatting, digits only)
- `string $text`: Message text

**Returns:** API response as array

**HTTP Request:**
```
POST {serverUrl}/message/sendText/{instanceName}
Headers:
  Content-Type: application/json
  apikey: {apiKey}
Body:
{
  "number": "5511999999999",
  "textMessage": {
    "text": "Your message here"
  }
}
```

**Error Handling:**
- Throws exception on non-200/201 status
- Logs all requests and errors
- Timeout: 30 seconds

#### 2.2 getInstanceStatus(...): array

Gets connection status of Evolution API instance.

**Parameters:**
- `string $serverUrl`
- `string $apiKey`
- `string $instanceName`

**Returns:** Status object

**HTTP Request:**
```
GET {serverUrl}/instance/connectionState/{instanceName}
Headers:
  apikey: {apiKey}
```

---

### 3. WhatsAppWebhookController

**File:** `/app/src/Controller/Api/WhatsAppWebhookController.php`

**Purpose:** API endpoint to receive Evolution API webhooks

**Route:** `POST /api/webhooks/whatsapp/evolution`

**Dependencies:**
- `WhatsAppService`
- `LoggerInterface`

**Method:** evolutionWebhook(Request $request): JsonResponse

**Logic:**
1. Decode JSON payload
2. Validate payload structure
3. Log webhook reception
4. Call WhatsAppService.processIncomingMessage()
5. Return success response

**Response Format:**
```json
{
  "success": true,
  "message": "Webhook processed"
}
```

**Error Handling:**
- Catches all exceptions
- Logs errors
- Returns 200 OK even on errors (prevents Evolution API retry storms)
- Never returns 500 (would cause Evolution API to retry)

**Security Considerations:**
- TODO: Add rate limiting (recommended: 100 requests/minute per IP)
- TODO: Add webhook signature validation if Evolution API supports it
- TODO: Add IP whitelist for Evolution API server

---

### 4. TalkMessageSubscriber Enhancement

**File:** `/app/src/EventSubscriber/TalkMessageSubscriber.php`

**Purpose:** Doctrine event listener for TalkMessage lifecycle

**Event:** `postPersist` (after TalkMessage is saved to database)

**Logic:**

```php
public function postPersist(PostPersistEventArgs $args): void
{
    $entity = $args->getObject();

    if (!$entity instanceof TalkMessage) {
        return;
    }

    // === EXISTING LOGIC: Process inbound messages for AI ===
    if ($entity->getDirection() === 'inbound') {
        $talk = $entity->getTalk();
        if ($talk) {
            $agent = $talk->getAgents()->first();
            if ($agent && $agent->getTreeFlow()) {
                $this->messageBus->dispatch(
                    new ProcessTalkMessageCommand($entity->getId()->toRfc4122())
                );
            }
        }
    }

    // === NEW LOGIC: Send outbound WhatsApp messages ===
    if ($entity->getDirection() === 'outbound' && $entity->getChannel() === 'whatsapp') {
        try {
            $this->whatsAppService->sendOutgoingMessage($entity);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send WhatsApp message', [
                'message_id' => $entity->getId()->toRfc4122(),
                'error' => $e->getMessage(),
            ]);
            // Don't re-throw - we don't want to rollback the transaction
        }
    }
}
```

**Key Points:**
- Inbound messages trigger AI processing (existing behavior)
- Outbound WhatsApp messages trigger send via Evolution API (new behavior)
- Exceptions are caught and logged, not re-thrown (prevents transaction rollback)

---

## Implementation Steps

### Step 1: Apply Database Constraints (5 minutes)

```bash
docker-compose exec -T app php bin/console dbal:run-sql "
-- 1. Globally unique WhatsApp instance name
CREATE UNIQUE INDEX idx_agent_whatsapp_instance
ON agent(whatsapp_instance_name)
WHERE whatsapp_instance_name IS NOT NULL;

-- 2. Fast lookup for active WhatsApp agents
CREATE INDEX idx_agent_whatsapp_active
ON agent(whatsapp_active, whatsapp_status)
WHERE whatsapp_active = true;

-- 3. Unique phone per organization
CREATE UNIQUE INDEX idx_contact_phone_org
ON contact(phone, organization_id)
WHERE phone IS NOT NULL;

-- 4. Unique mobile phone per organization
CREATE UNIQUE INDEX idx_contact_mobile_org
ON contact(mobile_phone, organization_id)
WHERE mobile_phone IS NOT NULL;
"
```

### Step 2: Create WhatsAppService (1 hour)

Create file: `/app/src/Service/WhatsApp/WhatsAppService.php`

Implement all methods as specified in Component Specifications section above.

**Key Implementation Notes:**

1. **Use Option A for Channel** (integer constants):
   ```php
   // Define in Talk entity or create ChannelType enum
   const CHANNEL_WHATSAPP = 2;

   // Use in queries
   $talk->setChannel(Talk::CHANNEL_WHATSAPP);
   ```

2. **Handle Race Conditions** in contact creation:
   ```php
   try {
       $this->entityManager->persist($contact);
       $this->entityManager->flush();
   } catch (UniqueConstraintViolationException $e) {
       $this->entityManager->clear();
       $contact = $this->contactRepository->findOneBy([
           'phone' => $phone,
           'organization' => $org,
       ]);
       if (!$contact) throw $e;
   }
   ```

3. **Retry Logic** with exponential backoff:
   ```php
   for ($attempt = 1; $attempt <= 3; $attempt++) {
       try {
           $response = $this->evolutionApiClient->sendTextMessage(...);
           // Success - update metadata and return
           return;
       } catch (\Exception $e) {
           if ($attempt < 3) {
               sleep(2 * $attempt);  // 2s, 4s
           }
       }
   }
   // Mark as failed after 3 attempts
   ```

### Step 3: Create EvolutionApiClient (30 minutes)

Create file: `/app/src/Service/WhatsApp/EvolutionApiClient.php`

Implement as specified in Component Specifications section.

**HTTP Client Configuration:**
```php
$response = $this->httpClient->request('POST', $url, [
    'headers' => [
        'Content-Type' => 'application/json',
        'apikey' => $apiKey,
    ],
    'json' => $payload,
    'timeout' => 30,
]);
```

### Step 4: Create WhatsAppWebhookController (30 minutes)

Create file: `/app/src/Controller/Api/WhatsAppWebhookController.php`

Implement as specified in Component Specifications section.

**Route Configuration:**
```php
#[Route('/api/webhooks/whatsapp', name: 'api_whatsapp_webhook_')]
class WhatsAppWebhookController extends AbstractController
{
    #[Route('/evolution', name: 'evolution', methods: ['POST'])]
    public function evolutionWebhook(Request $request): JsonResponse
    {
        // Implementation
    }
}
```

### Step 5: Update TalkMessageSubscriber (15 minutes)

Edit file: `/app/src/EventSubscriber/TalkMessageSubscriber.php`

Add WhatsAppService dependency in constructor:
```php
public function __construct(
    private readonly MessageBusInterface $messageBus,
    private readonly WhatsAppService $whatsAppService,  // ADD THIS
    private readonly LoggerInterface $logger
) {
}
```

Add outbound WhatsApp send logic as shown in Component Specifications section.

### Step 6: Register Services (10 minutes)

Edit file: `/app/config/services.yaml`

Add service definitions:
```yaml
services:
    # ... existing services ...

    # WhatsApp Integration Services
    App\Service\WhatsApp\WhatsAppService:
        autowire: true
        autoconfigure: true
        arguments:
            $logger: '@monolog.logger.whatsapp'

    App\Service\WhatsApp\EvolutionApiClient:
        autowire: true
        autoconfigure: true
        arguments:
            $logger: '@monolog.logger.whatsapp'

    # Optional: Dedicated WhatsApp logger channel
    monolog.logger.whatsapp:
        class: Psr\Log\LoggerInterface
        factory: ['@monolog.logger_prototype', 'withName']
        arguments: ['whatsapp']
```

### Step 7: Update Environment Configuration (5 minutes)

Edit file: `/app/.env` or `/app/.env.local`

Add WhatsApp configuration:
```bash
###> WhatsApp Evolution API Configuration ###
EVOLUTION_API_DEFAULT_SERVER_URL=https://evolution-api.yourdomain.com
EVOLUTION_API_DEFAULT_APIKEY=your-default-api-key
WHATSAPP_WEBHOOK_BASE_URL=https://luminai.yourdomain.com/api/webhooks/whatsapp/evolution
###< WhatsApp Evolution API Configuration ###
```

### Step 8: Clear Cache (1 minute)

```bash
docker-compose exec app php bin/console cache:clear
```

---

## Configuration Guide

### 1. Configure Agent with WhatsApp

Via PHP (in a command or fixture):

```php
use App\Entity\Agent;

$agent = new Agent();

// Core fields
$agent->setOrganization($organization);
$agent->setName('SDR Bot - WhatsApp');
$agent->setTreeFlow($salesQualificationTreeFlow);
$agent->setPrompt('You are a professional SDR assistant for our company. Your goal is to qualify leads and schedule meetings. Be friendly, professional, and concise.');
$agent->setActive(true);
$agent->setAvailable(true);

// WhatsApp configuration
$agent->setWhatsappInstanceName('sdr-agent-1');  // Must match Evolution API instance
$agent->setWhatsappPhone('+5511999999999');      // Your WhatsApp number
$agent->setWhatsappServerUrl('https://evolution-api.yourdomain.com');
$agent->setWhatsappApiKey('your-instance-specific-api-key');
$agent->setWhatsappActive(true);
$agent->setWhatsappStatus('connected');
$agent->setWhatsappWebhookToken(bin2hex(random_bytes(32)));  // For future validation

$entityManager->persist($agent);
$entityManager->flush();
```

Via Admin UI (if you build one):
- Form fields for all WhatsApp properties
- Validation for unique instance name
- Test connection button (calls EvolutionApiClient.getInstanceStatus())

### 2. Configure Evolution API Webhook

Set webhook URL for your Evolution API instance:

```bash
curl -X POST https://evolution-api.yourdomain.com/webhook/set/sdr-agent-1 \
  -H "apikey: your-instance-specific-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://luminai.yourdomain.com/api/webhooks/whatsapp/evolution",
    "webhook_by_events": false,
    "events": [
      "MESSAGES_UPSERT",
      "CONNECTION_UPDATE"
    ]
  }'
```

**Verify webhook configuration:**
```bash
curl -X GET https://evolution-api.yourdomain.com/webhook/find/sdr-agent-1 \
  -H "apikey: your-instance-specific-api-key"
```

### 3. Verify Connection

Check Evolution API instance status:

```bash
curl -X GET https://evolution-api.yourdomain.com/instance/connectionState/sdr-agent-1 \
  -H "apikey: your-instance-specific-api-key"
```

Expected response:
```json
{
  "instance": {
    "instanceName": "sdr-agent-1",
    "state": "open"
  }
}
```

---

## Testing Guide

### Manual Test Scenarios

#### Test 1: New Contact from WhatsApp

**Given:**
- Agent configured with WhatsApp active
- No existing contact with phone +5511999999999

**Steps:**
1. Send WhatsApp message from +5511999999999: "Hello, I'm interested in your product"
2. Check logs: `docker-compose logs app | grep WhatsApp`
3. Query database:
   ```sql
   SELECT id, name, phone, email, lead_source
   FROM contact
   WHERE phone = '+5511999999999';
   ```

**Expected Results:**
- ✅ Contact created with:
  - name = "John Doe" (from WhatsApp display name)
  - phone = "+5511999999999"
  - email = NULL (no dummy email!)
  - leadSource = "WhatsApp"
- ✅ Talk created with channel = 2 (WhatsApp)
- ✅ Inbound TalkMessage created
- ✅ AI response generated
- ✅ Outbound TalkMessage created
- ✅ WhatsApp message sent back to user

#### Test 2: Returning Contact

**Given:**
- Existing contact with phone +5511999999999
- Existing Talk from Test 1

**Steps:**
1. Send another WhatsApp message: "What are your prices?"
2. Check logs

**Expected Results:**
- ✅ Existing contact used (no duplicate created)
- ✅ Existing Talk continued (same Talk ID)
- ✅ TalkFlow state preserved
- ✅ AI response contextual to previous conversation

#### Test 3: Multiple Agents / Organizations

**Given:**
- Organization A with Agent "SDR Bot A" (instance: "org-a-sdr")
- Organization B with Agent "SDR Bot B" (instance: "org-b-sdr")
- Same phone number +5511999999999 messages both

**Steps:**
1. Message org-a-sdr: "Hello A"
2. Message org-b-sdr: "Hello B"
3. Check database:
   ```sql
   SELECT c.id, c.phone, c.name, o.name as org_name
   FROM contact c
   JOIN organization o ON c.organization_id = o.id
   WHERE c.phone = '+5511999999999';
   ```

**Expected Results:**
- ✅ Two separate contacts created (one per organization)
- ✅ Two separate Talks
- ✅ No data leakage between organizations

#### Test 4: Retry Logic

**Given:**
- Agent with WhatsApp configured
- Evolution API server temporarily unreachable

**Steps:**
1. Stop Evolution API: `docker stop evolution-api`
2. Send inbound WhatsApp message (use webhook test)
3. Check logs for retry attempts
4. Wait 10 seconds
5. Start Evolution API: `docker start evolution-api`
6. Check TalkMessage metadata:
   ```sql
   SELECT metadata
   FROM talk_message
   WHERE direction = 'outbound'
   ORDER BY created_at DESC
   LIMIT 1;
   ```

**Expected Results:**
- ✅ 3 retry attempts logged
- ✅ Message marked as failed:
  ```json
  {
    "whatsappSent": false,
    "whatsappError": "Connection refused",
    "whatsappAttempts": 3,
    "whatsappFailedAt": "2025-11-13T10:30:45+00:00"
  }
  ```
- ✅ No exception thrown (transaction not rolled back)

#### Test 5: Webhook Endpoint

**Test webhook directly:**

```bash
curl -X POST https://luminai.yourdomain.com/api/webhooks/whatsapp/evolution \
  -H "Content-Type: application/json" \
  -d '{
    "event": "messages.upsert",
    "instance": "sdr-agent-1",
    "data": {
      "key": {
        "remoteJid": "5511999999999@s.whatsapp.net",
        "fromMe": false,
        "id": "TEST123ABC"
      },
      "pushName": "Test User",
      "message": {
        "conversation": "This is a test message"
      },
      "messageTimestamp": 1699876543
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Webhook processed"
}
```

---

## Troubleshooting

### Problem: Webhook not receiving messages

**Symptoms:**
- No log entries when WhatsApp messages sent
- No TalkMessages created

**Diagnosis:**
1. Check Evolution API webhook config:
   ```bash
   curl -X GET https://evolution-api.yourdomain.com/webhook/find/sdr-agent-1 \
     -H "apikey: YOUR_API_KEY"
   ```
2. Check webhook URL is publicly accessible
3. Check SSL certificate validity
4. Check firewall rules

**Solutions:**
- Update webhook URL if incorrect
- Ensure HTTPS with valid cert
- Whitelist Evolution API server IP if using firewall
- Test webhook endpoint manually (see Test 5 above)

### Problem: Messages not sending

**Symptoms:**
- Inbound messages processed
- No outbound messages sent
- Errors in logs: "Failed to send WhatsApp message"

**Diagnosis:**
1. Check Agent WhatsApp config:
   ```sql
   SELECT
     whatsapp_instance_name,
     whatsapp_active,
     whatsapp_status,
     whatsapp_server_url
   FROM agent
   WHERE id = 'YOUR_AGENT_ID';
   ```
2. Check Evolution API instance status:
   ```bash
   curl -X GET https://evolution-api.yourdomain.com/instance/connectionState/sdr-agent-1 \
     -H "apikey: YOUR_API_KEY"
   ```
3. Check TalkMessage metadata for error details:
   ```sql
   SELECT body, metadata
   FROM talk_message
   WHERE direction = 'outbound'
     AND channel = 'whatsapp'
   ORDER BY created_at DESC
   LIMIT 5;
   ```

**Solutions:**
- Set `whatsappActive = true` if false
- Reconnect Evolution API instance if disconnected
- Verify API key is correct
- Check Evolution API server logs

### Problem: Duplicate contacts created

**Symptoms:**
- Multiple contacts with same phone number in one organization

**Diagnosis:**
```sql
SELECT phone, COUNT(*), array_agg(id) as contact_ids
FROM contact
WHERE organization_id = 'YOUR_ORG_ID'
GROUP BY phone, organization_id
HAVING COUNT(*) > 1;
```

**Causes:**
- Race condition (multiple messages arriving simultaneously)
- Unique constraint not applied
- Different phone formats ("+5511999999999" vs "5511999999999")

**Solutions:**
1. Verify unique constraints exist:
   ```sql
   SELECT indexname, indexdef
   FROM pg_indexes
   WHERE tablename = 'contact'
     AND indexname LIKE '%phone%';
   ```
2. If constraints missing, apply from Step 1
3. Merge duplicate contacts manually:
   ```sql
   -- Keep the oldest, update Talk and TalkMessage references
   UPDATE talk SET contact_id = 'OLDEST_CONTACT_ID'
   WHERE contact_id IN ('DUP1', 'DUP2');

   UPDATE talk_message SET from_contact_id = 'OLDEST_CONTACT_ID'
   WHERE from_contact_id IN ('DUP1', 'DUP2');

   DELETE FROM contact WHERE id IN ('DUP1', 'DUP2');
   ```

### Problem: Channel field errors

**Symptoms:**
- Database errors about channel type mismatch
- Queries return wrong Talk records

**Diagnosis:**
Check channel types:
```sql
SELECT data_type
FROM information_schema.columns
WHERE table_name IN ('talk', 'talk_message')
  AND column_name = 'channel';
```

**Solutions:**
- If using integer: Define constants (Option A)
- If inconsistent: Run migration (Option B)
- Update all queries to use correct type

### Problem: AI not responding

**Symptoms:**
- Inbound messages processed
- No outbound messages created

**Diagnosis:**
1. Check if Agent has TreeFlow assigned:
   ```sql
   SELECT a.name, a.id, a.tree_flow_id
   FROM agent a
   WHERE a.whatsapp_active = true;
   ```
2. Check Talk agents assignment:
   ```sql
   SELECT t.id, t.subject, t.agents
   FROM talk t
   WHERE t.channel = 2  -- WhatsApp
   ORDER BY t.created_at DESC
   LIMIT 5;
   ```
3. Check message processing logs:
   ```bash
   docker-compose logs app | grep ProcessTalkMessageCommand
   ```

**Solutions:**
- Assign TreeFlow to Agent if null
- Verify TalkMessageSubscriber is triggering
- Check Messenger configuration
- Verify OpenAI API key is configured

### Problem: "No default company" error

**Symptoms:**
- Contact creation fails
- Error: "Company is required"

**Diagnosis:**
```sql
SELECT id, name FROM company
WHERE organization_id = 'YOUR_ORG_ID'
  AND name = 'Default Company';
```

**Solutions:**
- WhatsAppService automatically creates "Default Company" if needed
- If still failing, manually create:
  ```sql
  INSERT INTO company (id, organization_id, name, created_at, updated_at)
  VALUES (
    gen_random_uuid(),
    'YOUR_ORG_ID',
    'Default Company',
    NOW(),
    NOW()
  );
  ```

---

## Limitations & Future Enhancements

### Current Limitations

1. **Text Messages Only**
   - Only handles text messages
   - Images, videos, audio, documents not yet supported
   - Media captions are extracted but files not saved

2. **No Group Chat Support**
   - Messages from groups (@g.us) are ignored
   - Only handles 1:1 conversations

3. **No Message Status Tracking**
   - Doesn't track delivered/read receipts
   - CONNECTION_UPDATE events logged but not acted upon

4. **No Webhook Idempotency**
   - Same message could be processed twice if webhook retries
   - Recommendation: Add Redis-based deduplication

5. **No Rate Limiting**
   - Webhook endpoint not rate-limited
   - Could be abused if publicly accessible

6. **No Webhook Signature Validation**
   - Evolution API webhook requests not cryptographically verified
   - Relies on obscure URL for security

### Phase 2 Enhancements (Future)

#### 1. Media Message Support

- Handle images, videos, audio, documents
- Download media files to S3/MinIO
- Create Attachment entities linked to TalkMessage
- Generate thumbnails for images/videos

**Complexity:** Medium (4-6 hours)

#### 2. WhatsApp Templates

- Send template messages (pre-approved by Meta)
- Template variable substitution
- Campaign integration

**Complexity:** Medium (3-4 hours)

#### 3. Group Chat Support

- Handle @g.us messages
- Multi-participant Talks
- Group mention detection
- Admin command parsing

**Complexity:** High (8-10 hours)

#### 4. Webhook Idempotency

- Redis-based message ID tracking
- Prevent duplicate processing
- TTL for cleanup (e.g., 1 hour)

**Complexity:** Low (1-2 hours)

**Implementation:**
```php
$messageId = $payload['data']['key']['id'];
$cacheKey = "whatsapp:processed:{$messageId}";

if ($this->redis->exists($cacheKey)) {
    return; // Already processed
}

$this->redis->setex($cacheKey, 3600, '1'); // 1 hour TTL
// Process message...
```

#### 5. Rate Limiting

- Implement rate limiting on webhook endpoint
- IP-based or API key-based
- Configurable limits (e.g., 100 requests/minute)

**Complexity:** Low (1-2 hours)

**Using Symfony Rate Limiter:**
```php
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/api/webhooks/whatsapp/evolution', name: 'evolution', methods: ['POST'])]
public function evolutionWebhook(Request $request): JsonResponse
{
    $limiter = $this->rateLimiterFactory->create($request->getClientIp());

    if (!$limiter->consume(1)->isAccepted()) {
        return new JsonResponse(['error' => 'Rate limit exceeded'], 429);
    }

    // Process webhook...
}
```

#### 6. Webhook Signature Validation

- Validate Evolution API webhook signatures (if supported)
- HMAC-based verification
- Prevent spoofed webhooks

**Complexity:** Low (1-2 hours)

#### 7. Message Status Tracking

- Handle MESSAGE_UPDATE events
- Track delivered/read status
- Update TalkMessage metadata
- Show in UI (double check marks like WhatsApp)

**Complexity:** Medium (2-3 hours)

#### 8. Advanced Contact Enrichment

- Extract structured data from messages (email, company, title, etc.)
- Update Contact fields automatically
- Use OpenAI for entity extraction

**Complexity:** Medium (4-5 hours)

#### 9. Analytics Dashboard

- Message volume metrics
- Response time tracking
- Agent performance by WhatsApp number
- Conversation completion rates
- Lead conversion tracking

**Complexity:** High (12-16 hours)

#### 10. Multi-Channel Routing

- Unified conversation across WhatsApp, email, web chat
- Channel preference tracking
- Seamless context switching

**Complexity:** Very High (20+ hours)

---

## Security Considerations

### Data Protection

1. **API Keys**: Stored as plain text per user decision. For production with sensitive data, consider:
   - Environment variable storage
   - AWS Secrets Manager / HashiCorp Vault
   - Database encryption at rest

2. **Webhook Security**:
   - Use HTTPS only
   - Consider IP whitelisting for Evolution API server
   - Implement signature validation when Evolution API supports it
   - Add rate limiting

3. **Phone Numbers**:
   - Store in E.164 format for consistency
   - Unique constraints prevent duplicates
   - Consider GDPR/LGPD compliance for data retention

### Multi-Tenant Security

1. **Organization Isolation**:
   - All queries filtered by organization
   - Agent.whatsappInstanceName globally unique
   - Contact phone unique per organization (not global)

2. **Access Control**:
   - Webhook endpoint is public (by design)
   - No authentication required (Evolution API controls access)
   - Consider API key validation in webhook if needed

---

## Appendices

### Appendix A: Evolution API Webhook Payload Examples

**Incoming Text Message:**
```json
{
  "event": "messages.upsert",
  "instance": "sdr-agent-1",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB0XXXXXXXXXXX"
    },
    "pushName": "John Doe",
    "message": {
      "conversation": "Hello, I need help"
    },
    "messageType": "conversation",
    "messageTimestamp": 1699876543,
    "owner": "5511888888888",
    "source": "ios"
  },
  "destination": "https://luminai.yourdomain.com/api/webhooks/whatsapp/evolution",
  "date_time": "2025-11-13T10:22:23.123Z",
  "sender": "5511999999999@s.whatsapp.net",
  "server_url": "https://evolution-api.yourdomain.com",
  "apikey": "hidden"
}
```

**Incoming Extended Text Message:**
```json
{
  "event": "messages.upsert",
  "instance": "sdr-agent-1",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB0XXXXXXXXXXX"
    },
    "pushName": "John Doe",
    "message": {
      "extendedTextMessage": {
        "text": "Hello, I need help with pricing"
      }
    },
    "messageType": "extendedTextMessage",
    "messageTimestamp": 1699876543
  }
}
```

**Incoming Image with Caption:**
```json
{
  "event": "messages.upsert",
  "instance": "sdr-agent-1",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB0XXXXXXXXXXX"
    },
    "pushName": "John Doe",
    "message": {
      "imageMessage": {
        "url": "https://...",
        "mimetype": "image/jpeg",
        "caption": "Here's a photo of our office",
        "fileLength": "123456"
      }
    },
    "messageType": "imageMessage",
    "messageTimestamp": 1699876543
  }
}
```

**Group Message (ignored):**
```json
{
  "event": "messages.upsert",
  "instance": "sdr-agent-1",
  "data": {
    "key": {
      "remoteJid": "120363XXXXXXXXXX@g.us",
      "fromMe": false,
      "id": "3EB0XXXXXXXXXXX",
      "participant": "5511999999999@s.whatsapp.net"
    },
    "pushName": "John Doe",
    "message": {
      "conversation": "Hello everyone"
    }
  }
}
```

**Our Sent Message (ignored - fromMe=true):**
```json
{
  "event": "messages.upsert",
  "instance": "sdr-agent-1",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": true,
      "id": "3EB0XXXXXXXXXXX"
    },
    "message": {
      "conversation": "Thanks for reaching out!"
    }
  }
}
```

### Appendix B: Evolution API Send Message Examples

**Send Simple Text:**
```bash
curl -X POST https://evolution-api.yourdomain.com/message/sendText/sdr-agent-1 \
  -H "apikey: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "5511999999999",
    "textMessage": {
      "text": "Hello! How can I help you today?"
    }
  }'
```

**Send with Delay and Presence:**
```bash
curl -X POST https://evolution-api.yourdomain.com/message/sendText/sdr-agent-1 \
  -H "apikey: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "5511999999999",
    "options": {
      "delay": 1200,
      "presence": "composing"
    },
    "textMessage": {
      "text": "Thanks for your interest! Let me get that information for you."
    }
  }'
```

### Appendix C: Database Schema Reference

**Agent Table (WhatsApp fields only):**
```sql
Column                       | Type            | Nullable | Default
-----------------------------|-----------------|----------|--------
whatsapp_instance_name       | VARCHAR(255)    | YES      | NULL
whatsapp_phone               | VARCHAR(25)     | YES      | NULL
whatsapp_server_url          | VARCHAR(500)    | YES      | NULL
whatsapp_api_key             | TEXT            | YES      | NULL
whatsapp_active              | BOOLEAN         | NO       | FALSE
whatsapp_status              | VARCHAR(50)     | YES      | NULL
whatsapp_webhook_token       | VARCHAR(255)    | YES      | NULL
whatsapp_last_connected_at   | TIMESTAMP       | YES      | NULL
whatsapp_metadata            | JSON            | YES      | NULL
```

**Indexes:**
```sql
idx_agent_whatsapp_instance      UNIQUE (whatsapp_instance_name) WHERE NOT NULL
idx_agent_whatsapp_active        (whatsapp_active, whatsapp_status) WHERE whatsapp_active = true
```

**Contact Table (relevant fields):**
```sql
Column          | Type            | Nullable | Constraints
----------------|-----------------|----------|---------------------------
id              | UUID            | NO       | PRIMARY KEY
organization_id | UUID            | NO       | FOREIGN KEY → organization
phone           | VARCHAR(25)     | YES      | UNIQUE per org
mobile_phone    | VARCHAR(25)     | YES      | UNIQUE per org
email           | VARCHAR(180)    | YES      | Was NOT NULL, now NULLABLE
company_id      | UUID            | NO       | FOREIGN KEY → company
```

**Indexes:**
```sql
idx_contact_phone_org       UNIQUE (phone, organization_id) WHERE phone IS NOT NULL
idx_contact_mobile_org      UNIQUE (mobile_phone, organization_id) WHERE mobile_phone IS NOT NULL
```

**Talk Table (relevant fields):**
```sql
Column          | Type            | Notes
----------------|-----------------|--------------------------------
id              | UUID            | PRIMARY KEY
organization_id | UUID            | FOREIGN KEY → organization
contact_id      | UUID            | FOREIGN KEY → contact
channel         | INTEGER         | 0=unknown, 1=web, 2=whatsapp, 3=email
status          | INTEGER         | 0=active, 1=closed, etc.
talk_flow       | JSON            | TreeFlow state
```

**TalkMessage Table (relevant fields):**
```sql
Column          | Type            | Notes
----------------|-----------------|--------------------------------
id              | UUID            | PRIMARY KEY
talk_id         | UUID            | FOREIGN KEY → talk
from_contact_id | UUID            | FOREIGN KEY → contact (nullable)
from_agent_id   | UUID            | FOREIGN KEY → agent (nullable)
direction       | VARCHAR(20)     | 'inbound' or 'outbound'
channel         | VARCHAR(50)     | 'whatsapp', 'email', 'web'
body            | TEXT            | Message text
metadata        | JSON            | WhatsApp-specific data
sent_at         | TIMESTAMP       | When message was sent
delivered_at    | TIMESTAMP       | When delivery confirmed (nullable)
```

### Appendix D: Glossary

- **Agent**: AI-powered bot that handles conversations. Now includes WhatsApp configuration.
- **Contact**: Person or lead in the system. Can have no email (WhatsApp-only contacts).
- **Talk**: Conversation thread between Contact and Agent(s). Has channel (WhatsApp, email, web).
- **TalkMessage**: Individual message within a Talk. Has direction (inbound/outbound).
- **TreeFlow**: AI workflow template defining conversation structure and logic.
- **TalkFlow**: Instance of TreeFlow execution, stored as JSON in Talk entity.
- **Evolution API**: Open-source WhatsApp integration server.
- **Instance**: Evolution API WhatsApp connection (one phone number per instance).
- **remoteJid**: WhatsApp identifier (e.g., "5511999999999@s.whatsapp.net").
- **E.164**: International phone number format (e.g., "+5511999999999").
- **Webhook**: HTTP callback from Evolution API to Luminai when message received.

---

## Document Control

**Version History:**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-11-13 | Claude | Initial comprehensive plan |

**Approval:**

- [ ] Technical Review
- [ ] Security Review
- [ ] User Acceptance

**Next Steps:**

1. Review this document thoroughly
2. Approve for implementation
3. Create implementation tickets
4. Begin Step 1 (Database constraints)

---

**End of Document**
