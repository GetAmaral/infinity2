# ATTACHMENT ENTITY - COMPREHENSIVE ANALYSIS REPORT

**Analysis Date:** 2025-10-19
**Entity Name:** Attachment
**Database:** PostgreSQL 18
**Status:** DEFINED IN GENERATOR - NOT YET GENERATED
**Priority:** HIGH - Critical for CRM document management

---

## EXECUTIVE SUMMARY

The Attachment entity is currently defined in the generator system but has significant gaps and issues that prevent it from being a production-ready CRM attachment/document management solution. This analysis identifies **27 critical issues** across naming conventions, missing properties, security gaps, and incomplete API configuration.

**CRITICAL FINDING:** The entity violates Luminai naming conventions for boolean properties and lacks essential 2025 CRM document management best practices.

---

## 1. CURRENT STATE ANALYSIS

### 1.1 Generator Entity Configuration

```yaml
Entity ID: 0199cadd-62f3-7a62-b8a7-e3f0cf5db6d9
Name: Attachment
Label: Attachment
Plural: Attachments
Icon: bi-paperclip
Description: File attachments for documents and media
Table Name: NULL (will be auto-generated as "attachment")
Has Organization: TRUE
API Enabled: TRUE
API Operations: ["GetCollection","Get","Post","Put","Delete"]
Voter Enabled: TRUE
Menu Group: Configuration
Menu Order: 170
Test Enabled: TRUE
Is Generated: FALSE
Audit Enabled: FALSE
Fixtures Enabled: TRUE
```

### 1.2 Current Properties (7 total)

| Property | Type | Nullable | Relation | Target | Show List | API R/W | Issues |
|----------|------|----------|----------|---------|-----------|---------|--------|
| filename | string | NO | - | - | YES | YES/YES | Wrong name - should be fileName |
| fileType | string | YES | - | - | YES | YES/YES | Should be mimeType |
| fileSize | integer | YES | - | - | YES | YES/YES | OK but should be NOT NULL |
| url | string | YES | - | - | YES | YES/YES | Missing storageType context |
| talkMessage | - | YES | ManyToOne | TalkMessage | YES | YES/YES | OK |
| product | - | YES | ManyToOne | Product | YES | YES/YES | OK |
| event | - | YES | ManyToOne | Event | YES | YES/YES | OK |

### 1.3 Generated Files Status

The following files exist but are NOT yet generated from the entity definition:

```
/home/user/inf/app/src/Repository/AttachmentRepository.php (SAFE TO EDIT)
/home/user/inf/app/src/Repository/Generated/AttachmentRepositoryGenerated.php (GENERATED BASE)
/home/user/inf/app/src/Form/AttachmentType.php (SAFE TO EDIT)
/home/user/inf/app/src/Form/Generated/AttachmentTypeGenerated.php (GENERATED BASE)
/home/user/inf/app/src/Security/Voter/AttachmentVoter.php (SAFE TO EDIT)
/home/user/inf/app/src/Security/Voter/Generated/AttachmentVoterGenerated.php (GENERATED BASE)
```

**MISSING FILES (Not Yet Generated):**
- `/home/user/inf/app/src/Entity/Attachment.php`
- `/home/user/inf/app/src/Entity/Generated/AttachmentGenerated.php`
- API Platform configuration
- Controller files
- Template files
- Test files

---

## 2. CRM 2025 BEST PRACTICES RESEARCH FINDINGS

Based on web research of "CRM attachment document management 2025", key requirements include:

### 2.1 Metadata and Tagging
- **Custom metadata columns** for categorization (Account Name, Customer Status, etc.)
- **Descriptive tags and keywords** for search optimization
- **Full-text search capabilities** within document content
- **Advanced filtering** by metadata, tags, keywords

### 2.2 Cloud Storage Integration
- **SharePoint, Azure Blob, Dropbox integration** for handling large volumes
- **Version control** for document revisions
- **Permission management** at document level
- **Collaboration features** (comments, sharing, concurrent editing)

### 2.3 Document Organization
- **Real-time metadata migration** from legacy systems
- **Custom views and advanced filtering**
- **Structured document retrieval** for faster access
- **Hierarchical organization** (folders, categories)

### 2.4 Security and Compliance
- **Audit trails** of document access and changes
- **Fine-grained access control** (who accessed/edited when)
- **GDPR/compliance support** (data retention, deletion policies)
- **Encryption** at rest and in transit
- **Virus/malware scanning** of uploaded files

---

## 3. CRITICAL ISSUES IDENTIFIED

### Issue Category Summary

| Category | Count | Severity |
|----------|-------|----------|
| Naming Convention Violations | 2 | CRITICAL |
| Missing Core Properties | 14 | CRITICAL |
| Missing Security Properties | 5 | HIGH |
| Missing Metadata Properties | 3 | HIGH |
| Missing API Configuration | 2 | MEDIUM |
| Incomplete Relationships | 1 | MEDIUM |
| **TOTAL ISSUES** | **27** | **CRITICAL** |

