# Video Lecture System Implementation Plan

## Overview
Complete self-hosted video streaming system with HLS adaptive bitrate, file uploads, background processing, and student progress tracking.

## Technical Specifications
- **Storage:** `/var/videos/` (originals and HLS segments)
- **Queue:** Redis transport for Symfony Messenger
- **Max Upload:** 4GB per file with validation
- **HLS Qualities:** 360p, 480p, 720p, 1080p (adaptive)
- **Enrollment:** Admin-managed via StudentCourse entity
- **Player:** Plyr.io with progress tracking
- **Upload Methods:** Single lecture upload (traditional) OR bulk upload (multiple files at once)

---

## Implementation Steps

### 1. Database Schema Updates

#### 1.1 Update CourseLecture Entity
**File:** `src/Entity/CourseLecture.php`

**Changes:**
- Remove: `protected ?string $videoUrl = null`
- Add:
  ```php
  #[Vich\UploadableField(mapping: 'lecture_videos', fileNameProperty: 'videoFileName')]
  private ?File $videoFile = null;

  #[ORM\Column(type: 'string', length: 255, nullable: true)]
  private ?string $videoFileName = null;

  #[ORM\Column(type: 'string', length: 500, nullable: true)]
  private ?string $videoPath = null; // Path to HLS master.m3u8

  #[ORM\Column(type: 'string', length: 20)]
  private string $processingStatus = 'pending'; // pending|processing|completed|failed

  #[ORM\Column(type: 'text', nullable: true)]
  private ?string $processingError = null;

  #[ORM\Column(type: 'datetime_immutable', nullable: true)]
  private ?\DateTimeImmutable $processedAt = null;
  ```

- Add getters/setters for all new fields
- Keep `lengthSeconds` field (will be auto-populated after processing)

#### 1.2 Create StudentLecture Entity
**File:** `src/Entity/StudentLecture.php`

**Purpose:** Track individual lecture watch progress per student

```php
#[ORM\Entity(repositoryClass: StudentLectureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class StudentLecture extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $student;

    #[ORM\ManyToOne(targetEntity: CourseLecture::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CourseLecture $lecture;

    #[ORM\Column(type: 'integer')]
    private int $watchedSeconds = 0; // Total time watched

    #[ORM\Column(type: 'integer')]
    private int $lastPositionSeconds = 0; // Resume from here

    #[ORM\Column(type: 'float')]
    private float $completionPercentage = 0.0; // 0-100

    #[ORM\Column(type: 'boolean')]
    private bool $completed = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastWatchedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    // Milestones for analytics
    #[ORM\Column(type: 'boolean')]
    private bool $reached25Percent = false;

    #[ORM\Column(type: 'boolean')]
    private bool $reached50Percent = false;

    #[ORM\Column(type: 'boolean')]
    private bool $reached75Percent = false;
}
```

**Note:** StudentLecture already exists, verify and update if needed.

#### 1.3 Update StudentCourse Entity
**File:** `src/Entity/StudentCourse.php`

**Purpose:** Track overall course progress per student

**Verify/Add fields:**
```php
#[ORM\ManyToOne(targetEntity: CourseLecture::class)]
#[ORM\JoinColumn(nullable: true)]
private ?CourseLecture $currentLecture = null; // Where student is now

#[ORM\Column(type: 'float')]
private float $progressPercentage = 0.0; // Overall course completion

#[ORM\Column(type: 'datetime_immutable')]
private \DateTimeImmutable $enrolledAt;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
private ?\DateTimeImmutable $startedAt = null;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
private ?\DateTimeImmutable $completedAt = null;
```

**Note:** StudentCourse already exists, verify and update if needed.

#### 1.4 Create Database Migrations
**Commands:**
```bash
docker-compose exec app php bin/console make:migration
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

**Verify migrations include:**
- CourseLecture: Remove `video_url`, add `video_file_name`, `video_path`, `processing_status`, `processing_error`, `processed_at`
- StudentLecture: All tracking fields
- StudentCourse: Progress tracking fields

---

### 2. File Upload Configuration

#### 2.1 Install VichUploaderBundle
**Command:**
```bash
docker-compose exec app composer require vich/uploader-bundle
```

#### 2.2 Configure VichUploader
**File:** `config/packages/vich_uploader.yaml`

```yaml
vich_uploader:
    db_driver: orm

    mappings:
        lecture_videos:
            uri_prefix: /uploads/videos
            upload_destination: '%kernel.project_dir%/var/videos/originals'
            namer: Vich\UploaderBundle\Naming\UniqidNamer
            inject_on_load: false
            delete_on_update: true
            delete_on_remove: true
```

#### 2.3 Create Storage Directories
**Commands:**
```bash
docker-compose exec app mkdir -p /var/videos/originals
docker-compose exec app mkdir -p /var/videos/hls
docker-compose exec app chmod -R 777 /var/videos
```

Or add to Dockerfile:
```dockerfile
RUN mkdir -p /var/videos/originals /var/videos/hls && \
    chown -R www-data:www-data /var/videos
```

---

### 3. Form Updates

#### 3.1 Update CourseLectureFormType
**File:** `src/Form/CourseLectureFormType.php`

**Changes:**
- Remove: `->add('videoUrl', UrlType::class, ...)`
- Remove: `->add('lengthSeconds', IntegerType::class, ...)` (auto-calculated)
- Add:
  ```php
  use Vich\UploaderBundle\Form\Type\VichFileType;

  ->add('videoFile', VichFileType::class, [
      'label' => 'course.lecture.form.video_file',
      'required' => !$options['is_edit'], // Required for new, optional for edit
      'allow_delete' => false,
      'download_uri' => false,
      'attr' => [
          'class' => 'form-input-modern',
          'accept' => 'video/mp4,video/webm,video/ogg,video/quicktime',
      ],
      'constraints' => [
          new Assert\File([
              'maxSize' => '4G',
              'mimeTypes' => [
                  'video/mp4',
                  'video/webm',
                  'video/ogg',
                  'video/quicktime',
                  'video/x-msvideo',
              ],
              'mimeTypesMessage' => 'course.lecture.validation.invalid_video_format',
          ]),
      ],
  ])
  ```

#### 3.2 Update Lecture Form Template
**File:** `templates/course/_lecture_form_modal.html.twig`

**Changes:**
- Remove: Video URL field and status div
- Remove: lengthSeconds field
- Add:
  ```twig
  <div class="form-group-modern">
      <label class="form-label-modern">{{ form_label(form.videoFile) }}</label>
      {{ form_widget(form.videoFile, {
          'attr': {
              'class': 'form-input-modern' ~ (form.videoFile.vars.errors|length > 0 ? ' input-error' : '')
          }
      }) }}
      {{ form_errors(form.videoFile) }}
      <small class="form-text text-muted mt-2 d-block">
          <i class="bi bi-info-circle me-1"></i>
          Max size: 4GB. Formats: MP4, WebM, OGG, MOV. Duration auto-calculated after upload.
      </small>

      {% if is_edit and lecture.processingStatus %}
          <div class="mt-2">
              <span class="badge bg-{{ lecture.processingStatus == 'completed' ? 'success' : (lecture.processingStatus == 'failed' ? 'danger' : 'warning') }}">
                  {{ ('course.lecture.status.' ~ lecture.processingStatus)|trans }}
              </span>
          </div>
      {% endif %}
  </div>
  ```

#### 3.3 Add Translation Keys
**Files:** `translations/messages.en.xliff`, `translations/messages.pt_BR.xliff`

**Add:**
```xml
<trans-unit id="course.lecture.form.video_file">
    <source>course.lecture.form.video_file</source>
    <target>Video File</target>
