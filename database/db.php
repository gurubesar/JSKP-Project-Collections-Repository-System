<?php
require_once __DIR__ . '/encryption.php';

$driver = 'sqlite';

try {

    $dbPath = __DIR__ . '/fyp_system.db';

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

function initializeDatabase(PDO $db): void
{
    $schemaFile = __DIR__ . '/schema.sql';
    $schema = file_get_contents($schemaFile);
    $db->exec($schema);
}