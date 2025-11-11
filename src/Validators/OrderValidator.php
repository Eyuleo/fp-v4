<?php

/**
 * Order Validator
 *
 * Validates order input data
 */
class OrderValidator
{
    private array $errors = [];

    /**
     * Validate order creation data
     *
     * @param array $data
     * @return bool
     */
    public function validateCreate(array $data): bool
    {
        $this->errors = [];

        // Validate requirements
        if (empty($data['requirements']) || strlen(trim($data['requirements'])) < 10) {
            $this->errors['requirements'] = 'Requirements must be at least 10 characters';
        }

        return empty($this->errors);
    }

    /**
     * Validate file uploads
     *
     * @param array $files
     * @return bool
     */
    public function validateFiles(array $files): bool
    {
        $this->errors = [];

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip', 'txt'];
        $maxFileSize       = 10 * 1024 * 1024; // 10MB per file
        $maxTotalSize      = 25 * 1024 * 1024; // 25MB total

        $totalSize = 0;

        foreach ($files as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $this->errors['files'] = 'File upload error occurred';
                return false;
            }

            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            // Check file size
            if ($file['size'] > $maxFileSize) {
                $this->errors['files'] = 'Each file must not exceed 10MB';
                return false;
            }

            $totalSize += $file['size'];

            // Check file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (! in_array($extension, $allowedExtensions)) {
                $this->errors['files'] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions);
                return false;
            }

            // Check MIME type
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimeTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'text/plain',
            ];

            if (! in_array($mimeType, $allowedMimeTypes)) {
                $this->errors['files'] = 'Invalid file type detected';
                return false;
            }
        }

        // Check total size
        if ($totalSize > $maxTotalSize) {
            $this->errors['files'] = 'Total file size must not exceed 25MB';
            return false;
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
