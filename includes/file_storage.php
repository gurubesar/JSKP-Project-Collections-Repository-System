<?php

declare(strict_types=1);

const SECURE_UPLOAD_MAX_BYTES = 209715200;

/**
 * Returns an upload directory outside web-accessible document paths.
 */
function secure_upload_base_dir(): string
{
    $base = dirname(__DIR__) . '/storage/repository_files';
    if (!is_dir($base)) {
        mkdir($base, 0750, true);
    }
    return $base;
}

/**
 * Generates a random repository filename so uploaded names cannot control paths.
 */
function random_repository_filename(string $extension): string
{
    return bin2hex(random_bytes(16)) . '.' . strtolower($extension);
}

/**
 * Rejects double-extension and executable-like filenames before upload storage.
 */
function assert_safe_original_filename(string $originalName): void
{
    $base = basename($originalName);
    if ($base === '' || preg_match('/\.(php|phtml|phar|exe|bat|cmd|sh|js|html?|pl|py|cgi)(\.|$)/i', $base)) {
        throw new RuntimeException('Executable or double-extension files are not allowed.');
    }

    $parts = array_values(array_filter(explode('.', $base), static fn($part) => $part !== ''));
    if (count($parts) !== 2) {
        throw new RuntimeException('Files must use a single allowed extension.');
    }
}

/**
 * Validates size, extension, MIME type, and upload status for repository documents.
 */
function validate_repository_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }

    if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > SECURE_UPLOAD_MAX_BYTES) {
        throw new RuntimeException('File size must be 200 MB or less.');
    }

    $originalName = (string) ($file['name'] ?? '');
    assert_safe_original_filename($originalName);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $allowed = [
        'pdf' => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    if (!array_key_exists($extension, $allowed)) {
        throw new RuntimeException('Only PDF, DOCX, PPTX, and ZIP files are allowed.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file((string) $file['tmp_name']);
    if (!in_array($mime, $allowed[$extension], true)) {
        throw new RuntimeException('File MIME type does not match its extension.');
    }

    return [$originalName, $extension, $mime];
}

/**
 * Stores an uploaded repository document in the secure storage directory.
 */
function store_repository_upload(array $file, int $projectId): array
{
    [$originalName, $extension, $mime] = validate_repository_upload($file);
    $projectDir = secure_upload_base_dir() . '/' . $projectId;
    if (!is_dir($projectDir)) {
        mkdir($projectDir, 0750, true);
    }

    $storedName = random_repository_filename($extension);
    $destination = $projectDir . '/' . $storedName;
    if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to store uploaded file.');
    }
    chmod($destination, 0640);

    return [
        'original_name' => $originalName,
        'stored_path' => 'secure://' . $projectId . '/' . $storedName,
        'absolute_path' => $destination,
        'mime' => $mime,
    ];
}

/**
 * Resolves an encrypted stored path while preventing traversal outside storage.
 */
function resolve_secure_file_path(string $storedPath): string
{
    if (str_starts_with($storedPath, 'secure://')) {
        $relative = substr($storedPath, strlen('secure://'));
        if (!preg_match('/^[0-9]+\/[a-f0-9]{32}\.(pdf|docx|pptx|zip)$/', $relative)) {
            throw new RuntimeException('Invalid stored file path.');
        }
        $path = secure_upload_base_dir() . '/' . $relative;
    } else {
        $path = dirname(__DIR__) . '/' . ltrim($storedPath, '/');
    }

    $realBase = realpath(dirname($path));
    $realPath = is_file($path) ? realpath($path) : false;
    if ($realBase === false || $realPath === false || !str_starts_with($realPath, dirname(__DIR__))) {
        throw new RuntimeException('File not found.');
    }

    return $realPath;
}
