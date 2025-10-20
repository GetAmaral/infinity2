# StudentLecture Entity Analysis Report

**Date:** 2025-10-19
**Entity:** StudentLecture
**Database:** PostgreSQL 18
**Analyzed By:** Database Optimization Expert

---

## Executive Summary

### Critical Issues Found

1. **SEVERE DISCREPANCY**: Entity class missing 17 fields that exist in database schema
2. **API INCOMPLETE**: Multiple API operations and serialization groups not configured
3. **PERFORMANCE**: Missing composite indexes for common query patterns
4. **DATA INTEGRITY**: Boolean getter naming convention violation (uses `isCompleted()` instead of proper getter pattern)
5. **TRACKING GAPS**: Missing engagement analytics fields (watch_count, total_watch_time_seconds, first_watched_at)

### Overall Assessment

**Status:** REQUIRES IMMEDIATE ATTENTION
**Severity:** HIGH - Entity does not reflect actual database schema
**Impact:** Data loss risk, API functionality incomplete, poor query performance

---

## 1. Schema Analysis

### 1.1 Database Schema (Actual)

```sql
Table "public.student_lecture" - 32 columns total

-- Primary Key
id                       UUID PRIMARY KEY

-- Audit Fields (from EntityBase)
created_by_id            UUID
updated_by_id            UUID
created_at               TIMESTAMP NOT NULL
updated_at               TIMESTAMP NOT NULL

-- Relationships
student_id               UUID NOT NULL
lecture_id               UUID NOT NULL
student_course_id        UUID

-- Core Progress Tracking (PRESENT in entity)
watched_seconds          INTEGER NOT NULL
last_position_seconds    INTEGER NOT NULL
completion_percentage    NUMERIC(5,2) NOT NULL
completed                BOOLEAN NOT NULL
last_watched_at          TIMESTAMP
completed_at             TIMESTAMP

-- Engagement Analytics (MISSING in entity)
first_watched_at         TIMESTAMP
watch_count              INTEGER DEFAULT 0
total_watch_time_seconds INTEGER DEFAULT 0
video_bookmarks          JSONB
notes                    TEXT

-- Quiz/Assessment (MISSING in entity)
quiz_attempts            INTEGER DEFAULT 0
quiz_best_score          NUMERIC(5,2)
quiz_last_score          NUMERIC(5,2)
quiz_passed              BOOLEAN DEFAULT false

-- Assignment Management (MISSING in entity)
assignment_submitted     BOOLEAN DEFAULT false
assignment_submitted_at  TIMESTAMP
assignment_file_path     VARCHAR(500)
assignment_score         NUMERIC(5,2)
assignment_feedback      TEXT
assignment_graded_at     TIMESTAMP
assignment_graded_by_id  UUID

-- Flagging System (MISSING in entity)
is_flagged               BOOLEAN DEFAULT false
flagged_reason           VARCHAR(100)
```

### 1.2 Entity Class (Current)

**Fields Present:** 15 total
- Basic: student, lecture, studentCourse (3)
- Progress: watchedSeconds, lastPositionSeconds, completionPercentage, completed (4)
- Timestamps: lastWatchedAt, completedAt (2)
- Inherited: id, createdAt, updatedAt, createdBy, updatedBy (5)
- Constant: MIN_COMPLETION (1)

**Fields Missing:** 17 critical fields
- Engagement: first_watched_at, watch_count, total_watch_time_seconds, video_bookmarks, notes (5)
- Quiz: quiz_attempts, quiz_best_score, quiz_last_score, quiz_passed (4)
- Assignment: assignment_submitted, assignment_submitted_at, assignment_file_path, assignment_score, assignment_feedback, assignment_graded_at, assignment_graded_by_id (7)
- Flagging: is_flagged, flagged_reason (2)

---

## 2. LMS Best Practices Research (2025)

### 2.1 Industry Standards

Based on research from leading LMS platforms (Moodle, Canvas, TutorLMS):