</trans-unit>
<trans-unit id="course.lecture.validation.invalid_video_format">
    <source>course.lecture.validation.invalid_video_format</source>
    <target>Please upload a valid video file (MP4, WebM, OGG, MOV)</target>
</trans-unit>
<trans-unit id="course.lecture.status.pending">
    <source>course.lecture.status.pending</source>
    <target>Pending Processing</target>
</trans-unit>
<trans-unit id="course.lecture.status.processing">
    <source>course.lecture.status.processing</source>
    <target>Processing...</target>
</trans-unit>
<trans-unit id="course.lecture.status.completed">
    <source>course.lecture.status.completed</source>
    <target>Ready</target>
</trans-unit>
<trans-unit id="course.lecture.status.failed">
    <source>course.lecture.status.failed</source>
    <target>Processing Failed</target>
</trans-unit>
```

**Portuguese (pt_BR):**
- Video File → Arquivo de Vídeo
- Invalid format → Por favor, envie um arquivo de vídeo válido
- Pending → Aguardando Processamento
- Processing → Processando...
- Ready → Pronto
- Failed → Falha no Processamento

#### 3.4 Add Bulk Upload Feature

**Purpose:** Allow uploading multiple video files at once from course detail page. Each file automatically creates a new lecture with proper ordering and filename as lecture name.

##### 3.4.1 Create Bulk Upload Modal Template
**File:** `templates/course/_bulk_upload_modal.html.twig`

```twig
<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--infinity-card-bg); border: 1px solid var(--infinity-border-color);">
            <div class="modal-header" style="border-bottom: 1px solid var(--infinity-border-color);">
                <h5 class="modal-title">
                    <i class="bi bi-cloud-upload me-2"></i>{{ 'course.bulk_upload.title'|trans }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Upload Instructions -->
                <div class="alert alert-info mb-4" style="background: rgba(79, 70, 229, 0.1); border: 1px solid rgba(79, 70, 229, 0.3);">
                    <i class="bi bi-info-circle me-2"></i>
                    {{ 'course.bulk_upload.instructions'|trans }}
                </div>

                <!-- Drag & Drop Area -->
                <div
                    data-controller="bulk-upload"
                    data-bulk-upload-course-id-value="{{ course.id }}"
                    data-bulk-upload-upload-url-value="{{ path('course_bulk_upload', {id: course.id}) }}"
                    class="bulk-upload-container">

                    <!-- Drop Zone -->
                    <div
                        data-bulk-upload-target="dropzone"
                        data-action="
                            drop->bulk-upload#handleDrop
                            dragover->bulk-upload#handleDragOver
                            dragleave->bulk-upload#handleDragLeave
                        "
                        class="dropzone">
                        <i class="bi bi-cloud-arrow-up display-1 text-primary"></i>
                        <h4 class="mt-3">{{ 'course.bulk_upload.drop_here'|trans }}</h4>
                        <p class="text-muted">{{ 'course.bulk_upload.or_click'|trans }}</p>
                        <button type="button"
                                class="btn infinity-btn-primary"
                                data-action="click->bulk-upload#openFileDialog">
                            <i class="bi bi-folder2-open me-2"></i>{{ 'course.bulk_upload.browse_files'|trans }}
                        </button>
                        <input
                            type="file"
                            data-bulk-upload-target="fileInput"
                            data-action="change->bulk-upload#handleFileSelect"
                            accept="video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo"
                            multiple
                            hidden>
                        <p class="text-muted mt-3 small">
                            <i class="bi bi-info-circle me-1"></i>
                            Max 4GB per file. Formats: MP4, WebM, OGG, MOV, AVI
                        </p>
                    </div>

                    <!-- Upload Progress List -->
                    <div data-bulk-upload-target="uploadList" class="upload-list mt-4" style="display: none;">
                        <h6 class="mb-3">{{ 'course.bulk_upload.uploading'|trans }}</h6>
                        <div data-bulk-upload-target="uploadItems"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="border-top: 1px solid var(--infinity-border-color);">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ 'button.close'|trans }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.dropzone {
    border: 3px dashed var(--infinity-border-color);
    border-radius: 16px;
    padding: 3rem 2rem;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
}

.dropzone:hover,
.dropzone.drag-over {
    border-color: var(--infinity-primary);
    background: rgba(79, 70, 229, 0.05);
}

.upload-list {
    max-height: 400px;
    overflow-y: auto;
}

.upload-item {
    background: var(--infinity-input-bg);
    border: 1px solid var(--infinity-border-color);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.5rem;
}

.upload-item .progress {
    height: 6px;
    margin-top: 0.5rem;
}
</style>
```

##### 3.4.2 Create Bulk Upload Stimulus Controller
**File:** `assets/controllers/bulk_upload_controller.js`

```javascript
import { Controller } from '@hotwired/stimulus';

/**
 * Bulk Upload Controller
 *
 * Handles multiple video file uploads with drag-and-drop
 * Each file creates a new lecture automatically
 */
export default class extends Controller {
    static targets = ['dropzone', 'fileInput', 'uploadList', 'uploadItems'];
    static values = {
        courseId: String,
        uploadUrl: String
    };

    connect() {
        console.log('Bulk Upload Controller connected');
        this.uploadQueue = [];
        this.isUploading = false;
    }

    openFileDialog() {
        this.fileInputTarget.click();
    }

    handleFileSelect(event) {
        const files = Array.from(event.target.files);
        this.processFiles(files);
    }

    handleDragOver(event) {
        event.preventDefault();
        event.stopPropagation();
        this.dropzoneTarget.classList.add('drag-over');
    }

    handleDragLeave(event) {
        event.preventDefault();
        event.stopPropagation();
        this.dropzoneTarget.classList.remove('drag-over');
    }

    handleDrop(event) {
        event.preventDefault();
        event.stopPropagation();
        this.dropzoneTarget.classList.remove('drag-over');

        const files = Array.from(event.dataTransfer.files);
        const videoFiles = files.filter(file => file.type.startsWith('video/'));

        if (videoFiles.length === 0) {
            alert('Please drop video files only');
            return;
        }

        this.processFiles(videoFiles);
    }

    processFiles(files) {
        if (files.length === 0) return;

        console.log(`Processing ${files.length} files`);

        // Show upload list
        this.uploadListTarget.style.display = 'block';
        this.dropzoneTarget.style.display = 'none';

        // Add files to queue
        files.forEach(file => {
            // Validate file size (4GB = 4 * 1024 * 1024 * 1024)
            const maxSize = 4 * 1024 * 1024 * 1024;
            if (file.size > maxSize) {
                this.showError(file.name, 'File exceeds 4GB limit');
                return;
            }

            const uploadItem = this.createUploadItem(file);
            this.uploadQueue.push({ file, uploadItem });
        });

        // Start uploading
        this.startUploading();
    }

