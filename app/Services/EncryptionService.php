<?php

namespace App\Services;

use App\Exceptions\EncryptionException;
use App\Exceptions\StreamWriteException;
use InvalidArgumentException;

class EncryptionService {
    protected const DEFAULT_CIPHER = 'AES-128-CBC';

    /**
     * The number of cipher-blocks to encrypt at once.
     * E.g. [1024] -> 1024 * 16 = 16KB for AES-128-CBC
     */
    protected const ENCRYPTION_BLOCKS = 1024;

    protected readonly string $cipher;

    public function __construct(?string $cipher = null) {
        $this->cipher = $cipher ?? static::DEFAULT_CIPHER;
    }

    /**
     * Encrypts the given stream with the given key and writes the result into the given output stream.
     * Does **not** close the pointers.
     *
     * @param string $key
     * @param resource $inputResource
     * @param resource $outputResource
     *
     * @throws \InvalidArgumentException
     * @throws \App\Exceptions\EncryptionException
     * @throws \App\Exceptions\StreamWriteException
     */
    public function encrypt(
        string $key,
        mixed $inputResource,
        mixed $outputResource
    ): void {
        if (!is_resource($inputResource) || !is_resource($outputResource)) {
            throw new InvalidArgumentException(
                'Resources must be valid streams.'
            );
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        if (@fwrite($outputResource, $iv) === false) {
            throw new StreamWriteException();
        }

        while (!feof($inputResource)) {
            $plainText = fread(
                $inputResource,
                static::ENCRYPTION_BLOCKS * $ivLength
            );

            $cipherText = openssl_encrypt(
                $plainText,
                $this->cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($cipherText === false) {
                throw new EncryptionException(
                    'Could not encrypt the given stream.'
                );
            }

            $iv = substr($cipherText, 0, $ivLength);

            if (@fwrite($outputResource, $cipherText) === false) {
                throw new StreamWriteException();
            }
        }
    }

    /**
     * Decrypts the given stream with the given key and writes the result into the given output stream.
     * Does **not** close the pointers.
     *
     * @param string $key
     * @param resource $inputResource
     * @param resource $outputResource
     *
     * @throws \InvalidArgumentException
     * @throws \App\Exceptions\EncryptionException
     * @throws \App\Exceptions\StreamWriteException
     */
    public function decrypt(
        string $key,
        mixed $inputResource,
        mixed $outputResource
    ): void {
        if (!is_resource($inputResource) || !is_resource($outputResource)) {
            throw new InvalidArgumentException(
                'Resources must be valid streams.'
            );
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = fread($inputResource, $ivLength);

        while (!feof($inputResource)) {
            $cipherText = fread(
                $inputResource,
                (static::ENCRYPTION_BLOCKS + 1) * $ivLength
            );

            $plainText = openssl_decrypt(
                $cipherText,
                $this->cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($plainText === false) {
                throw new EncryptionException(
                    'Could not decrypt the given stream.'
                );
            }

            $iv = substr($cipherText, 0, $ivLength);

            if (@fwrite($outputResource, $plainText) === false) {
                throw new StreamWriteException();
            }
        }
    }
}