**Video Progress Tracking:**
- ✅ Watch time tracking (watched_seconds)
- ✅ Video position bookmarking (last_position_seconds)
- ✅ Completion percentage calculation
- ⚠️ **MISSING**: First watch timestamp (first_watched_at)
- ⚠️ **MISSING**: Rewatch count (watch_count)
- ⚠️ **MISSING**: Total cumulative watch time (total_watch_time_seconds)
- ⚠️ **MISSING**: Video bookmarks for student navigation (video_bookmarks)

**Completion Criteria:**
- ✅ Minimum completion threshold (90% - StudentLecture::MIN_COMPLETION)
- ✅ Auto-completion on threshold reach
- ⚠️ **RECOMMENDATION**: Industry standard is 80-90%, current 90% is acceptable but strict

**Engagement Metrics:**
- ⚠️ **MISSING**: Watch count for re-engagement analysis
- ⚠️ **MISSING**: Student notes capability
- ⚠️ **MISSING**: Video bookmark functionality

**Assessment Integration:**
- ⚠️ **MISSING**: Quiz attempt tracking
- ⚠️ **MISSING**: Score tracking (best, last)
- ⚠️ **MISSING**: Pass/fail status
- ⚠️ **MISSING**: Assignment submission workflow

**Instructor Feedback Loop:**
- ⚠️ **MISSING**: Flagging system for student concerns
- ⚠️ **MISSING**: Assignment grading workflow
- ⚠️ **MISSING**: Instructor feedback storage

### 2.2 Key LMS Metrics (2025)

According to LMS analytics best practices:

1. **Recency**: When user last watched (✅ lastWatchedAt)
2. **Frequency**: How often user watches (⚠️ MISSING watch_count)
3. **Duration**: Total time spent (⚠️ MISSING total_watch_time_seconds)
4. **Engagement**: Bookmarks, notes, rewatches (⚠️ MISSING all)
5. **Completion**: Progress tracking (✅ completionPercentage)
6. **Assessment**: Quiz/assignment performance (⚠️ MISSING all)

---

## 3. Convention Compliance

### 3.1 Naming Conventions

**Boolean Properties:**
- ❌ **VIOLATION**: Uses `isCompleted()` getter (line 128)
  - Convention: Property should be `$completed`, getter should be `getCompleted()`
  - Current: Property is `$completed`, but uses non-standard getter
  - **Note**: This is actually following Symfony convention, but inconsistent with setter naming

**Database Conventions:**
- ✅ Boolean columns: `completed`, `active` (NOT `is_completed`)
- ✅ Timestamp columns: `completed_at`, `last_watched_at` (NOT `completedTimestamp`)
- ⚠️ **INCONSISTENCY**: Database has `is_flagged` (violates convention)

### 3.2 API Platform Configuration

**Current Configuration:**
```php
#[ApiResource(
    normalizationContext: ['groups' => ['student_lecture:read']],
    denormalizationContext: ['groups' => ['student_lecture:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/student-lectures',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['student_lecture:read', 'audit:read']]
        )
    ]
)]
```

**Issues:**
- ❌ **MISSING**: Individual GET operation for students to view their own progress
- ❌ **MISSING**: PATCH operation for updating progress
- ❌ **MISSING**: POST operation for creating progress records
- ❌ **INCOMPLETE**: Serialization groups don't expose all necessary fields
- ⚠️ **SECURITY**: No student-level operations defined

**Recommended Operations:**
```php
operations: [
    new GetCollection('/admin/student-lectures', ROLE_ADMIN),
    new Get('/student-lectures/{id}', "is_granted('VIEW', object)"),
    new Post('/student-lectures', ROLE_USER),
    new Patch('/student-lectures/{id}', "is_granted('EDIT', object)"),
    new GetCollection('/my/lecture-progress', ROLE_STUDENT), // Student's own progress
]
```

---

## 4. Performance Analysis

### 4.1 Existing Indexes