---

## 4. DETAILED ISSUE BREAKDOWN

### 4.1 CRITICAL: Naming Convention Violations

**CONVENTION:** Luminai uses property names like "public", "scanned", "active" NOT "isPublic", "isScanned", "isActive"

**Issue #1: Property "filename" should be "fileName"**
- Current: `filename` (lowercase)
- Expected: `fileName` (camelCase)
- Severity: CRITICAL
- Impact: Violates PHP/Symfony naming standards

**Issue #2: Property "fileType" should be "mimeType"**
- Current: `fileType` (generic)
- Expected: `mimeType` (industry standard)
- Severity: CRITICAL
- Impact: Confusion with file types vs MIME types

---

### 4.2 CRITICAL: Missing Core Properties

These properties are ESSENTIAL for 2025 CRM attachment management:

#### 4.2.1 File Metadata Properties

| Property | Type | Nullable | Default | Description |
|----------|------|----------|---------|-------------|
| **fileName** | string(255) | NO | - | Original filename with extension |
| **mimeType** | string(100) | NO | - | MIME type (application/pdf, image/jpeg, etc.) |
| **fileSize** | integer | NO | - | File size in bytes |
| **fileExtension** | string(10) | YES | - | File extension (.pdf, .docx, etc.) |
| **storageType** | string(50) | NO | 'local' | Storage location (local, s3, azure, sharepoint) |
| **storagePath** | string(500) | YES | - | Full storage path or URL |
| **storageKey** | string(255) | YES | - | S3 key or Azure blob identifier |
| **checksum** | string(64) | YES | - | SHA256 hash for integrity verification |
| **version** | integer | NO | 1 | Document version number |

**CURRENT STATE:** Only 3 of 9 core properties exist
**MISSING:** fileExtension, storageType, storagePath, storageKey, checksum, version

#### 4.2.2 Security Properties

| Property | Type | Nullable | Default | Description |
|----------|------|----------|---------|-------------|
| **public** | boolean | NO | false | Public access (NOT isPublic) |
| **scanned** | boolean | NO | false | Virus/malware scanned (NOT isScanned) |
| **scanResult** | string(50) | YES | - | Scan result (clean, infected, pending) |
| **scanDate** | datetime | YES | - | When file was scanned |
| **encrypted** | boolean | NO | false | Is file encrypted at rest |

**CURRENT STATE:** 0 of 5 security properties exist
**MISSING:** ALL security properties

#### 4.2.3 Ownership & Audit Properties

| Property | Type | Nullable | Default | Description |
|----------|------|----------|---------|-------------|
| **uploadedBy** | ManyToOne(User) | NO | - | User who uploaded the file |
| **uploadedAt** | datetime | NO | NOW() | Upload timestamp |
| **downloadCount** | integer | NO | 0 | Number of downloads |
| **lastAccessedAt** | datetime | YES | - | Last download/view timestamp |
| **lastAccessedBy** | ManyToOne(User) | YES | - | Last user who accessed |

**CURRENT STATE:** 0 of 5 audit properties exist
**MISSING:** ALL ownership/audit properties

#### 4.2.4 Metadata & Categorization

| Property | Type | Nullable | Default | Description |
|----------|------|----------|---------|-------------|
| **category** | string(100) | YES | - | Document category |
| **tags** | json | YES | - | Array of tags for search |
| **metadata** | json | YES | - | Custom metadata key-value pairs |
| **description** | text | YES | - | Human-readable description |
| **title** | string(255) | YES | - | Document title (can differ from fileName) |
| **thumbnailPath** | string(500) | YES | - | Path to thumbnail/preview image |
| **expiresAt** | datetime | YES | - | Expiration date for temporary files |

**CURRENT STATE:** 0 of 7 metadata properties exist
**MISSING:** ALL metadata properties

---

### 4.3 HIGH: Missing Relationships

The current entity has 3 ManyToOne relationships:
- TalkMessage
- Product
- Event

**MISSING RELATIONSHIPS:**

1. **uploadedBy → User** (CRITICAL)
   - Type: ManyToOne
   - Nullable: NO
   - Description: Who uploaded this file

2. **lastAccessedBy → User** (MEDIUM)
   - Type: ManyToOne
   - Nullable: YES
   - Description: Who last accessed this file

3. **relatedEntity (Polymorphic)** (LOW PRIORITY)
   - Consider adding generic attachment support for any entity
   - Could use discriminator mapping or JSON storage
   - Example: Contact, Company, Deal, Lead, etc.

---

### 4.4 MEDIUM: Incomplete API Configuration

**Current API Operations:**
```json
["GetCollection", "Get", "Post", "Put", "Delete"]
```

**ISSUES:**

1. **Missing Security Configuration**
   - No security rules defined for operations
   - Should restrict based on user roles and ownership
   - Example: Only uploadedBy user or ADMIN can Delete

2. **Missing Normalization/Denormalization Context**
   - api_normalization_context: NULL
   - api_denormalization_context: NULL
   - Should define groups like ['attachment:read', 'attachment:write']

