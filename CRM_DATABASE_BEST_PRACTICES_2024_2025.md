# CRM Database Modeling Best Practices & Essential Entities (2024-2025)

## Executive Summary

This comprehensive analysis provides modern CRM database modeling best practices based on 2024-2025 industry standards, AI/ML integration patterns, and enterprise-grade requirements. The report includes essential entities, critical relationships, performance optimization strategies, and implementation recommendations for building a complete CRM system.

---

## Table of Contents

1. [Essential CRM Entities](#1-essential-crm-entities)
2. [Modern CRM Features & AI Integration](#2-modern-crm-features--ai-integration)
3. [Critical Relationships & Indexes](#3-critical-relationships--indexes)
4. [Customer Journey Tracking](#4-customer-journey-tracking)
5. [Sales Pipeline & Opportunity Management](#5-sales-pipeline--opportunity-management)
6. [Marketing Automation Entities](#6-marketing-automation-entities)
7. [Customer Support & Ticketing](#7-customer-support--ticketing)
8. [Product Catalog & CPQ (Configure-Price-Quote)](#8-product-catalog--cpq-configure-price-quote)
9. [Communication & Integration](#9-communication--integration)
10. [Reporting & Analytics](#10-reporting--analytics)
11. [Current System Analysis (Luminai)](#11-current-system-analysis-luminai)
12. [Recommendations & Implementation Roadmap](#12-recommendations--implementation-roadmap)

---

## 1. Essential CRM Entities

### 1.1 Core Customer Entities

#### **Contact** (Individual Person)
```php
- id (UUIDv7)
- firstName
- lastName
- fullName (computed)
- email (unique, indexed)
- phone
- mobile
- position/jobTitle
- department
- leadSource
- leadStatus (New, Qualified, Unqualified, Converted)
- leadScore (AI-powered scoring)
- dateOfBirth
- socialProfiles (JSON: LinkedIn, Twitter, etc.)
- preferredContactMethod
- timezone
- language
- tags (JSON array)
- customFields (JSON for flexibility)
- organizationId (FK)
- accountId (FK)
- ownerId (assigned sales rep, FK to User)
- createdAt, updatedAt
- createdBy, updatedBy
```

#### **Account** (Company/Organization)
```php
- id (UUIDv7)
- name (indexed)
- website
- industry
- employeeCount
- annualRevenue
- accountType (Prospect, Customer, Partner, Competitor)
- accountStatus (Active, Inactive, Churned)
- rating (Hot, Warm, Cold)
- billingAddress (JSON)
- shippingAddress (JSON)
- parentAccountId (FK, for account hierarchy)
- territoryId (FK)
- ownerId (FK to User)
- socialMediaHandles (JSON)
- customFields (JSON)
- organizationId (FK for multi-tenant)
- createdAt, updatedAt
- createdBy, updatedBy
```

#### **Lead** (Separate from Contact for qualification process)
```php
- id (UUIDv7)
- firstName, lastName
- email, phone
- company
- position
- leadSource (Website, Referral, Campaign, Cold Call)
- leadStatus (New, Contacted, Qualified, Unqualified)
- leadScore (0-100, AI-calculated)
- conversionDate
- convertedContactId (FK)
- convertedAccountId (FK)
- convertedOpportunityId (FK)
- disqualificationReason
- ownerId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

### 1.2 Sales Entities

#### **Opportunity** (Deal/Sale)
```php
- id (UUIDv7)
- name
- accountId (FK)
- contactId (FK, primary contact)
- amount (decimal)
- probability (percentage)
- expectedRevenue (amount * probability)
- stage (Prospecting, Qualification, Proposal, Negotiation, Closed Won, Closed Lost)
- nextStep
- closeDate (expected/actual)
- lostReason
- competitorId (FK, if applicable)
- campaignId (FK, source campaign)
- type (New Business, Upsell, Renewal, Cross-sell)
- sourcedBy (FK to User - attribution)
- ownerId (FK to User)
- forecastCategory (Pipeline, Best Case, Commit, Omitted)
- organizationId (FK)
- createdAt, updatedAt
```

#### **Quote** (Proposal/Estimate)
```php
- id (UUIDv7)
- quoteNumber (auto-generated, unique)
- opportunityId (FK)
- accountId (FK)
- contactId (FK)
- status (Draft, Sent, Accepted, Rejected, Expired)
- validUntil
- subtotal
- taxAmount
- totalAmount
- discount (percentage or amount)
- terms (text)
- notes (text)
- pdfPath (generated PDF location)
- acceptedDate
- rejectedDate
- rejectionReason
- ownerId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

#### **QuoteLineItem**
```php
- id (UUIDv7)
- quoteId (FK)
- productId (FK)
- description
- quantity
- unitPrice
- discount
- taxRate
- lineTotal
- sortOrder
```

#### **Product**
```php
- id (UUIDv7)
- name
- sku (unique, indexed)
- description
- category
- productFamily
- isActive
- unitPrice
- costPrice
- currency
- taxRate
- attributes (JSON for CPQ configuration)
- imageUrls (JSON array)
- organizationId (FK)
- createdAt, updatedAt
```

#### **PriceBook**
```php
- id (UUIDv7)
- name
- isActive
- isStandard
- currency
- validFrom
- validUntil
- organizationId (FK)
```

#### **PriceBookEntry**
```php
- id (UUIDv7)
- priceBookId (FK)
- productId (FK)
- unitPrice
- isActive
```

### 1.3 Marketing Entities

#### **Campaign**
```php
- id (UUIDv7)
- name
- type (Email, Social, Event, Webinar, Content, PPC)
- status (Planned, Active, Completed, Aborted)
- startDate
- endDate
- budgetCost
- actualCost
- expectedRevenue
- actualRevenue
- targetAudience (JSON)
- description
- parentCampaignId (FK, for campaign hierarchy)
- ownerId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

#### **CampaignMember**
```php
- id (UUIDv7)
- campaignId (FK)
- contactId (FK, nullable)
- leadId (FK, nullable)
- status (Sent, Responded, Clicked, Bounced, Converted)
- memberStatus (Invited, Attended, No-show)
- responseDate
- hasResponded
- conversionDate
- conversionValue
```

#### **EmailTemplate**
```php
- id (UUIDv7)
- name
- subject
- htmlBody
- textBody
- category
- isActive
- thumbnailUrl
- organizationId (FK)
- createdAt, updatedAt
```

#### **MarketingList** (Segment/Audience)
```php
- id (UUIDv7)
- name
- description
- type (Static, Dynamic)
- criteria (JSON for dynamic lists)
- memberCount
- isActive
- organizationId (FK)
- createdAt, updatedAt
```

### 1.4 Customer Support Entities

#### **Case** (Support Ticket)
```php
- id (UUIDv7)
- caseNumber (auto-generated, unique)
- subject
- description
- accountId (FK)
- contactId (FK)
- status (New, In Progress, Waiting, Resolved, Closed)
- priority (Low, Medium, High, Critical)
- severity (1-4)
- category
- subCategory
- channel (Email, Phone, Chat, Portal, Social)
- assignedToId (FK to User)
- assignedTeamId (FK)
- productId (FK, related product)
- resolution (text)
- responseTime (calculated)
- resolutionTime (calculated)
- firstResponseAt
- resolvedAt
- closedAt
- escalated
- escalationDate
- organizationId (FK)
- createdAt, updatedAt
```

#### **CaseComment**
```php
- id (UUIDv7)
- caseId (FK)
- authorId (FK to User)
- comment (text)
- isInternal (boolean)
- createdAt
```

#### **KnowledgeArticle**
```php
- id (UUIDv7)
- title
- summary
- content (text/HTML)
- category
- tags (JSON array)
- status (Draft, Published, Archived)
- viewCount
- helpfulCount
- notHelpfulCount
- publishedDate
- lastReviewedDate
- authorId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

### 1.5 Activity & Interaction Entities

#### **Activity** (Base for all interactions - Event Sourcing Pattern)
```php
- id (UUIDv7)
- type (Email, Call, Meeting, Task, Note, SMS)
- subject
- description
- status (Planned, In Progress, Completed, Cancelled)
- priority (Low, Normal, High)
- dueDate
- completedDate
- duration (minutes)
- outcome
- relatedToType (polymorphic: Account, Contact, Lead, Opportunity, Case)
- relatedToId (UUIDv7)
- ownerId (FK to User)
- assignedToId (FK to User)
- organizationId (FK)
- createdAt, updatedAt
```

#### **Email** (extends Activity)
```php
- activityId (FK to Activity)
- fromAddress
- toAddresses (JSON array)
- ccAddresses (JSON array)
- bccAddresses (JSON array)
- htmlBody
- textBody
- trackingId (for open/click tracking)
- isOpened
- openedAt
- isClicked
- clickedAt
- bounceType
- bouncedAt
```

#### **Call** (extends Activity)
```php
- activityId (FK to Activity)
- callType (Inbound, Outbound)
- phoneNumber
- callDuration
- recordingUrl
- transcription (text, AI-generated)
- sentiment (AI-analyzed)
- keyTopics (JSON array, AI-extracted)
```

#### **Meeting** (extends Activity)
```php
- activityId (FK to Activity)
- location
- meetingUrl (for virtual meetings)
- attendees (JSON array of contact/user IDs)
- agenda (text)
- minutes (text)
- recordingUrl
- transcription (text)
```

#### **Task**
```php
- id (UUIDv7)
- subject
- description
- status (Not Started, In Progress, Completed, Deferred)
- priority (Low, Normal, High)
- dueDate
- completedDate
- relatedToType
- relatedToId
- assignedToId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

#### **Note** (Annotation/Attachment)
```php
- id (UUIDv7)
- title
- content (text)
- isPrivate
- relatedToType
- relatedToId
- authorId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

#### **Attachment** (File/Document)
```php
- id (UUIDv7)
- fileName
- fileSize
- mimeType
- storagePath
- relatedToType
- relatedToId
- uploadedById (FK)
- organizationId (FK)
- createdAt
```

### 1.6 Communication Integration Entities

#### **EmailIntegration**
```php
- id (UUIDv7)
- userId (FK)
- provider (Gmail, Outlook, Exchange)
- emailAddress
- syncEnabled
- lastSyncAt
- syncStatus
- accessToken (encrypted)
- refreshToken (encrypted)
- organizationId (FK)
- createdAt, updatedAt
```

#### **CalendarIntegration**
```php
- id (UUIDv7)
- userId (FK)
- provider (Google, Outlook, Exchange)
- calendarId
- syncEnabled
- lastSyncAt
- syncDirection (OneWay, TwoWay)
- accessToken (encrypted)
- refreshToken (encrypted)
- organizationId (FK)
- createdAt, updatedAt
```

#### **ExternalEvent** (Synced Calendar Events)
```php
- id (UUIDv7)
- calendarIntegrationId (FK)
- externalId (provider's event ID)
- subject
- startTime
- endTime
- attendees (JSON)
- location
- activityId (FK, if linked to CRM activity)
- lastSyncedAt
```

### 1.7 Analytics & Reporting Entities

#### **Dashboard**
```php
- id (UUIDv7)
- name
- description
- layout (JSON configuration)
- isDefault
- isShared
- visibility (Private, Team, Organization)
- ownerId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

#### **Report**
```php
- id (UUIDv7)
- name
- description
- type (Tabular, Summary, Matrix, Chart)
- baseEntity (Account, Contact, Opportunity, etc.)
- filters (JSON)
- columns (JSON)
- grouping (JSON)
- sorting (JSON)
- chartConfig (JSON)
- schedule (JSON for automated reports)
- ownerId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

#### **CustomerJourney** (Journey Tracking)
```php
- id (UUIDv7)
- contactId (FK)
- accountId (FK)
- stage (Awareness, Consideration, Decision, Retention, Advocacy)
- touchpoints (JSON array of interactions)
- currentStep
- progressPercentage
- score (engagement score)
- lastInteractionAt
- organizationId (FK)
- createdAt, updatedAt
```

#### **CustomerTouchpoint** (Individual Journey Event)
```php
- id (UUIDv7)
- journeyId (FK)
- touchpointType (Website Visit, Email Open, Form Submit, Call, Meeting)
- channel (Email, Web, Social, Phone, In-person)
- description
- url
- metadata (JSON)
- timestamp
```

### 1.8 AI/ML Entities

#### **LeadScoreModel**
```php
- id (UUIDv7)
- name
- version
- algorithm (Regression, Neural Network, etc.)
- features (JSON array of scoring factors)
- weights (JSON)
- accuracy
- isActive
- lastTrainedAt
- organizationId (FK)
- createdAt, updatedAt
```

#### **Prediction**
```php
- id (UUIDv7)
- modelType (LeadScore, ChurnRisk, DealWinProbability, NextBestAction)
- entityType (Lead, Contact, Opportunity)
- entityId (UUIDv7)
- predictionValue (score/probability)
- confidence
- factors (JSON - explainability)
- predictedAt
- organizationId (FK)
```

#### **AIInsight**
```php
- id (UUIDv7)
- insightType (Anomaly, Trend, Recommendation, Risk)
- entityType
- entityId
- title
- description
- priority
- actionRecommendation
- dismissedAt
- dismissedById (FK)
- organizationId (FK)
- createdAt
```

### 1.9 Workflow & Automation Entities

#### **Workflow**
```php
- id (UUIDv7)
- name
- description
- triggerType (Record Create, Record Update, Time-based, Manual)
- triggerConditions (JSON)
- entityType (Account, Contact, Lead, Opportunity)
- isActive
- executionCount
- lastExecutedAt
- organizationId (FK)
- createdAt, updatedAt
```

#### **WorkflowAction**
```php
- id (UUIDv7)
- workflowId (FK)
- actionType (SendEmail, CreateTask, UpdateField, SendWebhook, RunScript)
- actionConfig (JSON)
- sortOrder
- delayMinutes
```

#### **WorkflowExecution**
```php
- id (UUIDv7)
- workflowId (FK)
- entityId (triggered on)
- status (Running, Completed, Failed)
- errorMessage
- executedAt
- completedAt
```

### 1.10 Territory & Team Management

#### **Territory**
```php
- id (UUIDv7)
- name
- description
- parentTerritoryId (FK, for hierarchy)
- territoryType (Geographic, Account-based, Product-based)
- rules (JSON - assignment criteria)
- organizationId (FK)
- createdAt, updatedAt
```

#### **Team**
```php
- id (UUIDv7)
- name
- description
- teamType (Sales, Support, Marketing)
- managerId (FK to User)
- territoryId (FK)
- organizationId (FK)
- createdAt, updatedAt
```

#### **TeamMember**
```php
- id (UUIDv7)
- teamId (FK)
- userId (FK)
- role (Member, Lead, Manager)
- joinedAt
```

---

## 2. Modern CRM Features & AI Integration

### 2.1 AI-Powered Capabilities (2024-2025)

#### **Predictive Analytics**
- **Lead Scoring**: ML algorithms analyze historical data, engagement patterns, behavioral signals
- **Opportunity Win Probability**: Predict deal closure likelihood
- **Churn Prediction**: Identify at-risk customers before they leave
- **Next Best Action**: AI recommends optimal next steps for each customer
- **Revenue Forecasting**: AI-enhanced sales forecasting with trend analysis

**Database Requirements**:
```sql
-- Store training data
CREATE TABLE ml_training_dataset (
    id UUID PRIMARY KEY,
    model_type VARCHAR(50),
    features JSONB,
    target_value NUMERIC,
    created_at TIMESTAMP
);

-- Store model performance metrics
CREATE TABLE ml_model_metrics (
    id UUID PRIMARY KEY,
    model_id UUID REFERENCES lead_score_model(id),
    metric_name VARCHAR(50),
    metric_value NUMERIC,
    evaluated_at TIMESTAMP
);
```

#### **Natural Language Processing (NLP)**
- **Email Sentiment Analysis**: Automatically detect customer sentiment
- **Call Transcription**: Convert sales calls to text
- **Topic Extraction**: Identify key discussion points
- **Entity Recognition**: Extract names, products, dates from conversations
- **Smart Reply Suggestions**: AI-generated email responses

**Database Requirements**:
```sql
-- Store NLP analysis results
CREATE TABLE nlp_analysis (
    id UUID PRIMARY KEY,
    entity_type VARCHAR(50),
    entity_id UUID,
    analysis_type VARCHAR(50),
    sentiment_score NUMERIC,
    entities_extracted JSONB,
    topics JSONB,
    created_at TIMESTAMP
);
```

#### **Intelligent Automation**
- **Auto-assignment**: AI routes leads/cases to best rep
- **Duplicate Detection**: ML-based record deduplication
- **Data Enrichment**: Automatically fill missing contact/company data
- **Anomaly Detection**: Flag unusual patterns (sudden deal value drop, etc.)

**Database Requirements**:
```sql
-- Track automation actions
CREATE TABLE automation_log (
    id UUID PRIMARY KEY,
    automation_type VARCHAR(50),
    entity_type VARCHAR(50),
    entity_id UUID,
    action_taken VARCHAR(255),
    ai_confidence NUMERIC,
    created_at TIMESTAMP
);
```

### 2.2 Real-Time Features

#### **Live Activity Feeds**
- WebSocket-based real-time updates
- Customer 360 timeline with all interactions
- Team collaboration notifications

**Database Requirements**:
```sql
-- Activity stream with event sourcing
CREATE TABLE activity_stream (
    id UUID PRIMARY KEY,
    event_type VARCHAR(50),
    aggregate_id UUID,
    event_data JSONB,
    actor_id UUID,
    occurred_at TIMESTAMP,
    organization_id UUID
);

CREATE INDEX idx_activity_stream_aggregate ON activity_stream(aggregate_id, occurred_at DESC);
```

#### **Collaborative Features**
- @mentions and comments on records
- Shared notes and files
- Real-time record locking during edits
- Activity notifications

### 2.3 Omnichannel Communication

**Supported Channels**:
- Email (Gmail, Outlook, Exchange sync)
- Phone (VoIP integration, call recording)
- SMS/WhatsApp
- Live Chat
- Social Media (LinkedIn, Twitter, Facebook)
- Video meetings (Zoom, Teams, Meet)

**Database Requirements**:
```sql
-- Unified communication log
CREATE TABLE communication (
    id UUID PRIMARY KEY,
    channel VARCHAR(50),
    direction VARCHAR(20), -- inbound/outbound
    from_address VARCHAR(255),
    to_address VARCHAR(255),
    contact_id UUID,
    account_id UUID,
    subject VARCHAR(500),
    body TEXT,
    metadata JSONB,
    occurred_at TIMESTAMP,
    organization_id UUID
);
```

---

## 3. Critical Relationships & Indexes

### 3.1 Core Relationships

```
Organization (1) → (N) Users
Organization (1) → (N) Accounts
Organization (1) → (N) Contacts
Organization (1) → (N) Leads

Account (1) → (N) Contacts
Account (1) → (N) Opportunities
Account (1) → (N) Cases
Account (1) → (1) Account (parent/child hierarchy)

Contact (1) → (N) Opportunities (via OpportunityContact join table)
Contact (1) → (N) Cases
Contact (1) → (N) Activities
Contact (1) → (1) Account

Lead (1) → (1) Contact (when converted)
Lead (1) → (1) Account (when converted)
Lead (1) → (1) Opportunity (when converted)

Opportunity (1) → (N) QuoteLineItems
Opportunity (1) → (N) Activities
Opportunity (1) → (N) Quotes
Opportunity (1) → (1) Campaign (source)

Case (1) → (N) CaseComments
Case (1) → (N) Activities

Campaign (1) → (N) CampaignMembers
Campaign (1) → (N) Opportunities (sourced from)

User (1) → (N) Accounts (owns)
User (1) → (N) Contacts (owns)
User (1) → (N) Opportunities (owns)
User (1) → (N) Cases (assigned)
```

### 3.2 Essential Indexes for Performance

#### **High-Priority Indexes** (Create immediately)

```sql
-- Multi-tenant isolation (CRITICAL for performance)
CREATE INDEX idx_contact_org ON contact(organization_id, created_at DESC);
CREATE INDEX idx_account_org ON account(organization_id, created_at DESC);
CREATE INDEX idx_opportunity_org ON opportunity(organization_id, close_date DESC);
CREATE INDEX idx_case_org ON "case"(organization_id, created_at DESC);

-- Ownership and assignment lookups
CREATE INDEX idx_contact_owner ON contact(owner_id, organization_id);
CREATE INDEX idx_account_owner ON account(owner_id, organization_id);
CREATE INDEX idx_opportunity_owner ON opportunity(owner_id, organization_id);
CREATE INDEX idx_case_assigned ON "case"(assigned_to_id, status);

-- Foreign key relationships
CREATE INDEX idx_contact_account ON contact(account_id);
CREATE INDEX idx_opportunity_account ON opportunity(account_id);
CREATE INDEX idx_case_account ON "case"(account_id);
CREATE INDEX idx_case_contact ON "case"(contact_id);

-- Search and filtering
CREATE INDEX idx_contact_email ON contact(email) WHERE email IS NOT NULL;
CREATE INDEX idx_account_name ON account(name);
CREATE INDEX idx_opportunity_stage ON opportunity(stage, organization_id);
CREATE INDEX idx_case_status ON "case"(status, priority, organization_id);

-- Activity timeline queries
CREATE INDEX idx_activity_related ON activity(related_to_type, related_to_id, created_at DESC);
CREATE INDEX idx_activity_owner ON activity(owner_id, due_date);

-- Lead scoring and status
CREATE INDEX idx_lead_score ON lead(lead_score DESC, organization_id);
CREATE INDEX idx_lead_status ON lead(lead_status, organization_id);

-- Campaign performance
CREATE INDEX idx_campaign_member_campaign ON campaign_member(campaign_id, status);

-- Date-based queries
CREATE INDEX idx_opportunity_close_date ON opportunity(close_date, organization_id);
CREATE INDEX idx_activity_due_date ON activity(due_date, assigned_to_id) WHERE status != 'Completed';
```

#### **Full-Text Search Indexes**

```sql
-- PostgreSQL full-text search
CREATE INDEX idx_contact_search ON contact USING GIN(
    to_tsvector('english',
        COALESCE(first_name, '') || ' ' ||
        COALESCE(last_name, '') || ' ' ||
        COALESCE(email, '')
    )
);

CREATE INDEX idx_account_search ON account USING GIN(
    to_tsvector('english',
        COALESCE(name, '') || ' ' ||
        COALESESCE(website, '')
    )
);

CREATE INDEX idx_case_search ON "case" USING GIN(
    to_tsvector('english',
        COALESCE(subject, '') || ' ' ||
        COALESCE(description, '')
    )
);
```

#### **JSON Field Indexes** (PostgreSQL JSONB)

```sql
-- Index on JSON fields for fast filtering
CREATE INDEX idx_contact_tags ON contact USING GIN(tags);
CREATE INDEX idx_product_attributes ON product USING GIN(attributes);
CREATE INDEX idx_account_custom_fields ON account USING GIN(custom_fields);

-- Specific JSON path indexes
CREATE INDEX idx_contact_social_linkedin ON contact((social_profiles->>'linkedin'));
```

#### **Composite Indexes** (Multiple columns)

```sql
-- Pipeline analysis
CREATE INDEX idx_opportunity_pipeline ON opportunity(stage, owner_id, close_date);

-- Support queue management
CREATE INDEX idx_case_queue ON "case"(status, priority, assigned_to_id);

-- Campaign attribution
CREATE INDEX idx_opportunity_campaign ON opportunity(campaign_id, stage, organization_id);

-- Forecast reporting
CREATE INDEX idx_opportunity_forecast ON opportunity(
    forecast_category,
    close_date,
    owner_id
) WHERE stage NOT IN ('Closed Won', 'Closed Lost');
```

#### **Covering Indexes** (Include frequently accessed columns)

```sql
-- Reduce table lookups for common queries
CREATE INDEX idx_contact_list_view ON contact(organization_id, created_at DESC)
    INCLUDE (first_name, last_name, email, account_id, owner_id);

CREATE INDEX idx_opportunity_pipeline_view ON opportunity(organization_id, stage)
    INCLUDE (name, amount, close_date, probability, owner_id);
```

### 3.3 Database Constraints & Data Integrity

```sql
-- Prevent orphaned records
ALTER TABLE contact ADD CONSTRAINT fk_contact_organization
    FOREIGN KEY (organization_id) REFERENCES organization(id) ON DELETE CASCADE;

-- Ensure converted leads maintain referential integrity
ALTER TABLE lead ADD CONSTRAINT fk_lead_converted_contact
    FOREIGN KEY (converted_contact_id) REFERENCES contact(id) ON DELETE SET NULL;

-- Validate status transitions
ALTER TABLE opportunity ADD CONSTRAINT chk_opportunity_stage
    CHECK (stage IN ('Prospecting', 'Qualification', 'Proposal', 'Negotiation', 'Closed Won', 'Closed Lost'));

-- Business logic constraints
ALTER TABLE quote ADD CONSTRAINT chk_quote_dates
    CHECK (valid_until >= created_at);

ALTER TABLE opportunity ADD CONSTRAINT chk_opportunity_amount
    CHECK (amount >= 0);

-- Unique constraints
ALTER TABLE product ADD CONSTRAINT uq_product_sku
    UNIQUE (sku, organization_id);

ALTER TABLE case ADD CONSTRAINT uq_case_number
    UNIQUE (case_number, organization_id);
```

### 3.4 Partitioning Strategy (For Scale)

```sql
-- Partition activity table by date (monthly partitions)
CREATE TABLE activity (
    id UUID,
    created_at TIMESTAMP NOT NULL,
    ...
) PARTITION BY RANGE (created_at);

CREATE TABLE activity_2024_01 PARTITION OF activity
    FOR VALUES FROM ('2024-01-01') TO ('2024-02-01');

-- Partition by organization_id for large multi-tenant systems
CREATE TABLE contact (
    organization_id UUID NOT NULL,
    ...
) PARTITION BY HASH (organization_id);
```

---

## 4. Customer Journey Tracking

### 4.1 Journey Architecture

Modern CRM systems use **event sourcing** patterns to track complete customer journeys:

```sql
CREATE TABLE customer_journey_event (
    id UUID PRIMARY KEY,
    journey_id UUID REFERENCES customer_journey(id),
    event_type VARCHAR(100),
    event_category VARCHAR(50), -- Awareness, Consideration, Decision, Retention
    channel VARCHAR(50),
    touchpoint_data JSONB,
    occurred_at TIMESTAMP,
    sequence_number INTEGER,
    organization_id UUID
);

CREATE INDEX idx_journey_timeline ON customer_journey_event(journey_id, occurred_at);
CREATE INDEX idx_journey_category ON customer_journey_event(event_category, occurred_at);
```

### 4.2 Journey Stages & Touchpoints

**Awareness Stage**:
- Website visits
- Social media interactions
- Content downloads
- Webinar registrations

**Consideration Stage**:
- Demo requests
- Trial signups
- Pricing page views
- Case study downloads

**Decision Stage**:
- Quote requests
- Contract negotiations
- Sales meetings
- Proposal views

**Retention Stage**:
- Product usage
- Support interactions
- Renewal discussions
- Upsell opportunities

**Advocacy Stage**:
- Referrals
- Reviews
- Case study participation
- Community engagement

### 4.3 Analytics Queries

```sql
-- Average time to conversion by source
SELECT
    lead_source,
    AVG(EXTRACT(EPOCH FROM (conversion_date - created_at))/86400) as avg_days_to_convert,
    COUNT(*) as conversions
FROM lead
WHERE conversion_date IS NOT NULL
GROUP BY lead_source;

-- Touchpoint effectiveness analysis
SELECT
    event_type,
    COUNT(*) as touchpoint_count,
    COUNT(DISTINCT journey_id) as unique_journeys,
    AVG(CASE WHEN conversion_occurred THEN 1 ELSE 0 END) as conversion_rate
FROM customer_journey_event
GROUP BY event_type
ORDER BY conversion_rate DESC;

-- Customer journey funnel
SELECT
    event_category,
    COUNT(DISTINCT journey_id) as journeys,
    COUNT(DISTINCT journey_id) * 100.0 /
        (SELECT COUNT(DISTINCT journey_id) FROM customer_journey_event) as percentage
FROM customer_journey_event
GROUP BY event_category
ORDER BY
    CASE event_category
        WHEN 'Awareness' THEN 1
        WHEN 'Consideration' THEN 2
        WHEN 'Decision' THEN 3
        WHEN 'Retention' THEN 4
        WHEN 'Advocacy' THEN 5
    END;
```

---

## 5. Sales Pipeline & Opportunity Management

### 5.1 Pipeline Stages & Best Practices

**Recommended Stage Definitions**:

1. **Prospecting** - Initial outreach, qualifying conversation
   - Exit Criteria: Budget confirmed, timeline established

2. **Qualification** - BANT (Budget, Authority, Need, Timeline) validated
   - Exit Criteria: Decision maker engaged, pain points identified

3. **Discovery/Analysis** - Solution mapping, technical validation
   - Exit Criteria: Requirements documented, solution proposed

4. **Proposal/Business Case** - Formal proposal, value demonstration
   - Exit Criteria: Proposal presented, pricing discussed

5. **Negotiation** - Contract terms, pricing finalization
   - Exit Criteria: Mutual agreement on terms

6. **Closed Won** - Deal won, contract signed

7. **Closed Lost** - Deal lost (track reason for analysis)

### 5.2 Opportunity Attribution

```sql
-- Track who sourced the opportunity
CREATE TABLE opportunity_attribution (
    id UUID PRIMARY KEY,
    opportunity_id UUID REFERENCES opportunity(id),
    attribution_type VARCHAR(50), -- Sourced, Assisted, Closed
    user_id UUID REFERENCES "user"(id),
    campaign_id UUID REFERENCES campaign(id),
    attribution_percentage NUMERIC,
    created_at TIMESTAMP
);

-- Multi-touch attribution query
SELECT
    u.name as rep_name,
    SUM(CASE WHEN oa.attribution_type = 'Sourced' THEN o.amount ELSE 0 END) as sourced_pipeline,
    SUM(CASE WHEN oa.attribution_type = 'Closed' THEN o.amount ELSE 0 END) as closed_revenue,
    AVG(CASE WHEN o.stage = 'Closed Won' THEN 100 ELSE 0 END) as win_rate
FROM opportunity_attribution oa
JOIN opportunity o ON oa.opportunity_id = o.id
JOIN "user" u ON oa.user_id = u.id
WHERE o.organization_id = :org_id
GROUP BY u.id, u.name;
```

### 5.3 Forecast Management

```sql
CREATE TABLE sales_forecast (
    id UUID PRIMARY KEY,
    period_start DATE,
    period_end DATE,
    owner_id UUID REFERENCES "user"(id),
    category VARCHAR(50), -- Pipeline, Best Case, Commit, Closed
    forecasted_amount NUMERIC,
    actual_amount NUMERIC,
    created_at TIMESTAMP,
    organization_id UUID
);

-- Forecast accuracy analysis
SELECT
    DATE_TRUNC('month', period_start) as month,
    category,
    SUM(forecasted_amount) as forecast,
    SUM(actual_amount) as actual,
    (SUM(actual_amount) / NULLIF(SUM(forecasted_amount), 0) * 100) as accuracy_percentage
FROM sales_forecast
GROUP BY month, category
ORDER BY month DESC, category;
```

### 5.4 Pipeline Velocity Metrics

```sql
-- Calculate pipeline velocity
WITH stage_durations AS (
    SELECT
        opportunity_id,
        stage,
        LAG(created_at) OVER (PARTITION BY opportunity_id ORDER BY created_at) as previous_stage_date,
        created_at as current_stage_date
    FROM opportunity_stage_history
)
SELECT
    stage,
    AVG(EXTRACT(EPOCH FROM (current_stage_date - previous_stage_date))/86400) as avg_days_in_stage,
    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY EXTRACT(EPOCH FROM (current_stage_date - previous_stage_date))/86400) as median_days
FROM stage_durations
WHERE previous_stage_date IS NOT NULL
GROUP BY stage;
```

---

## 6. Marketing Automation Entities

### 6.1 Campaign Tracking

```sql
-- Track campaign ROI
CREATE VIEW campaign_roi AS
SELECT
    c.id,
    c.name,
    c.budget_cost,
    c.actual_cost,
    COUNT(DISTINCT cm.id) as members,
    COUNT(DISTINCT CASE WHEN cm.status = 'Responded' THEN cm.id END) as responses,
    COUNT(DISTINCT o.id) as opportunities_created,
    SUM(CASE WHEN o.stage = 'Closed Won' THEN o.amount ELSE 0 END) as revenue_generated,
    (SUM(CASE WHEN o.stage = 'Closed Won' THEN o.amount ELSE 0 END) - c.actual_cost) / NULLIF(c.actual_cost, 0) * 100 as roi_percentage
FROM campaign c
LEFT JOIN campaign_member cm ON c.id = cm.campaign_id
LEFT JOIN opportunity o ON c.id = o.campaign_id
GROUP BY c.id, c.name, c.budget_cost, c.actual_cost;
```

### 6.2 Email Tracking & Engagement

```sql
CREATE TABLE email_engagement (
    id UUID PRIMARY KEY,
    email_id UUID REFERENCES email(id),
    contact_id UUID,
    event_type VARCHAR(50), -- Sent, Delivered, Opened, Clicked, Bounced, Unsubscribed
    event_data JSONB,
    occurred_at TIMESTAMP,
    organization_id UUID
);

CREATE INDEX idx_email_engagement_contact ON email_engagement(contact_id, occurred_at DESC);

-- Email engagement scoring
SELECT
    c.id,
    c.first_name,
    c.last_name,
    COUNT(CASE WHEN ee.event_type = 'Opened' THEN 1 END) as opens,
    COUNT(CASE WHEN ee.event_type = 'Clicked' THEN 1 END) as clicks,
    (COUNT(CASE WHEN ee.event_type = 'Clicked' THEN 1 END) * 5 +
     COUNT(CASE WHEN ee.event_type = 'Opened' THEN 1 END) * 2) as engagement_score
FROM contact c
LEFT JOIN email_engagement ee ON c.id = ee.contact_id
GROUP BY c.id, c.first_name, c.last_name
ORDER BY engagement_score DESC;
```

### 6.3 Marketing List Segmentation

```sql
CREATE TABLE marketing_list_criteria (
    id UUID PRIMARY KEY,
    list_id UUID REFERENCES marketing_list(id),
    field_name VARCHAR(100),
    operator VARCHAR(50), -- equals, contains, greater_than, etc.
    value VARCHAR(255),
    logic_operator VARCHAR(10) -- AND, OR
);

-- Dynamic list evaluation (stored procedure)
CREATE OR REPLACE FUNCTION evaluate_marketing_list(list_id UUID)
RETURNS TABLE (contact_id UUID) AS $$
BEGIN
    -- Dynamic SQL generation based on criteria
    -- This is a simplified example
    RETURN QUERY EXECUTE (
        SELECT build_dynamic_query(list_id)
    );
END;
$$ LANGUAGE plpgsql;
```

---

## 7. Customer Support & Ticketing

### 7.1 SLA Management

```sql
CREATE TABLE sla_policy (
    id UUID PRIMARY KEY,
    name VARCHAR(255),
    priority VARCHAR(50),
    first_response_minutes INTEGER,
    resolution_minutes INTEGER,
    business_hours_only BOOLEAN,
    organization_id UUID
);

CREATE TABLE case_sla (
    id UUID PRIMARY KEY,
    case_id UUID REFERENCES "case"(id),
    sla_policy_id UUID REFERENCES sla_policy(id),
    first_response_due_at TIMESTAMP,
    resolution_due_at TIMESTAMP,
    first_response_at TIMESTAMP,
    resolved_at TIMESTAMP,
    first_response_breached BOOLEAN,
    resolution_breached BOOLEAN
);

-- SLA breach monitoring
SELECT
    c.case_number,
    c.subject,
    c.priority,
    sp.name as sla_policy,
    cs.first_response_due_at,
    cs.resolution_due_at,
    CASE
        WHEN cs.first_response_at IS NULL AND NOW() > cs.first_response_due_at THEN 'Response Overdue'
        WHEN cs.resolved_at IS NULL AND NOW() > cs.resolution_due_at THEN 'Resolution Overdue'
        ELSE 'On Track'
    END as sla_status
FROM "case" c
JOIN case_sla cs ON c.id = cs.case_id
JOIN sla_policy sp ON cs.sla_policy_id = sp.id
WHERE c.status NOT IN ('Resolved', 'Closed');
```

### 7.2 Knowledge Base Integration

```sql
-- Track article usage and helpfulness
CREATE TABLE article_feedback (
    id UUID PRIMARY KEY,
    article_id UUID REFERENCES knowledge_article(id),
    user_id UUID,
    was_helpful BOOLEAN,
    feedback_text TEXT,
    created_at TIMESTAMP
);

-- Auto-suggest articles for cases
CREATE TABLE case_suggested_article (
    id UUID PRIMARY KEY,
    case_id UUID REFERENCES "case"(id),
    article_id UUID REFERENCES knowledge_article(id),
    relevance_score NUMERIC, -- AI-calculated
    was_used BOOLEAN,
    created_at TIMESTAMP
);
```

### 7.3 Multi-channel Support Tracking

```sql
-- Unified communication table
CREATE TABLE support_interaction (
    id UUID PRIMARY KEY,
    case_id UUID REFERENCES "case"(id),
    channel VARCHAR(50), -- Email, Phone, Chat, Social, Portal
    direction VARCHAR(20), -- Inbound, Outbound
    from_address VARCHAR(255),
    to_address VARCHAR(255),
    subject VARCHAR(500),
    body TEXT,
    sentiment VARCHAR(50), -- AI-analyzed: Positive, Neutral, Negative
    urgency_score NUMERIC, -- AI-calculated
    occurred_at TIMESTAMP,
    agent_id UUID,
    organization_id UUID
);
```

---

## 8. Product Catalog & CPQ (Configure-Price-Quote)

### 8.1 Product Configuration

```sql
-- Product bundles and configurations
CREATE TABLE product_bundle (
    id UUID PRIMARY KEY,
    name VARCHAR(255),
    parent_product_id UUID REFERENCES product(id),
    bundle_type VARCHAR(50), -- Standard, Custom
    organization_id UUID
);

CREATE TABLE product_bundle_item (
    id UUID PRIMARY KEY,
    bundle_id UUID REFERENCES product_bundle(id),
    product_id UUID REFERENCES product(id),
    quantity INTEGER,
    is_required BOOLEAN,
    sort_order INTEGER
);

-- Product options (for CPQ)
CREATE TABLE product_option (
    id UUID PRIMARY KEY,
    product_id UUID REFERENCES product(id),
    option_name VARCHAR(100),
    option_type VARCHAR(50), -- Dropdown, Text, Checkbox, Number
    is_required BOOLEAN,
    sort_order INTEGER
);

CREATE TABLE product_option_value (
    id UUID PRIMARY KEY,
    option_id UUID REFERENCES product_option(id),
    value VARCHAR(255),
    price_modifier NUMERIC, -- Additional cost
    is_default BOOLEAN
);
```

### 8.2 Pricing Rules & Discounts

```sql
CREATE TABLE pricing_rule (
    id UUID PRIMARY KEY,
    name VARCHAR(255),
    rule_type VARCHAR(50), -- Volume, Bundle, Promotional
    product_id UUID,
    min_quantity INTEGER,
    max_quantity INTEGER,
    discount_type VARCHAR(20), -- Percentage, Fixed
    discount_value NUMERIC,
    valid_from DATE,
    valid_until DATE,
    organization_id UUID
);

CREATE TABLE discount_approval (
    id UUID PRIMARY KEY,
    quote_id UUID REFERENCES quote(id),
    requested_discount NUMERIC,
    approved_discount NUMERIC,
    requested_by UUID REFERENCES "user"(id),
    approved_by UUID REFERENCES "user"(id),
    status VARCHAR(50),
    reason TEXT,
    created_at TIMESTAMP
);
```

### 8.3 Quote Generation & Tracking

```sql
-- Quote versioning
CREATE TABLE quote_version (
    id UUID PRIMARY KEY,
    quote_id UUID REFERENCES quote(id),
    version_number INTEGER,
    total_amount NUMERIC,
    line_items JSONB,
    changes_description TEXT,
    created_by UUID,
    created_at TIMESTAMP
);

-- Quote acceptance tracking
CREATE TABLE quote_interaction (
    id UUID PRIMARY KEY,
    quote_id UUID REFERENCES quote(id),
    interaction_type VARCHAR(50), -- Viewed, Downloaded, Accepted, Rejected
    ip_address VARCHAR(45),
    user_agent TEXT,
    occurred_at TIMESTAMP
);
```

---

## 9. Communication & Integration

### 9.1 Email Integration Architecture

```sql
-- Bi-directional email sync
CREATE TABLE email_sync_log (
    id UUID PRIMARY KEY,
    integration_id UUID REFERENCES email_integration(id),
    sync_direction VARCHAR(20), -- Inbound, Outbound
    email_count INTEGER,
    status VARCHAR(50),
    error_message TEXT,
    started_at TIMESTAMP,
    completed_at TIMESTAMP
);

-- Email-to-case routing
CREATE TABLE email_routing_rule (
    id UUID PRIMARY KEY,
    from_pattern VARCHAR(255), -- Regex pattern
    subject_pattern VARCHAR(255),
    to_address VARCHAR(255),
    assign_to_queue UUID,
    assign_to_user UUID,
    priority VARCHAR(50),
    category VARCHAR(100),
    organization_id UUID
);
```

### 9.2 Calendar Synchronization

```sql
-- Two-way calendar sync
CREATE TABLE calendar_sync_mapping (
    id UUID PRIMARY KEY,
    crm_activity_id UUID REFERENCES activity(id),
    external_event_id VARCHAR(255),
    provider VARCHAR(50),
    last_synced_at TIMESTAMP,
    sync_status VARCHAR(50)
);

-- Meeting scheduling
CREATE TABLE meeting_availability (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES "user"(id),
    day_of_week INTEGER,
    start_time TIME,
    end_time TIME,
    timezone VARCHAR(50)
);
```

### 9.3 Webhook & API Integration

```sql
CREATE TABLE webhook_subscription (
    id UUID PRIMARY KEY,
    url VARCHAR(500),
    event_types JSONB, -- Array of events to listen to
    secret_key VARCHAR(255), -- For webhook verification
    is_active BOOLEAN,
    retry_policy JSONB,
    organization_id UUID
);

CREATE TABLE webhook_delivery (
    id UUID PRIMARY KEY,
    subscription_id UUID REFERENCES webhook_subscription(id),
    event_type VARCHAR(100),
    payload JSONB,
    response_status INTEGER,
    response_body TEXT,
    attempt_count INTEGER,
    delivered_at TIMESTAMP
);
```

---

## 10. Reporting & Analytics

### 10.1 Data Warehouse Schema (Star Schema)

```sql
-- Fact table for opportunity analysis
CREATE TABLE fact_opportunity (
    id UUID PRIMARY KEY,
    opportunity_id UUID,
    account_id UUID,
    owner_id UUID,
    date_key INTEGER, -- Foreign key to dim_date
    stage_key INTEGER,
    amount NUMERIC,
    probability NUMERIC,
    expected_revenue NUMERIC,
    age_days INTEGER,
    organization_id UUID
);

-- Dimension tables
CREATE TABLE dim_date (
    date_key INTEGER PRIMARY KEY,
    full_date DATE,
    year INTEGER,
    quarter INTEGER,
    month INTEGER,
    week INTEGER,
    day_of_week INTEGER,
    is_weekend BOOLEAN,
    is_holiday BOOLEAN
);

CREATE TABLE dim_account (
    account_key UUID PRIMARY KEY,
    account_id UUID,
    name VARCHAR(255),
    industry VARCHAR(100),
    revenue_band VARCHAR(50),
    employee_count_band VARCHAR(50),
    current_as_of DATE
);

-- Slowly Changing Dimension (Type 2) for historical tracking
CREATE TABLE dim_user (
    user_key UUID PRIMARY KEY,
    user_id UUID,
    name VARCHAR(255),
    role VARCHAR(100),
    team VARCHAR(100),
    territory VARCHAR(100),
    valid_from DATE,
    valid_to DATE,
    is_current BOOLEAN
);
```

### 10.2 Pre-calculated Analytics

```sql
-- Daily snapshot for trend analysis
CREATE TABLE daily_snapshot_opportunity (
    snapshot_date DATE,
    organization_id UUID,
    stage VARCHAR(50),
    owner_id UUID,
    opportunity_count INTEGER,
    total_amount NUMERIC,
    weighted_amount NUMERIC,
    PRIMARY KEY (snapshot_date, organization_id, stage, owner_id)
);

-- Create daily via scheduled job
INSERT INTO daily_snapshot_opportunity
SELECT
    CURRENT_DATE,
    organization_id,
    stage,
    owner_id,
    COUNT(*) as opportunity_count,
    SUM(amount) as total_amount,
    SUM(amount * probability / 100) as weighted_amount
FROM opportunity
WHERE stage NOT IN ('Closed Won', 'Closed Lost')
GROUP BY organization_id, stage, owner_id;
```

### 10.3 Real-Time Dashboards

```sql
-- Materialized view for dashboard performance
CREATE MATERIALIZED VIEW mv_sales_dashboard AS
SELECT
    DATE_TRUNC('month', o.close_date) as month,
    o.owner_id,
    u.name as owner_name,
    COUNT(CASE WHEN o.stage = 'Closed Won' THEN 1 END) as deals_won,
    COUNT(CASE WHEN o.stage = 'Closed Lost' THEN 1 END) as deals_lost,
    SUM(CASE WHEN o.stage = 'Closed Won' THEN o.amount ELSE 0 END) as revenue,
    AVG(CASE WHEN o.stage IN ('Closed Won', 'Closed Lost')
        THEN EXTRACT(EPOCH FROM (o.updated_at - o.created_at))/86400
    END) as avg_sales_cycle_days
FROM opportunity o
JOIN "user" u ON o.owner_id = u.id
GROUP BY month, o.owner_id, u.name;

CREATE UNIQUE INDEX ON mv_sales_dashboard (month, owner_id);

-- Refresh periodically
REFRESH MATERIALIZED VIEW CONCURRENTLY mv_sales_dashboard;
```

### 10.4 Custom Report Builder Schema

```sql
CREATE TABLE custom_report_definition (
    id UUID PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    base_entity VARCHAR(100),
    filters JSONB,
    columns JSONB,
    aggregations JSONB,
    grouping JSONB,
    sorting JSONB,
    is_shared BOOLEAN,
    owner_id UUID,
    organization_id UUID
);

-- Saved report results (cached)
CREATE TABLE report_execution (
    id UUID PRIMARY KEY,
    report_id UUID REFERENCES custom_report_definition(id),
    parameters JSONB,
    result_data JSONB,
    row_count INTEGER,
    execution_time_ms INTEGER,
    executed_at TIMESTAMP,
    executed_by UUID
);
```

---

## 11. Current System Analysis (Luminai)

### 11.1 Existing Entities (Currently Implemented)

Based on the codebase analysis, Luminai currently has:

**Core Entities**:
- ✅ `Organization` - Multi-tenant foundation
- ✅ `User` - Authentication, roles, permissions
- ✅ `Role` - RBAC implementation
- ✅ `AuditLog` - Change tracking

**Education/Course Entities** (Current focus):
- ✅ `Course`
- ✅ `CourseModule`
- ✅ `CourseLecture`
- ✅ `StudentCourse`
- ✅ `StudentLecture`
- ✅ `TreeFlow` (Canvas editor)
- ✅ `Step`, `StepConnection`, `StepInput`, `StepOutput`, `StepQuestion`

**Base Infrastructure**:
- ✅ UUIDv7 primary keys
- ✅ Audit trail (createdAt, updatedAt, createdBy, updatedBy)
- ✅ Multi-tenant isolation via Organization
- ✅ Soft delete capability (SoftDeletableTrait)

### 11.2 Missing Critical CRM Entities

**Customer Management** (Not Present):
- ❌ Contact
- ❌ Account (Company)
- ❌ Lead

**Sales Management** (Not Present):
- ❌ Opportunity (Deal/Sale)
- ❌ Quote
- ❌ Product
- ❌ PriceBook

**Marketing** (Not Present):
- ❌ Campaign
- ❌ CampaignMember
- ❌ EmailTemplate
- ❌ MarketingList

**Support** (Not Present):
- ❌ Case (Ticket)
- ❌ CaseComment
- ❌ KnowledgeArticle

**Activities** (Not Present):
- ❌ Activity (Base)
- ❌ Email
- ❌ Call
- ❌ Meeting
- ❌ Task
- ❌ Note

**Communication** (Not Present):
- ❌ EmailIntegration
- ❌ CalendarIntegration

**Analytics** (Minimal):
- ❌ Dashboard
- ❌ Report
- ❌ CustomerJourney

**AI/ML** (Not Present):
- ❌ LeadScoreModel
- ❌ Prediction
- ❌ AIInsight

### 11.3 Architecture Strengths (Leverage These)

1. **Solid Foundation**:
   - UUIDv7 implementation (time-ordered, performant)
   - Multi-tenant architecture with Organization filtering
   - Comprehensive audit trail via AuditTrait
   - EntityBase abstract class for consistency

2. **Modern Tech Stack**:
   - Symfony 7.3
   - API Platform 4.1
   - PostgreSQL 18 (supports advanced features)
   - Redis for caching

3. **Security**:
   - Voter-based authorization
   - API token system
   - Failed login tracking
   - Account locking

4. **Frontend**:
   - Turbo Drive for SPA-like experience
   - Stimulus controllers
   - Bootstrap 5.3

### 11.4 Recommended Migration Path

**Phase 1: Core CRM Entities** (Weeks 1-2)
1. Contact
2. Account
3. Lead
4. Activity (base)
5. Note

**Phase 2: Sales Pipeline** (Weeks 3-4)
6. Opportunity
7. Product
8. Quote
9. QuoteLineItem

**Phase 3: Communication** (Week 5)
10. Email (activity type)
11. Call (activity type)
12. Task
13. EmailIntegration

**Phase 4: Marketing** (Week 6)
14. Campaign
15. CampaignMember
16. EmailTemplate

**Phase 5: Support** (Week 7)
17. Case
18. CaseComment
19. KnowledgeArticle

**Phase 6: Analytics & AI** (Weeks 8-10)
20. Dashboard
21. Report
22. CustomerJourney
23. LeadScoreModel
24. Prediction

---

## 12. Recommendations & Implementation Roadmap

### 12.1 Immediate Actions (Week 1)

#### 1. Database Schema Planning
```bash
# Create comprehensive Entity.csv for generator
cat > /home/user/inf/app/Entity.csv << 'EOF'
entityName,fieldName,fieldType,nullable,defaultValue,unique,indexed,relationship,validations
Contact,firstName,string,false,,,true,,NotBlank
Contact,lastName,string,false,,,true,,NotBlank
Contact,email,string,true,,true,true,,Email
Contact,phone,string,true,,,false
Contact,mobile,string,true,,,false
Contact,position,string,true,,,false
Contact,leadSource,string,true,,,true
Contact,leadStatus,string,false,New,,true
Contact,leadScore,integer,false,0,,true
Contact,accountId,uuid,true,,,true,ManyToOne:Account
Contact,ownerId,uuid,false,,,true,ManyToOne:User
Contact,organizationId,uuid,false,,,true,ManyToOne:Organization
Account,name,string,false,,,true,,NotBlank
Account,website,string,true,,,false,,Url
Account,industry,string,true,,,true
Account,employeeCount,integer,true,,,false
Account,annualRevenue,decimal,true,,,false
Account,accountType,string,false,Prospect,,true
Account,parentAccountId,uuid,true,,,true,ManyToOne:Account
Account,ownerId,uuid,false,,,true,ManyToOne:User
Account,organizationId,uuid,false,,,true,ManyToOne:Organization
# ... continue for all entities
EOF
```

#### 2. Index Strategy
```sql
-- Create essential indexes immediately
CREATE INDEX CONCURRENTLY idx_contact_org_created ON contact(organization_id, created_at DESC);
CREATE INDEX CONCURRENTLY idx_contact_owner ON contact(owner_id, organization_id);
CREATE INDEX CONCURRENTLY idx_contact_email ON contact(email) WHERE email IS NOT NULL;
CREATE INDEX CONCURRENTLY idx_account_org_name ON account(organization_id, name);
CREATE INDEX CONCURRENTLY idx_opportunity_pipeline ON opportunity(organization_id, stage, close_date);
```

#### 3. Migration Scripts
```php
// migrations/VersionXXX_AddCoreContacts.php
public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE contact (
        id UUID PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255),
        phone VARCHAR(50),
        account_id UUID REFERENCES account(id),
        owner_id UUID REFERENCES "user"(id),
        organization_id UUID NOT NULL REFERENCES organization(id),
        created_at TIMESTAMP NOT NULL,
        updated_at TIMESTAMP NOT NULL,
        created_by UUID,
        updated_by UUID
    )');

    // Add indexes
    $this->addSql('CREATE INDEX idx_contact_org ON contact(organization_id, created_at DESC)');
    $this->addSql('CREATE INDEX idx_contact_owner ON contact(owner_id)');
    $this->addSql('CREATE UNIQUE INDEX idx_contact_email ON contact(email) WHERE email IS NOT NULL');
}
```

### 12.2 Performance Optimization Strategy

#### 1. Query Optimization
```php
// Use query builder with proper joins and indexes
$qb = $this->createQueryBuilder('c')
    ->select('c', 'a', 'o') // Eager load related entities
    ->leftJoin('c.account', 'a')
    ->leftJoin('c.owner', 'o')
    ->where('c.organization = :org')
    ->setParameter('org', $organization)
    ->orderBy('c.createdAt', 'DESC')
    ->setMaxResults(25);
```

#### 2. Caching Strategy
```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            crm.cache:
                adapter: cache.adapter.redis
                default_lifetime: 3600
            crm.entity.cache:
                adapter: cache.adapter.redis
                default_lifetime: 86400
```

```php
// Use in repository
#[Cache(pool: 'crm.cache', ttl: 3600)]
public function findActiveContactsByAccount(Account $account): array
{
    return $this->createQueryBuilder('c')
        ->where('c.account = :account')
        ->andWhere('c.deletedAt IS NULL')
        ->setParameter('account', $account)
        ->getQuery()
        ->enableResultCache(3600, 'contacts_account_' . $account->getId())
        ->getResult();
}
```

#### 3. Database Connection Pooling
```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        server_version: '18'
        options:
            max_connections: 100
            idle_timeout: 300
```

### 12.3 AI/ML Integration Points

#### 1. Lead Scoring Service
```php
namespace App\Service\AI;

class LeadScoringService
{
    public function calculateScore(Lead $lead): int
    {
        $score = 0;

        // Demographic scoring
        if ($lead->getPosition() && str_contains(strtolower($lead->getPosition()), 'director|vp|ceo')) {
            $score += 20;
        }

        // Engagement scoring
        $activityCount = $this->activityRepository->countByLead($lead, 30); // last 30 days
        $score += min($activityCount * 5, 30);

        // Source scoring
        $sourceScores = [
            'Referral' => 25,
            'Website' => 15,
            'Event' => 20,
            'Cold Call' => 5
        ];
        $score += $sourceScores[$lead->getLeadSource()] ?? 0;

        // ML prediction (if model exists)
        if ($prediction = $this->mlService->predict('lead_conversion', $lead)) {
            $score += (int)($prediction->getProbability() * 25);
        }

        return min($score, 100);
    }
}
```

#### 2. Sentiment Analysis
```php
namespace App\Service\AI;

class SentimentAnalysisService
{
    public function analyzeEmail(Email $email): string
    {
        $text = $email->getTextBody();

        // Call external AI API (OpenAI, Azure, etc.)
        $response = $this->aiClient->analyzeSentiment($text);

        $sentimentScore = $response['score']; // -1 to 1

        if ($sentimentScore > 0.3) {
            return 'Positive';
        } elseif ($sentimentScore < -0.3) {
            return 'Negative';
        }
        return 'Neutral';
    }
}
```

#### 3. Next Best Action Recommendations
```php
namespace App\Service\AI;

class NextBestActionService
{
    public function recommend(Contact $contact): array
    {
        $recommendations = [];

        // Rule-based + ML hybrid

        // No activity in 30 days
        if ($this->getLastActivityAge($contact) > 30) {
            $recommendations[] = [
                'action' => 'Send re-engagement email',
                'priority' => 'High',
                'template_id' => $this->findBestTemplate($contact)
            ];
        }

        // High-value opportunity stalled
        if ($opportunity = $this->findStalledOpportunity($contact)) {
            $recommendations[] = [
                'action' => 'Schedule executive meeting',
                'priority' => 'Critical',
                'reason' => 'Deal stuck in ' . $opportunity->getStage() . ' for ' . $this->getDaysInStage($opportunity) . ' days'
            ];
        }

        // ML prediction
        $mlRecommendations = $this->mlService->predictNextAction($contact);

        return array_merge($recommendations, $mlRecommendations);
    }
}
```

### 12.4 Data Migration Strategy

#### 1. Import Contacts from CSV
```php
namespace App\Service\Import;

class ContactImportService
{
    public function importFromCsv(string $filePath, Organization $organization): ImportResult
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $imported = 0;
        $errors = [];

        foreach ($csv->getRecords() as $row) {
            try {
                $contact = new Contact();
                $contact->setFirstName($row['first_name']);
                $contact->setLastName($row['last_name']);
                $contact->setEmail($row['email']);
                $contact->setOrganization($organization);

                // Find or create account
                if (!empty($row['company'])) {
                    $account = $this->findOrCreateAccount($row['company'], $organization);
                    $contact->setAccount($account);
                }

                $this->entityManager->persist($contact);
                $imported++;

                if ($imported % 100 === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            } catch (\Exception $e) {
                $errors[] = "Row {$row['email']}: {$e->getMessage()}";
            }
        }

        $this->entityManager->flush();

        return new ImportResult($imported, count($errors), $errors);
    }
}
```

#### 2. Deduplication Service
```php
namespace App\Service;

class DeduplicationService
{
    public function findDuplicateContacts(Contact $contact): array
    {
        $qb = $this->contactRepository->createQueryBuilder('c');

        $qb->where('c.organization = :org')
            ->andWhere('c.id != :id')
            ->setParameter('org', $contact->getOrganization())
            ->setParameter('id', $contact->getId());

        // Fuzzy matching
        if ($contact->getEmail()) {
            $qb->orWhere('c.email = :email')
                ->setParameter('email', $contact->getEmail());
        }

        // Name similarity (Levenshtein distance)
        $qb->orWhere(
            'LEVENSHTEIN(CONCAT(c.firstName, c.lastName), :fullname) < 3'
        )->setParameter('fullname', $contact->getFirstName() . $contact->getLastName());

        return $qb->getQuery()->getResult();
    }

    public function mergeContacts(Contact $primary, Contact $duplicate): void
    {
        // Move all relationships to primary
        foreach ($duplicate->getOpportunities() as $opp) {
            $opp->setContact($primary);
        }

        foreach ($duplicate->getCases() as $case) {
            $case->setContact($primary);
        }

        foreach ($duplicate->getActivities() as $activity) {
            $activity->setRelatedTo($primary);
        }

        // Merge custom fields
        $mergedFields = array_merge(
            $duplicate->getCustomFields() ?? [],
            $primary->getCustomFields() ?? []
        );
        $primary->setCustomFields($mergedFields);

        // Soft delete duplicate
        $duplicate->setDeletedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }
}
```

### 12.5 Reporting & Analytics Implementation

#### 1. Sales Dashboard Service
```php
namespace App\Service\Analytics;

class SalesDashboardService
{
    public function getPipelineMetrics(Organization $org, User $user = null): array
    {
        $qb = $this->opportunityRepository->createQueryBuilder('o')
            ->select('o.stage')
            ->addSelect('COUNT(o.id) as count')
            ->addSelect('SUM(o.amount) as total')
            ->addSelect('SUM(o.amount * o.probability / 100) as weighted')
            ->where('o.organization = :org')
            ->andWhere('o.stage NOT IN (:closedStages)')
            ->setParameter('org', $org)
            ->setParameter('closedStages', ['Closed Won', 'Closed Lost'])
            ->groupBy('o.stage');

        if ($user) {
            $qb->andWhere('o.owner = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getWinRateAnalysis(Organization $org, \DateTimeInterface $since): array
    {
        return $this->opportunityRepository->createQueryBuilder('o')
            ->select('u.name as rep')
            ->addSelect('COUNT(CASE WHEN o.stage = :won THEN 1 END) as wins')
            ->addSelect('COUNT(CASE WHEN o.stage = :lost THEN 1 END) as losses')
            ->addSelect('AVG(CASE WHEN o.stage IN (:closed) THEN
                TIMESTAMPDIFF(DAY, o.createdAt, o.updatedAt) END) as avg_cycle_days')
            ->join('o.owner', 'u')
            ->where('o.organization = :org')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('org', $org)
            ->setParameter('since', $since)
            ->setParameter('won', 'Closed Won')
            ->setParameter('lost', 'Closed Lost')
            ->setParameter('closed', ['Closed Won', 'Closed Lost'])
            ->groupBy('u.id', 'u.name')
            ->getQuery()
            ->getArrayResult();
    }
}
```

#### 2. Marketing Attribution Report
```php
namespace App\Service\Analytics;

class MarketingAttributionService
{
    public function getCampaignROI(Campaign $campaign): array
    {
        $members = $this->campaignMemberRepository->countByCampaign($campaign);
        $opportunities = $this->opportunityRepository->findByCampaign($campaign);

        $revenue = 0;
        $pipeline = 0;

        foreach ($opportunities as $opp) {
            if ($opp->getStage() === 'Closed Won') {
                $revenue += $opp->getAmount();
            } else {
                $pipeline += $opp->getAmount();
            }
        }

        $cost = $campaign->getActualCost();
        $roi = $cost > 0 ? (($revenue - $cost) / $cost) * 100 : 0;

        return [
            'campaign_name' => $campaign->getName(),
            'members' => $members,
            'opportunities' => count($opportunities),
            'pipeline' => $pipeline,
            'revenue' => $revenue,
            'cost' => $cost,
            'roi_percentage' => round($roi, 2)
        ];
    }
}
```

### 12.6 Security & Compliance

#### 1. Data Privacy (GDPR/CCPA)
```php
namespace App\Service\Compliance;

class DataPrivacyService
{
    public function exportPersonalData(Contact $contact): array
    {
        return [
            'personal_info' => [
                'first_name' => $contact->getFirstName(),
                'last_name' => $contact->getLastName(),
                'email' => $contact->getEmail(),
                'phone' => $contact->getPhone(),
            ],
            'activities' => $this->activityRepository->findByContact($contact),
            'opportunities' => $this->opportunityRepository->findByContact($contact),
            'cases' => $this->caseRepository->findByContact($contact),
        ];
    }

    public function anonymizeContact(Contact $contact): void
    {
        $contact->setFirstName('Anonymized');
        $contact->setLastName('User');
        $contact->setEmail('deleted_' . Uuid::v7() . '@anonymized.local');
        $contact->setPhone(null);
        $contact->setMobile(null);
        $contact->setCustomFields(null);
        $contact->setDeletedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->auditLog->log('GDPR_DELETION', $contact);
    }
}
```

#### 2. Field-Level Security
```php
#[ORM\Entity]
class Contact extends EntityBase
{
    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['contact:read:admin'])] // Only admins can see
    private ?string $ssn = null;

    #[ORM\Column(type: 'string', nullable: true)]
    #[FieldSecurity(roles: ['ROLE_SALES_MANAGER'])]
    private ?string $internalNotes = null;
}
```

### 12.7 Testing Strategy

#### 1. Unit Tests
```php
namespace App\Tests\Service;

class LeadScoringServiceTest extends TestCase
{
    public function testCalculateScoreForHighValueLead(): void
    {
        $lead = new Lead();
        $lead->setPosition('VP of Engineering');
        $lead->setLeadSource('Referral');

        $service = new LeadScoringService($this->activityRepo, $this->mlService);
        $score = $service->calculateScore($lead);

        $this->assertGreaterThanOrEqual(45, $score); // 20 (position) + 25 (referral)
        $this->assertLessThanOrEqual(100, $score);
    }
}
```

#### 2. Integration Tests
```php
namespace App\Tests\Controller;

class OpportunityControllerTest extends WebTestCase
{
    public function testCreateOpportunityWithValidData(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/opportunities', [
            'json' => [
                'name' => 'Big Deal Corp',
                'amount' => 150000,
                'stage' => 'Qualification',
                'closeDate' => '2025-12-31',
                'accountId' => $this->account->getId(),
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['name' => 'Big Deal Corp']);
    }
}
```

### 12.8 Deployment Checklist

**Pre-Deployment**:
- [ ] All migrations tested in staging
- [ ] Indexes created on large tables
- [ ] Cache warmed
- [ ] Backup database
- [ ] Security scan completed
- [ ] Performance tests passed

**Deployment**:
```bash
# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Create indexes concurrently (no table locks)
php bin/console app:create-indexes

# Clear and warm cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Restart workers
supervisorctl restart messenger-worker:*
```

**Post-Deployment**:
- [ ] Health check passed
- [ ] Monitoring dashboards reviewed
- [ ] Sample queries tested
- [ ] User acceptance testing
- [ ] Documentation updated

---

## 13. Summary & Next Steps

### 13.1 Key Findings

1. **Essential Missing Entities**: 30+ core CRM entities not yet implemented in Luminai
2. **Performance Critical**: Proper indexing strategy will determine scalability
3. **AI Integration**: Modern CRMs require ML for lead scoring, sentiment analysis, and predictions
4. **Customer Journey**: Event sourcing pattern recommended for comprehensive tracking
5. **Data Quality**: Deduplication and data enrichment services are essential

### 13.2 Immediate Priorities (Next 2 Weeks)

**Week 1**:
1. Create Contact, Account, Lead entities
2. Implement base Activity entity
3. Add critical indexes
4. Build import services

**Week 2**:
5. Implement Opportunity entity
6. Add Product and Quote entities
7. Create basic reporting views
8. Set up email integration foundation

### 13.3 Success Metrics

**Performance**:
- Query response time < 100ms for list views
- Search results < 200ms
- Dashboard load < 500ms
- Support 10,000+ contacts per organization

**Functionality**:
- Complete customer 360 view
- Full sales pipeline management
- Marketing campaign tracking
- Support ticket system
- Real-time activity timeline

**AI Capabilities**:
- Automated lead scoring (accuracy > 70%)
- Sentiment analysis on communications
- Churn prediction (precision > 60%)
- Next best action recommendations

### 13.4 Long-Term Vision

Transform Luminai into a comprehensive, AI-powered CRM platform with:
- Unified customer data platform (CDP)
- Predictive analytics and forecasting
- Omnichannel communication
- Advanced workflow automation
- Real-time collaboration
- Mobile-first experience
- API-first architecture for integrations

---

## Appendix A: Entity Relationship Diagram (ERD)

```
┌─────────────┐
│Organization │
└──────┬──────┘
       │ 1:N
       ├─────────────────┬─────────────────┬──────────────────┬─────────────────┐
       │                 │                 │                  │                 │
       ▼                 ▼                 ▼                  ▼                 ▼
  ┌────────┐        ┌─────────┐      ┌─────────┐        ┌──────────┐     ┌──────────┐
  │ User   │        │ Account │      │ Contact │        │   Lead   │     │ Campaign │
  └────┬───┘        └────┬────┘      └────┬────┘        └────┬─────┘     └────┬─────┘
       │                 │                │                  │                │
       │ Owns            │ 1:N            │ 1:N              │ Converts       │ 1:N
       │                 ▼                ▼                  ▼                ▼
       │            ┌────────────┐   ┌──────────┐      ┌─────────┐    ┌──────────────┐
       │            │Opportunity │   │   Case   │      │ Contact │    │CampaignMember│
       │            └─────┬──────┘   └────┬─────┘      └─────────┘    └──────────────┘
       │                  │               │
       │ 1:N              │ 1:N           │ 1:N
       ▼                  ▼               ▼
  ┌──────────┐       ┌────────┐     ┌──────────────┐
  │ Activity │       │ Quote  │     │ CaseComment  │
  └──────────┘       └───┬────┘     └──────────────┘
                         │ 1:N
                         ▼
                   ┌─────────────┐
                   │QuoteLineItem│
                   └─────────────┘
```

## Appendix B: Sample API Endpoints

```
# Contacts
GET    /api/contacts
POST   /api/contacts
GET    /api/contacts/{id}
PUT    /api/contacts/{id}
DELETE /api/contacts/{id}
GET    /api/contacts/{id}/activities
GET    /api/contacts/{id}/opportunities
POST   /api/contacts/{id}/convert-lead

# Opportunities
GET    /api/opportunities
POST   /api/opportunities
GET    /api/opportunities/{id}
PUT    /api/opportunities/{id}
GET    /api/opportunities/pipeline
GET    /api/opportunities/forecast

# Analytics
GET    /api/analytics/pipeline
GET    /api/analytics/win-rate
GET    /api/analytics/campaign-roi
GET    /api/analytics/customer-journey/{contactId}

# AI/ML
POST   /api/ai/score-lead
POST   /api/ai/predict-churn
GET    /api/ai/next-best-action/{contactId}
POST   /api/ai/analyze-sentiment
```

## Appendix C: Performance Benchmarks

**Target Performance** (PostgreSQL 18, 100K contacts):

| Operation | Target | Actual |
|-----------|--------|--------|
| Contact list (25 items) | < 50ms | TBD |
| Contact search | < 100ms | TBD |
| Opportunity pipeline | < 100ms | TBD |
| Customer 360 view | < 200ms | TBD |
| Dashboard load | < 500ms | TBD |
| Report generation | < 2s | TBD |
| Bulk import (1000 records) | < 30s | TBD |

---

**Document Version**: 1.0
**Last Updated**: 2025-10-08
**Author**: Claude Code Analysis
**Based on**: Industry research 2024-2025 + Luminai codebase analysis