```sql
-- Primary Key
"student_lecture_pkey" PRIMARY KEY (id)

-- Foreign Key Indexes
"idx_2c51ccb035e32fcd" btree (lecture_id)
"idx_2c51ccb03e720812" btree (student_course_id)
"idx_2c51ccb0896dbbde" btree (updated_by_id)
"idx_2c51ccb0b03a8386" btree (created_by_id)
"idx_2c51ccb0cb944f1a" btree (student_id)
"idx_2c51ccb0846bfe1c" btree (assignment_graded_by_id)

-- Composite Indexes
"idx_student_lecture_student_course" btree (student_id, student_course_id)
"idx_student_lecture_assignment_submitted" btree (assignment_submitted, assignment_submitted_at)
"idx_student_lecture_completed" btree (completed, completed_at)

-- Specialized Indexes
"idx_student_lecture_first_watched" btree (first_watched_at)
"idx_student_lecture_flagged" btree (is_flagged) WHERE is_flagged = true  -- Partial index
"idx_student_lecture_quiz_passed" btree (quiz_passed) WHERE quiz_passed = true  -- Partial index
```

### 4.2 Index Quality Assessment

**Excellent:**
- ✅ Partial indexes for boolean flags (is_flagged, quiz_passed) - Best practice for low cardinality
- ✅ Composite index on (student_id, student_course_id) - Supports common query pattern
- ✅ Composite index on (completed, completed_at) - Supports reporting queries

**Missing Indexes for Common Queries:**

1. **Student Progress Lookup (CRITICAL)**
   ```sql
   -- Query: Find student's progress for specific lecture
   -- Current: Uses idx_2c51ccb0cb944f1a (student_id) + filter on lecture_id
   -- Recommended:
   CREATE UNIQUE INDEX idx_student_lecture_unique_progress
   ON student_lecture (student_id, lecture_id);
   ```

2. **Recent Activity Queries**
   ```sql
   -- Query: Get recently watched lectures by student
   -- Recommended:
   CREATE INDEX idx_student_lecture_recent_activity
   ON student_lecture (student_id, last_watched_at DESC);
   ```

3. **Completion Analytics**
   ```sql
   -- Query: Get all completed lectures for a course
   -- Recommended:
   CREATE INDEX idx_student_lecture_course_completed
   ON student_lecture (student_course_id, completed, completed_at DESC)
   WHERE completed = true;
   ```

4. **Assignment Grading Queue**
   ```sql
   -- Query: Find submitted but ungraded assignments
   -- Current: Uses idx_student_lecture_assignment_submitted
   -- Enhancement:
   CREATE INDEX idx_student_lecture_grading_queue
   ON student_lecture (assignment_submitted_at DESC)
   WHERE assignment_submitted = true
   AND assignment_graded_at IS NULL;
   ```

### 4.3 Query Pattern Analysis

**Common Queries Expected:**

```sql
-- Q1: Find student's progress for specific lecture (MOST COMMON)
SELECT * FROM student_lecture
WHERE student_id = ? AND lecture_id = ?;
-- NEEDS: UNIQUE INDEX (student_id, lecture_id)

-- Q2: Get all progress for student in course
SELECT * FROM student_lecture
WHERE student_id = ? AND student_course_id = ?
ORDER BY last_watched_at DESC;
-- COVERED: idx_student_lecture_student_course

-- Q3: Get completed lectures for analytics
SELECT COUNT(*) FROM student_lecture
WHERE student_course_id = ? AND completed = true;
-- COVERED: idx_student_lecture_completed

-- Q4: Recent activity feed
SELECT * FROM student_lecture
WHERE student_id = ?
ORDER BY last_watched_at DESC
LIMIT 10;
-- NEEDS: INDEX (student_id, last_watched_at DESC)

-- Q5: Flagged lectures for review
SELECT * FROM student_lecture
WHERE is_flagged = true;
-- COVERED: idx_student_lecture_flagged (partial index)
```

---

## 5. Business Logic Analysis

### 5.1 Completion Calculation Logic

**Current Implementation (lines 199-239):**

