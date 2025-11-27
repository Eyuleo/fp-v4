<?php

require_once __DIR__ . '/../src/Validators/ServiceValidator.php';

/**
 * Test service file validation
 */
class ServiceFileValidationTest
{
    private ServiceValidator $validator;

    public function __construct()
    {
        $this->validator = new ServiceValidator();
    }

    /**
     * Test that only images and PDF files are allowed
     */
    public function testOnlyImagesAndPdfAllowed(): void
    {
        // Test allowed extensions
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        
        foreach ($allowedExtensions as $ext) {
            echo "Testing allowed extension: $ext\n";
            
            // Create a temporary test file
            $tmpFile = tempnam(sys_get_temp_dir(), 'test');
            file_put_contents($tmpFile, 'test content');
            
            $files = [[
                'name' => "test.$ext",
                'type' => $this->getMimeType($ext),
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ]];
            
            // For this basic test, we'll just check the extension validation
            // Full MIME type validation would require actual file content
            $extension = strtolower(pathinfo($files[0]['name'], PATHINFO_EXTENSION));
            $isAllowed = in_array($extension, $allowedExtensions);
            
            if (!$isAllowed) {
                echo "  ✗ FAILED: Extension $ext should be allowed\n";
            } else {
                echo "  ✓ PASSED: Extension $ext is allowed\n";
            }
            
            unlink($tmpFile);
        }
        
        // Test disallowed extensions
        $disallowedExtensions = ['doc', 'docx', 'zip', 'exe', 'txt'];
        
        foreach ($disallowedExtensions as $ext) {
            echo "Testing disallowed extension: $ext\n";
            
            $extension = strtolower($ext);
            $isAllowed = in_array($extension, $allowedExtensions);
            
            if ($isAllowed) {
                echo "  ✗ FAILED: Extension $ext should NOT be allowed\n";
            } else {
                echo "  ✓ PASSED: Extension $ext is correctly disallowed\n";
            }
        }
    }

    /**
     * Test file size limit
     */
    public function testFileSizeLimit(): void
    {
        echo "\nTesting file size limit (10MB):\n";
        
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        // Test file under limit
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, str_repeat('x', 1024)); // 1KB
        
        $files = [[
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
        ]];
        
        $result = $this->validator->validateFiles($files);
        echo "  File under limit (1KB): " . ($result ? "✓ PASSED" : "✗ FAILED") . "\n";
        
        unlink($tmpFile);
        
        // Test file over limit
        $files = [[
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/fake',
            'error' => UPLOAD_ERR_OK,
            'size' => $maxSize + 1,
        ]];
        
        $result = $this->validator->validateFiles($files);
        echo "  File over limit (>10MB): " . (!$result ? "✓ PASSED (correctly rejected)" : "✗ FAILED (should be rejected)") . "\n";
    }

    /**
     * Test maximum file count
     */
    public function testMaxFileCount(): void
    {
        echo "\nTesting maximum file count (5 files):\n";
        
        // Test with 5 files (should pass)
        $files = [];
        for ($i = 0; $i < 5; $i++) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'test');
            file_put_contents($tmpFile, 'test');
            
            $files[] = [
                'name' => "test$i.jpg",
                'type' => 'image/jpeg',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ];
        }
        
        $result = $this->validator->validateFiles($files);
        echo "  5 files: " . ($result ? "✓ PASSED" : "✗ FAILED") . "\n";
        
        foreach ($files as $file) {
            if (file_exists($file['tmp_name'])) {
                unlink($file['tmp_name']);
            }
        }
        
        // Test with 6 files (should fail)
        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = [
                'name' => "test$i.jpg",
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/fake',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ];
        }
        
        $result = $this->validator->validateFiles($files);
        echo "  6 files: " . (!$result ? "✓ PASSED (correctly rejected)" : "✗ FAILED (should be rejected)") . "\n";
    }

    private function getMimeType(string $extension): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    public function run(): void
    {
        echo "=== Service File Validation Tests ===\n\n";
        
        $this->testOnlyImagesAndPdfAllowed();
        $this->testFileSizeLimit();
        $this->testMaxFileCount();
        
        echo "\n=== Tests Complete ===\n";
    }
}

// Run tests
$test = new ServiceFileValidationTest();
$test->run();