    createUploadItem(file) {
        const item = document.createElement('div');
        item.className = 'upload-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <strong>${file.name}</strong>
                    <small class="d-block text-muted">${this.formatFileSize(file.size)}</small>
                </div>
                <span class="badge bg-info">Queued</span>
            </div>
            <div class="progress mt-2" style="display: none;">
                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
        `;

        this.uploadItemsTarget.appendChild(item);
        return item;
    }

    async startUploading() {
        if (this.isUploading || this.uploadQueue.length === 0) return;

        this.isUploading = true;

        while (this.uploadQueue.length > 0) {
            const { file, uploadItem } = this.uploadQueue.shift();
            await this.uploadFile(file, uploadItem);
        }

        this.isUploading = false;

        // Show success message and reload after short delay
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }

    async uploadFile(file, uploadItem) {
        const badge = uploadItem.querySelector('.badge');
        const progressBar = uploadItem.querySelector('.progress');
        const progressBarInner = progressBar.querySelector('.progress-bar');

        try {
            // Update status
            badge.textContent = 'Uploading...';
            badge.className = 'badge bg-warning';
            progressBar.style.display = 'block';

            // Create FormData
            const formData = new FormData();
            formData.append('videoFile', file);
            formData.append('courseId', this.courseIdValue);

            // Upload with progress tracking
            const response = await this.uploadWithProgress(
                this.uploadUrlValue,
                formData,
                (progress) => {
                    progressBarInner.style.width = `${progress}%`;
                }
            );

            if (response.ok) {
                const data = await response.json();
                badge.textContent = 'Uploaded';
                badge.className = 'badge bg-success';
                progressBarInner.style.width = '100%';
                progressBarInner.className = 'progress-bar bg-success';
                console.log('Upload successful:', data);
            } else {
                throw new Error('Upload failed');
            }

        } catch (error) {
            console.error('Upload error:', error);
            badge.textContent = 'Failed';
            badge.className = 'badge bg-danger';
            progressBar.style.display = 'none';
        }
    }

    uploadWithProgress(url, formData, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    onProgress(percentComplete);
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve({
                        ok: true,
                        json: () => Promise.resolve(JSON.parse(xhr.responseText))
                    });
                } else {
                    reject(new Error(`HTTP ${xhr.status}`));
                }
            });

            xhr.addEventListener('error', () => reject(new Error('Network error')));
            xhr.addEventListener('abort', () => reject(new Error('Upload aborted')));

            xhr.open('POST', url);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });
    }

    showError(filename, message) {
        const item = document.createElement('div');
        item.className = 'upload-item border-danger';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${filename}</strong>
                    <small class="d-block text-danger">${message}</small>
                </div>
                <span class="badge bg-danger">Error</span>
            </div>
        `;
        this.uploadItemsTarget.appendChild(item);
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
}
```

##### 3.4.3 Add Bulk Upload Button to Course Show Page
**File:** `templates/course/show.html.twig`

**Add button near the "Add Lecture" button:**

```twig
<!-- Lectures Section Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>
        <i class="bi bi-collection me-2"></i>{{ 'course.lectures'|trans }}
    </h4>
    <div>
        <!-- Bulk Upload Button -->
        <button type="button"
                class="btn btn-outline-primary me-2"
                data-bs-toggle="modal"
                data-bs-target="#bulkUploadModal">
            <i class="bi bi-cloud-upload me-2"></i>{{ 'course.bulk_upload.button'|trans }}
        </button>

