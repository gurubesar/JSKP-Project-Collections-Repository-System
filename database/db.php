<?php
require_once __DIR__ . '/encryption.php';

$driver = getenv('DB_CONNECTION') ?: 'sqlite';
$driver = getenv('DB_CONNECTION') ?: 'sqlite';

try {
    if ($driver === 'pgsql' || $driver === 'postgres' || $driver === 'postgresql') {
        $database = getenv('DB_DATABASE') ?: 'fyp_submission_system';
        $username = getenv('DB_USERNAME') ?: 'postgres';
        $host = getenv('DB_HOST');
        
        // Use Unix socket if no host specified, otherwise use TCP connection
        if ($host) {
            $port = getenv('DB_PORT') ?: '5432';
            $password = getenv('DB_PASSWORD') ?: 'Nigaman00';
            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            $db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } else {
            // Use Unix socket (peer authentication, no password needed)
            $dsn = "pgsql:dbname={$database}";
            $db = new PDO($dsn, $username, '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
    } elseif ($driver === 'mysql') {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE') ?: 'fyp_submission_system';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $db = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } else {
        $dbPath = getenv('SQLITE_DATABASE') ?: __DIR__ . '/fyp_system.db';
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    initializeDatabase($db);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

function initializeDatabase(PDO $db): void
{
    $schemaFile = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
        ? __DIR__ . '/postgres_schema.sql'
        : __DIR__ . '/schema.sql';
    $schema = file_get_contents($schemaFile);
    $db->exec($schema);
    ensureProjectColumns($db);
}

function ensureProjectColumns(PDO $db): void
{
    try {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $columns = $db->query("PRAGMA table_info(projects)")->fetchAll(PDO::FETCH_ASSOC);
            $names = array_map(static fn($column) => $column['name'] ?? '', $columns);

            if (!in_array('category_encrypted', $names, true)) {
                $db->exec('ALTER TABLE projects ADD COLUMN category_encrypted TEXT');
            }
            if (!in_array('progress_percentage', $names, true)) {
                $db->exec('ALTER TABLE projects ADD COLUMN progress_percentage INTEGER NOT NULL DEFAULT 0');
            }
            return;
        }

        if ($driver === 'pgsql') {
            $db->exec('ALTER TABLE projects ADD COLUMN IF NOT EXISTS category_encrypted TEXT');
            $db->exec('ALTER TABLE projects ADD COLUMN IF NOT EXISTS progress_percentage INTEGER NOT NULL DEFAULT 0');
            return;
        }

        if ($driver === 'mysql') {
            $columns = $db->query("SHOW COLUMNS FROM projects LIKE 'category_encrypted'")->fetchAll(PDO::FETCH_ASSOC);
            if (count($columns) === 0) {
                $db->exec('ALTER TABLE projects ADD COLUMN category_encrypted TEXT NULL');
            }
            $columns = $db->query("SHOW COLUMNS FROM projects LIKE 'progress_percentage'")->fetchAll(PDO::FETCH_ASSOC);
            if (count($columns) === 0) {
                $db->exec('ALTER TABLE projects ADD COLUMN progress_percentage INTEGER NOT NULL DEFAULT 0');
            }
        }
    } catch (Throwable $error) {
        // Keep the app available if the table does not exist yet; initializeDatabase creates it.
    }
}

ensureProjectColumns($db);