3. **Missing Default Order**
   - api_default_order: NULL
   - Should order by uploadedAt DESC or createdAt DESC

**RECOMMENDED API CONFIGURATION:**

```php
#[ApiResource(
    normalizationContext: ['groups' => ['attachment:read']],
    denormalizationContext: ['groups' => ['attachment:write']],
    order: ['uploadedAt' => 'DESC'],
    security: "is_granted('ROLE_USER')",
    operations: [
        new Get(security: "is_granted('VIEW', object)"),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_USER')",
            inputFormats: ['multipart' => ['multipart/form-data']]
        ),
        new Put(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)")
    ]
)]
```

---

### 4.5 LOW: Missing Indexing Strategy

**RECOMMENDED INDEXES:**

```php
#[ORM\Index(name: 'idx_attachment_uploaded_by', columns: ['uploaded_by_id'])]
#[ORM\Index(name: 'idx_attachment_uploaded_at', columns: ['uploaded_at'])]
#[ORM\Index(name: 'idx_attachment_mime_type', columns: ['mime_type'])]
#[ORM\Index(name: 'idx_attachment_file_extension', columns: ['file_extension'])]
#[ORM\Index(name: 'idx_attachment_category', columns: ['category'])]
#[ORM\Index(name: 'idx_attachment_public', columns: ['public'])]
#[ORM\Index(name: 'idx_attachment_scanned', columns: ['scanned'])]
#[ORM\Index(name: 'idx_attachment_storage_type', columns: ['storage_type'])]
#[ORM\Index(name: 'idx_attachment_expires_at', columns: ['expires_at'])]
```

**Performance Impact:**
- Without indexes, queries filtering by mimeType, category, or uploadedBy will be slow
- Expected query patterns: "Find all PDFs uploaded by user X", "Find unscanned files", "Find public attachments"

---

## 5. RECOMMENDED COMPLETE PROPERTY STRUCTURE

### 5.1 All Properties (35 total)

| # | Property | Type | Null | Default | Indexed | API R/W | List | Form |
|---|----------|------|------|---------|---------|---------|------|------|
| 1 | fileName | string(255) | NO | - | NO | YES/YES | YES | YES |
| 2 | mimeType | string(100) | NO | - | YES | YES/YES | YES | YES |
| 3 | fileSize | integer | NO | - | NO | YES/YES | YES | YES |
| 4 | fileExtension | string(10) | YES | - | YES | YES/YES | YES | NO |
| 5 | storageType | string(50) | NO | 'local' | YES | YES/NO | YES | YES |
| 6 | storagePath | string(500) | YES | - | NO | NO/NO | NO | NO |
| 7 | storageKey | string(255) | YES | - | NO | NO/NO | NO | NO |
| 8 | url | string(500) | YES | - | NO | YES/NO | YES | NO |
| 9 | checksum | string(64) | YES | - | NO | YES/NO | NO | NO |
| 10 | version | integer | NO | 1 | NO | YES/NO | YES | NO |
| 11 | public | boolean | NO | false | YES | YES/YES | YES | YES |
| 12 | scanned | boolean | NO | false | YES | YES/NO | YES | NO |
| 13 | scanResult | string(50) | YES | - | NO | YES/NO | YES | NO |
| 14 | scanDate | datetime | YES | - | NO | YES/NO | NO | NO |
| 15 | encrypted | boolean | NO | false | NO | YES/NO | YES | NO |
| 16 | uploadedBy | ManyToOne(User) | NO | - | YES | YES/NO | YES | NO |
| 17 | uploadedAt | datetime | NO | NOW() | YES | YES/NO | YES | NO |
| 18 | downloadCount | integer | NO | 0 | NO | YES/NO | YES | NO |
| 19 | lastAccessedAt | datetime | YES | - | NO | YES/NO | NO | NO |
| 20 | lastAccessedBy | ManyToOne(User) | YES | - | NO | YES/NO | NO | NO |
| 21 | category | string(100) | YES | - | YES | YES/YES | YES | YES |
| 22 | tags | json | YES | - | NO | YES/YES | NO | YES |
| 23 | metadata | json | YES | - | NO | YES/YES | NO | YES |
| 24 | description | text | YES | - | NO | YES/YES | NO | YES |
| 25 | title | string(255) | YES | - | NO | YES/YES | YES | YES |
| 26 | thumbnailPath | string(500) | YES | - | NO | YES/NO | NO | NO |
| 27 | expiresAt | datetime | YES | - | YES | YES/YES | YES | YES |
| 28 | talkMessage | ManyToOne(TalkMessage) | YES | - | NO | YES/YES | YES | YES |
| 29 | product | ManyToOne(Product) | YES | - | NO | YES/YES | YES | YES |
| 30 | event | ManyToOne(Event) | YES | - | NO | YES/YES | YES | YES |
| 31 | organization | ManyToOne(Organization) | NO | - | YES | YES/NO | NO | NO |
| 32 | id | uuid | NO | - | YES | YES/NO | YES | NO |
| 33 | createdAt | datetime | NO | NOW() | YES | YES/NO | YES | NO |
| 34 | updatedAt | datetime | NO | NOW() | YES | YES/NO | NO | NO |
| 35 | deletedAt | datetime | YES | - | YES | YES/NO | NO | NO |