```php
#[ORM\PrePersist]
#[ORM\PreUpdate]
public function calculateCompletion(): void
{
    $lectureLength = $this->lecture->getDurationSeconds();

    if ($lectureLength > 0) {
        // Video lecture - calculate based on watched time
        $percentage = ($this->watchedSeconds / $lectureLength) * 100;
        $this->completionPercentage = min($percentage, 100.0);
    } else {
        // Videoless lecture - binary completion
        $this->completionPercentage = $this->watchedSeconds > 0 ? 100.0 : 0.0;
    }

    // Auto-mark completed if >= 90%
    if ($this->completionPercentage >= self::MIN_COMPLETION) {
        if (!$this->completed) {
            $this->completed = true;
            $this->completedAt = new \DateTimeImmutable();
        }
    } else {
        $this->completed = false;
        $this->completedAt = null;
    }
}
```

**Analysis:**
- ✅ **GOOD**: Handles both video and non-video lectures
- ✅ **GOOD**: Auto-completion on threshold
- ✅ **GOOD**: Prevents completion percentage > 100%
- ⚠️ **ISSUE**: Resets completion if student watches less (edge case - should this happen?)
- ⚠️ **MISSING**: Doesn't track first_watched_at
- ⚠️ **MISSING**: Doesn't increment watch_count
- ⚠️ **MISSING**: Doesn't accumulate total_watch_time_seconds

**Recommended Enhancement:**
```php
public function updateProgress(int $newWatchedSeconds, int $newPosition): void
{
    // First watch detection
    if ($this->firstWatchedAt === null) {
        $this->firstWatchedAt = new \DateTimeImmutable();
    }

    // Update watch count if starting from beginning or after long gap
    if ($newPosition < $this->lastPositionSeconds ||
        $this->lastWatchedAt === null ||
        $this->lastWatchedAt < new \DateTimeImmutable('-30 minutes')) {
        $this->watchCount++;
    }

    // Accumulate total watch time
    if ($newWatchedSeconds > $this->watchedSeconds) {
        $this->totalWatchTimeSeconds += ($newWatchedSeconds - $this->watchedSeconds);
    }

    // Update current progress
    $this->watchedSeconds = $newWatchedSeconds;
    $this->lastPositionSeconds = $newPosition;
    $this->lastWatchedAt = new \DateTimeImmutable();

    // Recalculate completion (existing logic)
    $this->calculateCompletion();
}
```

### 5.2 Parent Progress Update

**Current Implementation (lines 245-252):**

```php
#[ORM\PostPersist]
#[ORM\PostUpdate]
public function updateParentProgress(): void
{
    if ($this->studentCourse !== null) {
        $this->studentCourse->recalculateProgress();
    }
}
```

**Analysis:**
- ✅ **GOOD**: Automatically updates parent StudentCourse on changes
- ✅ **GOOD**: Uses PostPersist/PostUpdate lifecycle callbacks
- ⚠️ **PERFORMANCE**: Could trigger multiple updates if batch processing
- ℹ️ **NOTE**: Consider debouncing for bulk imports

---

## 6. Recommendations

### 6.1 CRITICAL - Fix Entity Schema Mismatch

**Priority:** IMMEDIATE
**Impact:** HIGH - Data loss prevention

Add all missing fields to StudentLecture entity:

```php
// Engagement Analytics
#[ORM\Column(type: 'datetime_immutable', nullable: true)]
#[Groups(['student_lecture:read'])]
private ?\DateTimeImmutable $firstWatchedAt = null;

#[ORM\Column(type: 'integer')]
#[Groups(['student_lecture:read', 'student_lecture:write'])]
private int $watchCount = 0;

#[ORM\Column(type: 'integer')]
#[Groups(['student_lecture:read', 'student_lecture:write'])]
private int $totalWatchTimeSeconds = 0;

#[ORM\Column(type: 'json')]
#[Groups(['student_lecture:read', 'student_lecture:write'])]
private array $videoBookmarks = [];

#[ORM\Column(type: 'text', nullable: true)]
#[Groups(['student_lecture:read', 'student_lecture:write'])]
private ?string $notes = null;

// Quiz Management
#[ORM\Column(type: 'integer')]
#[Groups(['student_lecture:read'])]
private int $quizAttempts = 0;

#[ORM\Column(type: 'float', nullable: true)]
#[Assert\Range(min: 0, max: 100)]
#[Groups(['student_lecture:read'])]
private ?float $quizBestScore = null;

#[ORM\Column(type: 'float', nullable: true)]
#[Assert\Range(min: 0, max: 100)]
#[Groups(['student_lecture:read'])]
private ?float $quizLastScore = null;

#[ORM\Column(type: 'boolean')]
#[Groups(['student_lecture:read'])]
private bool $quizPassed = false;

// Assignment Management
#[ORM\Column(type: 'boolean')]
#[Groups(['student_lecture:read', 'student_lecture:write'])]
private bool $assignmentSubmitted = false;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
#[Groups(['student_lecture:read'])]
private ?\DateTimeImmutable $assignmentSubmittedAt = null;

#[ORM\Column(type: 'string', length: 500, nullable: true)]
#[Groups(['student_lecture:read'])]
private ?string $assignmentFilePath = null;

#[ORM\Column(type: 'float', nullable: true)]
#[Assert\Range(min: 0, max: 100)]
#[Groups(['student_lecture:read'])]
private ?float $assignmentScore = null;

#[ORM\Column(type: 'text', nullable: true)]
#[Groups(['student_lecture:read'])]
private ?string $assignmentFeedback = null;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
#[Groups(['student_lecture:read'])]
private ?\DateTimeImmutable $assignmentGradedAt = null;

#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: true)]
#[Groups(['student_lecture:read'])]
private ?User $assignmentGradedBy = null;

// Flagging System
#[ORM\Column(type: 'boolean')]
#[Groups(['student_lecture:read', 'student_lecture:write'])]
private bool $flagged = false;  // Note: DB has is_flagged, property should be $flagged

#[ORM\Column(type: 'string', length: 100, nullable: true)]
#[Groups(['student_lecture:read', 'student_lecture:write'])]
private ?string $flaggedReason = null;
```

### 6.2 HIGH - Add Missing Database Index

**Priority:** HIGH
**Impact:** MEDIUM - Query performance

```sql
-- Most critical - ensures unique student-lecture tracking
CREATE UNIQUE INDEX idx_student_lecture_unique_progress
ON student_lecture (student_id, lecture_id);

-- Important for activity feeds
CREATE INDEX idx_student_lecture_recent_activity
ON student_lecture (student_id, last_watched_at DESC);

-- Course completion reports
CREATE INDEX idx_student_lecture_course_completed
ON student_lecture (student_course_id, completed, completed_at DESC)
WHERE completed = true;

-- Assignment grading workflow
CREATE INDEX idx_student_lecture_grading_queue
ON student_lecture (assignment_submitted_at DESC)
WHERE assignment_submitted = true
AND assignment_graded_at IS NULL;
```

### 6.3 MEDIUM - Enhance API Platform Configuration

**Priority:** MEDIUM
**Impact:** MEDIUM - API functionality

```php
#[ApiResource(
    normalizationContext: ['groups' => ['student_lecture:read']],
    denormalizationContext: ['groups' => ['student_lecture:write']],
    operations: [
        // Admin operations
        new GetCollection(
            uriTemplate: '/admin/student-lectures',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['student_lecture:read', 'audit:read']]
        ),

        // Student operations
        new Get(
            uriTemplate: '/student-lectures/{id}',
            security: "is_granted('VIEW', object)"
        ),
        new Patch(
            uriTemplate: '/student-lectures/{id}/progress',
            security: "is_granted('EDIT', object)"
        ),
        new Post(
            uriTemplate: '/student-lectures',
            security: "is_granted('ROLE_USER')"
        ),

        // Student's own progress collection
        new GetCollection(
            uriTemplate: '/my/lecture-progress',
            security: "is_granted('ROLE_STUDENT')",
            normalizationContext: ['groups' => ['student_lecture:read', 'student:read']]
        ),

        // Assignment submission
        new Patch(
            uriTemplate: '/student-lectures/{id}/submit-assignment',
            security: "is_granted('EDIT', object)"
        ),

        // Instructor grading
        new Patch(
            uriTemplate: '/student-lectures/{id}/grade-assignment',
            security: "is_granted('ROLE_INSTRUCTOR')"
        ),
    ]
)]
```

