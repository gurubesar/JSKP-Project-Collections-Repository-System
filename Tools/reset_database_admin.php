<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

require $projectRoot . '/database/db.php';
require_once $projectRoot . '/database/encryption.php';

function reset_all_tables(PDO $db): void
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        $db->exec(
            'TRUNCATE TABLE
                audit_logs,
                comment_visibility,
                comments,
                file_versions,
                files,
                notifications,
                password_reset_tokens,
                project_members,
                students,
                lecturers,
                submissions,
                projects,
                users
             RESTART IDENTITY CASCADE'
        );
        return;
    }

    $tables = [
        'audit_logs',
        'comment_visibility',
        'comments',
        'file_versions',
        'files',
        'notifications',
        'password_reset_tokens',
        'project_members',
        'students',
        'lecturers',
        'submissions',
        'projects',
        'users',
    ];

    if ($driver === 'sqlite') {
        $db->exec('PRAGMA foreign_keys = OFF');
    }

    foreach ($tables as $table) {
        $db->exec('DELETE FROM ' . $table);
    }

    if ($driver === 'sqlite') {
        $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('" . implode("','", $tables) . "')");
        $db->exec('PRAGMA foreign_keys = ON');
    }
}

try {
    initializeDatabase($db);
    $db->beginTransaction();

    reset_all_tables($db);

    $stmt = $db->prepare(
        'INSERT INTO users (name_encrypted, email_hash, email_encrypted, password_hash, role, created_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        encryptData('admin'),
        hashEmail('admin@admin.com'),
        encryptData('admin'),
        password_hash('admin123', PASSWORD_DEFAULT),
        'admin',
        null,
    ]);

    $db->commit();

    echo "Database cleared successfully.\n";
    echo "Created one admin user:\n";
    echo "  username: admin@admin.com\n";
    echo "  password: admin123\n";
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    fwrite(STDERR, 'Reset failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
