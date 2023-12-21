<?php

namespace Tests\Unit;

use Exception;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HelpersTest extends TestCase {
    use LazilyRefreshDatabase;

    /**
     * @dataProvider formatHoursProvider
     */
    public function testFormatHoursReturnsTheExpectedFormat(
        float $hours,
        string $expectedFormat
    ): void {
        $this->assertEquals($expectedFormat, formatHours($hours));
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

    /**
     * @dataProvider tableLoopDropdownPositionAlignedProvider
     */
    public function testGetTableLoopDropdownPositionAlignedReturnsExpectedPosition(
        int $index,
        int $totalItems,
        int $dropdownHeightRows,
        string $expectedPosition,
        string $expectedAlign
    ): void {
        $positionAligned = getTableLoopDropdownPositionAligned(
            $index,
            $totalItems,
            $dropdownHeightRows
        );

        [$position, $align] = explode('-', $positionAligned, 2);

        $this->assertEquals($expectedPosition, $position);
        $this->assertEquals($expectedAlign, $align);
    }

    public function testAuthUserReturnsTheAuthenticatedUser(): void {
        $user = $this->createUser();

        auth()->login($user);

        $this->assertEquals($user->id, authUser()->id);
    }

    public function testAuthUserReturnsNullIfNoUserIsAuthenticated(): void {
        $this->assertNull(authUser());
    }

    public function testProcessResourcePassesTheResourceToTheCallback(): void {
        $contents = 'test contents';

        $stream = $this->createStream($contents);

        $expectedResponse = 'test response';

        $response = processResource($stream, function (
            mixed $receivedStream
        ) use ($stream, $expectedResponse) {
            $this->assertIsResource($receivedStream);
            $this->assertEquals($stream, $receivedStream);

            return $expectedResponse;
        });

        $this->assertEquals($expectedResponse, $response);
    }

    public function testProcessResourceClosesTheStreamAfterTheCallbackIsFinished(): void {
        $stream = $this->createStream('test contents');

        $resource = processResource($stream, function (mixed $receivedStream) {
            $this->assertIsNotClosedResource($receivedStream);

            return $receivedStream;
        });

        $this->assertIsClosedResource($resource);
    }

    public function testProcessResourceDoesNotCloseStreamIfIsAlreadyClosedAfterTheCallbackIsFinished(): void {
        $stream = $this->createStream('test contents');

        fclose($stream);

        $resource = processResource($stream, function (mixed $receivedStream) {
            $this->assertIsClosedResource($receivedStream);

            return $receivedStream;
        });

        $this->assertIsClosedResource($resource);
    }

    public function testProcessResourceClosesTheStreamOnExceptionAndRethrows(): void {
        $expectedException = new Exception('test exception');

        $stream = $this->createStream('test contents');

        $this->expectExceptionObject($expectedException);

        $resource = null;

        try {
            processResource($stream, function (mixed $receivedStream) use (
                $expectedException,
                &$resource
            ) {
                $this->assertIsNotClosedResource($receivedStream);

                $resource = $receivedStream;

                throw $expectedException;
            });
        } catch (Exception $e) {
            $this->assertIsClosedResource($resource);

            throw $e;
        }
    }

    public function testProcessResourceDoesNotCloseStreamIfIsAlreadyClosedOnException(): void {
        $expectedException = new Exception('test exception');

        $stream = $this->createStream('test contents');

        fclose($stream);

        $this->expectExceptionObject($expectedException);

        $resource = null;

        try {
            processResource($stream, function (mixed $receivedStream) use (
                $expectedException,
                &$resource
            ) {
                $this->assertIsClosedResource($receivedStream);

                $resource = $receivedStream;

                throw $expectedException;
            });
        } catch (Exception $e) {
            $this->assertIsClosedResource($resource);

            throw $e;
        }
    }

    public function testProcessResourceExecutesExceptionCallbackOnException(): void {
        $expectedException = new Exception('test exception');

        $stream = $this->createStream('test contents');

        $this->expectExceptionObject($expectedException);

        $resource = null;

        $callbackExecuted = false;

        try {
            processResource(
                $stream,
                function (mixed $receivedStream) use (
                    $expectedException,
                    &$resource
                ) {
                    $resource = $receivedStream;

                    throw $expectedException;
                },
                function (Exception $e) use (
                    $expectedException,
                    &$resource,
                    &$callbackExecuted
                ) {
                    $this->assertEquals($expectedException, $e);
                    $this->assertIsClosedResource($resource);

                    $callbackExecuted = true;
                }
            );
        } catch (Exception $e) {
            $this->assertIsClosedResource($resource);

            $this->assertTrue($callbackExecuted);

            throw $e;
        }
    }

    public function testPreviousUrlReturnsThePreviousUrl(): void {
        $url = 'http://localhost/foo';

        $this->from($url);

        $this->assertEquals($url, previousUrl($url));
    }

    public function testPreviousUrlReturnsFallbackUrlIfPreviousUrlIsNotSet(): void {
        $fallback = 'http://localhost/foo';

        $this->assertEquals($fallback, previousUrl($fallback));
    }

    public function testPreviousUrlReturnsFallbackUrlIfPreviousUrlIsCurrentUrl(): void {
        $fallback = 'http://localhost/foo';
        $current = route('browse.index');

        $this->from($current);
        $this->get($current);

        $this->assertEquals($fallback, previousUrl($fallback));
    }

    public static function formatHoursProvider(): array {
        return [
            [0.5, '30 minutes'],
            [1, '1 hour'],
            [2.5, '2 hours, 30 minutes'],
            [24.5, '1 day, 30 minutes'],
            [720, '30 days'],
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

    public static function tableLoopDropdownPositionAlignedProvider(): array {
        return [
            [0, 1, 3, 'left', 'start'],
            [0, 1, 2, 'left', 'end'],
            [0, 2, 2, 'left', 'start'],
            [1, 2, 2, 'left', 'end'],
            [0, 3, 2, 'bottom', 'end'],
        ];
    }
}

