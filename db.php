<?php
$dbPath = __DIR__ . '/fyp_system.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

function initializeDatabase($db) {
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $db->exec($schema);
}

