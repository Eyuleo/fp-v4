<?php
/**
 * Fix Payment Status Script
 * 
 * This script updates payment records to 'succeeded' status for all completed orders
 * that currently have 'pending' status.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "Checking for completed orders with pending payments...\n\n";
    
    // First, let's see what we have
    $checkSql = "SELECT 
                    o.id as order_id,
                    o.status as order_status,
                    o.price,
                    p.id as payment_id,
                    p.status as payment_status,
                    p.amount,
                    p.commission_amount
                FROM orders o
                LEFT JOIN payments p ON o.id = p.order_id
                WHERE o.status = 'completed'";
    
    $checkStmt = $db->query($checkSql);
    $orders = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($orders) . " completed orders\n\n";
    
    $needsUpdate = 0;
    $noPayment = 0;
    $alreadySucceeded = 0;
    
    foreach ($orders as $order) {
        if (!$order['payment_id']) {
            echo "⚠️  Order #{$order['order_id']} - NO PAYMENT RECORD\n";
            $noPayment++;
        } elseif ($order['payment_status'] === 'pending') {
            echo "🔧 Order #{$order['order_id']} - Payment #{$order['payment_id']} needs update (pending → succeeded)\n";
            $needsUpdate++;
        } elseif ($order['payment_status'] === 'succeeded') {
            echo "✅ Order #{$order['order_id']} - Payment #{$order['payment_id']} already succeeded\n";
            $alreadySucceeded++;
        } else {
            echo "ℹ️  Order #{$order['order_id']} - Payment #{$order['payment_id']} status: {$order['payment_status']}\n";
        }
    }
    
    echo "\n";
    echo "Summary:\n";
    echo "- Already succeeded: $alreadySucceeded\n";
    echo "- Needs update: $needsUpdate\n";
    echo "- No payment record: $noPayment\n";
    echo "\n";
    
    if ($needsUpdate > 0) {
        echo "Do you want to update $needsUpdate payment(s) to 'succeeded' status? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $answer = trim(strtolower($line));
        fclose($handle);
        
        if ($answer === 'yes' || $answer === 'y') {
            $updateSql = "UPDATE payments p
                         INNER JOIN orders o ON p.order_id = o.id
                         SET p.status = 'succeeded', p.updated_at = NOW()
                         WHERE o.status = 'completed'
                           AND p.status = 'pending'";
            
            $result = $db->exec($updateSql);
            
            echo "\n✅ Updated $result payment record(s) to 'succeeded' status\n";
            
            // Show updated stats
            echo "\nChecking updated stats...\n";
            $statsSql = "SELECT 
                            SUM(CASE WHEN p.status = 'succeeded' THEN p.amount ELSE 0 END) as total_amount,
                            SUM(CASE WHEN p.status = 'succeeded' THEN p.commission_amount ELSE 0 END) as total_commission,
                            COUNT(CASE WHEN p.status = 'succeeded' THEN 1 END) as succeeded_count
                        FROM payments p";
            
            $statsStmt = $db->query($statsSql);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "\nUpdated Payment Statistics:\n";
            echo "- Total Volume: $" . number_format($stats['total_amount'], 2) . "\n";
            echo "- Total Commission: $" . number_format($stats['total_commission'], 2) . "\n";
            echo "- Successful Payments: " . $stats['succeeded_count'] . "\n";
        } else {
            echo "\nNo changes made.\n";
        }
    } else {
        echo "No updates needed!\n";
    }
    
    if ($noPayment > 0) {
        echo "\n⚠️  WARNING: $noPayment completed order(s) have no payment records!\n";
        echo "This might indicate orders were created without going through the payment flow.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
