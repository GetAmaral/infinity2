<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service for encrypting and decrypting sensitive audit data
 *
 * Uses AES-256-GCM encryption to protect sensitive information
 * stored in audit logs (changes, metadata).
 */
final class AuditEncryptionService
{
    private const CIPHER = 'aes-256-gcm';
    private const TAG_LENGTH = 16;

    public function __construct(
        #[Autowire(env: 'AUDIT_ENCRYPTION_KEY')]
        private readonly string $encryptionKey
    ) {
        if (strlen($this->encryptionKey) < 32) {
            throw new \InvalidArgumentException('Encryption key must be at least 32 characters');
        }
    }

    /**
     * Encrypt audit changes data
     *
     * @param array $changes Field-level changes to encrypt
     * @return string Base64-encoded encrypted data with IV and auth tag
     * @throws \RuntimeException If encryption fails
     */
    public function encryptChanges(array $changes): string
    {
        if (empty($changes)) {
            return '';
        }

        $json = json_encode($changes);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode changes to JSON');
        }

        return $this->encrypt($json);
    }

    /**
     * Decrypt audit changes data
     *
     * @param string $encrypted Base64-encoded encrypted data
     * @return array Decrypted changes array
     * @throws \RuntimeException If decryption fails
     */
    public function decryptChanges(string $encrypted): array
    {
        if (empty($encrypted)) {
            return [];
        }

        $json = $this->decrypt($encrypted);

        $changes = json_decode($json, true);
        if ($changes === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode decrypted changes: ' . json_last_error_msg());
        }

        return $changes ?? [];
    }

    /**
     * Encrypt metadata
     *
     * @param array|null $metadata Metadata to encrypt
     * @return string|null Base64-encoded encrypted data
     */
    public function encryptMetadata(?array $metadata): ?string
    {
        if ($metadata === null || empty($metadata)) {
            return null;
        }

        $json = json_encode($metadata);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode metadata to JSON');
        }

        return $this->encrypt($json);
    }

    /**
     * Decrypt metadata
     *
     * @param string|null $encrypted Base64-encoded encrypted data
     * @return array|null Decrypted metadata
     */
    public function decryptMetadata(?string $encrypted): ?array
    {
        if ($encrypted === null || empty($encrypted)) {
            return null;
        }

        $json = $this->decrypt($encrypted);

        $metadata = json_decode($json, true);
        if ($metadata === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode decrypted metadata: ' . json_last_error_msg());
        }

        return $metadata;
    }

    /**
     * Internal encryption method using AES-256-GCM
     *
     * @param string $plaintext Data to encrypt
     * @return string Base64-encoded: IV + auth tag + ciphertext
     * @throws \RuntimeException If encryption fails
     */
    private function encrypt(string $plaintext): string
    {
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false) {
            throw new \RuntimeException('Failed to get cipher IV length');
        }

        $iv = openssl_random_pseudo_bytes($ivLength);
        if ($iv === false) {
            throw new \RuntimeException('Failed to generate initialization vector');
        }

        $tag = '';
        $encrypted = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        // Combine IV + tag + ciphertext and base64 encode
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Internal decryption method using AES-256-GCM
     *
     * @param string $encrypted Base64-encoded encrypted data
     * @return string Decrypted plaintext
     * @throws \RuntimeException If decryption fails
     */
    private function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted, true);
        if ($data === false) {
            throw new \RuntimeException('Invalid base64 encoding');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false) {
            throw new \RuntimeException('Failed to get cipher IV length');
        }

        if (strlen($data) < $ivLength + self::TAG_LENGTH) {
            throw new \RuntimeException('Encrypted data is too short');
        }

        // Extract IV, tag, and ciphertext
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, self::TAG_LENGTH);
        $ciphertext = substr($data, $ivLength + self::TAG_LENGTH);

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed - data may be corrupted or tampered');
        }

        return $decrypted;
    }

    /**
     * Check if encryption is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->encryptionKey) && strlen($this->encryptionKey) >= 32;
    }

    /**
     * Generate a secure encryption key
     *
     * This should be run once and the result stored in .env.local
     *
     * @return string 64-character hex string (256 bits)
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}
