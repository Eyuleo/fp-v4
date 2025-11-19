<?php

require_once __DIR__ . '/../config/database.php';

$pdo = getDatabaseConnection();

echo "Current migrations in database:\n";
echo str_repeat('-', 80) . "\n";

$stmt = $pdo->query('SELECT id, migration, batch FROM migrations ORDER BY id');
$migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($migrations as $migration) {
    printf("ID: %3d | Batch: %2d | %s\n", 
        $migration['id'], 
        $migration['batch'], 
        $migration['migration']
    );
}

echo str_repeat('-', 80) . "\n";
echo "Total: " . count($migrations) . " migrations recorded\n";
