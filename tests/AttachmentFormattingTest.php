<?php
/**
 * Attachment Formatting Test
 *
 * Tests for ModerationController attachment formatting functionality
 */

require_once __DIR__ . '/../src/Controllers/ModerationController.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Helpers.php';

class AttachmentFormattingTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;

    public function run(): void
    {
        echo "Running Attachment Formatting Tests...\n\n";

        $this->testEmptyAttachments();
        $this->testImageAttachments();
        $this->testPdfAttachments();
        $this->testMixedAttachments();
        $this->testNonAllowedFileTypes();

        echo "\n==================================================\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo "==================================================\n\n";

        exit($this->testsFailed > 0 ? 1 : 0);
    }

    private function testEmptyAttachments(): void
    {
        echo "Test: Empty attachments display\n";

        try {
            $controller = new ModerationController();
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('formatAttachments');
            $method->setAccessible(true);

            // Test null
            $result = $method->invoke($controller, null);
            $this->assert(
                str_contains($result, 'No attachments'),
                "Null attachments should display 'No attachments'"
            );

            // Test empty array
            $result = $method->invoke($controller, []);
            $this->assert(
                str_contains($result, 'No attachments'),
                "Empty array should display 'No attachments'"
            );

            $this->testsPassed++;
            echo "  ✓ PASSED\n\n";
        } catch (Exception $e) {
            $this->testsFailed++;
            echo "  ✗ FAILED: " . $e->getMessage() . "\n\n";
        }
    }

    private function testImageAttachments(): void
    {
        echo "Test: Image attachment formatting\n";

        try {
            $controller = new ModerationController();
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('formatAttachments');
            $method->setAccessible(true);

            $attachments = [
                [
                    'path' => 'messages/1/test.jpg',
                    'filename' => 'test.jpg',
                    'original_name' => 'my-photo.jpg',
                    'extension' => 'jpg',
                    'size' => 1024000,
                    'uploaded_at' => '2024-01-15 10:30:00'
                ]
            ];

            $result = $method->invoke($controller, $attachments);

            $this->assert(
                str_contains($result, 'my-photo.jpg'),
                "Should display original filename"
            );
            $this->assert(
                str_contains($result, 'Image'),
                "Should display 'Image' label"
            );
            $this->assert(
                str_contains($result, '🖼️'),
                "Should display image icon"
            );
            $this->assert(
                str_contains($result, '/files/download?path='),
                "Should include download link"
            );
            // Verify signed URL parameters are present (expires and signature)
            $hasSignedParams = str_contains($result, 'expires=') && str_contains($result, 'signature=');
            $this->assert(
                $hasSignedParams,
                "Should include signed URL parameters (expires and signature)"
            );

            $this->testsPassed++;
            echo "  ✓ PASSED\n\n";
        } catch (Exception $e) {
            $this->testsFailed++;
            echo "  ✗ FAILED: " . $e->getMessage() . "\n\n";
        }
    }

    private function testPdfAttachments(): void
    {
        echo "Test: PDF attachment formatting\n";

        try {
            $controller = new ModerationController();
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('formatAttachments');
            $method->setAccessible(true);

            $attachments = [
                [
                    'path' => 'messages/1/document.pdf',
                    'filename' => 'document.pdf',
                    'original_name' => 'report.pdf',
                    'extension' => 'pdf',
                    'size' => 2048000,
                    'uploaded_at' => '2024-01-15 10:30:00'
                ]
            ];

            $result = $method->invoke($controller, $attachments);

            $this->assert(
                str_contains($result, 'report.pdf'),
                "Should display original filename"
            );
            $this->assert(
                str_contains($result, 'PDF'),
                "Should display 'PDF' label"
            );
            $this->assert(
                str_contains($result, '📄'),
                "Should display PDF icon"
            );

            $this->testsPassed++;
            echo "  ✓ PASSED\n\n";
        } catch (Exception $e) {
            $this->testsFailed++;
            echo "  ✗ FAILED: " . $e->getMessage() . "\n\n";
        }
    }

    private function testMixedAttachments(): void
    {
        echo "Test: Mixed attachment types\n";

        try {
            $controller = new ModerationController();
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('formatAttachments');
            $method->setAccessible(true);

            $attachments = [
                [
                    'path' => 'messages/1/photo.png',
                    'filename' => 'photo.png',
                    'original_name' => 'screenshot.png',
                    'extension' => 'png',
                    'size' => 512000,
                    'uploaded_at' => '2024-01-15 10:30:00'
                ],
                [
                    'path' => 'messages/1/doc.pdf',
                    'filename' => 'doc.pdf',
                    'original_name' => 'contract.pdf',
                    'extension' => 'pdf',
                    'size' => 1024000,
                    'uploaded_at' => '2024-01-15 10:31:00'
                ]
            ];

            $result = $method->invoke($controller, $attachments);

            $this->assert(
                str_contains($result, 'screenshot.png'),
                "Should display image filename"
            );
            $this->assert(
                str_contains($result, 'contract.pdf'),
                "Should display PDF filename"
            );
            $this->assert(
                str_contains($result, 'Image'),
                "Should display Image label"
            );
            $this->assert(
                str_contains($result, 'PDF'),
                "Should display PDF label"
            );

            $this->testsPassed++;
            echo "  ✓ PASSED\n\n";
        } catch (Exception $e) {
            $this->testsFailed++;
            echo "  ✗ FAILED: " . $e->getMessage() . "\n\n";
        }
    }

    private function testNonAllowedFileTypes(): void
    {
        echo "Test: Non-allowed file types are filtered\n";

        try {
            $controller = new ModerationController();
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('formatAttachments');
            $method->setAccessible(true);

            $attachments = [
                [
                    'path' => 'messages/1/document.docx',
                    'filename' => 'document.docx',
                    'original_name' => 'essay.docx',
                    'extension' => 'docx',
                    'size' => 1024000,
                    'uploaded_at' => '2024-01-15 10:30:00'
                ],
                [
                    'path' => 'messages/1/archive.zip',
                    'filename' => 'archive.zip',
                    'original_name' => 'files.zip',
                    'extension' => 'zip',
                    'size' => 2048000,
                    'uploaded_at' => '2024-01-15 10:31:00'
                ]
            ];

            $result = $method->invoke($controller, $attachments);

            $this->assert(
                !str_contains($result, 'essay.docx'),
                "Should not display DOCX files"
            );
            $this->assert(
                !str_contains($result, 'files.zip'),
                "Should not display ZIP files"
            );
            $this->assert(
                str_contains($result, 'No attachments') || str_contains($result, 'only images and PDFs'),
                "Should indicate no valid attachments"
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
    $test = new AttachmentFormattingTest();
    $test->run();
}
