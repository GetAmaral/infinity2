# STUDENT PORTAL - COMPREHENSIVE DOCUMENTATION

**Complete Learning Management System with Real-Time Progress Tracking**

---

## TABLE OF CONTENTS

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Controllers](#controllers)
   - [StudentController](#studentcontroller)
   - [StudentProgressController](#studentprogresscontroller)
   - [CertificateController](#certificatecontroller)
4. [Entities](#entities)
   - [StudentCourse](#studentcourse-entity)
   - [StudentLecture](#studentlecture-entity)
5. [Progress Tracking System](#progress-tracking-system)
6. [Completion Thresholds](#completion-thresholds)
7. [Dual-Flush Pattern](#dual-flush-pattern)
8. [Milestone Tracking](#milestone-tracking)
9. [Navigation System](#navigation-system)
10. [Video Player Integration](#video-player-integration)
11. [Certificate Generation](#certificate-generation)
12. [API Reference](#api-reference)
13. [Usage Patterns](#usage-patterns)

---

## OVERVIEW

The Student Portal is a complete Learning Management System (LMS) built into Luminai. It provides students with:

- **Course Enrollment Management**: View all enrolled courses with progress tracking
- **Lecture Viewing**: HLS video streaming with automatic progress synchronization
- **Real-Time Progress Tracking**: Dual-level progress (lecture + course) with automatic cascade updates
- **Milestone Tracking**: Automatic milestone detection (25%, 50%, 75%)
- **Certificate Generation**: PDF certificates upon course completion (Dompdf)
- **Navigation Controls**: Previous/next lecture navigation with module sidebar
- **Completion Toggles**: Manual completion override for videoless lectures

**Key Features:**
- ✅ Automatic progress tracking every 5 seconds during video playback
- ✅ Resume playback from last watched position
- ✅ Dual-flush pattern ensures parent entities update correctly
- ✅ Completion thresholds: 90% for lectures, 95% for courses
- ✅ Dynamic milestone calculation without database storage
- ✅ PDF certificate generation with organization branding

---

## ARCHITECTURE

### **Directory Structure**

```
app/
├── src/
│   ├── Controller/
│   │   ├── StudentController.php              # Course browsing & lecture viewing
│   │   ├── StudentProgressController.php      # Real-time progress API
│   │   └── CertificateController.php          # PDF certificate generation
│   ├── Entity/
│   │   ├── StudentCourse.php                  # Course-level enrollment & progress
│   │   ├── StudentLecture.php                 # Lecture-level watch progress
│   │   ├── Course.php                         # Course entity
│   │   ├── CourseLecture.php                  # Lecture entity
│   │   └── CourseModule.php                   # Module entity (groups lectures)
│   └── Repository/
│       ├── StudentCourseRepository.php
│       └── StudentLectureRepository.php
├── templates/
│   └── student/
│       ├── courses.html.twig                  # Enrolled courses list
│       ├── course.html.twig                   # Course details & modules
│       └── lecture.html.twig                  # Video player & navigation
└── assets/
    └── controllers/
        ├── student-video_controller.js        # HLS video player (Plyr + Hls.js)
        └── completion-toggle_controller.js    # Manual completion toggle
```

### **Data Flow**

```
┌─────────────────┐
│  StudentCourse  │ (Course-level enrollment & progress)
│  - enrolledAt   │
│  - progressPercentage
│  - completedAt  │
└────────┬────────┘
         │ 1:N
         │
┌────────▼────────┐
│ StudentLecture  │ (Lecture-level watch progress)
│ - watchedSeconds│
│ - completionPercentage
│ - completed     │
└─────────────────┘
```

**Progress Update Flow:**

1. Video player sends progress update every 5 seconds
2. `StudentProgressController::updateProgress()` receives API call
3. Updates `StudentLecture` entity (watchedSeconds, lastPositionSeconds)
4. **First Flush**: Triggers `StudentLecture::calculateCompletion()` (PreUpdate)
   - Calculates lecture completion percentage
   - Auto-marks completed if ≥90%
5. **PostUpdate Callback**: Triggers `StudentLecture::updateParentProgress()`
   - Calls `StudentCourse::recalculateProgress()`
   - Sums all child lecture watched seconds
   - Calculates course completion percentage
   - Auto-marks course completed if ≥95%
6. **Second Flush**: Persists parent `StudentCourse` changes

---

## CONTROLLERS

### **StudentController**

**Purpose**: Handle student course browsing and lecture viewing (frontend pages).

**Routes:**

| Route | URL | Method | Description |
|-------|-----|--------|-------------|
| `student_courses` | `/student/courses` | GET | List all enrolled courses |
| `student_course` | `/student/course/{id}` | GET | View course details & modules |
| `student_lecture` | `/student/course/{courseId}/lecture/{lectureId}` | GET | Watch lecture video |

**Security**: All routes require `ROLE_USER`.

#### **Route: student_courses**

```php
#[Route('/student/courses', name: 'student_courses', methods: ['GET'])]
public function courses(): Response
```

**Purpose**: Display all active course enrollments for the current student.

**Implementation:**
```php
$student = $this->getUser();

// Get all active enrollments ordered by enrollment date
$enrollments = $this->studentCourseRepository->findBy(
    ['student' => $student, 'active' => true],
    ['enrolledAt' => 'DESC']
);

return $this->render('student/courses.html.twig', [
    'enrollments' => $enrollments,
]);
```

**Template Variables:**
- `enrollments` - Array of `StudentCourse` entities

**UI Features:**
- Progress bars for each course
- Completion badges (Not Started, In Progress, Completed)
- Continue/Start/Restart buttons based on state
- Certificate download button (if completed)
- Summary stats (total courses, completed, in progress)

---

#### **Route: student_course**

```php
#[Route('/student/course/{id}', name: 'student_course', methods: ['GET'])]
public function course(string $id): Response
```

**Purpose**: Display course details with module accordion and lecture list.

**Implementation:**
```php
$course = $this->courseRepository->find($id);

// Verify student enrollment
$enrollment = $this->studentCourseRepository->findOneBy([
    'student' => $student,
    'course' => $course,
    'active' => true
]);

if (!$enrollment) {
    throw $this->createAccessDeniedException('You are not enrolled in this course.');
}

// Get modules ordered by viewOrder
$modules = $this->moduleRepository->createQueryBuilder('cm')
    ->where('cm.course = :course')
    ->setParameter('course', $course)
    ->orderBy('cm.viewOrder', 'ASC')
    ->addOrderBy('cm.name', 'ASC')
    ->getQuery()
    ->getResult();

// Get all lectures with progress
$lectures = $this->lectureRepository->findByCourseOrdered($course->getId()->toString());

$lectureProgress = [];
foreach ($lectures as $lecture) {
    $progress = $this->studentLectureRepository->findOneBy([
        'student' => $student,
        'lecture' => $lecture
    ]);

    if ($progress) {
        $lectureProgress[$lecture->getId()->toString()] = $progress;
    }
}
```

**Template Variables:**
- `course` - Course entity
- `enrollment` - StudentCourse entity
- `modules` - Array of CourseModule entities (ordered)
- `lectures` - Array of CourseLecture entities (ordered)
- `lectureProgress` - Associative array [lectureId => StudentLecture]

**UI Features:**
- Course progress overview (percentage, completed lectures)
- Module accordion with lecture lists
- Lecture completion indicators
- Certificate download (if course completed)

---

#### **Route: student_lecture**

```php
#[Route('/student/course/{courseId}/lecture/{lectureId}', name: 'student_lecture', methods: ['GET'])]
public function lecture(string $courseId, string $lectureId): Response
```

**Purpose**: Display lecture video player with navigation and progress tracking.

**Implementation:**
```php
$course = $this->courseRepository->find($courseId);
$lecture = $this->lectureRepository->find($lectureId);

// Verify enrollment
$enrollment = $this->studentCourseRepository->findOneBy([
    'student' => $student,
    'course' => $course,
    'active' => true
]);

// Get or create student progress
$studentProgress = $this->studentLectureRepository->findOneBy([
    'student' => $student,
    'lecture' => $lecture
]);

// Get all modules and lectures for sidebar navigation
$modules = $this->moduleRepository->findByCourseOrdered($course->getId()->toString());
$allLectures = $this->lectureRepository->findByCourseOrdered($course->getId()->toString());

// Get progress for all lectures (sidebar indicators)
$allLectureProgress = [];
foreach ($allLectures as $courseLecture) {
    $progress = $this->studentLectureRepository->findOneBy([
        'student' => $student,
        'lecture' => $courseLecture
    ]);

    if ($progress) {
        $allLectureProgress[$courseLecture->getId()->toString()] = $progress;
    }
}

// Find previous/next lectures
$currentIndex = null;
foreach ($allLectures as $index => $courseLecture) {
    if ($courseLecture->getId()->toString() === $lectureId) {
        $currentIndex = $index;
        break;
    }
}

$previousLecture = ($currentIndex > 0) ? $allLectures[$currentIndex - 1] : null;
$nextLecture = ($currentIndex < count($allLectures) - 1) ? $allLectures[$currentIndex + 1] : null;

// Update enrollment tracking
if (!$enrollment->getStartDate()) {
    $enrollment->setStartDate(new \DateTimeImmutable());
}
$enrollment->setCurrentLecture($lecture);
$enrollment->setLastDate(new \DateTimeImmutable());
$this->studentCourseRepository->save($enrollment, true);
```

**Template Variables:**
- `course` - Course entity
- `lecture` - CourseLecture entity
- `enrollment` - StudentCourse entity
- `studentProgress` - StudentLecture entity (or null)
- `previousLecture` - CourseLecture entity (or null)
- `nextLecture` - CourseLecture entity (or null)
- `modules` - Array of modules (for sidebar)
- `allLectures` - Array of all lectures (for sidebar)
- `allLectureProgress` - Associative array [lectureId => StudentLecture]

**UI Features:**
- HLS video player with Plyr controls
- Automatic resume from last position
- Real-time progress tracking (every 5 seconds)
- Previous/next navigation buttons
- Module sidebar with completion indicators
- Manual completion toggle (for videoless lectures)
- Breadcrumb navigation
- Lecture stats (duration, progress, status)

---

### **StudentProgressController**

**Purpose**: RESTful API for real-time progress tracking (AJAX endpoints).

**Routes:**

| Route | URL | Method | Description |
|-------|-----|--------|-------------|
| `student_progress_update` | `/api/student/progress/lecture/{lectureId}` | POST | Update watch progress |
| `student_progress_get` | `/api/student/progress/lecture/{lectureId}` | GET | Get current progress |
| `student_progress_complete` | `/api/student/progress/lecture/{lectureId}/complete` | POST | Toggle completion |
| `student_progress_milestone` | `/api/student/progress/lecture/{lectureId}/milestone` | POST | Record milestone (deprecated) |

**Security**: All routes require authentication (no explicit `IsGranted` - handled by firewall).

---

#### **API: updateProgress**

```php
#[Route('/api/student/progress/lecture/{lectureId}', name: 'student_progress_update', methods: ['POST'])]
public function updateProgress(string $lectureId, Request $request): JsonResponse
```

**Purpose**: Update lecture watch progress (called automatically by video player every 5 seconds).

**Request Body (JSON):**
```json
{
  "position": 120,    // Current playback position (seconds)
  "duration": 600     // Total video duration (seconds)
}
```

**Response (JSON):**
```json
{
  "success": true,
  "position": 120,
  "completion": 20.0,
  "completed": false
}
```

**Implementation:**
```php
$user = $this->getUser();
$lecture = $this->lectureRepository->find($lectureId);

// Parse JSON body
$data = json_decode($request->getContent(), true);
$position = (int) ($data['position'] ?? 0);
$duration = (int) ($data['duration'] ?? 0);

// Find StudentCourse enrollment
$studentCourse = $this->studentCourseRepository->findOneBy([
    'student' => $user,
    'course' => $lecture->getCourseModule()->getCourse(),
    'active' => true
]);

// Find or create StudentLecture
$studentLecture = $this->studentLectureRepository->findOneBy([
    'student' => $user,
    'lecture' => $lecture
]);

if (!$studentLecture) {
    $studentLecture = new StudentLecture();
    $studentLecture->setStudent($user);
    $studentLecture->setLecture($lecture);
    $studentLecture->setStudentCourse($studentCourse);
    $this->entityManager->persist($studentLecture);
}

// Update progress
$studentLecture->setLastPositionSeconds($position);
$studentLecture->setLastWatchedAt(new \DateTimeImmutable());

// Update watched seconds (approximate - use position as proxy)
if ($position > $studentLecture->getWatchedSeconds()) {
    $studentLecture->setWatchedSeconds($position);
}

// Update StudentCourse tracking
if ($studentCourse) {
    $studentCourse->setCurrentLecture($lecture);

    if (!$studentCourse->getStartDate()) {
        $studentCourse->setStartDate(new \DateTimeImmutable());
    }
}

// DUAL FLUSH PATTERN (critical for cascading updates)
$this->entityManager->flush(); // First flush: triggers StudentLecture lifecycle callbacks
$this->entityManager->flush(); // Second flush: persists parent StudentCourse changes

return new JsonResponse([
    'success' => true,
    'position' => $position,
    'completion' => $studentLecture->getCompletionPercentage(),
    'completed' => $studentLecture->isCompleted()
]);
```

**Key Points:**
- Creates `StudentLecture` on first watch
- Only increases `watchedSeconds` (never decreases)
- Uses `lastPositionSeconds` for resume functionality
- Dual-flush ensures parent course progress updates
- Updates `currentLecture` to track last watched position

---

#### **API: getProgress**

```php
#[Route('/api/student/progress/lecture/{lectureId}', name: 'student_progress_get', methods: ['GET'])]
public function getProgress(string $lectureId): JsonResponse
```

**Purpose**: Retrieve current lecture progress (used for initializing video player).

**Response (JSON):**
```json
{
  "position": 120,
  "completion": 20.0,
  "completed": false,
  "milestones": {
    "25": false,
    "50": false,
    "75": false
  }
}
```

**Implementation:**
```php
$studentLecture = $this->studentLectureRepository->findOneBy([
    'student' => $user,
    'lecture' => $lecture
]);

if (!$studentLecture) {
    return new JsonResponse([
        'position' => 0,
        'completion' => 0,
        'completed' => false
    ]);
}

return new JsonResponse([
    'position' => $studentLecture->getLastPositionSeconds(),
    'completion' => $studentLecture->getCompletionPercentage(),
    'completed' => $studentLecture->isCompleted(),
    'milestones' => [
        25 => $studentLecture->isReached25Percent(),
        50 => $studentLecture->isReached50Percent(),
        75 => $studentLecture->isReached75Percent(),
    ]
]);
```

---

#### **API: toggleCompletion**

```php
#[Route('/api/student/progress/lecture/{lectureId}/complete', name: 'student_progress_complete', methods: ['POST'])]
public function toggleCompletion(string $lectureId, Request $request): JsonResponse
```

**Purpose**: Manually toggle lecture completion (used for videoless lectures or user override).

**Request Body (JSON):**
```json
{
  "completed": true
}
```

**Response (JSON):**
```json
{
  "success": true,
  "completed": true,
  "completion": 100.0,
  "courseProgress": 67.5
}
```

**Implementation:**
```php
$data = json_decode($request->getContent(), true);
$shouldBeCompleted = $data['completed'] ?? false;

$lecture = $this->lectureRepository->find($lectureId);
$lectureLength = $lecture->getLengthSeconds();

// Find or create StudentLecture
$studentLecture = $this->studentLectureRepository->findOneBy([
    'student' => $user,
    'lecture' => $lecture
]);

if (!$studentLecture) {
    $studentLecture = new StudentLecture();
    $studentLecture->setStudent($user);
    $studentLecture->setLecture($lecture);
    $studentLecture->setStudentCourse($studentCourse);
    $this->entityManager->persist($studentLecture);
}

if ($shouldBeCompleted) {
    if ($lectureLength > 0) {
        // Video lecture: set watched seconds to full length
        $studentLecture->setWatchedSeconds($lectureLength);
        $studentLecture->setLastPositionSeconds($lectureLength);
    } else {
        // Videoless lecture: set to 1 to indicate manually completed
        $studentLecture->setWatchedSeconds(1);
        $studentLecture->setLastPositionSeconds(1);
    }
} else {
    // Reset to incomplete
    $studentLecture->setWatchedSeconds(0);
    $studentLecture->setLastPositionSeconds(0);
}

$studentLecture->setLastWatchedAt(new \DateTimeImmutable());

// Update StudentCourse tracking
if ($studentCourse) {
    $studentCourse->setCurrentLecture($lecture);
    if (!$studentCourse->getStartDate()) {
        $studentCourse->setStartDate(new \DateTimeImmutable());
    }
}

// DUAL FLUSH PATTERN
$this->entityManager->flush(); // First flush: calculates completion
$this->entityManager->flush(); // Second flush: updates parent course

$courseProgress = $studentCourse ? $studentCourse->getProgressPercentage() : 0;

return new JsonResponse([
    'success' => true,
    'completed' => $studentLecture->isCompleted(),
    'completion' => $studentLecture->getCompletionPercentage(),
    'courseProgress' => $courseProgress
]);
```

**Special Cases:**
- **Video Lectures**: Sets `watchedSeconds` to full lecture length
- **Videoless Lectures**: Sets `watchedSeconds` to 1 (flag for completion)
- **Uncomplete**: Resets `watchedSeconds` to 0

---

### **CertificateController**

**Purpose**: Generate completion certificates for finished courses.

**Routes:**

| Route | URL | Method | Description |
|-------|-----|--------|-------------|
| `certificate_course` | `/course/{id}/certificate` | GET | HTML certificate (printable) |
| `certificate_course_pdf` | `/course/{id}/certificate/pdf` | GET | PDF download |

**Security**: Both routes require `ROLE_USER`.

---

#### **Route: certificate_course**

```php
#[Route('/course/{id}/certificate', name: 'certificate_course', methods: ['GET'])]
public function course(Course $course): Response
```

**Purpose**: Display certificate in HTML format (browser print).

**Implementation:**
```php
$student = $this->getUser();

// Find enrollment
$enrollment = $this->studentCourseRepository->findOneBy([
    'student' => $student,
    'course' => $course,
    'active' => true
]);

// Security: Only allow if completed
if (!$enrollment->isCompleted()) {
    $this->addFlash('error', 'certificate.error.not_completed');
    return $this->redirectToRoute('student_course', ['id' => $course->getId()->toString()]);
}

$organization = $course->getOrganization();

return $this->render('course/certificate.html.twig', [
    'course' => $course,
    'enrollment' => $enrollment,
    'student' => $student,
    'organization' => $organization,
    'isPdf' => false,
]);
```

**Template Variables:**
- `course` - Course entity
- `enrollment` - StudentCourse entity
- `student` - User entity
- `organization` - Organization entity
- `isPdf` - Boolean (false for HTML view)

---

#### **Route: certificate_course_pdf**

```php
#[Route('/course/{id}/certificate/pdf', name: 'certificate_course_pdf', methods: ['GET'])]
public function coursePdf(Course $course, Request $request): Response
```

**Purpose**: Generate downloadable PDF certificate using Dompdf.

**Implementation:**
```php
$student = $this->getUser();

// Find enrollment
$enrollment = $this->studentCourseRepository->findOneBy([
    'student' => $student,
    'course' => $course,
    'active' => true
]);

// Security: Only allow if completed
if (!$enrollment->isCompleted()) {
    $this->addFlash('error', 'certificate.error.not_completed');
    return $this->redirectToRoute('student_course', ['id' => $course->getId()->toString()]);
}

$organization = $course->getOrganization();

// Render HTML for PDF
$html = $this->renderView('course/certificate_pdf.html.twig', [
    'course' => $course,
    'enrollment' => $enrollment,
    'student' => $student,
    'organization' => $organization,
]);

// Debug mode: show HTML instead of PDF
if ($request->query->get('debug') === '1') {
    return new Response($html);
}

// Configure Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);      // Load external resources
$options->set('isHtml5ParserEnabled', true); // HTML5 parsing

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();

// Generate filename: Certificate-Course_Name-Student_Name-yyyy-mm-dd-hh-mm-ss
$certificateWord = $this->translator->trans('certificate.title');
$filename = sprintf(
    '%s-%s-%s-%s.pdf',
    Utils::stringToSnake($certificateWord, true),
    Utils::stringToSnake($course->getName(), true),
    Utils::stringToSnake($student->getName(), true),
    date('Y-m-d-H-i-s')
);

// Return PDF
return new Response(
    $dompdf->output(),
    200,
    [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
    ]
);
```

**Features:**
- **A4 Landscape**: Optimized for certificate layout
- **Debug Mode**: Add `?debug=1` to view HTML before PDF conversion
- **Snake Case Filename**: `Certificate-Introduction_To_Symfony-John_Doe-2025-10-05-14-30-00.pdf`
- **Organization Branding**: Includes organization logo and name

**Dompdf Configuration:**
- `isRemoteEnabled: true` - Allows loading images from URLs
- `isHtml5ParserEnabled: true` - Better CSS support

---

## ENTITIES

### **StudentCourse Entity**

**Purpose**: Represents course enrollment and overall course-level progress.

**Table**: `student_course`

**Fields:**

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `id` | UUIDv7 | Primary Key | Unique identifier |
| `enrolledAt` | DateTimeImmutable | Required | Enrollment date |
| `active` | Boolean | Default: true | Enrollment status |
| `startDate` | DateTimeImmutable | Nullable | First lecture watch date |
| `lastDate` | DateTimeImmutable | Nullable | Last activity date |
| `progressSeconds` | Float | ≥0 | Total watched seconds |
| `progressPercentage` | Float | 0-100 | Course completion % |
| `completedAt` | DateTimeImmutable | Nullable | Completion timestamp |
| `organization` | Organization | ManyToOne | Parent organization |
| `student` | User | ManyToOne | Enrolled student |
| `course` | Course | ManyToOne | Enrolled course |
| `currentLecture` | CourseLecture | ManyToOne | Last watched lecture |
| `studentLectures` | Collection | OneToMany | Child lecture progress |

**Relationships:**
```
StudentCourse (1) ──► (N) StudentLecture
StudentCourse (N) ──► (1) Organization
StudentCourse (N) ──► (1) User (student)
StudentCourse (N) ──► (1) Course
StudentCourse (N) ──► (1) CourseLecture (currentLecture)
```

**Completion Threshold:**
```php
public const MIN_COMPLETED = 95.0; // Must watch 95% to complete
```

**Key Methods:**

#### **recalculateProgress()**

```php
public function recalculateProgress(): void
{
    $totalWatchedSeconds = 0;

    // Sum all child lecture watched seconds
    foreach ($this->studentLectures as $studentLecture) {
        $totalWatchedSeconds += $studentLecture->getWatchedSeconds();
    }

    $this->progressSeconds = (float) $totalWatchedSeconds;

    // Calculate percentage
    $courseTotalSeconds = $this->course->getTotalLengthSeconds();
    if ($courseTotalSeconds > 0) {
        $percentage = ($this->progressSeconds / $courseTotalSeconds) * 100;
        $this->progressPercentage = min($percentage, 100.0); // Max 100%
    } else {
        $this->progressPercentage = 0.0;
    }

    // Update lastDate
    $this->lastDate = new \DateTimeImmutable();

    // Auto-complete if >= 95%
    if ($this->progressPercentage >= self::MIN_COMPLETED && $this->completedAt === null) {
        $this->completedAt = new \DateTimeImmutable();
    } elseif ($this->progressPercentage < self::MIN_COMPLETED && $this->completedAt !== null) {
        // Reset completion if progress drops below threshold
        $this->completedAt = null;
    }
}
```

**Usage:**
```php
// Called automatically by StudentLecture::updateParentProgress()
// Do NOT call manually - use dual-flush pattern in controller
```

---

### **StudentLecture Entity**

**Purpose**: Represents watch progress for a specific lecture.

**Table**: `student_lecture`

**Fields:**

| Field | Type | Validation | Description |
|-------|------|------------|-------------|
| `id` | UUIDv7 | Primary Key | Unique identifier |
| `student` | User | ManyToOne | Student user |
| `lecture` | CourseLecture | ManyToOne | Lecture being watched |
| `studentCourse` | StudentCourse | ManyToOne | Parent enrollment |
| `watchedSeconds` | Integer | ≥0 | Total watched seconds |
| `lastPositionSeconds` | Integer | ≥0 | Last playback position |
| `completionPercentage` | Float | 0-100 | Lecture completion % |
| `completed` | Boolean | Auto-calculated | Completion status |
| `lastWatchedAt` | DateTimeImmutable | Nullable | Last watch timestamp |
| `completedAt` | DateTimeImmutable | Nullable | Completion timestamp |

**Relationships:**
```
StudentLecture (N) ──► (1) User (student)
StudentLecture (N) ──► (1) CourseLecture
StudentLecture (N) ──► (1) StudentCourse (parent)
```

**Completion Threshold:**
```php
public const MIN_COMPLETION = 90.0; // Must watch 90% to complete
```

**Lifecycle Callbacks:**

#### **calculateCompletion() - PrePersist/PreUpdate**

```php
#[ORM\PrePersist]
#[ORM\PreUpdate]
public function calculateCompletion(): void
{
    $lectureLength = $this->lecture->getLengthSeconds();

    if ($lectureLength > 0) {
        // Video lecture - calculate percentage
        $percentage = ($this->watchedSeconds / $lectureLength) * 100;
        $this->completionPercentage = min($percentage, 100.0);
    } else {
        // Videoless lecture - 100% if any watched seconds, 0% otherwise
        if ($this->watchedSeconds > 0) {
            $this->completionPercentage = 100.0;
        } else {
            $this->completionPercentage = 0.0;
        }
    }

    // Auto-mark as completed if >= 90%
    if ($this->completionPercentage >= self::MIN_COMPLETION) {
        if (!$this->completed) {
            $this->completed = true;
            $this->completedAt = new \DateTimeImmutable();
        }
    } else {
        // Reset completion if below threshold
        $this->completed = false;
        $this->completedAt = null;
    }
}
```

**Triggered By:**
- `$entityManager->persist($studentLecture)`
- `$entityManager->flush()` (after field changes)

**Purpose:**
- Automatically calculate `completionPercentage` based on `watchedSeconds`
- Auto-mark `completed = true` when reaching 90% threshold
- Handle videoless lectures (mark 100% if any progress)

---

#### **updateParentProgress() - PostPersist/PostUpdate**

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

**Triggered By:**
- After `$entityManager->flush()` completes

**Purpose:**
- Cascade progress updates to parent `StudentCourse`
- Ensures course completion percentage stays in sync
- **Requires second flush** to persist parent changes

---

**Milestone Methods:**

```php
public function isReached25Percent(): bool
{
    return $this->completionPercentage >= 25.0;
}

public function isReached50Percent(): bool
{
    return $this->completionPercentage >= 50.0;
}

public function isReached75Percent(): bool
{
    return $this->completionPercentage >= 75.0;
}
```

**Key Points:**
- **Dynamic Calculation**: No database fields for milestones
- **Computed on Read**: Calculated from `completionPercentage`
- **Backwards Compatible**: Old milestone API endpoint deprecated but functional

---

## PROGRESS TRACKING SYSTEM

### **Two-Level Progress Hierarchy**

1. **Lecture-Level Progress (StudentLecture)**
   - Tracks individual lecture watch progress
   - Stores `watchedSeconds`, `lastPositionSeconds`
   - Auto-calculates `completionPercentage`
   - Auto-marks `completed = true` at 90%

2. **Course-Level Progress (StudentCourse)**
   - Aggregates all child lecture progress
   - Sums `watchedSeconds` from all lectures
   - Calculates course `progressPercentage`
   - Auto-marks `completedAt` at 95%

### **Progress Update Flow**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Video Player (Stimulus Controller)                       │
│    - Sends progress update every 5 seconds                  │
│    - POST /api/student/progress/lecture/{id}                │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│ 2. StudentProgressController::updateProgress()              │
│    - Receives position & duration                           │
│    - Updates StudentLecture.watchedSeconds                  │
│    - Updates StudentLecture.lastPositionSeconds             │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│ 3. First Flush: $entityManager->flush()                     │
│    - Triggers StudentLecture::calculateCompletion()         │
│      • Calculates completionPercentage                      │
│      • Auto-marks completed if >= 90%                       │
│    - Triggers StudentLecture::updateParentProgress()        │
│      • Calls StudentCourse::recalculateProgress()           │
│      • Sums all child watchedSeconds                        │
│      • Calculates course progressPercentage                 │
│      • Auto-marks completedAt if >= 95%                     │
└───────────────────────┬─────────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────────┐
│ 4. Second Flush: $entityManager->flush()                    │
│    - Persists StudentCourse changes from step 3             │
└─────────────────────────────────────────────────────────────┘
```

---

## COMPLETION THRESHOLDS

### **Lecture Completion: 90%**

```php
// StudentLecture::MIN_COMPLETION = 90.0

if ($this->completionPercentage >= 90.0) {
    $this->completed = true;
    $this->completedAt = new \DateTimeImmutable();
}
```

**Rationale:**
- Allows skipping credits/outro (last 10%)
- Generous threshold for user experience
- Prevents false "incomplete" states

**Special Cases:**
- **Videoless Lectures**: Marked 100% if `watchedSeconds > 0`
- **Manual Toggle**: Can override automatic completion

---

### **Course Completion: 95%**

```php
// StudentCourse::MIN_COMPLETED = 95.0

if ($this->progressPercentage >= 95.0) {
    $this->completedAt = new \DateTimeImmutable();
}
```

**Rationale:**
- Requires watching majority of course content
- Allows skipping 1-2 optional lectures
- Balances rigor with flexibility

**Certificate Access:**
- Only granted when `completedAt !== null`
- Security check enforced in `CertificateController`

---

## DUAL-FLUSH PATTERN

### **Why Two Flushes?**

**Problem:**
Doctrine lifecycle callbacks (PostUpdate) modify related entities but don't automatically persist those changes.

**Example:**
```php
$studentLecture->setWatchedSeconds(120);
$entityManager->flush(); // First flush
// PostUpdate triggers: StudentCourse::recalculateProgress() modifies parent
// Parent changes are NOT persisted yet!
```

**Solution:**
```php
$studentLecture->setWatchedSeconds(120);
$entityManager->flush(); // First flush: triggers lifecycle callbacks
$entityManager->flush(); // Second flush: persists parent changes
```

### **Implementation in Controllers**

```php
// StudentProgressController::updateProgress()
$studentLecture->setWatchedSeconds($position);

// First flush: persists StudentLecture and triggers lifecycle callbacks
// - PreUpdate calculates completionPercentage
// - PostUpdate modifies parent StudentCourse
$this->entityManager->flush();

// Second flush: persists the parent StudentCourse changes made in PostUpdate
$this->entityManager->flush();
```

**Critical:** Both flushes are required for cascading updates to work correctly.

---

## MILESTONE TRACKING

### **Dynamic Milestones (No Database Storage)**

Old approach (deprecated):
```php
// BAD: Storing milestones in database
$studentLecture->setReached25Percent(true);
$studentLecture->setReached50Percent(true);
```

New approach (current):
```php
// GOOD: Dynamic calculation from completionPercentage
public function isReached25Percent(): bool
{
    return $this->completionPercentage >= 25.0;
}
```

**Benefits:**
- No database migrations needed
- Always accurate (calculated from source of truth)
- Backwards compatible with API

### **Milestone Detection**

```php
// API Response
{
  "milestones": {
    "25": true,   // isReached25Percent()
    "50": true,   // isReached50Percent()
    "75": false   // isReached75Percent()
  }
}
```

**Frontend Usage:**
```javascript
// Can trigger UI celebrations, achievements, etc.
if (data.milestones[50] && !this.shownMilestone50) {
    showCelebration('Halfway there!');
    this.shownMilestone50 = true;
}
```

---

## NAVIGATION SYSTEM

### **Previous/Next Lecture Navigation**

**Algorithm:**
```php
$allLectures = $this->lectureRepository->findByCourseOrdered($courseId);

// Find current lecture index
$currentIndex = null;
foreach ($allLectures as $index => $lecture) {
    if ($lecture->getId()->toString() === $currentLectureId) {
        $currentIndex = $index;
        break;
    }
}

// Get adjacent lectures
$previousLecture = ($currentIndex > 0) ? $allLectures[$currentIndex - 1] : null;
$nextLecture = ($currentIndex < count($allLectures) - 1) ? $allLectures[$currentIndex + 1] : null;
```

**UI Behavior:**
- **Previous Button**: Disabled on first lecture
- **Next Button**: Replaced with "Finish Course" on last lecture
- **Tooltips**: Show lecture name on hover

---

### **Module Sidebar Navigation**

**Features:**
- Accordion UI (one module expanded at a time)
- Auto-expand module containing current lecture
- Completion indicators (green checkmark)
- Progress indicator (current lecture highlighted)
- Compact view with truncated names

**Template Pattern:**
```twig
{% for module in modules %}
    {% set moduleHasCurrentLecture = false %}
    {% for moduleLecture in module.lectures %}
        {% if moduleLecture.id.toString == lecture.id.toString %}
            {% set moduleHasCurrentLecture = true %}
        {% endif %}
    {% endfor %}

    <div class="accordion-item">
        <button data-bs-target="#module-{{ module.id }}"
                aria-expanded="{{ moduleHasCurrentLecture ? 'true' : 'false' }}">
            {{ module.name }}
        </button>
        <div id="module-{{ module.id }}" class="collapse {{ moduleHasCurrentLecture ? 'show' : '' }}">
            {% for lecture in module.lectures %}
                <a href="{{ path('student_lecture', {courseId: course.id, lectureId: lecture.id}) }}">
                    {{ lecture.name }}
                    {% if lectureProgress[lecture.id.toString] and lectureProgress[lecture.id.toString].completed %}
                        ✓
                    {% endif %}
                </a>
            {% endfor %}
        </div>
    </div>
{% endfor %}
```

---

## VIDEO PLAYER INTEGRATION

### **Technology Stack**

- **Plyr**: Modern HTML5 video player with rich UI
- **Hls.js**: HLS streaming support for adaptive bitrate
- **Stimulus**: Controller for progress tracking

### **Player Initialization**

```javascript
// student-video_controller.js
import { Controller } from '@hotwired/stimulus';
import Plyr from 'plyr';
import Hls from 'hls.js';

export default class extends Controller {
    static values = {
        videoUrl: String,       // HLS m3u8 URL
        lectureId: String,      // Lecture UUID
        progressUrl: String,    // API endpoint for progress updates
        currentPosition: Number // Resume position (seconds)
    }

    connect() {
        this.initializePlayer();
        this.startProgressTracking();
    }

    initializePlayer() {
        const video = this.playerTarget;

        if (Hls.isSupported()) {
            const hls = new Hls();
            hls.loadSource(this.videoUrlValue);
            hls.attachMedia(video);
        }

        this.player = new Plyr(video, {
            controls: ['play', 'progress', 'current-time', 'mute', 'volume', 'fullscreen']
        });

        // Resume from last position
        this.player.currentTime = this.currentPositionValue;
    }

    startProgressTracking() {
        setInterval(() => {
            if (!this.player.paused) {
                this.saveProgress();
            }
        }, 5000); // Every 5 seconds
    }

    saveProgress() {
        const position = Math.floor(this.player.currentTime);
        const duration = Math.floor(this.player.duration);

        fetch(this.progressUrlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ position, duration })
        });
    }
}
```

### **Plyr Configuration**

```javascript
const plyrOptions = {
    controls: [
        'play-large',      // Center play button
        'play',            // Play/pause
        'progress',        // Seek bar
        'current-time',    // Current timestamp
        'duration',        // Total duration
        'mute',            // Mute toggle
        'volume',          // Volume slider
        'settings',        // Quality settings
        'fullscreen'       // Fullscreen toggle
    ],
    settings: ['quality', 'speed'],
    speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
    quality: { default: 720, options: [1080, 720, 480, 360] }
};
```

### **HLS Adaptive Streaming**

```javascript
const hls = new Hls({
    maxBufferLength: 30,      // 30 seconds buffer
    maxMaxBufferLength: 600,  // 10 minutes max buffer
    startLevel: -1,           // Auto-select quality
    enableWorker: true        // Background processing
});

hls.on(Hls.Events.MANIFEST_PARSED, () => {
    console.log('HLS manifest loaded, starting playback');
});

hls.on(Hls.Events.ERROR, (event, data) => {
    if (data.fatal) {
        console.error('Fatal HLS error:', data);
        // Fallback to direct MP4 playback
    }
});
```

---

## CERTIFICATE GENERATION

### **Dompdf Integration**

**Installation:**
```bash
composer require dompdf/dompdf
```

**Configuration:**
```php
$options = new Options();
$options->set('isRemoteEnabled', true);      // Load images from URLs
$options->set('isHtml5ParserEnabled', true); // Better CSS support
$options->set('defaultFont', 'Arial');       // Fallback font

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'landscape');
$dompdf->loadHtml($html);
$dompdf->render();
```

### **Certificate Template Structure**

```html
<!-- certificate_pdf.html.twig -->
<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        body {
            margin: 0;
            padding: 40px;
            font-family: 'Georgia', serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .certificate {
            width: 100%;
            height: 100%;
            background: white;
            border: 20px solid #f0f0f0;
            padding: 60px;
            text-align: center;
        }
        .title {
            font-size: 48px;
            color: #333;
            margin-bottom: 20px;
        }
        .student-name {
            font-size: 36px;
            color: #667eea;
            font-weight: bold;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <img src="{{ organization.logoUrl }}" alt="{{ organization.name }}" style="height: 80px;">

        <h1 class="title">Certificate of Completion</h1>

        <p>This certifies that</p>
        <h2 class="student-name">{{ student.name }}</h2>

        <p>has successfully completed the course</p>
        <h3>{{ course.name }}</h3>

        <p>Completed on {{ enrollment.completedAt|format_date('long') }}</p>

        <div class="signature">
            <p>{{ organization.name }}</p>
        </div>
    </div>
</body>
</html>
```

### **Filename Generation**

```php
$filename = sprintf(
    '%s-%s-%s-%s.pdf',
    Utils::stringToSnake($this->translator->trans('certificate.title'), true),  // "Certificate"
    Utils::stringToSnake($course->getName(), true),                             // "Introduction_To_Symfony"
    Utils::stringToSnake($student->getName(), true),                            // "John_Doe"
    date('Y-m-d-H-i-s')                                                         // "2025-10-05-14-30-00"
);
// Result: Certificate-Introduction_To_Symfony-John_Doe-2025-10-05-14-30-00.pdf
```

---

## API REFERENCE

### **Progress Update**

**Endpoint:** `POST /api/student/progress/lecture/{lectureId}`

**Request:**
```json
{
  "position": 120,
  "duration": 600
}
```

**Response:**
```json
{
  "success": true,
  "position": 120,
  "completion": 20.0,
  "completed": false
}
```

---

### **Get Progress**

**Endpoint:** `GET /api/student/progress/lecture/{lectureId}`

**Response:**
```json
{
  "position": 120,
  "completion": 20.0,
  "completed": false,
  "milestones": {
    "25": false,
    "50": false,
    "75": false
  }
}
```

---

### **Toggle Completion**

**Endpoint:** `POST /api/student/progress/lecture/{lectureId}/complete`

**Request:**
```json
{
  "completed": true
}
```

**Response:**
```json
{
  "success": true,
  "completed": true,
  "completion": 100.0,
  "courseProgress": 67.5
}
```

---

## USAGE PATTERNS

### **Pattern 1: Enroll Student in Course**

```php
$studentCourse = new StudentCourse();
$studentCourse->setStudent($user);
$studentCourse->setCourse($course);
$studentCourse->setOrganization($course->getOrganization());
$studentCourse->setActive(true);

$entityManager->persist($studentCourse);
$entityManager->flush();
```

---

### **Pattern 2: Track Video Progress**

```javascript
// Stimulus controller
setInterval(() => {
    if (!this.player.paused) {
        const position = Math.floor(this.player.currentTime);
        const duration = Math.floor(this.player.duration);

        fetch(`/api/student/progress/lecture/${this.lectureIdValue}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ position, duration })
        }).then(response => response.json())
          .then(data => {
              console.log(`Progress: ${data.completion}%`);
          });
    }
}, 5000);
```

---

### **Pattern 3: Check Course Completion**

```php
$enrollment = $studentCourseRepository->findOneBy([
    'student' => $user,
    'course' => $course
]);

if ($enrollment && $enrollment->isCompleted()) {
    // Allow certificate download
    return $this->redirectToRoute('certificate_course', ['id' => $course->getId()]);
}
```

---

### **Pattern 4: Generate Certificate**

```php
// Only allow if course completed
if (!$enrollment->isCompleted()) {
    throw $this->createAccessDeniedException('Course not completed');
}

// Render certificate
$html = $this->renderView('course/certificate_pdf.html.twig', [
    'course' => $course,
    'enrollment' => $enrollment,
    'student' => $student,
    'organization' => $organization
]);

// Generate PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->render();

return new Response($dompdf->output(), 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'attachment; filename="certificate.pdf"'
]);
```

---

### **Pattern 5: Implement Previous/Next Navigation**

```php
$allLectures = $lectureRepository->findByCourseOrdered($course->getId()->toString());

// Find current lecture
$currentIndex = array_search($currentLecture, $allLectures, true);

$previousLecture = ($currentIndex > 0) ? $allLectures[$currentIndex - 1] : null;
$nextLecture = ($currentIndex < count($allLectures) - 1) ? $allLectures[$currentIndex + 1] : null;

return $this->render('student/lecture.html.twig', [
    'previousLecture' => $previousLecture,
    'nextLecture' => $nextLecture
]);
```

---

## SUMMARY

The Student Portal provides a complete LMS experience with:

- **3 Controllers**: StudentController (pages), StudentProgressController (API), CertificateController (PDF)
- **2 Entities**: StudentCourse (course-level), StudentLecture (lecture-level)
- **Dual-Flush Pattern**: Ensures cascading progress updates work correctly
- **Dynamic Milestones**: Calculated from completionPercentage, no database storage
- **Completion Thresholds**: 90% lectures, 95% courses
- **HLS Video Streaming**: Plyr + Hls.js for adaptive bitrate
- **PDF Certificates**: Dompdf with organization branding

**Total Lines of Documentation**: 700+ lines covering all aspects of the Student Portal system.
