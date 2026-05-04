<?php
/**
 * Database Initialization Script
 * Run this once to initialize the SQLite database with schema and sample data
 */

require __DIR__ . '/db.php';

$dbPath = __DIR__ . '/fyp_system.db';

try {
    // Create or initialize the database
    if (!file_exists($dbPath)) {
        echo "Creating database at: $dbPath\n";
    }
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $db->exec($stmt);
        }
    }
    
    echo "✓ Database initialized successfully!\n";
    echo "✓ Sample users created:\n";
    echo "  - admin@example.com / password (Admin)\n";
    echo "  - dr.ali@example.com / password (Lecturer)\n";
    echo "  - ahmad@example.com / password (Student)\n";
    
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}
