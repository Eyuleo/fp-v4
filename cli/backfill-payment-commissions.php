<?php

/**
 * Backfill commission amounts for completed orders
 * 
 * This script updates the payments table to include commission_amount and student_amount
 * for orders that were completed before this fix was implemented.
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Backfill Payment Commission Data ===\n\n";

try {
    $db = getDatabaseConnection();
    
    // Find all completed orders with payments that don't have commission data
    $stmt = $db->query("
        SELECT 
            p.id as payment_id,
            p.order_id,
            p.amount,
            o.price,
            o.commission_rate,
            p.commission_amount,
            p.student_amount
        FROM payments p
        INNER JOIN orders o ON p.order_id = o.id
        WHERE o.status = 'completed'
        AND p.status = 'succeeded'
        AND (p.commission_amount IS NULL OR p.commission_amount = 0)
    ");
    
    $paymentsToUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($paymentsToUpdate);
    
    echo "Found $count payment(s) that need commission data backfilled.\n\n";
    
    if ($count === 0) {
        echo "No payments need updating. All done!\n";
        exit(0);
    }
    
    $db->beginTransaction();
    
    $updateStmt = $db->prepare("
        UPDATE payments 
        SET commission_amount = :commission_amount,
            student_amount = :student_amount
        WHERE id = :payment_id
    ");
    
    $updated = 0;
    foreach ($paymentsToUpdate as $payment) {
        $orderAmount = (float) $payment['price'];
        $commissionRate = (float) $payment['commission_rate'];
        $commissionAmount = $orderAmount * ($commissionRate / 100);
        $studentAmount = $orderAmount - $commissionAmount;
        
        echo "Updating Payment #{$payment['payment_id']} (Order #{$payment['order_id']}):\n";
        echo "  Order Amount: $" . number_format($orderAmount, 2) . "\n";
        echo "  Commission Rate: {$commissionRate}%\n";
        echo "  Commission Amount: $" . number_format($commissionAmount, 2) . "\n";
        echo "  Student Amount: $" . number_format($studentAmount, 2) . "\n\n";
        
        $updateStmt->execute([
            'payment_id'        => $payment['payment_id'],
            'commission_amount' => $commissionAmount,
            'student_amount'    => $studentAmount,
        ]);
        
        $updated++;
    }
    
    $db->commit();
    
    echo "=== Summary ===\n";
    echo "Successfully updated $updated payment record(s).\n";
    echo "Commission data has been backfilled for all completed orders.\n";
    echo "\nYou can now view accurate commission statistics in the admin dashboard.\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