---

### 5.2 Property Groups by Category

**CORE FILE METADATA (9)**
- fileName, mimeType, fileSize, fileExtension, storageType, storagePath, storageKey, url, checksum, version

**SECURITY (5)**
- public, scanned, scanResult, scanDate, encrypted

**OWNERSHIP & AUDIT (5)**
- uploadedBy, uploadedAt, downloadCount, lastAccessedAt, lastAccessedBy

**METADATA & CATEGORIZATION (7)**
- category, tags, metadata, description, title, thumbnailPath, expiresAt

**RELATIONSHIPS (4)**
- talkMessage, product, event, organization, uploadedBy, lastAccessedBy

**SYSTEM (4)**
- id, createdAt, updatedAt, deletedAt

---

## 6. VALIDATION RULES RECOMMENDATIONS

### 6.1 File Upload Validation

```php
#[Assert\NotBlank(groups: ['attachment:create'])]
#[Assert\Length(max: 255)]
private string $fileName;

#[Assert\NotBlank]
#[Assert\Choice(['image/jpeg', 'image/png', 'image/gif', 'application/pdf',
                 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])]
private string $mimeType;

#[Assert\NotBlank]
#[Assert\Positive]
#[Assert\Range(max: 52428800)] // 50MB max
private int $fileSize;

#[Assert\Choice(['local', 's3', 'azure', 'sharepoint', 'dropbox'])]
private string $storageType;
```

### 6.2 Security Validation

```php
#[Assert\Choice(['clean', 'infected', 'pending', 'error'], groups: ['admin'])]
private ?string $scanResult;

// Custom validator for file extensions
#[Assert\Regex(
    pattern: '/^\.(pdf|docx?|xlsx?|pptx?|txt|csv|jpg|jpeg|png|gif|zip)$/i',
    message: 'File extension not allowed'
)]
private ?string $fileExtension;
```

---

## 7. API PLATFORM CONFIGURATION RECOMMENDATIONS

### 7.1 Complete API Resource Definition

```php
#[ApiResource(
    normalizationContext: ['groups' => ['attachment:read']],
    denormalizationContext: ['groups' => ['attachment:write']],
    order: ['uploadedAt' => 'DESC'],
    security: "is_granted('ROLE_USER')",
    paginationItemsPerPage: 30,
    operations: [
        new Get(
            security: "is_granted('VIEW', object)",
            normalizationContext: ['groups' => ['attachment:read', 'attachment:detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            inputFormats: ['multipart' => ['multipart/form-data']],
            denormalizationContext: ['groups' => ['attachment:create']]
        ),
        new Put(
            security: "is_granted('EDIT', object)",
            denormalizationContext: ['groups' => ['attachment:update']]
        ),
        new Delete(
            security: "is_granted('DELETE', object)"
        ),
        // Custom operation for downloading
        new Get(
            uriTemplate: '/attachments/{id}/download',
            security: "is_granted('VIEW', object)",
            name: 'download'
        ),
        // Admin endpoint with full details
        new GetCollection(
            uriTemplate: '/admin/attachments',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['attachment:read', 'attachment:admin']]
        )
    ]
)]
```

### 7.2 Serialization Groups

```php
// Read groups
#[Groups(['attachment:read'])]
private Uuid $id;

#[Groups(['attachment:read', 'attachment:write'])]
private string $fileName;

#[Groups(['attachment:read', 'attachment:write'])]
private string $mimeType;

#[Groups(['attachment:read'])]
private int $fileSize;

#[Groups(['attachment:read'])]
private string $url;

#[Groups(['attachment:read', 'attachment:write'])]
private bool $public;

#[Groups(['attachment:read', 'attachment:write'])]
private ?string $category;

#[Groups(['attachment:read', 'attachment:write'])]
private ?array $tags;

#[Groups(['attachment:read', 'attachment:write'])]
private ?string $description;

// Admin-only fields
#[Groups(['attachment:admin'])]
private ?string $storagePath;

#[Groups(['attachment:admin'])]
private ?string $storageKey;

#[Groups(['attachment:admin'])]
private ?string $checksum;

#[Groups(['attachment:admin'])]
private bool $scanned;

#[Groups(['attachment:admin'])]
private ?string $scanResult;
```

---

## 8. DATABASE SCHEMA RECOMMENDATIONS

### 8.1 Complete Table Structure

