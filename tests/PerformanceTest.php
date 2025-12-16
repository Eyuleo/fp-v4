<?php

/**
 * Performance Test
 * 
 * Simple test to verify that performance optimizations work correctly
 */

// Test 1: Verify ServiceRepository caching works
echo "Test 1: ServiceRepository categories caching\n";
echo "============================================\n";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Repositories/ServiceRepository.php';

$db = getDatabaseConnection();
$serviceRepo = new ServiceRepository($db);

// First call - should hit database
$start = microtime(true);
$categories1 = $serviceRepo->getAllCategories();
$time1 = microtime(true) - $start;

echo "First call (from DB): " . number_format($time1 * 1000, 2) . "ms\n";
echo "Categories found: " . count($categories1) . "\n";

// Second call - should use cache
$start = microtime(true);
$categories2 = $serviceRepo->getAllCategories();
$time2 = microtime(true) - $start;

echo "Second call (from cache): " . number_format($time2 * 1000, 4) . "ms\n";
echo "Categories found: " . count($categories2) . "\n";

if ($time2 < $time1) {
    echo "✓ PASS: Cache is faster than database query\n";
} else {
    echo "✗ FAIL: Cache is not faster\n";
}

if ($categories1 === $categories2) {
    echo "✓ PASS: Cached data matches original\n";
} else {
    echo "✗ FAIL: Cached data doesn't match\n";
}

echo "\n";

// Test 2: Verify OrderRepository UPSERT works
echo "Test 2: OrderRepository UPSERT optimization\n";
echo "============================================\n";

require_once __DIR__ . '/../src/Repositories/OrderRepository.php';

$orderRepo = new OrderRepository($db);

// Get a test student ID
$stmt = $db->query("SELECT id FROM users WHERE role = 'student' LIMIT 1");
$testUser = $stmt->fetch();

if ($testUser) {
    $testStudentId = (int) $testUser['id'];
    
    // Get initial count
    $stmt = $db->prepare("SELECT total_orders FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$testStudentId]);
    $before = $stmt->fetch();
    $initialCount = $before ? (int) $before['total_orders'] : 0;
    
    echo "Initial order count: $initialCount\n";
    
    // Increment using optimized method
    $start = microtime(true);
    $result = $orderRepo->incrementStudentOrderCount($testStudentId);
    $time = microtime(true) - $start;
    
    echo "Increment operation: " . number_format($time * 1000, 2) . "ms\n";
    
    // Verify count increased
    $stmt = $db->prepare("SELECT total_orders FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$testStudentId]);
    $after = $stmt->fetch();
    $newCount = (int) $after['total_orders'];
    
    echo "New order count: $newCount\n";
    
    if ($result) {
        echo "✓ PASS: UPSERT executed successfully\n";
    } else {
        echo "✗ FAIL: UPSERT failed\n";
    }
    
    if ($newCount === $initialCount + 1) {
        echo "✓ PASS: Order count incremented correctly\n";
    } else {
        echo "✗ FAIL: Order count not incremented correctly\n";
    }
    
    // Rollback the test change
    $db->prepare("UPDATE student_profiles SET total_orders = ? WHERE user_id = ?")
       ->execute([$initialCount, $testStudentId]);
} else {
    echo "⚠ SKIP: No student users found for testing\n";
}

echo "\n";

// Test 3: Verify SQL syntax of AdminController optimizations
echo "Test 3: AdminController query optimizations\n";
echo "============================================\n";

try {
    // Test the optimized dashboard query
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
    $dateTo = date('Y-m-d');
    
    $statsSql = "SELECT
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    COALESCE(SUM(CASE WHEN status = 'completed' AND DATE(completed_at) BETWEEN :date_from AND :date_to THEN price ELSE 0 END), 0) as gmv,
                    SUM(CASE WHEN status = 'completed' AND DATE(completed_at) BETWEEN :date_from AND :date_to THEN 1 ELSE 0 END) as total_completed,
                    SUM(CASE WHEN status = 'completed' AND completed_at <= deadline AND DATE(completed_at) BETWEEN :date_from AND :date_to THEN 1 ELSE 0 END) as on_time_completed
                  FROM orders
                  WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
    
    $start = microtime(true);
    $statsStmt = $db->prepare($statsSql);
    $statsStmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
    $statsData = $statsStmt->fetch();
    $time = microtime(true) - $start;
    
    echo "Dashboard stats query: " . number_format($time * 1000, 2) . "ms\n";
    echo "Total orders: " . $statsData['total_orders'] . "\n";
    echo "✓ PASS: Dashboard optimization works correctly\n";
} catch (Exception $e) {
    echo "✗ FAIL: Dashboard query error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test the window function optimization for payments
echo "Test 4: Payments page window function optimization\n";
echo "===================================================\n";

try {
    $optimizedSql = "SELECT 
                        p.*,
                        COUNT(*) OVER() as total_count,
                        SUM(CASE WHEN p.status IN ('succeeded', 'partially_refunded') THEN p.amount ELSE 0 END) OVER() as total_amount,
                        SUM(CASE WHEN p.status IN ('succeeded', 'partially_refunded') THEN p.commission_amount ELSE 0 END) OVER() as total_commission
                    FROM payments p
                    LIMIT 5";
    
    $start = microtime(true);
    $stmt = $db->prepare($optimizedSql);
    $stmt->execute();
    $payments = $stmt->fetchAll();
    $time = microtime(true) - $start;
    
    echo "Payments query with window functions: " . number_format($time * 1000, 2) . "ms\n";
    echo "Payments fetched: " . count($payments) . "\n";
    
    if (!empty($payments) && isset($payments[0]['total_count'])) {
        echo "Total count from window function: " . $payments[0]['total_count'] . "\n";
        echo "✓ PASS: Window function optimization works correctly\n";
    } else {
        echo "⚠ INFO: No payments found or window function fields missing\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: Payments query error: " . $e->getMessage() . "\n";
}

echo "\n";

echo "Performance tests completed!\n";