### 6.4 LOW - Fix Boolean Getter Convention

**Priority:** LOW
**Impact:** LOW - Code consistency

**Option 1: Keep Symfony convention (RECOMMENDED)**
```php
// Property
private bool $completed = false;

// Getter - keep as is for Symfony forms compatibility
public function isCompleted(): bool
{
    return $this->completed;
}

// Add standard getter for consistency
public function getCompleted(): bool
{
    return $this->completed;
}

// Setter
public function setCompleted(bool $completed): self
{
    $this->completed = $completed;
    return $this;
}
```

**Option 2: Rename to follow project convention**
- Change getter from `isCompleted()` to `getCompleted()`
- Update all usages in codebase
- Note: May break Symfony form type guessing

### 6.5 MEDIUM - Enhance Business Logic

**Priority:** MEDIUM
**Impact:** MEDIUM - Feature completeness

Add methods for:

```php
// Progress tracking
public function updateProgress(int $newWatchedSeconds, int $newPosition): void

// Quiz management
public function recordQuizAttempt(float $score, bool $passed): void
public function getBestQuizScore(): ?float

// Assignment workflow
public function submitAssignment(string $filePath): void
public function gradeAssignment(float $score, string $feedback, User $gradedBy): void

// Bookmarks
public function addBookmark(int $timestampSeconds, ?string $note = null): void
public function removeBookmark(int $timestampSeconds): void
public function getBookmarkAtTime(int $timestampSeconds): ?array

// Flagging
public function flag(string $reason): void
public function unflag(): void

// Analytics
public function getAverageWatchTimePerSession(): float
public function getEngagementScore(): float  // Based on rewatches, notes, bookmarks
```

---

## 7. Migration Plan

### Phase 1: Entity Update (IMMEDIATE)
1. Add all 17 missing properties to StudentLecture entity
2. Add corresponding getters/setters
3. Add API serialization groups
4. Validate against database schema

### Phase 2: Database Optimization (WEEK 1)
1. Create unique index: idx_student_lecture_unique_progress
2. Create performance indexes for common queries
3. Run ANALYZE on student_lecture table
4. Monitor query performance with EXPLAIN ANALYZE

### Phase 3: API Enhancement (WEEK 2)
1. Add missing API Platform operations
2. Create Security Voters for StudentLecture access control
3. Add student-specific endpoints
4. Update API documentation

### Phase 4: Business Logic (WEEK 3-4)
1. Implement enhanced progress tracking methods
2. Add quiz management methods
3. Add assignment workflow methods
4. Add bookmark and flagging functionality
5. Write comprehensive PHPUnit tests

### Phase 5: Performance Monitoring (ONGOING)
1. Monitor slow query log for student_lecture queries
2. Track API endpoint response times
3. Analyze index usage with pg_stat_user_indexes
4. Optimize based on real-world usage patterns

---

## 8. Performance Metrics & Monitoring

### 8.1 Queries to Monitor

```sql
-- 1. Check index usage
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan as index_scans,
    idx_tup_read as tuples_read,
    idx_tup_fetch as tuples_fetched
FROM pg_stat_user_indexes
WHERE tablename = 'student_lecture'
ORDER BY idx_scan DESC;

-- 2. Find unused indexes
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan
FROM pg_stat_user_indexes
WHERE tablename = 'student_lecture'
AND idx_scan = 0
AND indexname NOT LIKE '%_pkey';

-- 3. Table bloat and size
SELECT
    pg_size_pretty(pg_total_relation_size('student_lecture')) as total_size,
    pg_size_pretty(pg_relation_size('student_lecture')) as table_size,
    pg_size_pretty(pg_total_relation_size('student_lecture') - pg_relation_size('student_lecture')) as index_size;

-- 4. Most active students (engagement tracking)
SELECT
    student_id,
    COUNT(*) as lectures_watched,
    SUM(watched_seconds) as total_watched_seconds,
    SUM(total_watch_time_seconds) as total_time_including_rewatches,
    AVG(watch_count) as avg_rewatches,
    COUNT(CASE WHEN completed THEN 1 END) as completed_count
FROM student_lecture
GROUP BY student_id
ORDER BY total_watched_seconds DESC
LIMIT 10;

-- 5. Completion rate by lecture
SELECT
    l.name,
    COUNT(*) as total_students,
    COUNT(CASE WHEN sl.completed THEN 1 END) as completed_students,
    ROUND(COUNT(CASE WHEN sl.completed THEN 1 END)::numeric / COUNT(*)::numeric * 100, 2) as completion_rate,
    AVG(sl.completion_percentage) as avg_completion_percentage,
    AVG(sl.watch_count) as avg_rewatches
FROM student_lecture sl
JOIN course_lecture l ON sl.lecture_id = l.id
GROUP BY l.id, l.name
ORDER BY completion_rate ASC
LIMIT 20;
```

