<?php

namespace App\Services;

use InvalidArgumentException;

class FileEncryptionService {
    protected const FILE_ENCRYPTION_BLOCKS = 10000;
    protected const CIPHER = 'AES-128-CBC';

    /**
     * Encrypts the given file with the given key and writes the result into the given output file.
     * Does not close the file pointers.
     *
     * @param string $key
     * @param resource $inputResource
     * @param resource $outputResource
     *
     * @throws InvalidArgumentException
     */
    public function encrypt(
        string $key,
        mixed $inputResource,
        mixed $outputResource
    ): void {
        if (!is_resource($inputResource) || !is_resource($outputResource)) {
            throw new InvalidArgumentException(
                'Resources must be valid file pointers.'
            );
        }

        $ivLength = openssl_cipher_iv_length(static::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLength);

        fwrite($outputResource, $iv);

        while (!feof($inputResource)) {
            $plainText = fread(
                $inputResource,
                $ivLength * static::FILE_ENCRYPTION_BLOCKS
            );

            $cipherText = openssl_encrypt(
                $plainText,
                static::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            $iv = substr($cipherText, 0, $ivLength);

            fwrite($outputResource, $cipherText);
        }
    }

    /**
     * Decrypts the given file with the given key and writes the result into the given output file.
     * Does not close the file pointers.
     *
     * @param string $key
     * @param resource $inputResource
     * @param resource $outputResource
     *
     * @throws InvalidArgumentException
     */
    public function decrypt(
        string $key,
        mixed $inputResource,
        mixed $outputResource
    ): void {
        if (!is_resource($inputResource) || !is_resource($outputResource)) {
            throw new InvalidArgumentException(
                'Resources must be valid file pointers.'
            );
        }

        $ivLength = openssl_cipher_iv_length(static::CIPHER);
        $iv = fread($inputResource, $ivLength);

        while (!feof($inputResource)) {
            $cipherText = fread(
                $inputResource,
                $ivLength * (static::FILE_ENCRYPTION_BLOCKS + 1)
            );
            $plainText = openssl_decrypt(
                $cipherText,
                static::CIPHER,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            $iv = substr($plainText, 0, $ivLength);

            fwrite($outputResource, $plainText);
        }
    }
}