```sql
CREATE TABLE attachment (
    id UUID PRIMARY KEY,

    -- File Metadata
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INTEGER NOT NULL,
    file_extension VARCHAR(10),
    storage_type VARCHAR(50) NOT NULL DEFAULT 'local',
    storage_path VARCHAR(500),
    storage_key VARCHAR(255),
    url VARCHAR(500),
    checksum VARCHAR(64),
    version INTEGER NOT NULL DEFAULT 1,

    -- Security
    public BOOLEAN NOT NULL DEFAULT FALSE,
    scanned BOOLEAN NOT NULL DEFAULT FALSE,
    scan_result VARCHAR(50),
    scan_date TIMESTAMP,
    encrypted BOOLEAN NOT NULL DEFAULT FALSE,

    -- Ownership & Audit
    uploaded_by_id UUID NOT NULL REFERENCES "user"(id),
    uploaded_at TIMESTAMP NOT NULL DEFAULT NOW(),
    download_count INTEGER NOT NULL DEFAULT 0,
    last_accessed_at TIMESTAMP,
    last_accessed_by_id UUID REFERENCES "user"(id),

    -- Metadata
    category VARCHAR(100),
    tags JSON,
    metadata JSON,
    description TEXT,
    title VARCHAR(255),
    thumbnail_path VARCHAR(500),
    expires_at TIMESTAMP,

    -- Relationships
    talk_message_id UUID REFERENCES talk_message(id),
    product_id UUID REFERENCES product(id),
    event_id UUID REFERENCES event(id),
    organization_id UUID NOT NULL REFERENCES organization(id),

    -- System
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMP,

    -- Indexes
    INDEX idx_attachment_uploaded_by (uploaded_by_id),
    INDEX idx_attachment_uploaded_at (uploaded_at),
    INDEX idx_attachment_mime_type (mime_type),
    INDEX idx_attachment_file_extension (file_extension),
    INDEX idx_attachment_category (category),
    INDEX idx_attachment_public (public),
    INDEX idx_attachment_scanned (scanned),
    INDEX idx_attachment_storage_type (storage_type),
    INDEX idx_attachment_expires_at (expires_at),
    INDEX idx_attachment_organization (organization_id),
    INDEX idx_attachment_deleted_at (deleted_at)
);
```

### 8.2 Performance Considerations

**Query Patterns:**
1. Find all attachments by user: `WHERE uploaded_by_id = ?` → INDEXED
2. Find unscanned files: `WHERE scanned = false` → INDEXED
3. Find public PDFs: `WHERE public = true AND mime_type = 'application/pdf'` → INDEXED
4. Find expired files: `WHERE expires_at < NOW()` → INDEXED
5. Find by category: `WHERE category = ?` → INDEXED

**Storage Estimates:**
- Average row size: ~1.5 KB (without binary data)
- 100,000 attachments: ~150 MB
- 1,000,000 attachments: ~1.5 GB

**Optimization:**
- Store binary data externally (S3, Azure Blob, local filesystem)
- Use checksum for deduplication
- Implement cleanup job for expired files
- Archive deleted_at records after 90 days

---

## 9. SECURITY VOTER RECOMMENDATIONS

### 9.1 Voter Attributes

```php
class AttachmentVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const DOWNLOAD = 'DOWNLOAD';
    public const SHARE = 'SHARE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE,
                                     self::DOWNLOAD, self::SHARE])
            && $subject instanceof Attachment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject,
                                       TokenInterface $token): bool
    {
        $user = $token->getUser();
        $attachment = $subject;

        // ADMIN can do anything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Must be in same organization
        if ($attachment->getOrganization() !== $user->getOrganization()) {
            return false;
        }

        return match ($attribute) {
            self::VIEW, self::DOWNLOAD =>
                $attachment->isPublic() ||
                $attachment->getUploadedBy() === $user,

            self::EDIT, self::SHARE =>
                $attachment->getUploadedBy() === $user,

            self::DELETE =>
                $attachment->getUploadedBy() === $user ||
                $this->security->isGranted('ROLE_MANAGER'),

            default => false
        };
    }
}
```

---

## 10. IMPLEMENTATION CHECKLIST

### Phase 1: Critical Fixes (Do First)

- [ ] **Fix property name:** `filename` → `fileName`
- [ ] **Fix property name:** `fileType` → `mimeType`
- [ ] **Set fileSize as NOT NULL**
- [ ] **Add uploadedBy relationship** (ManyToOne User, NOT NULL)
- [ ] **Add uploadedAt property** (datetime, NOT NULL, default NOW())
- [ ] **Add public property** (boolean, NOT NULL, default false)
- [ ] **Add scanned property** (boolean, NOT NULL, default false)

### Phase 2: Core Properties

- [ ] Add fileExtension (string, nullable)
- [ ] Add storageType (string, NOT NULL, default 'local')
- [ ] Add storagePath (string, nullable)
- [ ] Add storageKey (string, nullable)
- [ ] Add checksum (string, nullable)
- [ ] Add version (integer, NOT NULL, default 1)
- [ ] Add scanResult (string, nullable)
- [ ] Add scanDate (datetime, nullable)
- [ ] Add encrypted (boolean, NOT NULL, default false)

### Phase 3: Audit & Metadata