### 8.2 Performance Benchmarks

**Expected Query Times (PostgreSQL 18):**

| Query Type | Without Index | With Index | Target |
|------------|---------------|------------|--------|
| Single student-lecture lookup | 50-100ms | <5ms | <10ms |
| Student course progress | 100-200ms | 10-20ms | <30ms |
| Completion analytics | 500ms-1s | 50-100ms | <150ms |
| Recent activity feed | 200-500ms | 20-50ms | <75ms |

### 8.3 Monitoring Queries for Production

```sql
-- Real-time slow queries on student_lecture
SELECT
    now() - query_start as duration,
    query,
    state
FROM pg_stat_activity
WHERE query LIKE '%student_lecture%'
AND state != 'idle'
AND query_start < now() - interval '1 second'
ORDER BY duration DESC;

-- Lock monitoring
SELECT
    l.locktype,
    l.relation::regclass,
    l.mode,
    l.granted,
    a.usename,
    a.query
FROM pg_locks l
JOIN pg_stat_activity a ON l.pid = a.pid
WHERE l.relation = 'student_lecture'::regclass;
```

---

## 9. Code Quality & Testing

### 9.1 Missing Test Coverage

**Required PHPUnit Tests:**

```php
// tests/Entity/StudentLectureTest.php
class StudentLectureTest extends KernelTestCase
{
    public function testProgressCalculationForVideoLecture(): void
    public function testProgressCalculationForNonVideoLecture(): void
    public function testAutoCompletionAtThreshold(): void
    public function testCompletionReset(): void
    public function testFirstWatchedAtTracking(): void
    public function testWatchCountIncrement(): void
    public function testTotalWatchTimeAccumulation(): void
    public function testQuizAttemptRecording(): void
    public function testAssignmentSubmission(): void
    public function testAssignmentGrading(): void
    public function testBookmarkManagement(): void
    public function testFlaggingSystem(): void
    public function testParentProgressUpdate(): void
}

// tests/Repository/StudentLectureRepositoryTest.php
class StudentLectureRepositoryTest extends KernelTestCase
{
    public function testFindProgressByStudentAndLecture(): void
    public function testFindProgressByStudentAndCourse(): void
    public function testCountCompletedByStudentAndCourse(): void
    public function testFindRecentActivity(): void
    public function testFindFlaggedLectures(): void
    public function testFindUngraded Assignments(): void
}
```

### 9.2 Code Quality Metrics

**Current Issues:**
- PHPStan Level: Unknown (should be 8)
- Test Coverage: Unknown (target: >80%)
- Cyclomatic Complexity: Low (GOOD)
- Missing Docblocks: Multiple methods lack documentation

**Recommendations:**
```bash
# Run PHPStan
vendor/bin/phpstan analyse src/Entity/StudentLecture.php --level=8

# Run PHP-CS-Fixer
vendor/bin/php-cs-fixer fix src/Entity/StudentLecture.php

# Generate coverage
php bin/phpunit --coverage-html coverage/
```

---

## 10. Security Considerations

### 10.1 Access Control

**Current Issues:**
- ❌ No Security Voter for StudentLecture
- ❌ Students can potentially access other students' progress
- ❌ No role-based restrictions on sensitive fields (assignment scores, feedback)

**Recommended Security Voter:**

