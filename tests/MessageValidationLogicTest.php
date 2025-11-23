<?php

/**
 * Message Validation Logic Test
 *
 * Tests the validation logic for plain text messaging without database dependencies
 */

class MessageValidationLogicTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;

    public function run(): void
    {
        echo "Running Message Validation Logic Tests...\n\n";

        $this->testEmptyAttachmentsDetection();
        $this->testSingleFileUploadDetection();
        $this->testMultipleFileUploadDetection();
        $this->testProcessedAttachmentArrayDetection();
        $this->testEmptyFileUploadDetection();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    /**
     * Simulate the attachment detection logic from MessageService
     */
    private function hasAttachments(array $attachments): bool
    {
        $hasAttachments = false;
        
        if (!empty($attachments)) {
            // Check if this is a $_FILES structure (has 'name' key)
            if (isset($attachments['name'])) {
                // Single or multiple file upload from $_FILES
                if (is_array($attachments['name'])) {
                    // Multiple files: check if any file was actually uploaded
                    $hasAttachments = count(array_filter($attachments['name'], function($name) {
                        return !empty($name);
                    })) > 0;
                } else {
                    // Single file: check if name is not empty
                    $hasAttachments = !empty($attachments['name']);
                }
            } else {
                // Already-processed attachment array (from FileService)
                // Check if it's a non-empty array
                $hasAttachments = is_array($attachments) && count($attachments) > 0;
            }
        }
        
        return $hasAttachments;
    }

    private function testEmptyAttachmentsDetection(): void
    {
        echo "Test: Empty attachments array detection\n";

        // Test with empty array
        $result = $this->hasAttachments([]);
        $this->assert(!$result, "Empty array is detected as no attachments");

        // Test with null-like empty $_FILES structure
        $emptyFiles = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => 4,
            'size' => 0
        ];
        $result = $this->hasAttachments($emptyFiles);
        $this->assert(!$result, "Empty $_FILES structure is detected as no attachments");
    }

    private function testSingleFileUploadDetection(): void
    {
        echo "\nTest: Single file upload detection\n";

        // Simulate single file upload from $_FILES
        $singleFile = [
            'name' => 'document.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => 0,
            'size' => 1024
        ];

        $result = $this->hasAttachments($singleFile);
        $this->assert($result, "Single file upload is detected as having attachments");
    }

    private function testMultipleFileUploadDetection(): void
    {
        echo "\nTest: Multiple file upload detection\n";

        // Simulate multiple file upload from $_FILES
        $multipleFiles = [
            'name' => ['file1.pdf', 'file2.jpg'],
            'type' => ['application/pdf', 'image/jpeg'],
            'tmp_name' => ['/tmp/phpXXXXXX', '/tmp/phpYYYYYY'],
            'error' => [0, 0],
            'size' => [1024, 2048]
        ];

        $result = $this->hasAttachments($multipleFiles);
        $this->assert($result, "Multiple file upload is detected as having attachments");

        // Test with one empty and one valid file
        $mixedFiles = [
            'name' => ['', 'file2.jpg'],
            'type' => ['', 'image/jpeg'],
            'tmp_name' => ['', '/tmp/phpYYYYYY'],
            'error' => [4, 0],
            'size' => [0, 2048]
        ];

        $result = $this->hasAttachments($mixedFiles);
        $this->assert($result, "Mixed upload with one valid file is detected as having attachments");
    }

    private function testProcessedAttachmentArrayDetection(): void
    {
        echo "\nTest: Processed attachment array detection\n";

        // Simulate already-processed attachments from FileService
        $processedAttachments = [
            [
                'path' => 'storage/uploads/messages/1/file.pdf',
                'original_name' => 'file.pdf',
                'size' => 1024
            ]
        ];

        $result = $this->hasAttachments($processedAttachments);
        $this->assert($result, "Processed attachment array is detected as having attachments");
    }

    private function testEmptyFileUploadDetection(): void
    {
        echo "\nTest: Empty file upload detection\n";

        // Simulate multiple file input with no files selected
        $emptyMultipleFiles = [
            'name' => ['', '', ''],
            'type' => ['', '', ''],
            'tmp_name' => ['', '', ''],
            'error' => [4, 4, 4],
            'size' => [0, 0, 0]
        ];

        $result = $this->hasAttachments($emptyMultipleFiles);
        $this->assert(!$result, "Empty multiple file upload is detected as no attachments");
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  ✓ PASS: $message\n";
            $this->testsPassed++;
        } else {
            echo "  ✗ FAIL: $message\n";
            $this->testsFailed++;
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    $test = new MessageValidationLogicTest();
    $test->run();
}
