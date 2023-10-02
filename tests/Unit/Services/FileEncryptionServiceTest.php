<?php

namespace Tests\Unit\Services;

use App\Exceptions\FileWriteException;
use App\Services\FileEncryptionService;
use Exception;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FileEncryptionServiceTest extends TestCase {
    protected FileEncryptionService $service;

    protected string $inputPath;
    protected string $encryptedPath;
    protected string $decryptedPath;

    protected string $encryptionKey;
    protected string $testData = 'This file was created to test the file encryption service of the secure-dav project.';

    protected function setUp(): void {
        parent::setUp();

        $this->service = new FileEncryptionService();

        $tempDir = sys_get_temp_dir();

        $this->inputPath = tempnam($tempDir, 'secure-dav-testing');
        $this->encryptedPath = tempnam($tempDir, 'secure-dav-testing');
        $this->decryptedPath = tempnam($tempDir, 'secure-dav-testing');

        file_put_contents($this->inputPath, $this->testData);

        $this->encryptionKey = Str::random(16);
    }

    protected function tearDown(): void {
        parent::tearDown();

        if (file_exists($this->inputPath)) {
            unlink($this->inputPath);
        }
        if (file_exists($this->encryptedPath)) {
            unlink($this->encryptedPath);
        }
        if (file_exists($this->decryptedPath)) {
            unlink($this->decryptedPath);
        }
    }

    public function testFileCanBeEncrypted(): void {
        $inputResource = fopen($this->inputPath, 'rb');
        $encryptedResource = fopen($this->encryptedPath, 'w');

        $this->service->encrypt(
            $this->encryptionKey,
            $inputResource,
            $encryptedResource
        );

        fclose($inputResource);
        fclose($encryptedResource);

        $this->assertNotEquals(
            $this->testData,
            file_get_contents($this->encryptedPath)
        );
        $this->assertEquals(
            $this->testData,
            file_get_contents($this->inputPath)
        );
    }

    public function testFileCantBeEncryptedIfPassedResourcesAreNotValid(): void {
        $this->expectException(InvalidArgumentException::class);

        $this->service->encrypt($this->encryptionKey, 'invalid', 'invalid');
    }

    public function testEncryptThrowsAnExceptionIfOutputIsNotWritable(): void {
        $this->expectException(FileWriteException::class);

        $inputResource = fopen($this->inputPath, 'rb');
        $encryptedResource = fopen($this->encryptedPath, 'r');

        try {
            $this->service->encrypt(
                $this->encryptionKey,
                $inputResource,
                $encryptedResource
            );
        } catch (Exception $e) {
            fclose($inputResource);
            fclose($encryptedResource);

            throw $e;
        }

        fclose($inputResource);
        fclose($encryptedResource);
    }

    public function testFileCanBeDecrypted(): void {
        $inputResource = fopen($this->inputPath, 'rb');
        $encryptedResource = fopen($this->encryptedPath, 'w');

        $this->service->encrypt(
            $this->encryptionKey,
            $inputResource,
            $encryptedResource
        );

        fclose($inputResource);
        fclose($encryptedResource);

        $encryptedResource = fopen($this->encryptedPath, 'rb');
        $decryptedResource = fopen($this->decryptedPath, 'w');

        $this->service->decrypt(
            $this->encryptionKey,
            $encryptedResource,
            $decryptedResource
        );

        fclose($encryptedResource);
        fclose($decryptedResource);

        $this->assertEquals(
            $this->testData,
            file_get_contents($this->decryptedPath)
        );
    }

    public function testFileCantBeDecryptedIfPassedResourcesAreNotValid(): void {
        $this->expectException(InvalidArgumentException::class);

        $this->service->decrypt($this->encryptionKey, 'invalid', 'invalid');
    }

    public function testFileCantBeDecryptedWithWrongKey(): void {
        $inputResource = fopen($this->inputPath, 'rb');
        $encryptedResource = fopen($this->encryptedPath, 'w');

        $this->service->encrypt(
            $this->encryptionKey,
            $inputResource,
            $encryptedResource
        );

        fclose($inputResource);
        fclose($encryptedResource);

        $encryptedResource = fopen($this->encryptedPath, 'rb');
        $decryptedResource = fopen($this->decryptedPath, 'w');

        $this->service->decrypt(
            Str::random(16),
            $encryptedResource,
            $decryptedResource
        );

        fclose($encryptedResource);
        fclose($decryptedResource);

        $this->assertEmpty(file_get_contents($this->decryptedPath));
    }

    public function testDecryptThrowsAnExceptionIfOutputIsNotWritable(): void {
        $this->expectException(FileWriteException::class);

        $inputResource = fopen($this->inputPath, 'rb');
        $encryptedResource = fopen($this->encryptedPath, 'w');

        $this->service->encrypt(
            $this->encryptionKey,
            $inputResource,
            $encryptedResource
        );

        fclose($inputResource);
        fclose($encryptedResource);

        $encryptedResource = fopen($this->encryptedPath, 'rb');
        $decryptedResource = fopen($this->decryptedPath, 'r');

        try {
            $this->service->decrypt(
                $this->encryptionKey,
                $encryptedResource,
                $decryptedResource
            );
        } catch (Exception $e) {
            fclose($encryptedResource);
            fclose($decryptedResource);

            throw $e;
        }

        fclose($encryptedResource);
        fclose($decryptedResource);
    }
}