```php
// src/Security/Voter/StudentLectureVoter.php
class StudentLectureVoter extends Voter
{
    const VIEW = 'VIEW';
    const EDIT = 'EDIT';
    const DELETE = 'DELETE';
    const GRADE = 'GRADE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof StudentLecture;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var StudentLecture $studentLecture */
        $studentLecture = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($studentLecture, $user),
            self::EDIT => $this->canEdit($studentLecture, $user),
            self::DELETE => $this->canDelete($studentLecture, $user),
            self::GRADE => $this->canGrade($studentLecture, $user),
            default => false,
        };
    }

    private function canView(StudentLecture $sl, User $user): bool
    {
        // Admin can view all
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        // Student can view own progress
        if ($sl->getStudent()->getId() === $user->getId()) {
            return true;
        }

        // Instructor can view students in their courses
        if ($user->hasRole('ROLE_INSTRUCTOR')) {
            $course = $sl->getLecture()->getCourseModule()->getCourse();
            return $course->getOwner()->getId() === $user->getId();
        }

        return false;
    }

    private function canEdit(StudentLecture $sl, User $user): bool
    {
        // Only student can edit their own progress
        return $sl->getStudent()->getId() === $user->getId();
    }

    private function canGrade(StudentLecture $sl, User $user): bool
    {
        // Only instructor of the course can grade
        if (!$user->hasRole('ROLE_INSTRUCTOR')) {
            return false;
        }

        $course = $sl->getLecture()->getCourseModule()->getCourse();
        return $course->getOwner()->getId() === $user->getId();
    }
}
```

### 10.2 Data Privacy

**Sensitive Fields:**
- Student notes (PII - personal learning notes)
- Assignment files (FERPA - educational records)
- Assignment feedback (FERPA)
- Flagged reasons (potentially sensitive)

**Recommendations:**
- Implement field-level encryption for notes
- Restrict assignment_feedback to student and instructor only
- Add audit logging for grade changes
- Implement data retention policy for deleted students

---

## 11. Conclusion

### Summary of Findings

1. **CRITICAL**: 17 database fields not mapped in entity - immediate fix required
2. **HIGH**: Missing unique index on (student_id, lecture_id) - performance issue
3. **MEDIUM**: Incomplete API Platform configuration - limits functionality
4. **LOW**: Minor convention violations - cosmetic improvements

### Immediate Actions Required

1. ✅ Add all missing fields to StudentLecture entity
2. ✅ Create migration for unique index: idx_student_lecture_unique_progress
3. ✅ Add comprehensive getters/setters for all fields
4. ✅ Update API Platform serialization groups
5. ✅ Write PHPUnit tests for new functionality

### Long-term Improvements

1. Implement Security Voter for access control
2. Add comprehensive business logic methods
3. Implement field-level encryption for sensitive data
4. Create monitoring dashboard for query performance
5. Add automated performance regression tests

### Risk Assessment

**Without Fixes:**
- HIGH: Data loss risk (entity doesn't match schema)
- MEDIUM: Performance degradation at scale
- MEDIUM: Security vulnerabilities (no access control)

**With Fixes:**
- LOW: Well-structured, performant entity
- LOW: Comprehensive LMS tracking capabilities
- LOW: Production-ready with proper security

---

## Appendix A: Complete Fixed Entity

See `/home/user/inf/app/src/Entity/StudentLecture.php` (will be updated)

## Appendix B: Database Migration

```sql
-- Create unique constraint to prevent duplicate progress records
CREATE UNIQUE INDEX idx_student_lecture_unique_progress
ON student_lecture (student_id, lecture_id);

-- Add comment
COMMENT ON INDEX idx_student_lecture_unique_progress IS
'Ensures one progress record per student-lecture combination';
```

## Appendix C: Testing Checklist

- [ ] Entity schema matches database
- [ ] All getters/setters present
- [ ] API serialization groups complete
- [ ] PHPUnit tests written and passing
- [ ] Security Voter implemented
- [ ] Performance indexes created
- [ ] Documentation updated
- [ ] Code review completed
- [ ] Deployed to staging
- [ ] Performance tested under load

---

**Report End**
