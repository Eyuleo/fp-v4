<?php

/**
 * Performance Optimization Unit Test
 * 
 * Tests the code structure of performance optimizations without requiring database
 */

class PerformanceOptimizationTest
{
    private array $results = [];

    public function run(): void
    {
        echo "Performance Optimization Tests\n";
        echo "==============================\n\n";

        $this->testServiceRepositoryCacheProperty();
        $this->testServiceRepositoryClearCacheMethod();
        $this->testOrderRepositoryUpsertStructure();
        $this->testMigrationFileExists();
        $this->testAdminControllerOptimizations();
        $this->testCategoryRepositoryCacheClear();

        $this->printResults();
    }

    private function testServiceRepositoryCacheProperty(): void
    {
        echo "Test 1: ServiceRepository has cache property\n";
        
        $file = __DIR__ . '/../../src/Repositories/ServiceRepository.php';
        $content = file_get_contents($file);
        
        // Check for cache property
        if (strpos($content, 'private static ?array $categoriesCache') !== false) {
            $this->pass("✓ Cache property exists");
        } else {
            $this->fail("✗ Cache property not found");
        }
        
        // Check for cache usage in getAllCategories
        if (strpos($content, 'if (self::$categoriesCache !== null)') !== false) {
            $this->pass("✓ Cache check logic exists");
        } else {
            $this->fail("✗ Cache check logic not found");
        }
        
        // Check for cache assignment
        if (strpos($content, 'self::$categoriesCache = $stmt->fetchAll()') !== false) {
            $this->pass("✓ Cache assignment exists");
        } else {
            $this->fail("✗ Cache assignment not found");
        }
        
        echo "\n";
    }

    private function testServiceRepositoryClearCacheMethod(): void
    {
        echo "Test 2: ServiceRepository has clearCategoriesCache method\n";
        
        $file = __DIR__ . '/../../src/Repositories/ServiceRepository.php';
        $content = file_get_contents($file);
        
        if (strpos($content, 'public static function clearCategoriesCache()') !== false) {
            $this->pass("✓ clearCategoriesCache method exists");
        } else {
            $this->fail("✗ clearCategoriesCache method not found");
        }
        
        if (strpos($content, 'self::$categoriesCache = null') !== false) {
            $this->pass("✓ Cache clearing logic exists");
        } else {
            $this->fail("✗ Cache clearing logic not found");
        }
        
        echo "\n";
    }

    private function testOrderRepositoryUpsertStructure(): void
    {
        echo "Test 3: OrderRepository uses UPSERT\n";
        
        $file = __DIR__ . '/../../src/Repositories/OrderRepository.php';
        $content = file_get_contents($file);
        
        // Check for UPSERT syntax
        if (strpos($content, 'INSERT INTO student_profiles') !== false && 
            strpos($content, 'ON DUPLICATE KEY UPDATE') !== false) {
            $this->pass("✓ UPSERT syntax exists");
        } else {
            $this->fail("✗ UPSERT syntax not found");
        }
        
        // Check that old SELECT query is removed
        if (strpos($content, 'SELECT id, total_orders FROM student_profiles WHERE user_id') === false) {
            $this->pass("✓ Old SELECT query removed");
        } else {
            $this->fail("✗ Old SELECT query still exists");
        }
        
        echo "\n";
    }

    private function testMigrationFileExists(): void
    {
        echo "Test 4: Performance indexes migration exists\n";
        
        $file = __DIR__ . '/../../migrations/032_add_performance_indexes.sql';
        
        if (file_exists($file)) {
            $this->pass("✓ Migration file exists");
            
            $content = file_get_contents($file);
            
            // Check for key indexes
            $expectedIndexes = [
                'idx_status_created',
                'idx_status_completed',
                'idx_student_status',
                'idx_client_status',
                'idx_order_sender',
                'idx_average_rating',
                'idx_user_read',
            ];
            
            $foundCount = 0;
            foreach ($expectedIndexes as $index) {
                if (strpos($content, $index) !== false) {
                    $foundCount++;
                }
            }
            
            if ($foundCount >= 5) {
                $this->pass("✓ Migration contains multiple performance indexes ($foundCount found)");
            } else {
                $this->fail("✗ Migration missing expected indexes (only $foundCount found)");
            }
        } else {
            $this->fail("✗ Migration file not found");
        }
        
        echo "\n";
    }

    private function testAdminControllerOptimizations(): void
    {
        echo "Test 5: AdminController query optimizations\n";
        
        $file = __DIR__ . '/../../src/Controllers/AdminController.php';
        $content = file_get_contents($file);
        
        // Check for combined dashboard query
        if (strpos($content, 'SUM(CASE WHEN status') !== false &&
            strpos($content, 'COALESCE(SUM(CASE') !== false) {
            $this->pass("✓ Dashboard query optimization exists");
        } else {
            $this->fail("✗ Dashboard query optimization not found");
        }
        
        // Check for window functions in payments
        if (strpos($content, 'COUNT(*) OVER()') !== false &&
            strpos($content, 'SUM(') !== false && strpos($content, 'OVER()') !== false) {
            $this->pass("✓ Window function optimization exists");
        } else {
            $this->fail("✗ Window function optimization not found");
        }
        
        // Check for LEFT JOIN instead of subquery in services
        if (strpos($content, 'COUNT(CASE WHEN o.status IN') !== false &&
            strpos($content, 'LEFT JOIN orders o ON o.service_id = s.id') !== false) {
            $this->pass("✓ Services query optimization exists");
        } else {
            $this->fail("✗ Services query optimization not found");
        }
        
        echo "\n";
    }

    private function testCategoryRepositoryCacheClear(): void
    {
        echo "Test 6: CategoryRepository clears cache on modifications\n";
        
        $file = __DIR__ . '/../../src/Repositories/CategoryRepository.php';
        $content = file_get_contents($file);
        
        // Check for cache clear calls
        $clearCount = substr_count($content, 'ServiceRepository::clearCategoriesCache()');
        
        if ($clearCount >= 3) {
            $this->pass("✓ Cache cleared in create, update, and delete methods ($clearCount calls found)");
        } else {
            $this->fail("✗ Cache not properly cleared (only $clearCount calls found, expected 3)");
        }
        
        echo "\n";
    }

    private function pass(string $message): void
    {
        echo "  $message\n";
        $this->results[] = true;
    }

    private function fail(string $message): void
    {
        echo "  $message\n";
        $this->results[] = false;
    }

    private function printResults(): void
    {
        $total = count($this->results);
        $passed = array_sum($this->results);
        $failed = $total - $passed;
        
        echo "==============================\n";
        echo "Results: $passed/$total passed";
        
        if ($failed > 0) {
            echo ", $failed failed";
        }
        
        echo "\n";
        
        if ($failed === 0) {
            echo "✓ All tests passed!\n";
        } else {
            echo "✗ Some tests failed\n";
        }
    }
}

// Run tests
$test = new PerformanceOptimizationTest();
$test->run();
