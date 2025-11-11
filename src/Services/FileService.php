<?php

/**
 * File Service
 *
 * Handles file uploads, validation, and secure downloads with signed URLs
 */
class FileService
{
    private string $uploadBasePath;
    private string $secretKey;

    // File type configurations
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', // Images
        'pdf',                       // Documents
        'doc', 'docx',               // Word documents
        'zip',                       // Archives
    ];

    private const MIME_TYPE_MAP = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'zip'  => 'application/zip',
    ];

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

                                                          // Size limits in bytes
    private const IMAGE_SIZE_LIMIT    = 10 * 1024 * 1024; // 10MB
    private const DOCUMENT_SIZE_LIMIT = 25 * 1024 * 1024; // 25MB

    public function __construct()
    {
        $this->uploadBasePath = __DIR__ . '/../../storage/uploads';
        $this->secretKey      = config('app.key') ?? 'default-secret-key-change-in-production';
    }

    /**
     * Upload a file with validation
     *
     * @param array  $file     File from $_FILES array
     * @param string $context  Context for storage (e.g., 'profiles', 'services', 'orders', 'messages')
     * @param int    $contextId ID related to the context (user_id, service_id, order_id, etc.)
     * @return array File metadata ['success' => bool, 'file' => array, 'error' => string]
     */
    public function upload(array $file, string $context, int $contextId): array
    {
        // Check for upload errors
        if (! isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error'   => $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE),
            ];
        }

        $originalName = $file['name'] ?? '';
        $tmpPath      = $file['tmp_name'] ?? '';
        $size         = $file['size'] ?? 0;

        // Validate file extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return [
                'success' => false,
                'error'   => 'File type not allowed. Allowed types: ' . implode(', ', self::ALLOWED_EXTENSIONS),
            ];
        }

        // Validate file size based on type
        $sizeLimit = in_array($extension, self::IMAGE_EXTENSIONS)
            ? self::IMAGE_SIZE_LIMIT
            : self::DOCUMENT_SIZE_LIMIT;

        if ($size > $sizeLimit) {
            $limitMB = $sizeLimit / (1024 * 1024);
            return [
                'success' => false,
                'error'   => "File size exceeds {$limitMB}MB limit",
            ];
        }

        // Validate MIME type
        $mimeValidation = $this->validateMimeType($tmpPath, $extension);

        if ($mimeValidation !== true) {
            return [
                'success' => false,
                'error'   => $mimeValidation,
            ];
        }

        // For images, verify with getimagesize()
        if (in_array($extension, self::IMAGE_EXTENSIONS)) {
            $imageInfo = @getimagesize($tmpPath);

            if ($imageInfo === false) {
                return [
                    'success' => false,
                    'error'   => 'Invalid image file',
                ];
            }
        }

        // Generate random filename with UUID
        $filename = $this->generateUniqueFilename($extension);

        // Create directory structure
        $contextDir = $this->uploadBasePath . '/' . $context . '/' . $contextId;

        if (! is_dir($contextDir)) {
            if (! mkdir($contextDir, 0755, true)) {
                return [
                    'success' => false,
                    'error'   => 'Failed to create upload directory',
                ];
            }
        }

        // Move uploaded file
        $filePath = $contextDir . '/' . $filename;

        if (! move_uploaded_file($tmpPath, $filePath)) {
            return [
                'success' => false,
                'error'   => 'Failed to save uploaded file',
            ];
        }

        // Get actual MIME type after upload
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Return file metadata
        return [
            'success' => true,
            'file'    => [
                'path'          => $context . '/' . $contextId . '/' . $filename,
                'filename'      => $filename,
                'original_name' => $originalName,
                'size'          => $size,
                'mime_type'     => $mimeType,
                'extension'     => $extension,
                'uploaded_at'   => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Upload multiple files
     *
     * @param array  $files    Files array from $_FILES
     * @param string $context  Context for storage
     * @param int    $contextId ID related to the context
     * @return array ['success' => bool, 'files' => array, 'errors' => array]
     */
    public function uploadMultiple(array $files, string $context, int $contextId): array
    {
        $uploadedFiles = [];
        $errors        = [];

        // Handle both single and multiple file uploads
        if (isset($files['name']) && is_array($files['name'])) {
            // Multiple files
            $fileCount = count($files['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];

                $result = $this->upload($file, $context, $contextId);

                if ($result['success']) {
                    $uploadedFiles[] = $result['file'];
                } else {
                    $errors[] = $result['error'];
                }
            }
        } else {
            // Single file
            $result = $this->upload($files, $context, $contextId);

            if ($result['success']) {
                $uploadedFiles[] = $result['file'];
            } else {
                $errors[] = $result['error'];
            }
        }

        return [
            'success' => count($uploadedFiles) > 0,
            'files'   => $uploadedFiles,
            'errors'  => $errors,
        ];
    }

    /**
     * Validate MIME type matches extension
     */
    private function validateMimeType(string $filePath, string $extension): string | bool
    {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Get expected MIME type for extension
        $expectedMime = self::MIME_TYPE_MAP[$extension] ?? null;

        if (! $expectedMime) {
            return 'Unknown file extension';
        }

        // Check if MIME type matches
        if ($mimeType !== $expectedMime) {
            return 'File MIME type does not match extension';
        }

        return true;
    }

    /**
     * Generate unique filename using UUID-like approach
     */
    private function generateUniqueFilename(string $extension): string
    {
        // Generate UUID v4-like identifier
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return $uuid . '.' . $extension;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
            default               => 'Unknown upload error',
        };
    }

    /**
     * Delete a file
     *
     * @param string $path Relative path from uploads directory
     * @return bool Success status
     */
    public function delete(string $path): bool
    {
        $filePath = $this->uploadBasePath . '/' . $path;

        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Check if file exists
     *
     * @param string $path Relative path from uploads directory
     * @return bool
     */
    public function exists(string $path): bool
    {
        $filePath = $this->uploadBasePath . '/' . $path;
        return file_exists($filePath) && is_file($filePath);
    }

    /**
     * Get file full path
     *
     * @param string $path Relative path from uploads directory
     * @return string
     */
    public function getFullPath(string $path): string
    {
        return $this->uploadBasePath . '/' . $path;
    }

    /**
     * Generate signed URL for secure file download
     *
     * @param string $path     Relative file path from uploads directory
     * @param int    $expiresIn Expiration time in seconds (default: 5 minutes)
     * @return string Signed download URL
     */
    public function generateSignedUrl(string $path, int $expiresIn = 300): string
    {
        // Calculate expiration timestamp
        $expires = time() + $expiresIn;

        // Create signature using HMAC
        $signature = $this->createSignature($path, $expires);

        // Build URL
        $baseUrl = $this->getBaseUrl();
        $url     = $baseUrl . '/files/download?path=' . urlencode($path) . '&expires=' . $expires . '&signature=' . $signature;

        return $url;
    }

    /**
     * Verify signed URL
     *
     * @param string $path      File path
     * @param int    $expires   Expiration timestamp
     * @param string $signature Signature to verify
     * @return bool True if valid, false otherwise
     */
    public function verifySignature(string $path, int $expires, string $signature): bool
    {
        // Check if expired
        if (time() > $expires) {
            return false;
        }

        // Verify signature
        $expectedSignature = $this->createSignature($path, $expires);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create HMAC signature for file download
     *
     * @param string $path    File path
     * @param int    $expires Expiration timestamp
     * @return string HMAC signature
     */
    private function createSignature(string $path, int $expires): string
    {
        $data = $path . '|' . $expires;
        return hash_hmac('sha256', $data, $this->secretKey);
    }

    /**
     * Get base URL for the application
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        $protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
