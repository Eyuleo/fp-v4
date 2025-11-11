<?php

require_once __DIR__ . '/../Services/FileService.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Policies/OrderPolicy.php';
require_once __DIR__ . '/../Policies/ServicePolicy.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';

/**
 * File Controller
 *
 * Handles secure file downloads with signed URLs and authorization
 */
class FileController
{
    private FileService $fileService;
    private PDO $db;

    public function __construct()
    {
        $this->fileService = new FileService();
        $this->db          = $this->getDatabase();
    }

    /**
     * Get database connection
     */
    private function getDatabase(): PDO
    {
        static $db = null;
        if ($db === null) {
            $db = require __DIR__ . '/../../config/database.php';
        }
        return $db;
    }

    /**
     * Download file with signature verification and authorization
     */
    public function download(): void
    {
        // Get parameters from URL
        $path      = $_GET['path'] ?? '';
        $expires   = $_GET['expires'] ?? 0;
        $signature = $_GET['signature'] ?? '';

        if (empty($path) || empty($expires) || empty($signature)) {
            $this->sendError(400, 'Invalid request parameters');
            return;
        }

        // Sanitize path to prevent directory traversal
        $path = str_replace(['..', '\\'], ['', '/'], $path);

        // Verify signature and expiration
        if (! $this->fileService->verifySignature($path, (int) $expires, $signature)) {
            $this->sendError(403, 'Invalid or expired signature');
            return;
        }

        // Check if file exists
        if (! $this->fileService->exists($path)) {
            $this->sendError(404, 'File not found');
            return;
        }

        // Check authorization based on file context
        if (! $this->authorizeFileAccess($path)) {
            $this->sendError(403, 'Unauthorized access');
            return;
        }

        // Get full file path
        $filePath = $this->fileService->getFullPath($path);

        // Get file info
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $fileSize = filesize($filePath);
        $fileName = basename($filePath);

        // Set security headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $fileSize);
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=300'); // 5 minutes cache

        // Stream file contents
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            while (! feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }

        exit;
    }

    /**
     * Serve uploaded file (legacy method for backward compatibility)
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

    /**
     * Authorize file access based on context
     *
     * @param string $path File path
     * @return bool True if authorized, false otherwise
     */
    private function authorizeFileAccess(string $path): bool
    {
        // Parse path to determine context
        $parts = explode('/', $path);

        if (count($parts) < 2) {
            return false;
        }

        $context   = $parts[0]; // e.g., 'profiles', 'services', 'orders', 'messages'
        $contextId = (int) $parts[1];

        // Get current user
        $user = Auth::user();

        // Public files (profiles, services) can be viewed by anyone
        if (in_array($context, ['profiles', 'services'])) {
            return true;
        }

        // For private files, user must be authenticated
        if (! $user) {
            return false;
        }

        // Check authorization based on context
        switch ($context) {
            case 'orders':
                return $this->authorizeOrderFile($user, $contextId);

            case 'messages':
                return $this->authorizeMessageFile($user, $contextId);

            default:
                return false;
        }
    }

    /**
     * Authorize access to order files
     *
     * @param array $user    Current user
     * @param int   $orderId Order ID
     * @return bool
     */
    private function authorizeOrderFile(array $user, int $orderId): bool
    {
        // Get order
        $orderRepository = new OrderRepository($this->db);
        $order           = $orderRepository->findById($orderId);

        if (! $order) {
            return false;
        }

        // Check if user can view this order
        $orderPolicy = new OrderPolicy();
        return $orderPolicy->canView($user, $order);
    }

    /**
     * Authorize access to message files
     *
     * @param array $user    Current user
     * @param int   $orderId Order ID (messages are scoped to orders)
     * @return bool
     */
    private function authorizeMessageFile(array $user, int $orderId): bool
    {
        // Get order to check if user is part of the conversation
        $orderRepository = new OrderRepository($this->db);
        $order           = $orderRepository->findById($orderId);

        if (! $order) {
            return false;
        }

        // User must be either the client, student, or admin
        return $user['id'] === $order['client_id']
            || $user['id'] === $order['student_id']
            || $user['role'] === 'admin';
    }

    /**
     * Send error response
     *
     * @param int    $code    HTTP status code
     * @param string $message Error message
     */
    private function sendError(int $code, string $message): void
    {
        http_response_code($code);

        // Check if this is an API request
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
        } else {
            echo $message;
        }

        exit;
    }
}
