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

    public function testProcessResourcesPassesTheResourcesToTheCallback(): void {
        $contents1 = 'test contents 1';
        $contents2 = 'test contents 2';

        $stream1 = $this->createStream($contents1);
        $stream2 = $this->createStream($contents2);

        $expectedResponse = 'test response';

        $response = processResources([$stream1, $stream2], function (
            array $receivedStreams
        ) use ($stream1, $stream2, $expectedResponse) {
            foreach ($receivedStreams as $receivedStream) {
                $this->assertIsResource($receivedStream);
            }

            $this->assertEquals($stream1, $receivedStreams[0]);
            $this->assertEquals($stream2, $receivedStreams[1]);

            return $expectedResponse;
        });

        $this->assertEquals($expectedResponse, $response);
    }

    public function testProcessResourcesClosesTheStreamsAfterTheCallbackIsFinished(): void {
        $stream1 = $this->createStream('test contents');
        $stream2 = $this->createStream('test contents');

        $resources = processResources([$stream1, $stream2], function (
            array $receivedStreams
        ) {
            foreach ($receivedStreams as $receivedStream) {
                $this->assertIsNotClosedResource($receivedStream);
            }

            return $receivedStreams;
        });

        foreach ($resources as $resource) {
            $this->assertIsClosedResource($resource);
        }
    }

    public function testProcessResourcesDoesNotCloseStreamIfIsAlreadyClosedAfterTheCallbackIsFinished(): void {
        $stream = $this->createStream('test contents');

        fclose($stream);

        $resource = processResources([$stream], function (
            array $receivedStreams
        ) {
            $this->assertIsClosedResource($receivedStreams[0]);

            return $receivedStreams[0];
        });

        $this->assertIsClosedResource($resource);
    }

    public function testProcessResourcesClosesTheStreamsOnExceptionAndRethrows(): void {
        $expectedException = new Exception('test exception');

        $stream1 = $this->createStream('test contents');
        $stream2 = $this->createStream('test contents');

        $this->expectExceptionObject($expectedException);

        /**
         * @var resource[]|null
         */
        $resources = null;

        try {
            processResources([$stream1, $stream2], function (
                array $receivedStreams
            ) use ($expectedException, &$resources) {
                foreach ($receivedStreams as $receivedStream) {
                    $this->assertIsNotClosedResource($receivedStream);
                }

                $resources = $receivedStreams;

                throw $expectedException;
            });
        } catch (Exception $e) {
            foreach ($resources as $resource) {
                $this->assertIsClosedResource($resource);
            }

            throw $e;
        }
    }

    public function testProcessResourcesDoesNotCloseStreamIfIsAlreadyClosedOnException(): void {
        $expectedException = new Exception('test exception');

        $stream = $this->createStream('test contents');

        fclose($stream);

        $this->expectExceptionObject($expectedException);

        $resource = null;

        try {
            processResources([$stream], function (array $receivedStreams) use (
                $expectedException,
                &$resource
            ) {
                $this->assertIsClosedResource($receivedStreams[0]);

                $resource = $receivedStreams[0];

                throw $expectedException;
            });
        } catch (Exception $e) {
            $this->assertIsClosedResource($resource);

            throw $e;
        }
    }

    public function testProcessResourcesExecutesExceptionCallbackOnException(): void {
        $expectedException = new Exception('test exception');

        $stream = $this->createStream('test contents');

        $this->expectExceptionObject($expectedException);

        $resource = null;

        $callbackExecuted = false;

        try {
            processResources(
                [$stream],
                function (mixed $receivedStreams) use (
                    $expectedException,
                    &$resource
                ) {
                    $resource = $receivedStreams[0];

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

    public function testProcessResourceUsesProcessResourcesFunction(): void {
        $stream = $this->createStream('test contents');

        $expectedResponse = 'test response';

        $response = processResource($stream, function (
            mixed $receivedStream
        ) use ($stream, $expectedResponse) {
            $this->assertIsResource($receivedStream);
            $this->assertEquals($stream, $receivedStream);

            return $expectedResponse;
        });

        $this->assertEquals($expectedResponse, $response);

        $this->assertIsClosedResource($stream);
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

    public function testCreateStreamCreatesAStream(): void {
        $stream = createStream();

        $this->assertIsResource($stream);
        $this->assertIsNotClosedResource($stream);

        $this->assertEmpty(stream_get_contents($stream));
    }

    public function testCreateStreamCreatesAStreamWithTheGivenContents(): void {
        $contents = 'test contents';

        $stream = createStream($contents);

        $this->assertIsResource($stream);
        $this->assertIsNotClosedResource($stream);

        $this->assertEquals($contents, stream_get_contents($stream));
    }

    public function testCreateStreamCreatesIndependentStreams(): void {
        $content1 = 'test contents 1';
        $content2 = 'test contents 2';

        $stream1 = createStream($content1);
        $stream2 = createStream($content2);

        $this->assertEquals($content1, stream_get_contents($stream1));
        $this->assertEquals($content2, stream_get_contents($stream2));
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