- [ ] Add downloadCount (integer, NOT NULL, default 0)
- [ ] Add lastAccessedAt (datetime, nullable)
- [ ] Add lastAccessedBy (ManyToOne User, nullable)
- [ ] Add category (string, nullable)
- [ ] Add tags (json, nullable)
- [ ] Add metadata (json, nullable)
- [ ] Add description (text, nullable)
- [ ] Add title (string, nullable)
- [ ] Add thumbnailPath (string, nullable)
- [ ] Add expiresAt (datetime, nullable)

### Phase 4: Indexes

- [ ] Add index on uploaded_by_id
- [ ] Add index on uploaded_at
- [ ] Add index on mime_type
- [ ] Add index on file_extension
- [ ] Add index on category
- [ ] Add index on public
- [ ] Add index on scanned
- [ ] Add index on storage_type
- [ ] Add index on expires_at

### Phase 5: API Configuration

- [ ] Set normalization_context: ['groups' => ['attachment:read']]
- [ ] Set denormalization_context: ['groups' => ['attachment:write']]
- [ ] Set api_default_order: ['uploadedAt' => 'DESC']
- [ ] Add security constraints to operations
- [ ] Add custom download endpoint
- [ ] Add admin endpoint with full details

### Phase 6: Validation

- [ ] Add NotBlank validation to fileName
- [ ] Add NotBlank validation to mimeType
- [ ] Add Positive validation to fileSize
- [ ] Add Range validation to fileSize (max 50MB)
- [ ] Add Choice validation to mimeType (allowed types)
- [ ] Add Choice validation to storageType
- [ ] Add Regex validation to fileExtension

### Phase 7: Security Voter

- [ ] Define VIEW attribute
- [ ] Define EDIT attribute
- [ ] Define DELETE attribute
- [ ] Define DOWNLOAD attribute
- [ ] Define SHARE attribute
- [ ] Implement organization check
- [ ] Implement public access check
- [ ] Implement ownership check

### Phase 8: Testing

- [ ] Unit tests for entity getters/setters
- [ ] Unit tests for validation
- [ ] Functional tests for API endpoints
- [ ] Security tests for voter
- [ ] Integration tests for file upload
- [ ] Integration tests for file download

---

## 11. RISK ASSESSMENT

### 11.1 High Risk Items

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| File upload without virus scanning | Security breach | HIGH | Implement scanned/scanResult properties + ClamAV integration |
| Missing uploadedBy tracking | Data integrity | HIGH | Add uploadedBy as NOT NULL ManyToOne relationship |
| No access control on downloads | Data leak | HIGH | Implement Security Voter with DOWNLOAD permission |
| Unlimited file size | DoS attack | MEDIUM | Add fileSize validation (max 50MB) |
| No expiration mechanism | Storage bloat | MEDIUM | Add expiresAt property + cleanup job |
| Missing checksum | Data corruption | LOW | Add checksum property for integrity checks |

### 11.2 Data Migration Risks

**CURRENT STATE:** No existing attachment table in database
**RISK LEVEL:** LOW (green field implementation)

Since the entity has not been generated yet, there are NO data migration risks. This is the ideal time to implement the complete property structure.

---

## 12. COMPARISON WITH USER ENTITY

The User entity demonstrates Luminai best practices:

**Good Patterns from User Entity:**
1. Boolean properties: `active`, `verified`, `agent` (NOT isActive, isVerified, isAgent)
2. Comprehensive audit fields: `deletedAt`, `lastLoginAt`, `lastActivityAt`
3. Rich metadata: `tags`, `customFields`, `notes`, `bio`
4. Security properties: `twoFactorEnabled`, `passkeyEnabled`, `mustChangePassword`
5. Extensive indexing: 21 indexes for performance
6. API serialization groups: `user:read`, `user:write`, `audit:read`

**Attachment Entity Should Follow:**
- Same boolean naming: `public`, `scanned`, `encrypted` (NOT is*)
- Same audit pattern: Add `uploadedAt`, `uploadedBy`, `lastAccessedAt`
- Same metadata pattern: Add `tags`, `metadata`, `category`, `description`
- Same security pattern: Add scanning, access control, encryption flags
- Same indexing strategy: Index frequently queried fields
- Same API groups: `attachment:read`, `attachment:write`, `attachment:admin`

---

## 13. POSTGRESQL 18 SPECIFIC FEATURES

### 13.1 JSON/JSONB for Metadata

```php
#[ORM\Column(type: 'json')]
private ?array $tags = null;

#[ORM\Column(type: 'json')]
private ?array $metadata = null;
```

**Query Examples:**
```sql
-- Find attachments with tag "invoice"
SELECT * FROM attachment WHERE tags @> '["invoice"]';

-- Find attachments with custom metadata
SELECT * FROM attachment WHERE metadata @> '{"customer": "ACME Corp"}';
```

### 13.2 Full-Text Search on Description