        <!-- Traditional Add Lecture Button -->
        <button type="button"
                class="btn infinity-btn-primary"
                data-controller="modal-opener"
                data-action="click->modal-opener#open"
                data-modal-opener-url-value="{{ path('course_lecture_new', {courseId: course.id}) }}">
            <i class="bi bi-plus-lg me-2"></i>{{ 'course.lecture.button.add_lecture'|trans }}
        </button>
    </div>
</div>

<!-- Include Bulk Upload Modal -->
{% include 'course/_bulk_upload_modal.html.twig' %}
```

##### 3.4.4 Create Bulk Upload Controller Endpoint
**File:** `src/Controller/CourseController.php`

**Add new method:**

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/{id}/bulk-upload', name: 'course_bulk_upload', methods: ['POST'])]
public function bulkUpload(Request $request, string $id): JsonResponse
{
    $course = $this->courseRepository->find($id);

    if (!$course) {
        return $this->json(['error' => 'Course not found'], 404);
    }

    /** @var UploadedFile $uploadedFile */
    $uploadedFile = $request->files->get('videoFile');

    if (!$uploadedFile) {
        return $this->json(['error' => 'No file uploaded'], 400);
    }

    // Validate file size (4GB)
    $maxSize = 4 * 1024 * 1024 * 1024;
    if ($uploadedFile->getSize() > $maxSize) {
        return $this->json(['error' => 'File exceeds 4GB limit'], 400);
    }

    // Validate mime type
    $allowedMimeTypes = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/quicktime',
        'video/x-msvideo',
    ];

    if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
        return $this->json(['error' => 'Invalid video format'], 400);
    }

    try {
        // Get next view order (continue from last lecture)
        $lastLecture = $this->entityManager
            ->getRepository(CourseLecture::class)
            ->findOneBy(
                ['course' => $course],
                ['viewOrder' => 'DESC']
            );

        $nextOrder = $lastLecture ? $lastLecture->getViewOrder() + 1 : 1;

        // Generate lecture name from filename (remove extension, clean up)
        $originalFilename = $uploadedFile->getClientOriginalName();
        $lectureName = $this->generateLectureNameFromFilename($originalFilename);

        // Create new lecture
        $lecture = new CourseLecture();
        $lecture->setCourse($course);
        $lecture->setName($lectureName);
        $lecture->setViewOrder($nextOrder);
        $lecture->setProcessingStatus('pending');

        // Handle file upload using VichUploader
        $lecture->setVideoFile($uploadedFile);

        $this->entityManager->persist($lecture);
        $this->entityManager->flush();

        // Dispatch video processing message
        if ($lecture->getVideoFileName()) {
            $originalPath = sprintf(
                '%s/var/videos/originals/%s',
                $this->getParameter('kernel.project_dir'),
                $lecture->getVideoFileName()
            );

            $this->messageBus->dispatch(
                new ProcessVideoMessage(
                    $lecture->getId()->toString(),
                    $originalPath
                )
            );
        }

        return $this->json([
            'success' => true,
            'lecture' => [
                'id' => $lecture->getId()->toString(),
                'name' => $lecture->getName(),
                'viewOrder' => $lecture->getViewOrder(),
            ]
        ]);

    } catch (\Exception $e) {
        $this->logger->error('Bulk upload failed', [
            'error' => $e->getMessage(),
            'file' => $uploadedFile->getClientOriginalName()
        ]);

        return $this->json([
            'error' => 'Upload failed: ' . $e->getMessage()
        ], 500);
    }
}

private function generateLectureNameFromFilename(string $filename): string
{
    // Remove extension
    $name = pathinfo($filename, PATHINFO_FILENAME);

    // Replace common separators with spaces
    $name = str_replace(['_', '-', '.'], ' ', $name);

    // Remove numbers and common prefixes (Lecture 01, Video 02, etc.)
    $name = preg_replace('/^(lecture|video|aula|lesson|chapter)\s*\d+\s*/i', '', $name);

    // Clean up multiple spaces
    $name = preg_replace('/\s+/', ' ', $name);

    // Capitalize first letter of each word
    $name = ucwords(trim($name));

    // Limit length
    if (strlen($name) > 255) {
        $name = substr($name, 0, 255);
    }

    return $name ?: 'Untitled Lecture';
}
```

##### 3.4.5 Add Translation Keys for Bulk Upload
**Files:** `translations/messages.en.xliff`, `translations/messages.pt_BR.xliff`

**English (en.xliff):**
```xml
<trans-unit id="course.bulk_upload.button">
    <source>course.bulk_upload.button</source>
    <target>Bulk Upload</target>
</trans-unit>
<trans-unit id="course.bulk_upload.title">
    <source>course.bulk_upload.title</source>
    <target>Upload Multiple Videos</target>
</trans-unit>
<trans-unit id="course.bulk_upload.instructions">
    <source>course.bulk_upload.instructions</source>
    <target>Upload multiple video files at once. Each file will create a new lecture automatically with the filename as the lecture name. You can edit lecture names later.</target>
</trans-unit>
<trans-unit id="course.bulk_upload.drop_here">
    <source>course.bulk_upload.drop_here</source>
    <target>Drag and drop video files here</target>
</trans-unit>
<trans-unit id="course.bulk_upload.or_click">
    <source>course.bulk_upload.or_click</source>
    <target>or click the button below to browse</target>
</trans-unit>
<trans-unit id="course.bulk_upload.browse_files">
    <source>course.bulk_upload.browse_files</source>
    <target>Browse Files</target>
</trans-unit>
<trans-unit id="course.bulk_upload.uploading">
    <source>course.bulk_upload.uploading</source>
    <target>Upload Progress</target>
</trans-unit>
```

**Portuguese (pt_BR.xliff):**
```xml
<trans-unit id="course.bulk_upload.button">
    <source>course.bulk_upload.button</source>
    <target>Upload em Massa</target>
</trans-unit>
<trans-unit id="course.bulk_upload.title">
    <source>course.bulk_upload.title</source>
    <target>Enviar Múltiplos Vídeos</target>
</trans-unit>
<trans-unit id="course.bulk_upload.instructions">
    <source>course.bulk_upload.instructions</source>
    <target>Envie múltiplos arquivos de vídeo de uma vez. Cada arquivo criará uma nova aula automaticamente com o nome do arquivo como nome da aula. Você pode editar os nomes das aulas depois.</target>
</trans-unit>
<trans-unit id="course.bulk_upload.drop_here">
    <source>course.bulk_upload.drop_here</source>
    <target>Arraste e solte os vídeos aqui</target>
</trans-unit>
<trans-unit id="course.bulk_upload.or_click">
    <source>course.bulk_upload.or_click</source>
    <target>ou clique no botão abaixo para selecionar</target>
</trans-unit>
<trans-unit id="course.bulk_upload.browse_files">
    <source>course.bulk_upload.browse_files</source>
    <target>Selecionar Arquivos</target>
</trans-unit>
<trans-unit id="course.bulk_upload.uploading">
    <source>course.bulk_upload.uploading</source>
    <target>Progresso do Upload</target>
</trans-unit>
```

---

### 4. Remove Frontend Duration Detection

#### 4.1 Delete Video Duration Controller
**File:** `assets/controllers/video_duration_controller.js`

**Action:** Delete this file (no longer needed)

#### 4.2 Update Form Controller Reference
**File:** `templates/course/_lecture_form_modal.html.twig`

**Change:**
```twig
<!-- FROM -->
data-controller="form-navigation video-duration"

<!-- TO -->
data-controller="form-navigation"
```

#### 4.3 Remove Data Attributes from FormType
**File:** `src/Form/CourseLectureFormType.php`

**Remove from videoFile field:**
- `data-video-duration-target`
- `data-action` (video-duration related)

---

### 5. Symfony Messenger Setup

#### 5.1 Install Messenger Component
**Command:**
```bash
docker-compose exec app composer require symfony/messenger
```

#### 5.2 Configure Messenger Transports
**File:** `config/packages/messenger.yaml`

```yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            async:
                dsn: 'redis://redis:6379/messages'
                options:
                    stream_max_entries: 0 # unlimited
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2

            failed: 'doctrine://default?queue_name=failed'

        routing:
            'App\Message\ProcessVideoMessage': async
```

#### 5.3 Create ProcessVideoMessage
**File:** `src/Message/ProcessVideoMessage.php`

```php
<?php

declare(strict_types=1);

namespace App\Message;

class ProcessVideoMessage
{
    public function __construct(
        private readonly string $lectureId,
        private readonly string $originalFilePath
    ) {}

    public function getLectureId(): string
    {
        return $this->lectureId;
    }

    public function getOriginalFilePath(): string
    {
        return $this->originalFilePath;
    }
}
```

---

### 6. Video Processing Handler

#### 6.1 Create ProcessVideoHandler
**File:** `src/MessageHandler/ProcessVideoHandler.php`

```php
<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProcessVideoMessage;
use App\Repository\CourseLectureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessVideoHandler
{
    private const HLS_BASE_PATH = '/var/videos/hls';
    private const HLS_SEGMENT_DURATION = 6; // seconds

    public function __construct(
        private readonly CourseLectureRepository $lectureRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(ProcessVideoMessage $message): void
    {
        $lecture = $this->lectureRepository->find($message->getLectureId());

        if (!$lecture) {
            $this->logger->error('Lecture not found for video processing', [
                'lectureId' => $message->getLectureId()
            ]);
            return;
        }

        try {
            // Update status to processing
            $lecture->setProcessingStatus('processing');
            $this->entityManager->flush();

            $originalFile = $message->getOriginalFilePath();
            $outputDir = self::HLS_BASE_PATH . '/' . $lecture->getId()->toString();

            // Create output directory
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Extract video duration FIRST
            $duration = $this->extractDuration($originalFile);

            // Convert to HLS with multiple qualities
            $this->convertToHLS($originalFile, $outputDir);

            // Create master playlist
            $this->createMasterPlaylist($outputDir);

            // Update lecture
            $lecture->setVideoPath('/videos/hls/' . $lecture->getId()->toString() . '/master.m3u8');
            $lecture->setLengthSeconds($duration);
            $lecture->setProcessingStatus('completed');
            $lecture->setProcessedAt(new \DateTimeImmutable());
            $lecture->setProcessingError(null);

            $this->entityManager->flush();

            // Update parent course total length
            $course = $lecture->getCourse();
            if ($course) {
                $course->calculateTotalLengthSeconds();
                $this->entityManager->flush();
            }

            $this->logger->info('Video processing completed', [
                'lectureId' => $lecture->getId()->toString(),
                'duration' => $duration
            ]);

        } catch (\Exception $e) {
            $lecture->setProcessingStatus('failed');
            $lecture->setProcessingError($e->getMessage());
            $this->entityManager->flush();

            $this->logger->error('Video processing failed', [
                'lectureId' => $lecture->getId()->toString(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function extractDuration(string $file): int
    {
        $command = sprintf(
            'ffprobe -v error -show_entries format=duration ' .
            '-of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($file)
        );

        $output = shell_exec($command);

        if ($output === null) {
            throw new \RuntimeException('Failed to extract video duration');
        }

        $duration = (float) trim($output);

        if ($duration <= 0) {
            throw new \RuntimeException('Invalid video duration: ' . $duration);
        }

        return (int) round($duration);
    }

    private function convertToHLS(string $inputFile, string $outputDir): void
    {
        // Get video resolution to determine which qualities to generate
        $resolution = $this->getVideoResolution($inputFile);
        $qualities = $this->determineQualities($resolution);

        foreach ($qualities as $quality => $config) {
            $this->generateHLSQuality($inputFile, $outputDir, $quality, $config);
        }
    }

    private function getVideoResolution(string $file): array
    {
        $command = sprintf(
            'ffprobe -v error -select_streams v:0 ' .
            '-show_entries stream=width,height ' .
            '-of csv=s=x:p=0 %s 2>&1',
            escapeshellarg($file)
        );

        $output = shell_exec($command);

        if ($output === null) {
            return ['width' => 1920, 'height' => 1080]; // Default to 1080p
        }

        [$width, $height] = explode('x', trim($output));

        return [
            'width' => (int) $width,
            'height' => (int) $height
        ];
    }

    private function determineQualities(array $resolution): array
    {
        $sourceHeight = $resolution['height'];

        $allQualities = [
            '360p' => [
                'width' => 640,
                'height' => 360,
                'videoBitrate' => '800k',
                'audioBitrate' => '64k',
                'bandwidth' => 800000
            ],
            '480p' => [
                'width' => 854,
                'height' => 480,
                'videoBitrate' => '1400k',
                'audioBitrate' => '96k',
                'bandwidth' => 1400000
            ],
            '720p' => [
                'width' => 1280,
                'height' => 720,
                'videoBitrate' => '2800k',
                'audioBitrate' => '128k',
                'bandwidth' => 2800000
            ],
            '1080p' => [
                'width' => 1920,
                'height' => 1080,
                'videoBitrate' => '5000k',
                'audioBitrate' => '192k',
                'bandwidth' => 5000000
            ],
        ];

        // Only generate qualities up to source resolution
        $qualities = [];
        foreach ($allQualities as $name => $config) {
            if ($config['height'] <= $sourceHeight) {
                $qualities[$name] = $config;
            }
        }

        // Always have at least 360p
        if (empty($qualities)) {
            $qualities['360p'] = $allQualities['360p'];
        }

        return $qualities;
    }

    private function generateHLSQuality(
        string $inputFile,
        string $outputDir,
        string $quality,
        array $config
    ): void {
        $command = sprintf(
            'ffmpeg -i %s ' .
            '-vf scale=%d:%d ' .
            '-c:v libx264 -preset medium -crf 23 -b:v %s -maxrate %s -bufsize %s ' .
            '-c:a aac -b:a %s -ac 2 ' .
            '-hls_time %d ' .
            '-hls_playlist_type vod ' .
            '-hls_segment_type mpegts ' .
            '-hls_segment_filename %s ' .
            '-f hls %s 2>&1',
            escapeshellarg($inputFile),
            $config['width'],
            $config['height'],
            $config['videoBitrate'],
            $config['videoBitrate'],
            $config['videoBitrate'],
            $config['audioBitrate'],
            self::HLS_SEGMENT_DURATION,
            escapeshellarg($outputDir . '/' . $quality . '_%03d.ts'),
            escapeshellarg($outputDir . '/' . $quality . '.m3u8')
        );

        $this->logger->info('Generating HLS quality', [
            'quality' => $quality,
            'command' => $command
        ]);

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException(
                "FFmpeg conversion failed for {$quality}: " . implode("\n", $output)
            );
        }
    }

    private function createMasterPlaylist(string $outputDir): void
    {
        $playlists = glob($outputDir . '/*.m3u8');

        if (empty($playlists)) {
            throw new \RuntimeException('No HLS playlists generated');
        }

        $qualities = [
            '360p' => ['bandwidth' => 800000, 'resolution' => '640x360'],
            '480p' => ['bandwidth' => 1400000, 'resolution' => '854x480'],
            '720p' => ['bandwidth' => 2800000, 'resolution' => '1280x720'],
            '1080p' => ['bandwidth' => 5000000, 'resolution' => '1920x1080'],
        ];

        $content = "#EXTM3U\n#EXT-X-VERSION:3\n";

        foreach ($playlists as $playlist) {
            $filename = basename($playlist);
            $quality = str_replace('.m3u8', '', $filename);

            if ($quality === 'master') {
                continue; // Skip the master playlist itself
            }

            if (isset($qualities[$quality])) {
                $content .= sprintf(
                    "#EXT-X-STREAM-INF:BANDWIDTH=%d,RESOLUTION=%s\n%s\n",
                    $qualities[$quality]['bandwidth'],
                    $qualities[$quality]['resolution'],
                    $filename
                );
            }
        }

        file_put_contents($outputDir . '/master.m3u8', $content);
    }
}
```

---

### 7. Update Course Controller

#### 7.1 Dispatch Video Processing Message
**File:** `src/Controller/CourseController.php`

**Update `newLecture` and `editLecture` methods:**

```php
use App\Message\ProcessVideoMessage;
use Symfony\Component\Messenger\MessageBusInterface;

public function __construct(
    // ... existing dependencies
    private readonly MessageBusInterface $messageBus
) {}

#[Route('/{courseId}/lecture/new', name: 'course_lecture_new')]
public function newLecture(Request $request, string $courseId): Response
{
    // ... existing code ...

    if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->persist($lecture);
        $this->entityManager->flush();

        // Dispatch video processing if file was uploaded
        if ($lecture->getVideoFileName()) {
            $originalPath = sprintf(
                '%s/var/videos/originals/%s',
                $this->getParameter('kernel.project_dir'),
                $lecture->getVideoFileName()
            );

            $this->messageBus->dispatch(
                new ProcessVideoMessage(
                    $lecture->getId()->toString(),
                    $originalPath
                )
            );

            $this->addFlash('info', 'Video is being processed in background. Duration will be calculated automatically.');
        }

        $this->addFlash('success', 'course.lecture.flash.created_successfully');
        return $this->redirectToRoute('course_show', ['id' => $courseId]);
    }

    // ... rest of code ...
}

#[Route('/{courseId}/lecture/{lectureId}/edit', name: 'course_lecture_edit')]
public function editLecture(Request $request, string $courseId, string $lectureId): Response
{
    // ... existing code ...

    if ($form->isSubmitted() && $form->isValid()) {
        // Check if new video was uploaded
        $newVideoUploaded = $lecture->getVideoFile() !== null;

        $this->entityManager->flush();

        // Dispatch video processing if new file was uploaded
        if ($newVideoUploaded && $lecture->getVideoFileName()) {
            $lecture->setProcessingStatus('pending');
            $this->entityManager->flush();

            $originalPath = sprintf(
                '%s/var/videos/originals/%s',
                $this->getParameter('kernel.project_dir'),
                $lecture->getVideoFileName()
            );

            $this->messageBus->dispatch(
                new ProcessVideoMessage(
                    $lecture->getId()->toString(),
                    $originalPath
                )
            );

            $this->addFlash('info', 'Video is being processed in background.');
        }

        $this->addFlash('success', 'course.lecture.flash.updated_successfully');
        return $this->redirectToRoute('course_show', ['id' => $courseId]);
    }

    // ... rest of code ...
}
```

---

### 8. Nginx Configuration for HLS Serving

#### 8.1 Update Nginx Config
**File:** `nginx/conf/default.conf`

**Add location block:**

```nginx
# Serve HLS video segments
location /videos/hls/ {
    alias /var/videos/hls/;

    # CORS headers for video player
    add_header Access-Control-Allow-Origin * always;
    add_header Access-Control-Allow-Methods 'GET, HEAD, OPTIONS' always;
    add_header Access-Control-Allow-Headers 'Range,Content-Type' always;
    add_header Access-Control-Expose-Headers 'Content-Length,Content-Range' always;

    # Cache control for video segments
    add_header Cache-Control "public, max-age=31536000, immutable" always;

    # Support byte-range requests
    add_header Accept-Ranges bytes always;

    # MIME types for HLS
    types {
        application/vnd.apple.mpegurl m3u8;
        video/mp2t ts;
    }

    # Security: Only allow authenticated users
    # Uncomment when authentication endpoint is ready
    # auth_request /api/video/auth;

    # Enable CORS preflight
    if ($request_method = 'OPTIONS') {
        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Methods 'GET, HEAD, OPTIONS';
        add_header Access-Control-Allow-Headers 'Range,Content-Type';
        add_header Access-Control-Max-Age 1728000;
        add_header Content-Type 'text/plain; charset=utf-8';
        add_header Content-Length 0;
        return 204;
    }
}

# Video authentication endpoint (for future use)
location /api/video/auth {
    internal;
    proxy_pass http://app:8000/api/video/auth;
    proxy_pass_request_body off;
    proxy_set_header Content-Length "";
    proxy_set_header X-Original-URI $request_uri;
}
```

#### 8.2 Update Docker Compose Volume
**File:** `docker-compose.yml`

**Add volume mount for nginx:**

```yaml
nginx:
  volumes:
    - ./nginx/conf:/etc/nginx/conf.d
    - ./nginx/ssl:/etc/nginx/ssl
    - ./app/var/videos:/var/videos:ro  # Read-only access to videos
```

---

### 9. Frontend Player Installation

#### 9.1 Install Plyr.io
**Command:**
```bash
docker-compose exec app php bin/console importmap:require plyr
docker-compose exec app php bin/console importmap:require hls.js
```

Or manually add to `importmap.php`:
```php
'plyr' => [
    'version' => '3.8.3',
],
'hls.js' => [
    'version' => '1.5.22',
],
```

#### 9.2 Import Plyr CSS
**File:** `assets/app.js`

**Add:**
```javascript
import 'plyr/dist/plyr.css';
```

---

### 10. Student Video Player Controller

#### 10.1 Create Stimulus Controller
**File:** `assets/controllers/student_video_controller.js`

```javascript
import { Controller } from '@hotwired/stimulus';
import Plyr from 'plyr';
import Hls from 'hls.js';

/**
 * Student Video Player with Progress Tracking
 *
 * Tracks:
 * - Watch time
 * - Last position (resume playback)
 * - Completion percentage
 * - Milestones (25%, 50%, 75%, 100%)
 */
export default class extends Controller {
    static values = {
        videoUrl: String,
        lectureId: String,
        savedPosition: { type: Number, default: 0 },
        saveProgressUrl: String,
        markCompleteUrl: String
    };

    connect() {
        console.log('Student Video Player connected');

        // Initialize HLS and Plyr
        this.initializePlayer();

        // Track progress every 5 seconds
        this.trackingInterval = setInterval(() => {
            this.saveProgress();
        }, 5000);

        // Track milestones
        this.milestones = {
            25: false,
            50: false,
            75: false,
            100: false
        };
    }

    disconnect() {
        clearInterval(this.trackingInterval);
        this.saveProgress(); // Save on exit

        if (this.player) {
            this.player.destroy();
        }

        if (this.hls) {
            this.hls.destroy();
        }
    }

    initializePlayer() {
        const video = this.element;

        // Check if HLS is supported
        if (Hls.isSupported()) {
            this.hls = new Hls({
                maxBufferLength: 30,
                maxMaxBufferLength: 600,
                maxBufferSize: 60 * 1000 * 1000, // 60 MB
            });

            this.hls.loadSource(this.videoUrlValue);
            this.hls.attachMedia(video);

            this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                console.log('HLS manifest loaded');
                this.initializePlyr();
            });

            this.hls.on(Hls.Events.ERROR, (event, data) => {
                console.error('HLS Error:', data);
                if (data.fatal) {
                    switch (data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            console.error('Network error, trying to recover...');
                            this.hls.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            console.error('Media error, trying to recover...');
                            this.hls.recoverMediaError();
                            break;
                        default:
                            console.error('Fatal error, cannot recover');
                            this.hls.destroy();
                            break;
                    }
                }
            });
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            // Native HLS support (Safari)
            video.src = this.videoUrlValue;
            this.initializePlyr();
        } else {
            console.error('HLS is not supported in this browser');
        }
    }

    initializePlyr() {
        this.player = new Plyr(this.element, {
            controls: [
                'play-large',
                'play',
                'progress',
                'current-time',
                'duration',
                'mute',
                'volume',
                'settings',
                'pip',
                'fullscreen'
            ],
            settings: ['quality', 'speed'],
            quality: {
                default: 720,
                options: [360, 480, 720, 1080],
                forced: true,
                onChange: (quality) => {
                    console.log('Quality changed to:', quality);
                }
            },
            speed: {
                selected: 1,
                options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2]
            },
            keyboard: { focused: true, global: true },
            tooltips: { controls: true, seek: true }
        });

        // Resume from saved position
        if (this.savedPositionValue > 0) {
            this.player.currentTime = this.savedPositionValue;
            console.log('Resuming from:', this.savedPositionValue, 'seconds');
        }

        // Event listeners
        this.player.on('timeupdate', () => this.onTimeUpdate());
        this.player.on('ended', () => this.onEnded());
        this.player.on('play', () => console.log('Video playing'));
        this.player.on('pause', () => console.log('Video paused'));

        console.log('Plyr initialized successfully');
    }

    onTimeUpdate() {
        if (!this.player.duration) return;

        const percentage = (this.player.currentTime / this.player.duration) * 100;

        // Check milestones
        [25, 50, 75, 100].forEach(milestone => {
            if (percentage >= milestone && !this.milestones[milestone]) {
                this.milestones[milestone] = true;
                this.trackMilestone(milestone);
            }
        });
    }

    onEnded() {
        console.log('Video ended');
        this.markComplete();
    }

    async saveProgress() {
        if (!this.player || !this.player.duration) return;

        const currentTime = Math.floor(this.player.currentTime);
        const duration = Math.floor(this.player.duration);
        const percentage = Math.min(100, (currentTime / duration) * 100);

        try {
            const response = await fetch(this.saveProgressUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lectureId: this.lectureIdValue,
                    watchedSeconds: currentTime,
                    lastPosition: currentTime,
                    completionPercentage: percentage,
                    reached25Percent: this.milestones[25],
                    reached50Percent: this.milestones[50],
                    reached75Percent: this.milestones[75]
                })
            });

            if (response.ok) {
                console.log('Progress saved:', percentage.toFixed(1) + '%');
            }
        } catch (error) {
            console.error('Failed to save progress:', error);
        }
    }

    async markComplete() {
        try {
            const response = await fetch(this.markCompleteUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lectureId: this.lectureIdValue
                })
            });

            if (response.ok) {
                console.log('Lecture marked as complete');
                this.milestones[100] = true;
            }
        } catch (error) {
            console.error('Failed to mark complete:', error);
        }
    }

    trackMilestone(percentage) {
        console.log(`Milestone reached: ${percentage}%`);
        // Could send analytics event here
    }
}
```

---

### 11. Student Progress API Controller

#### 11.1 Create StudentProgressController
**File:** `src/Controller/StudentProgressController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\StudentCourse;
use App\Entity\StudentLecture;
use App\Repository\CourseLectureRepository;
use App\Repository\StudentCourseRepository;
use App\Repository\StudentLectureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/student')]
class StudentProgressController extends AbstractController
{
    public function __construct(
        private readonly StudentLectureRepository $studentLectureRepository,
        private readonly StudentCourseRepository $studentCourseRepository,
        private readonly CourseLectureRepository $lectureRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('/lecture-progress', name: 'student_lecture_progress', methods: ['POST'])]
    public function saveProgress(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['lectureId'])) {
            return $this->json(['error' => 'Missing lectureId'], 400);
        }

        $lecture = $this->lectureRepository->find($data['lectureId']);

        if (!$lecture) {
            return $this->json(['error' => 'Lecture not found'], 404);
        }

        $student = $this->getUser();

        if (!$student) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        // Verify student is enrolled in the course
        $enrollment = $this->studentCourseRepository->findOneBy([
            'student' => $student,
            'course' => $lecture->getCourse()
        ]);

        if (!$enrollment) {
            return $this->json(['error' => 'Not enrolled in this course'], 403);
        }

        // Find or create StudentLecture record
        $progress = $this->studentLectureRepository->findOneBy([
            'student' => $student,
            'lecture' => $lecture
        ]);

        if (!$progress) {
            $progress = new StudentLecture();
            $progress->setStudent($student);
            $progress->setLecture($lecture);
            $this->entityManager->persist($progress);
        }

        // Update progress
        if (isset($data['watchedSeconds'])) {
            $progress->setWatchedSeconds((int) $data['watchedSeconds']);
        }

        if (isset($data['lastPosition'])) {
            $progress->setLastPositionSeconds((int) $data['lastPosition']);
        }

        if (isset($data['completionPercentage'])) {
            $progress->setCompletionPercentage((float) $data['completionPercentage']);
        }

        if (isset($data['reached25Percent'])) {
            $progress->setReached25Percent((bool) $data['reached25Percent']);
        }

        if (isset($data['reached50Percent'])) {
            $progress->setReached50Percent((bool) $data['reached50Percent']);
        }

        if (isset($data['reached75Percent'])) {
            $progress->setReached75Percent((bool) $data['reached75Percent']);
        }

        $progress->setLastWatchedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        // Update course progress
        $this->updateCourseProgress($enrollment);

        return $this->json([
            'status' => 'success',
            'progress' => [
                'completion' => $progress->getCompletionPercentage(),
                'lastPosition' => $progress->getLastPositionSeconds()
            ]
        ]);
    }

    #[Route('/lecture-complete', name: 'student_lecture_complete', methods: ['POST'])]
    public function markComplete(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['lectureId'])) {
            return $this->json(['error' => 'Missing lectureId'], 400);
        }

        $lecture = $this->lectureRepository->find($data['lectureId']);

        if (!$lecture) {
            return $this->json(['error' => 'Lecture not found'], 404);
        }

        $student = $this->getUser();

        if (!$student) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $progress = $this->studentLectureRepository->findOneBy([
            'student' => $student,
            'lecture' => $lecture
        ]);

        if (!$progress) {
            $progress = new StudentLecture();
            $progress->setStudent($student);
            $progress->setLecture($lecture);
            $this->entityManager->persist($progress);
        }

        if (!$progress->isCompleted()) {
            $progress->setCompleted(true);
            $progress->setCompletedAt(new \DateTimeImmutable());
            $progress->setCompletionPercentage(100);

            $this->entityManager->flush();

            // Check if course is complete
            $enrollment = $this->studentCourseRepository->findOneBy([
                'student' => $student,
                'course' => $lecture->getCourse()
            ]);

            if ($enrollment) {
                $this->updateCourseProgress($enrollment);
                $this->checkCourseCompletion($enrollment);
            }
        }

        return $this->json(['status' => 'completed']);
    }

    private function updateCourseProgress(StudentCourse $enrollment): void
    {
        $course = $enrollment->getCourse();
        $student = $enrollment->getStudent();

        $allLectures = $course->getLectures();
        $totalLectures = count($allLectures);

        if ($totalLectures === 0) {
            return;
        }

        $completedCount = 0;
        $totalProgress = 0.0;

        foreach ($allLectures as $lecture) {
            $progress = $this->studentLectureRepository->findOneBy([
                'student' => $student,
                'lecture' => $lecture
            ]);

            if ($progress) {
                $totalProgress += $progress->getCompletionPercentage();
                if ($progress->isCompleted()) {
                    $completedCount++;
                }
            }
        }

        $overallProgress = $totalProgress / $totalLectures;
        $enrollment->setProgressPercentage($overallProgress);

        $this->entityManager->flush();
    }

    private function checkCourseCompletion(StudentCourse $enrollment): void
    {
        $course = $enrollment->getCourse();
        $student = $enrollment->getStudent();

        $allLectures = $course->getLectures();
        $allCompleted = true;

        foreach ($allLectures as $lecture) {
            $progress = $this->studentLectureRepository->findOneBy([
                'student' => $student,
                'lecture' => $lecture
            ]);

            if (!$progress || !$progress->isCompleted()) {
                $allCompleted = false;
                break;
            }
        }

        if ($allCompleted && !$enrollment->getCompletedAt()) {
            $enrollment->setCompletedAt(new \DateTimeImmutable());
            $enrollment->setProgressPercentage(100);
            $this->entityManager->flush();
        }
    }
}
```

---

### 12. Student Lecture View Template

#### 12.1 Create Student Lecture Template
**File:** `templates/student/lecture.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ lecture.name }} - {{ course.name }}{% endblock %}

{% block body %}
<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ path('student_courses') }}">My Courses</a></li>
            <li class="breadcrumb-item"><a href="{{ path('student_course', {id: course.id}) }}">{{ course.name }}</a></li>
            <li class="breadcrumb-item active">{{ lecture.name }}</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Video Player Column -->
        <div class="col-lg-9">
            <div class="infinity-card p-0 mb-4">
                {% if lecture.processingStatus == 'completed' and lecture.videoPath %}
                    <!-- Plyr Video Player -->
                    <video
                        data-controller="student-video"
                        data-student-video-video-url-value="{{ lecture.videoPath }}"
                        data-student-video-lecture-id-value="{{ lecture.id }}"
                        data-student-video-saved-position-value="{{ studentProgress.lastPositionSeconds ?? 0 }}"
                        data-student-video-save-progress-url-value="{{ path('student_lecture_progress') }}"
                        data-student-video-mark-complete-url-value="{{ path('student_lecture_complete') }}"
                        playsinline
                        crossorigin="anonymous">
                    </video>
                {% elseif lecture.processingStatus == 'processing' %}
                    <div class="p-5 text-center">
                        <i class="bi bi-hourglass-split fs-1 text-warning"></i>
                        <h4 class="mt-3">Video is being processed...</h4>
                        <p class="text-muted">This usually takes a few minutes. Please refresh the page shortly.</p>
                    </div>
                {% elseif lecture.processingStatus == 'failed' %}
                    <div class="p-5 text-center">
                        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                        <h4 class="mt-3">Video processing failed</h4>
                        <p class="text-muted">Please contact support.</p>
                    </div>
                {% else %}
                    <div class="p-5 text-center">
                        <i class="bi bi-film fs-1 text-muted"></i>
                        <h4 class="mt-3">No video available</h4>
                        <p class="text-muted">Video is pending upload.</p>
                    </div>
                {% endif %}
            </div>

            <!-- Lecture Info -->
            <div class="infinity-card p-4">
                <h2 class="text-gradient mb-3">{{ lecture.name }}</h2>

                {% if lecture.description %}
                    <p class="text-secondary">{{ lecture.description }}</p>
                {% endif %}

                <div class="row mt-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Duration</small>
                        <strong>{{ lecture.lengthSeconds ? (lecture.lengthSeconds / 60)|round ~ ' minutes' : 'N/A' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Your Progress</small>
                        <strong>{{ studentProgress ? studentProgress.completionPercentage|round ~ '%' : '0%' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Status</small>
                        <strong>
                            {% if studentProgress and studentProgress.completed %}
                                <span class="badge bg-success">Completed</span>
                            {% else %}
                                <span class="badge bg-warning">In Progress</span>
                            {% endif %}
                        </strong>
                    </div>
                </div>

                <!-- Progress Bar -->
                {% if studentProgress %}
                <div class="mt-4">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success"
                             role="progressbar"
                             style="width: {{ studentProgress.completionPercentage }}%"
                             aria-valuenow="{{ studentProgress.completionPercentage }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
                {% endif %}
            </div>
        </div>

        <!-- Course Navigation Sidebar -->
        <div class="col-lg-3">
            <div class="infinity-card p-3">
                <h5 class="mb-3">Course Lectures</h5>

                <div class="list-group">
                    {% for courseLecture in course.lectures|sort((a, b) => a.viewOrder <=> b.viewOrder) %}
                        <a href="{{ path('student_lecture', {courseId: course.id, lectureId: courseLecture.id}) }}"
                           class="list-group-item list-group-item-action {{ courseLecture.id == lecture.id ? 'active' : '' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>
                                    <small class="text-muted">{{ courseLecture.viewOrder }}.</small>
                                    {{ courseLecture.name }}
                                </span>
                                {% set lectureProgress = studentLectureProgress[courseLecture.id.toString] ?? null %}
                                {% if lectureProgress and lectureProgress.completed %}
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                {% endif %}
                            </div>
                        </a>
                    {% endfor %}
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

---

### 13. Messenger Worker Setup

#### 13.1 Create Messenger Worker Service
**File:** Add to `docker-compose.yml`

```yaml
services:
  # ... existing services ...

