<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase {
    /**
     * @dataProvider formatBytesProvider
     */
    public function testFormatBytesReturnsTheExpectedFormat(
        int $size,
        string $expectedFormat
    ): void {
        $this->assertEquals($expectedFormat, formatBytes($size));
    }

    public function testFormatBytesReturnsTheExpectedPrecision(): void {
        $this->assertEquals('1.001 KB', formatBytes(1025, precision: 3));
    }

    public function testFormatBytesReturnsTheBytesIfSizeIsLessThanOrEqualZero(): void {
        $this->assertEquals('0 B', formatBytes(0));
        $this->assertEquals('-1 B', formatBytes(-1));
    }

    public function testGenerateInitialsReturnsTheFirstCharacterOfTheFirstAndLastName(): void {
        $firstName = fake()->firstName();
        $middleName = fake()->firstName();
        $lastName = fake()->lastName();

        $this->assertEquals(
            Str::upper($firstName[0] . $lastName[0]),
            generateInitials("$firstName $middleName $lastName")
        );
    }

    public function testGenerateInitialsReturnsTheFirstTwoCharactersIfOnlyOneNameIsGiven(): void {
        $name = fake()->firstName();

        $this->assertEquals(
            Str::upper(Str::substr($name, 0, 2)),
            generateInitials($name)
        );
    }

    /**
     * @dataProvider fileIconForExtensionProvider
     */
    public function testGetFileIconForExtensionReturnsTheExpectedIcon(
        string $extension,
        string $expectedIcon
    ): void {
        $this->assertStringContainsString(
            $expectedIcon,
            getFileIconForExtension($extension)
        );
    }

    public function testGetFileIconForExtensionReturnsTheDefaultIconIfTheExtensionIsNull(): void {
        $this->assertStringContainsString(
            'fa-file',
            getFileIconForExtension(null)
        );
    }

    public function testGetFileIconForExtensionReturnsTheDefaultIconIfNoIconIsFound(): void {
        $this->assertStringContainsString(
            'fa-file',
            getFileIconForExtension('foo')
        );
    }

    public static function formatBytesProvider(): array {
        return [
            [1, '1 B'],
            [1030, '1.01 KB'],
            [1055770, '1.01 MB'],
            [1083741825, '1.01 GB'],
            [1109511627777, '1.01 TB'],
        ];
    }

    public static function fileIconForExtensionProvider(): array {
        return [
            ['jpg', 'fa-file-image'],
            ['jpeg', 'fa-file-image'],
            ['png', 'fa-file-image'],
            ['gif', 'fa-file-image'],
            ['svg', 'fa-file-image'],

            ['mp4', 'fa-file-video'],
            ['webm', 'fa-file-video'],
            ['mov', 'fa-file-video'],
            ['avi', 'fa-file-video'],
            ['wmv', 'fa-file-video'],
            ['mkv', 'fa-file-video'],

            ['mp3', 'fa-file-audio'],
            ['wav', 'fa-file-audio'],
            ['ogg', 'fa-file-audio'],
            ['wma', 'fa-file-audio'],

            ['doc', 'fa-file-word'],
            ['docx', 'fa-file-word'],
            ['odt', 'fa-file-word'],
            ['rtf', 'fa-file-word'],
            ['txt', 'fa-file-word'],

            ['xls', 'fa-file-excel'],
            ['xlsx', 'fa-file-excel'],
            ['ods', 'fa-file-excel'],
            ['csv', 'fa-file-excel'],

            ['pdf', 'fa-file-pdf'],

            ['zip', 'fa-file-zipper'],
            ['rar', 'fa-file-zipper'],
            ['7z', 'fa-file-zipper'],
            ['tar', 'fa-file-zipper'],
            ['gz', 'fa-file-zipper'],
            ['bz2', 'fa-file-zipper'],

            ['kdbx', 'fa-file-shield'],
        ];
    }
}