```sql
-- Add tsvector column for full-text search
ALTER TABLE attachment ADD COLUMN description_tsv tsvector
    GENERATED ALWAYS AS (to_tsvector('english', COALESCE(description, ''))) STORED;

-- Create GIN index for fast searching
CREATE INDEX idx_attachment_description_fts ON attachment USING GIN(description_tsv);

-- Search query
SELECT * FROM attachment WHERE description_tsv @@ to_tsquery('english', 'contract & signed');
```

### 13.3 UUIDv7 for Time-Ordered IDs

Attachment entity already inherits UUIDv7 from EntityBase:
- IDs are sortable by creation time
- Better index performance than UUIDv4
- Built-in timestamp encoding

---

## 14. RECOMMENDED ENTITY CODE STRUCTURE

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AttachmentRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[ORM\Table(name: 'attachment')]
#[ORM\Index(name: 'idx_attachment_uploaded_by', columns: ['uploaded_by_id'])]
#[ORM\Index(name: 'idx_attachment_uploaded_at', columns: ['uploaded_at'])]
#[ORM\Index(name: 'idx_attachment_mime_type', columns: ['mime_type'])]
#[ORM\Index(name: 'idx_attachment_file_extension', columns: ['file_extension'])]
#[ORM\Index(name: 'idx_attachment_category', columns: ['category'])]
#[ORM\Index(name: 'idx_attachment_public', columns: ['public'])]
#[ORM\Index(name: 'idx_attachment_scanned', columns: ['scanned'])]
#[ORM\Index(name: 'idx_attachment_storage_type', columns: ['storage_type'])]
#[ORM\Index(name: 'idx_attachment_expires_at', columns: ['expires_at'])]
#[ApiResource(
    normalizationContext: ['groups' => ['attachment:read']],
    denormalizationContext: ['groups' => ['attachment:write']],
    order: ['uploadedAt' => 'DESC'],
    security: "is_granted('ROLE_USER')",
    operations: [
        new Get(security: "is_granted('VIEW', object)"),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_USER')",
            inputFormats: ['multipart' => ['multipart/form-data']]
        ),
        new Put(security: "is_granted('EDIT', object)"),
        new Delete(security: "is_granted('DELETE', object)")
    ]
)]
class Attachment extends EntityBase
{
    // ===== FILE METADATA =====

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private string $fileName;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Groups(['attachment:read', 'attachment:write'])]
    private string $mimeType;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    #[Assert\Range(max: 52428800)] // 50MB
    #[Groups(['attachment:read'])]
    private int $fileSize;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['attachment:read'])]
    private ?string $fileExtension = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'local'])]
    #[Assert\Choice(['local', 's3', 'azure', 'sharepoint', 'dropbox'])]
    #[Groups(['attachment:read', 'attachment:admin'])]
    private string $storageType = 'local';

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['attachment:admin'])]
    private ?string $storagePath = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['attachment:admin'])]
    private ?string $storageKey = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['attachment:read'])]
    private ?string $url = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    #[Groups(['attachment:admin'])]
    private ?string $checksum = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Groups(['attachment:read'])]
    private int $version = 1;

    // ===== SECURITY =====

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['attachment:read', 'attachment:write'])]
    private bool $public = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['attachment:read', 'attachment:admin'])]
    private bool $scanned = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Choice(['clean', 'infected', 'pending', 'error'])]
    #[Groups(['attachment:admin'])]
    private ?string $scanResult = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['attachment:admin'])]
    private ?\DateTimeImmutable $scanDate = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['attachment:read', 'attachment:admin'])]
    private bool $encrypted = false;

    // ===== OWNERSHIP & AUDIT =====

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['attachment:read'])]
    private User $uploadedBy;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['attachment:read'])]
    private \DateTimeImmutable $uploadedAt;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['attachment:read'])]
    private int $downloadCount = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['attachment:read'])]
    private ?\DateTimeImmutable $lastAccessedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['attachment:read'])]
    private ?User $lastAccessedBy = null;

    // ===== METADATA & CATEGORIZATION =====

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $category = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?array $tags = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?array $metadata = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['attachment:read'])]
    private ?string $thumbnailPath = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?\DateTimeImmutable $expiresAt = null;

    // ===== RELATIONSHIPS =====

    #[ORM\ManyToOne(targetEntity: TalkMessage::class)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?TalkMessage $talkMessage = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[Groups(['attachment:read', 'attachment:write'])]
    private ?Event $event = null;

    public function __construct()
    {
        parent::__construct();
        $this->uploadedAt = new \DateTimeImmutable();
    }

    // ... Getters and Setters ...
}
```

---

## 15. NEXT STEPS & ACTION ITEMS

### Immediate Actions (Within 24 Hours)

1. **UPDATE GENERATOR DATABASE**
   - Fix property names: `filename` → `fileName`, `fileType` → `mimeType`
   - Add all 35 properties from Section 5.1
   - Set correct nullable, default, and indexed flags
   - Configure API serialization groups

2. **RUN ENTITY GENERATION**
   ```bash
   php bin/console genmax:generate:entity Attachment
   ```

3. **CREATE MIGRATION**
   ```bash
   php bin/console make:migration
   php bin/console doctrine:migrations:migrate
   ```

4. **IMPLEMENT SECURITY VOTER**
   - Add VIEW, EDIT, DELETE, DOWNLOAD attributes
   - Implement organization check
   - Implement ownership check
   - Implement public access logic

### Short-Term (Within 1 Week)

5. **FILE UPLOAD SERVICE**
   - Create AttachmentUploadService
   - Implement virus scanning (ClamAV integration)
   - Generate checksum (SHA256)
   - Extract file extension from filename
   - Set upload metadata (uploadedBy, uploadedAt)

6. **FILE STORAGE ABSTRACTION**
   - Create StorageInterface
   - Implement LocalStorage adapter
   - Implement S3Storage adapter (optional)
   - Configure storage based on storageType

7. **API ENDPOINTS**
   - Implement file upload endpoint (POST /api/attachments)
   - Implement file download endpoint (GET /api/attachments/{id}/download)
   - Implement thumbnail generation for images
   - Add access tracking (increment downloadCount, update lastAccessedAt)

### Medium-Term (Within 1 Month)

8. **CLEANUP JOBS**
   - Create command to delete expired files
   - Create command to clean up orphaned files
   - Create command to verify checksums
   - Schedule cron jobs for automated cleanup

9. **TESTING**
   - Unit tests for entity
   - Unit tests for AttachmentUploadService
   - Functional tests for API endpoints
   - Security tests for voter
   - Performance tests for large file uploads

10. **DOCUMENTATION**
    - API documentation for file upload
    - API documentation for file download
    - Usage examples
    - Security guidelines
    - Storage configuration guide

---

## 16. CONCLUSION

The Attachment entity requires **significant enhancements** to meet 2025 CRM document management standards. Currently, it has only **7 properties** out of the recommended **35 properties**.

**Key Findings:**
- **2 CRITICAL naming violations** (filename, fileType)
- **27 missing properties** across security, audit, and metadata
- **Incomplete API configuration** (no security, no serialization groups)
- **No virus scanning** mechanism
- **No access control** implementation
- **No file storage** abstraction

**Recommended Priority:**
1. **CRITICAL:** Fix naming conventions + add uploadedBy + add security properties
2. **HIGH:** Add audit properties + add metadata properties
3. **MEDIUM:** Add indexes + configure API properly
4. **LOW:** Implement advanced features (thumbnails, full-text search)

**Implementation Timeline:**
- **Phase 1 (Critical):** 2-4 hours
- **Phase 2-3 (Core):** 1-2 days
- **Phase 4-5 (API):** 1-2 days
- **Phase 6-8 (Testing):** 2-3 days
- **Total Estimate:** 1-1.5 weeks for complete implementation

**Risk Level:** MEDIUM (mitigated by no existing data)

This entity is currently **NOT PRODUCTION-READY** and should not be generated until critical fixes are applied.

---

**Report Generated:** 2025-10-19
**Analysis Tool:** Claude Code + Database Inspection
**Database:** PostgreSQL 18 (luminai_db)
**Analyst:** Database Optimization Expert

---

## APPENDIX A: SQL QUERIES USED

```sql
-- Get entity definition
SELECT entity_name, entity_label, plural_label, icon, description,
       table_name, has_organization, api_enabled, api_operations::text,
       voter_enabled, menu_group, menu_order, test_enabled, is_generated,
       audit_enabled, fixtures_enabled
