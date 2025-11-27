<?php
/**
 * File Service Context Validation Test
 *
 * Tests for FileService context-based file type restrictions
 */

require_once __DIR__ . '/../src/Services/FileService.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Helpers.php';

class FileServiceContextValidationTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private FileService $fileService;

    public function __construct()
    {
        $this->fileService = new FileService();
    }

    public function run(): void
    {
        echo "Running File Service Context Validation Tests...\n\n";

        $this->testMessagesContextAllowedTypes();
        $this->testMessagesContextRejectedTypes();
        $this->testOtherContextsAllowAllTypes();

        echo "\n==================================================\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo "==================================================\n\n";

        exit($this->testsFailed > 0 ? 1 : 0);
    }

    private function testMessagesContextAllowedTypes(): void
    {
        echo "Test: Messages context allows images and PDFs\n";

        try {
            $reflection = new ReflectionClass($this->fileService);
            $method = $reflection->getMethod('getAllowedExtensionsForContext');
            $method->setAccessible(true);

            $allowedExtensions = $method->invoke($this->fileService, 'messages');

            $this->assert(
                in_array('jpg', $allowedExtensions),
                "Messages should allow JPG files"
            );
            $this->assert(
                in_array('jpeg', $allowedExtensions),
                "Messages should allow JPEG files"
            );
            $this->assert(
                in_array('png', $allowedExtensions),
                "Messages should allow PNG files"
            );
            $this->assert(
                in_array('gif', $allowedExtensions),
                "Messages should allow GIF files"
            );
            $this->assert(
                in_array('pdf', $allowedExtensions),
                "Messages should allow PDF files"
            );

            $this->testsPassed++;
            echo "  ✓ PASSED\n\n";
        } catch (Exception $e) {
            $this->testsFailed++;
            echo "  ✗ FAILED: " . $e->getMessage() . "\n\n";
        }
    }

    private function testMessagesContextRejectedTypes(): void
    {
        echo "Test: Messages context rejects non-image/PDF files\n";

        try {
            $reflection = new ReflectionClass($this->fileService);
            $method = $reflection->getMethod('getAllowedExtensionsForContext');
            $method->setAccessible(true);

            $allowedExtensions = $method->invoke($this->fileService, 'messages');

            $this->assert(
                !in_array('docx', $allowedExtensions),
                "Messages should NOT allow DOCX files"
            );
            $this->assert(
                !in_array('doc', $allowedExtensions),
                "Messages should NOT allow DOC files"
            );
            $this->assert(
                !in_array('zip', $allowedExtensions),
                "Messages should NOT allow ZIP files"
            );
            $this->assert(
                !in_array('txt', $allowedExtensions),
                "Messages should NOT allow TXT files"
            );

            $this->testsPassed++;
            echo "  ✓ PASSED\n\n";
        } catch (Exception $e) {
            $this->testsFailed++;
            echo "  ✗ FAILED: " . $e->getMessage() . "\n\n";
        }
    }

    private function testOtherContextsAllowAllTypes(): void
    {
        echo "Test: Other contexts allow all file types\n";

        try {
            $reflection = new ReflectionClass($this->fileService);
            $method = $reflection->getMethod('getAllowedExtensionsForContext');
            $method->setAccessible(true);

            // Test services context
            $allowedExtensions = $method->invoke($this->fileService, 'services');

            $this->assert(
                in_array('jpg', $allowedExtensions),
                "Services should allow JPG files"
            );
            $this->assert(
                in_array('pdf', $allowedExtensions),
                "Services should allow PDF files"
            );
            $this->assert(
                in_array('docx', $allowedExtensions),
                "Services should allow DOCX files"
            );
            $this->assert(
                in_array('zip', $allowedExtensions),
                "Services should allow ZIP files"
            );

            // Test orders context
            $allowedExtensions = $method->invoke($this->fileService, 'orders');

            $this->assert(
                in_array('docx', $allowedExtensions),
                "Orders should allow DOCX files"
            );
            $this->assert(
                in_array('zip', $allowedExtensions),
                "Orders should allow ZIP files"
            );

            $this->testsPassed++;
            echo "  ✓ PASSED\n\n";
        } catch (Exception $e) {
            $this->testsFailed++;
            echo "  ✗ FAILED: " . $e->getMessage() . "\n\n";
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new FileServiceContextValidationTest();
    $test->run();
}
