<?php
/**
 * Encryption helper for FYP Submission Management System
 * Uses AES-256-GCM for encrypting sensitive data at rest.
 */

define('ENCRYPTION_KEY_FILE', __DIR__ . '/.encryption_key');

function getEncryptionKey(): string {
    if (file_exists(ENCRYPTION_KEY_FILE)) {
        return trim(file_get_contents(ENCRYPTION_KEY_FILE));
    }
    $key = bin2hex(random_bytes(32));
    file_put_contents(ENCRYPTION_KEY_FILE, $key);
    chmod(ENCRYPTION_KEY_FILE, 0600);
    return $key;
}

function encryptData(string $plaintext): string {
    $key = hex2bin(getEncryptionKey());
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    return base64_encode($iv . $tag . $ciphertext);
}

function decryptData(string $encoded): string {
    $key = hex2bin(getEncryptionKey());
    $data = base64_decode($encoded);
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $ciphertext = substr($data, 28);
    $result = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '');
    if ($result === false) {
        throw new RuntimeException('Decryption failed');
    }
    return $result;
}

function hashEmail(string $email): string {
    return hash('sha256', strtolower(trim($email)));
}