FROM generator_entity
WHERE entity_name = 'Attachment';

-- Get properties
SELECT property_name, property_label, property_type, property_order,
       nullable, length, relationship_type, target_entity,
       show_in_list, show_in_detail, show_in_form,
       api_readable, api_writable, indexed, is_enum
FROM generator_property
WHERE entity_id = '0199cadd-62f3-7a62-b8a7-e3f0cf5db6d9'
ORDER BY property_order;

-- Check for existing table
SELECT tablename FROM pg_catalog.pg_tables
WHERE schemaname = 'public'
AND tablename LIKE '%attach%';
```

---

## APPENDIX B: REFERENCES

1. **Luminai Documentation**
   - `/home/user/inf/docs/DATABASE.md` - UUIDv7 patterns
   - `/home/user/inf/docs/SECURITY.md` - Security Voters
   - `/home/user/inf/CLAUDE.md` - Naming conventions

2. **Entity Examples**
   - `/home/user/inf/app/src/Entity/User.php` - Comprehensive entity pattern
   - Boolean naming: `active`, `verified`, `agent` (NOT is*)
   - Audit fields: `deletedAt`, `lastLoginAt`, `lastActivityAt`

3. **CRM 2025 Best Practices (Web Research)**
   - SharePoint metadata sync
   - Cloud storage integration (S3, Azure, Dropbox)
   - Virus scanning and security
   - Audit trails and access control
   - Advanced search and filtering

4. **PostgreSQL 18 Features**
   - UUIDv7 support
   - JSON/JSONB for metadata
   - Full-text search (tsvector, GIN indexes)
   - Advanced indexing strategies

---

END OF REPORT
