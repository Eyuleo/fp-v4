<?php

/**
 * Student Balance Reconciliation Script
 * 
 * This script identifies students whose current balance + withdrawn amount 
 * is less than the sum of their successful payments from completed orders.
 * It then applies a fix to add the missing difference.
 */

// Adjust this path if your script is not in a 'scripts' subfolder
if (!file_exists(__DIR__ . '/../config/database.php')) {
    die("Error: Could not find config/database.php. Please check the file path.\n");
}

$db = require __DIR__ . '/../config/database.php';

echo "Starting student balance reconciliation...\n";
echo "----------------------------------------\n";

try {
    // 1. Calculate expected total earnings per student from completed orders
    // We only look at 'succeeded' payments for 'completed' orders
    $sql = "
        SELECT 
            o.student_id, 
            SUM(p.student_amount) as expected_total_earnings
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        WHERE p.status = 'succeeded' 
        AND o.status = 'completed'
        GROUP BY o.student_id
    ";

    $stmt = $db->query($sql);
    $studentEarnings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updatedCount = 0;
    $processedCount = 0;

    foreach ($studentEarnings as $record) {
        $processedCount++;
        $studentId = (int)$record['student_id'];
        $expectedTotal = (float)$record['expected_total_earnings'];

        // 2. Get current profile state to verify what they currently have
        $stmt = $db->prepare("SELECT available_balance, total_withdrawn FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$studentId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        $currentAvailable = 0.0;
        $currentWithdrawn = 0.0;
        $profileExists = false;

        if ($profile) {
            $profileExists = true;
            $currentAvailable = (float)$profile['available_balance'];
            $currentWithdrawn = (float)$profile['total_withdrawn'];
        }

        // 3. Calculate Discrepancy
        // Logic: Total Money Ever Earned should equal (Money currently held + Money taken out)
        $currentLifetime = $currentAvailable + $currentWithdrawn;
        
        // The amount we need to ADD to fix the profile
        $missingAmount = round($expectedTotal - $currentLifetime, 2);

        // Check if missing amount is positive (tolerance for floating point math)
        if ($missingAmount > 0.001) {
            $status = $profileExists ? "Profile Exists" : "Profile Missing";
            echo "[FIXING] Student ID {$studentId} ({$status})\n";
            echo "  - Expected Lifetime Earnings: $" . number_format($expectedTotal, 2) . "\n";
            echo "  - Current Lifetime State:     $" . number_format($currentLifetime, 2) . "\n";
            echo "  - Action: Adding missing $" . number_format($missingAmount, 2) . "\n";

            // 4. Apply Fix using UPSERT
            // We use unique parameter names (:amount_insert, :amount_update) 
            // to avoid PDO parameter count errors.
            $upsertSql = "
                INSERT INTO student_profiles (
                    user_id, available_balance, skills, portfolio_files, created_at, updated_at
                ) VALUES (
                    :student_id, :amount_insert, '[]', '[]', NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    available_balance = available_balance + :amount_update,
                    updated_at = NOW()
            ";

            $updateStmt = $db->prepare($upsertSql);
            $updateStmt->execute([
                'student_id'    => $studentId,
                'amount_insert' => $missingAmount,
                'amount_update' => $missingAmount // Pass the value a second time
            ]);

            $updatedCount++;
        } elseif ($missingAmount < -0.001) {
            // This usually means manual adjustments were made or logic differs, best to skip automated fixes
            echo "[SKIP] Student ID {$studentId} has $" . number_format(abs($missingAmount), 2) . " MORE than expected. Skipping.\n";
        }
    }

    echo "----------------------------------------\n";
    echo "Reconciliation complete.\n";
    echo "Processed: {$processedCount} students.\n";
    echo "Fixed:     {$updatedCount} profiles.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}