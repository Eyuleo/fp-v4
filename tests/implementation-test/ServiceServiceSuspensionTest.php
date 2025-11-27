<?php

/**
 * Service Service Suspension Test
 *
 * Tests for ServiceService suspension checking functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Services/ServiceService.php';
require_once __DIR__ . '/../src/Repositories/ServiceRepository.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';

class ServiceServiceSuspensionTest
{
    private int $testsPassed = 0;
    private int $testsFailed = 0;
    private PDO $db;
    private ServiceService $serviceService;
    private UserRepository $userRepository;

    public function __construct()
    {
        // Use test database connection
        $this->db = getDatabaseConnection();
        $serviceRepository = new ServiceRepository($this->db);
        $this->userRepository = new UserRepository($this->db);
        
        $this->serviceService = new ServiceService($serviceRepository);
    }

    public function run(): void
    {
        echo "Running Service Service Suspension Tests...\n\n";

        $this->testSuspendedStudentCannotCreateService();
        $this->testTemporarySuspensionErrorMessage();
        $this->testPermanentBanErrorMessage();
        $this->testActiveStudentCanCreateService();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: {$this->testsPassed}\n";
        echo "Tests Failed: {$this->testsFailed}\n";
        echo str_repeat("=", 50) . "\n";
    }

    private function testSuspendedStudentCannotCreateService(): void
    {
        echo "Test: Suspended student cannot create service\n";

        // Create test data
        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Suspend the student
        $this->userRepository->setSuspension($testData['student_id'], 7);

        // Attempt to create service
        $result = $this->serviceService->createService(
            $testData['student_id'],
            [
                'category_id' => 1,
                'title' => 'Test Service',
                'description' => 'Test service description',
                'price' => 100.00,
                'delivery_days' => 7,
                'tags' => 'test,service'
            ]
        );

        $this->assert(!$result['success'], "Service creation fails for suspended student");
        $this->assert(isset($result['errors']['suspension']), "Suspension error is returned");
        $this->assert(
            strpos($result['errors']['suspension'], 'suspended') !== false,
            "Error message mentions suspension"
        );

        // Cleanup
        $this->cleanupTestData($testData);
    }

    private function testTemporarySuspensionErrorMessage(): void
    {
        echo "\nTest: Temporary suspension error message includes end date\n";

        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Apply temporary suspension
        $this->userRepository->setSuspension($testData['student_id'], 7);

        // Attempt to create service
        $result = $this->serviceService->createService(
            $testData['student_id'],
            [
                'category_id' => 1,
                'title' => 'Test Service',
                'description' => 'Test service description',
                'price' => 100.00,
                'delivery_days' => 7,
                'tags' => 'test,service'
            ]
        );

        $this->assert(!$result['success'], "Service creation fails");
        $this->assert(isset($result['errors']['suspension']), "Suspension error exists");
        $this->assert(
            strpos($result['errors']['suspension'], 'will end on') !== false,
            "Error message includes suspension end date"
        );

        // Cleanup
        $this->cleanupTestData($testData);
    }

    private function testPermanentBanErrorMessage(): void
    {
        echo "\nTest: Permanent ban error message does not include end date\n";

        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Apply permanent ban (null days)
        $this->userRepository->setSuspension($testData['student_id'], null);

        // Attempt to create service
        $result = $this->serviceService->createService(
            $testData['student_id'],
            [
                'category_id' => 1,
                'title' => 'Test Service',
                'description' => 'Test service description',
                'price' => 100.00,
                'delivery_days' => 7,
                'tags' => 'test,service'
            ]
        );

        $this->assert(!$result['success'], "Service creation fails");
        $this->assert(isset($result['errors']['suspension']), "Suspension error exists");
        $this->assert(
            strpos($result['errors']['suspension'], 'will end on') === false,
            "Error message does not include end date for permanent ban"
        );

        // Cleanup
        $this->cleanupTestData($testData);
    }

    private function testActiveStudentCanCreateService(): void
    {
        echo "\nTest: Active student can create service\n";

        $testData = $this->createTestData();
        
        if (!$testData) {
            echo "  ⚠ WARNING: Could not create test data\n";
            return;
        }

        // Ensure student is active (not suspended)
        $this->userRepository->clearSuspension($testData['student_id']);

        // Attempt to create service
        $result = $this->serviceService->createService(
            $testData['student_id'],
            [
                'category_id' => 1,
                'title' => 'Test Service',
                'description' => 'Test service description from active student',
                'price' => 100.00,
                'delivery_days' => 7,
                'tags' => 'test,service'
            ]
        );

        $this->assert($result['success'], "Service creation succeeds for active student");
        $this->assert(!isset($result['errors']['suspension']), "No suspension error");
        $this->assert($result['service_id'] > 0, "Service ID is returned");

        // Cleanup service
        if ($result['service_id']) {
            $this->db->prepare("DELETE FROM services WHERE id = :id")
                ->execute(['id' => $result['service_id']]);
        }
        
        $this->cleanupTestData($testData);
    }

    private function createTestData(): ?array
    {
        try {
            $this->db->beginTransaction();

            // Create test student
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
                VALUES (:email, :password, 'student', 'active', NOW(), NOW())
            ");
            $stmt->execute([
                'email' => 'test_student_' . time() . rand(1000, 9999) . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
            $studentId = (int) $this->db->lastInsertId();

            // Create student profile
            $stmt = $this->db->prepare("
                INSERT INTO student_profiles (user_id, bio, skills, created_at, updated_at)
                VALUES (:user_id, 'Test bio', :skills, NOW(), NOW())
            ");
            $stmt->execute([
                'user_id' => $studentId,
                'skills' => json_encode(['PHP', 'Testing'])
            ]);

            $this->db->commit();

            return [
                'student_id' => $studentId
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            echo "  ERROR: Failed to create test data: " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function cleanupTestData(?array $testData): void
    {
        if (!$testData) {
            return;
        }

        try {
            // Delete in reverse order of creation to respect foreign keys
            $this->db->prepare("DELETE FROM student_profiles WHERE user_id = :user_id")
                ->execute(['user_id' => $testData['student_id']]);
            
            $this->db->prepare("DELETE FROM users WHERE id = :id")
                ->execute(['id' => $testData['student_id']]);
        } catch (Exception $e) {
            error_log('Failed to cleanup test data: ' . $e->getMessage());
        }
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
    $test = new ServiceServiceSuspensionTest();
    $test->run();
}
