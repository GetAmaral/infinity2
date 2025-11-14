<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\AttachmentGenerated;
use App\Repository\AttachmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Attachment Entity
 *
 * File attachments for documents and media *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[ORM\Table(name: 'attachment')]
#[Vich\Uploadable]
class Attachment extends AttachmentGenerated
{
    /**
     * VichUploader file field (not persisted to database)
     */
    #[Vich\UploadableField(mapping: 'talk_attachments', fileNameProperty: 'filename', size: 'fileSize', mimeType: 'fileType')]
    private ?File $file = null;

    /**
     * Set file for upload
     */
    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        // Force Doctrine to update the entity if file is replaced
        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    /**
     * Get file
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Get file URL (public path)
     */
    public function getFileUrl(): ?string
    {
        if ($this->filename) {
            return '/uploads/talk_attachments/' . $this->filename;
        }

        return $this->url;
    }

    /**
     * Check if attachment is an image
     */
    public function isImage(): bool
    {
        if (!$this->fileType) {
            return false;
        }

        return str_starts_with($this->fileType, 'image/');
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->fileSize) {
            return 'Unknown size';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Get file icon based on type
     */
    public function getFileIcon(): string
    {
        if ($this->isImage()) {
            return 'bi-file-image';
        }

        if (!$this->fileType) {
            return 'bi-file-earmark';
        }

        return match (true) {
            str_contains($this->fileType, 'pdf') => 'bi-file-pdf',
            str_contains($this->fileType, 'word') || str_contains($this->fileType, 'document') => 'bi-file-word',
            str_contains($this->fileType, 'excel') || str_contains($this->fileType, 'spreadsheet') => 'bi-file-excel',
            str_contains($this->fileType, 'powerpoint') || str_contains($this->fileType, 'presentation') => 'bi-file-ppt',
            str_contains($this->fileType, 'zip') || str_contains($this->fileType, 'compressed') => 'bi-file-zip',
            str_contains($this->fileType, 'text') => 'bi-file-text',
            str_contains($this->fileType, 'video') => 'bi-file-play',
            str_contains($this->fileType, 'audio') => 'bi-file-music',
            default => 'bi-file-earmark'
        };
    }
}
