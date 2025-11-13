<?php

class FileService
{
    private string $uploadBasePath;
    private string $secretKey;

    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif',
        'pdf',
        'doc', 'docx',
        'zip',
        'txt', // Added plain text
               // Add design formats if you intend to support them:
               // 'psd','ai','fig'
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
        'txt'  => 'text/plain',
        // 'psd' => 'image/vnd.adobe.photoshop', // if supported
        // 'ai'  => 'application/postscript',
        // 'fig' => 'application/octet-stream',  // (Figma exports vary)
    ];

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    private const IMAGE_SIZE_LIMIT    = 10 * 1024 * 1024;
    private const DOCUMENT_SIZE_LIMIT = 25 * 1024 * 1024;

    public function __construct()
    {
        $this->uploadBasePath = __DIR__ . '/../../storage/uploads';
        $this->secretKey      = config('app.key') ?? 'default-secret-key-change-in-production';
    }

    public function upload(array $file, string $context, int $contextId): array
    {
        if (! isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error'   => $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE),
            ];
        }

        $originalName = $file['name'] ?? '';
        $tmpPath      = $file['tmp_name'] ?? '';
        $size         = $file['size'] ?? 0;
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return [
                'success' => false,
                'error'   => 'File type not allowed. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS),
            ];
        }

        $sizeLimit = in_array($extension, self::IMAGE_EXTENSIONS)
            ? self::IMAGE_SIZE_LIMIT
            : self::DOCUMENT_SIZE_LIMIT;

        if ($size > $sizeLimit) {
            return [
                'success' => false,
                'error'   => "File size exceeds " . ($sizeLimit / 1024 / 1024) . "MB limit",
            ];
        }

        $mimeValidation = $this->validateMimeType($tmpPath, $extension);
        if ($mimeValidation !== true) {
            return [
                'success' => false,
                'error'   => $mimeValidation,
            ];
        }

        if (in_array($extension, self::IMAGE_EXTENSIONS)) {
            $imageInfo = @getimagesize($tmpPath);
            if ($imageInfo === false) {
                return [
                    'success' => false,
                    'error'   => 'Invalid image file',
                ];
            }
        }

        $filename   = $this->generateUniqueFilename($extension);
        $contextDir = $this->uploadBasePath . '/' . $context . '/' . $contextId;

        if (! is_dir($contextDir) && ! mkdir($contextDir, 0755, true)) {
            return [
                'success' => false,
                'error'   => 'Failed to create upload directory',
            ];
        }

        $filePath = $contextDir . '/' . $filename;

        // MOVE FALLBACK: if file is not seen as an uploaded file (e.g. came from a temp folder after payment flow)
        $moved = false;
        if (is_uploaded_file($tmpPath)) {
            $moved = move_uploaded_file($tmpPath, $filePath);
        } else {
            // fallback for previously relocated files
            $moved = rename($tmpPath, $filePath);
            if (! $moved) {
                $moved = copy($tmpPath, $filePath);
                if ($moved) {
                    @unlink($tmpPath);
                }
            }
        }

        if (! $moved) {
            return [
                'success' => false,
                'error'   => 'Failed to save uploaded file',
            ];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

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

    public function uploadMultiple(array $files, string $context, int $contextId): array
    {
        $uploadedFiles = [];
        $errors        = [];

        if (empty($files)) {
            return ['success' => true, 'files' => [], 'errors' => []];
        }

        if (isset($files['name']) && is_array($files['name'])) {
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
        } elseif (! empty($files['name'])) {
            $result = $this->upload($files, $context, $contextId);
            if ($result['success']) {
                $uploadedFiles[] = $result['file'];
            } else {
                $errors[] = $result['error'];
            }
        }

        return [
            'success' => count($uploadedFiles) > 0 || empty($errors),
            'files'   => $uploadedFiles,
            'errors'  => $errors,
        ];
    }

    private function validateMimeType(string $filePath, string $extension): string | bool
    {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $expectedMime = self::MIME_TYPE_MAP[$extension] ?? null;
        if (! $expectedMime) {
            return 'Unknown file extension';
        }

        if ($mimeType !== $expectedMime) {
            // Allow plain text variations
            if ($extension === 'txt' && str_starts_with($mimeType, 'text/')) {
                return true;
            }
            return 'File MIME type does not match extension';
        }

        return true;
    }

    private function generateUniqueFilename(string $extension): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $uuid    = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return $uuid . '.' . $extension;
    }

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

    public function delete(string $path): bool
    {
        $filePath = $this->uploadBasePath . '/' . $path;
        return file_exists($filePath) && is_file($filePath) ? unlink($filePath) : false;
    }

    public function exists(string $path): bool
    {
        return file_exists($this->uploadBasePath . '/' . $path)
        && is_file($this->uploadBasePath . '/' . $path);
    }

    public function getFullPath(string $path): string
    {
        return $this->uploadBasePath . '/' . $path;
    }

    public function generateSignedUrl(string $path, int $expiresIn = 300): string
    {
        $expires   = time() + $expiresIn;
        $signature = $this->createSignature($path, $expires);
        $baseUrl   = $this->getBaseUrl();
        return $baseUrl . '/files/download?path=' . urlencode($path) . '&expires=' . $expires . '&signature=' . $signature;
    }

    public function verifySignature(string $path, int $expires, string $signature): bool
    {
        if (time() > $expires) {
            return false;
        }
        $expectedSignature = $this->createSignature($path, $expires);
        return hash_equals($expectedSignature, $signature);
    }

    private function createSignature(string $path, int $expires): string
    {
        return hash_hmac('sha256', $path . '|' . $expires, $this->secretKey);
    }

    private function getBaseUrl(): string
    {
        $protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
