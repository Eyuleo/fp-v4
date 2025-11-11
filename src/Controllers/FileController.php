<?php

/**
 * File Controller
 *
 * Handles file serving for uploaded content
 */
class FileController
{
    /**
     * Serve uploaded file
     */
    public function serve(): void
    {
        // Get file path from URL
        $path = $_GET['path'] ?? '';

        if (empty($path)) {
            http_response_code(404);
            echo 'File not found';
            return;
        }

        // Sanitize path to prevent directory traversal
        $path = str_replace(['..', '\\'], ['', '/'], $path);

        // Build full file path
        $filePath = __DIR__ . '/../../storage/uploads/' . $path;

        // Check if file exists
        if (! file_exists($filePath) || ! is_file($filePath)) {
            http_response_code(404);
            echo 'File not found';
            return;
        }

        // Get file info
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $fileSize = filesize($filePath);
        $fileName = basename($filePath);

        // Set headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('Cache-Control: public, max-age=31536000');
        header('X-Content-Type-Options: nosniff');

        // Output file
        readfile($filePath);
        exit;
    }
}
