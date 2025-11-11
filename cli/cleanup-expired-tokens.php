<?php

/**
 * Cleanup Expired Stripe Connect Tokens
 *
 * Run this periodically via cron to clean up expired tokens
 */

require __DIR__ . '/../vendor/autoload.php';

// Get database connection
$db = require __DIR__ . '/../config/database.php';

try {
    // Delete expired tokens
    $stmt = $db->prepare('DELETE FROM stripe_connect_tokens WHERE expires_at < NOW()');
    $stmt->execute();

    $deletedCount = $stmt->rowCount();

    echo "Cleanup complete. Deleted {$deletedCount} expired token(s).\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