  messenger_worker:
    build:
      context: ./app
      dockerfile: ../Dockerfile
    container_name: infinity_messenger_worker
    command: php bin/console messenger:consume async --time-limit=3600 --memory-limit=512M
    volumes:
      - ./app:/app
      - ./app/var/videos:/var/videos
    depends_on:
      - database
      - redis
    environment:
      DATABASE_URL: postgresql://infinity_user:InfinitySecure2025!@database:5432/infinity_db
      REDIS_URL: redis://redis:6379/0
    networks:
      - infinity-network
    restart: unless-stopped
```

**Or run manually for testing:**
```bash
docker-compose exec app php bin/console messenger:consume async -vv
```

---

### 14. Video Authentication Endpoint (Future)

#### 14.1 Create VideoAuthController
**File:** `src/Controller/VideoAuthController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VideoAuthController extends AbstractController
{
    /**
     * Nginx auth_request endpoint
     * Returns 200 if authenticated, 403 if not
     */
    #[Route('/api/video/auth', name: 'video_auth')]
    public function auth(Request $request): Response
    {
        // Check if user is authenticated
        if (!$this->getUser()) {
            return new Response('', 403);
        }

        // Extract video path from X-Original-URI header
        $originalUri = $request->headers->get('X-Original-URI');

        // Parse video ID from path: /videos/hls/{lectureId}/master.m3u8
        if (preg_match('#/videos/hls/([^/]+)/#', $originalUri, $matches)) {
            $lectureId = $matches[1];

            // TODO: Verify student is enrolled in course containing this lecture
            // For now, allow all authenticated users
        }

        return new Response('', 200);
    }
}
```

**Note:** Initially, leave Nginx authentication commented out. Enable after testing.

---

### 15. Course Show Page Updates

#### 15.1 Add Processing Status Indicator
**File:** `templates/course/show.html.twig`

**Update lecture display to show processing status:**

```twig
{% for lecture in lectures %}
    <div class="col-lg-6 mb-3">
        <div class="infinity-card p-3">
            <!-- ... existing lecture display ... -->

