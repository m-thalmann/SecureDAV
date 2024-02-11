<?php

namespace Tests\Unit\Models;

use App\Models\FileVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FileVersionTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testIsEncryptedAttributeReturnsTrueWhenEncryptionKeyIsNotNull(): void {
        $fileVersion = FileVersion::factory()->make([
            'encryption_key' => 'encrypted',
        ]);

        $this->assertTrue($fileVersion->isEncrypted);
    }

    public function testIsEncryptedAttributeReturnsFalseWhenEncryptionKeyIsNull(): void {
        $fileVersion = FileVersion::factory()->make(['encryption_key' => null]);

        $this->assertFalse($fileVersion->isEncrypted);
    }
}
