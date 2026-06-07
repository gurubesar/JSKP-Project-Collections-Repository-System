<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

require $projectRoot . '/database/db.php';
require_once $projectRoot . '/database/encryption.php';

function terminal_line(string $text = ''): void
{
    echo $text . PHP_EOL;
}

function database_tables(PDO $db): array
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        $stmt = $db->query(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = 'public'
               AND table_type = 'BASE TABLE'
             ORDER BY table_name"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    if ($driver === 'sqlite') {
        $stmt = $db->query(
            "SELECT name
             FROM sqlite_master
             WHERE type = 'table'
               AND name NOT LIKE 'sqlite_%'
             ORDER BY name"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $stmt = $db->query('SHOW TABLES');
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function quote_identifier(PDO $db, string $identifier): string
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $quote = $driver === 'mysql' ? '`' : '"';
    return $quote . str_replace($quote, $quote . $quote, $identifier) . $quote;
}

function display_value(string $column, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    $text = (string) $value;

    if (str_ends_with($column, '_encrypted') && $text !== '') {
        try {
            $text = decryptData($text);
            $column = substr($column, 0, -10);
        } catch (Throwable $error) {
            $text = '[unable to decrypt]';
        }
    }

    if ($column === 'password_hash' || $column === 'token_hash') {
        return '[hidden hash]';
    }

    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    return mb_strlen($text) > 180 ? mb_substr($text, 0, 177) . '...' : $text;
}

try {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    terminal_line('Database driver: ' . $driver);
    terminal_line('Generated at: ' . date('Y-m-d H:i:s'));

    $tables = database_tables($db);
    if (!$tables) {
        terminal_line('No tables found.');
        exit(0);
    }

    foreach ($tables as $table) {
        $table = (string) $table;
        $countStmt = $db->query('SELECT COUNT(*) FROM ' . quote_identifier($db, $table));
        $rowCount = (int) $countStmt->fetchColumn();

        terminal_line();
        terminal_line(str_repeat('=', 80));
        terminal_line($table . ' (' . $rowCount . ' rows)');
        terminal_line(str_repeat('=', 80));

        if ($rowCount === 0) {
            terminal_line('No records.');
            continue;
        }

        $stmt = $db->query('SELECT * FROM ' . quote_identifier($db, $table));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $index => $row) {
            terminal_line('[' . ($index + 1) . ']');
            foreach ($row as $column => $value) {
                terminal_line('  ' . $column . ': ' . display_value((string) $column, $value));
            }
            terminal_line();
        }
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Unable to show database: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