            <!-- Add processing status badge -->
            {% if lecture.processingStatus == 'processing' %}
                <span class="badge bg-warning">
                    <i class="bi bi-hourglass-split me-1"></i>Processing...
                </span>
            {% elseif lecture.processingStatus == 'failed' %}
                <span class="badge bg-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>Failed
                </span>
            {% elseif lecture.processingStatus == 'completed' %}
                <span class="badge bg-success">
                    <i class="bi bi-check-circle me-1"></i>Ready
                </span>
            {% else %}
                <span class="badge bg-secondary">
                    <i class="bi bi-clock me-1"></i>Pending
                </span>
            {% endif %}
        </div>
    </div>
{% endfor %}
```

---

### 16. Error Handling & Logging

#### 16.1 Configure Logging
**File:** `config/packages/monolog.yaml`

**Add video processing channel:**

```yaml
monolog:
    channels:
        - deprecation
        - security
        - business
        - performance
        - video_processing  # Add this

when@dev:
    monolog:
        handlers:
            video:
                type: stream
                path: "%kernel.logs_dir%/video.log"
                level: debug
                channels: ["video_processing"]
```

#### 16.2 Update ProcessVideoHandler with Logger
**Already included in ProcessVideoHandler implementation above**

---

### 17. Testing Checklist

#### 17.1 Manual Testing Steps

1. **Upload Video**
   - Navigate to course
   - Click "Add Lecture"
   - Upload video file (< 4GB)
   - Verify flash message about background processing

2. **Check Processing**
   - Start messenger worker: `docker-compose exec app php bin/console messenger:consume async -vv`
   - Watch logs for FFmpeg output
   - Verify HLS files created in `/var/videos/hls/{lectureId}/`
   - Check lecture status changes: pending → processing → completed

3. **Verify HLS Files**
   - Check master.m3u8 exists
   - Check quality playlists (360p.m3u8, 480p.m3u8, etc.)
   - Check .ts segment files
   - Verify duration was calculated

4. **Test Video Playback**
   - Navigate to student lecture view
   - Verify video loads and plays
   - Test quality switching
   - Test playback speed
   - Verify controls work

5. **Test Progress Tracking**
   - Watch video for 30 seconds
   - Reload page
   - Verify video resumes from last position
   - Watch to 25%, check milestone
   - Complete video, verify completion status

6. **Test Error Handling**
   - Upload corrupted video file
   - Verify processing fails gracefully
   - Check error message displayed
   - Verify status shows "Failed"

---

### 18. Production Optimizations (Future)

#### 18.1 Add Thumbnails Generation
**Extend ProcessVideoHandler:**

```php
private function generateThumbnail(string $inputFile, string $outputPath): void
{
    $command = sprintf(
        'ffmpeg -i %s -ss 00:00:05 -vframes 1 -vf scale=1280:720 %s',
        escapeshellarg($inputFile),
        escapeshellarg($outputPath)
    );

    exec($command);
}
```

#### 18.2 Add CDN Integration
- Upload processed HLS files to S3/Cloudflare R2
- Update `videoPath` to point to CDN URL
- Reduces VPS bandwidth usage

#### 18.3 Add Video Analytics
- Track average watch time
- Track drop-off points
- Generate heatmaps of most-watched sections

---

## Summary

This plan implements a complete self-hosted video streaming system with:

✅ **File upload** via VichUploaderBundle (max 4GB)
✅ **Background processing** with Symfony Messenger + Redis
✅ **HLS conversion** with FFmpeg (360p, 480p, 720p, 1080p)
✅ **Automatic duration extraction** and calculation
✅ **Modern video player** with Plyr.io + HLS.js
✅ **Progress tracking** (watch time, resume, milestones)
✅ **Student enrollment** via StudentCourse entity
✅ **Course completion** tracking
✅ **Error handling** and status indicators

**Total Steps:** 18 sections covering database, upload, processing, serving, playback, and tracking.
