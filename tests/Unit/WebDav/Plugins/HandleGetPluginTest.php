<?php

namespace Tests\Unit\WebDav\Plugins;

use App\WebDav\Filesystem\VirtualFile;
use App\WebDav\Plugins\HandleGetPlugin;
use App\WebDav\Server;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Tests\TestCase;

class HandleGetPluginTest extends TestCase {
    protected HandleGetPlugin $plugin;

    protected Server|MockInterface $mockServer;
    protected DAV\Tree|MockInterface $mockTree;

    protected function setUp(): void {
        parent::setUp();

        $this->mockServer = Mockery::mock(Server::class);

        $this->mockTree = Mockery::mock(DAV\Tree::class);
        $this->mockServer->tree = $this->mockTree;

        $this->plugin = new HandleGetPlugin();
    }

    public function testGetPluginNameReturnsPluginName(): void {
        $this->assertEquals('handle-get', $this->plugin->getPluginName());
    }

    public function testInitializeRegistersGetHandler(): void {
        $this->mockServer
            ->shouldReceive('on')
            ->with('method:GET', [$this->plugin, 'getHandler'], 90)
            ->once();

        $this->plugin->initialize($this->mockServer);
    }

    public function testGetHandlerReturnsTrueIfFileIsNotVirtualFile(): void {
        $this->initialize();

        $path = '/path/to/file';

        $this->setTreeNodeForPath($path, new DAV\FS\File(''));

        $result = $this->plugin->getHandler(
            ...$this->createMockRequestResponse($path)
        );

        $this->assertTrue($result);
    }

    public function testGetHandlerSetsHeadersStatusAndBody(): void {
        $this->initialize();

        $path = '/path/to/file.json';
        $contentType = 'application/json';
        $content = '{"foo":"bar"}';
        $lastModified = Carbon::now();
        $etag = '"' . md5($content) . '"';

        $file = $this->mockVirtualFile(
            $contentType,
            $content,
            $lastModified,
            $etag
        );

        $this->setTreeNodeForPath($path, $file);

        [$mockRequest, $mockResponse] = $this->createMockRequestResponse($path);

        $mockResponse
            ->shouldReceive('addHeaders')
            ->with([
                'Content-Type' => $contentType,
                'Content-Length' => strlen($content),
                'Last-Modified' => $lastModified->toRfc7231String(),
                'ETag' => $etag,
            ])
            ->once();

        $mockResponse
            ->shouldReceive('setStatus')
            ->with(200)
            ->once();

        $mockResponse
            ->shouldReceive('setBody')
            ->withArgs(function (mixed $body) {
                $this->assertIsCallable($body);

                return true;
            })
            ->once();

        $result = $this->plugin->getHandler($mockRequest, $mockResponse);

        $this->assertFalse($result);
    }

    public function testGetHandlerSetsBodyWhichWritesTheContentToTheOutput(): void {
        $this->initialize();

        $path = '/path/to/file.json';
        $content = '{"foo":"bar"}';

        $file = $this->mockVirtualFile(
            'application/json',
            $content,
            Carbon::now(),
            '"' . md5($content) . '"'
        );

        $this->setTreeNodeForPath($path, $file);

        [$mockRequest, $mockResponse] = $this->createMockRequestResponse($path);

        $mockResponse
            ->shouldReceive('addHeaders')
            ->withAnyArgs()
            ->once();

        $mockResponse
            ->shouldReceive('setStatus')
            ->with(200)
            ->once();

        $mockResponse
            ->shouldReceive('setBody')
            ->withArgs(function (mixed $body) use ($content) {
                $this->expectOutputString($content);

                $body();

                return true;
            })
            ->once();

        $result = $this->plugin->getHandler($mockRequest, $mockResponse);

        $this->assertFalse($result);
    }

    public function testGetHandlerSetsContentTypeIfFileHasNone(): void {
        $this->initialize();

        $path = '/path/to/file';
        $contentType = 'application/octet-stream';

        $file = $this->mockVirtualFile('', '', Carbon::now(), '');

        $this->setTreeNodeForPath($path, $file);

        [$mockRequest, $mockResponse] = $this->createMockRequestResponse($path);

        $mockResponse
            ->shouldReceive('addHeaders')
            ->withArgs(function (array $args) {
                $this->assertArrayHasKey('Content-Type', $args);
                $this->assertEquals(
                    'application/octet-stream',
                    $args['Content-Type']
                );

                return true;
            })
            ->once();

        $mockResponse
            ->shouldReceive('setStatus')
            ->with(200)
            ->once();

        $mockResponse
            ->shouldReceive('setBody')
            ->withAnyArgs()
            ->once();

        $result = $this->plugin->getHandler($mockRequest, $mockResponse);

        $this->assertFalse($result);
    }

    public function testGetHandlerSetsCorsHeadersIfCorsIsEnabled(): void {
        $origin = 'http://localhost';
        $path = '/path/to/file.json';

        config(['webdav.cors.enabled' => true]);

        [$mockRequest, $mockResponse] = $this->createMockRequestResponse($path);

        $mockRequest
            ->shouldReceive('getHeader')
            ->with('Origin')
            ->once()
            ->andReturn($origin);

        $corsHeaders = [
            'X-Test-Cors-Header' => 'http://localhost',
        ];

        $this->mockServer
            ->shouldReceive('getCorsHeaders')
            ->with($origin)
            ->once()
            ->andReturn($corsHeaders);

        $this->initialize();

        $file = $this->mockVirtualFile(
            'application/json',
            '{"foo":"bar"}',
            Carbon::now(),
            '"123"'
        );

        $this->setTreeNodeForPath($path, $file);

        $mockResponse
            ->shouldReceive('addHeaders')
            ->withArgs(function (array $args) use ($corsHeaders) {
                foreach ($corsHeaders as $key => $value) {
                    $this->assertArrayHasKey($key, $args);
                    $this->assertEquals($value, $args[$key]);
                }

                return true;
            })
            ->once();

        $mockResponse
            ->shouldReceive('setStatus')
            ->with(200)
            ->once();

        $mockResponse
            ->shouldReceive('setBody')
            ->withAnyArgs()
            ->once();

        $result = $this->plugin->getHandler($mockRequest, $mockResponse);

        $this->assertFalse($result);
    }

    public function testGetPluginInfoReturnsPluginInfo(): void {
        $info = $this->plugin->getPluginInfo();

        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('description', $info);
        $this->assertArrayHasKey('link', $info);
    }

    protected function initialize(): void {
        $this->mockServer
            ->shouldReceive('on')
            ->with('method:GET', [$this->plugin, 'getHandler'], 90);

        $this->plugin->initialize($this->mockServer);
    }

    /**
     * @return array{0: RequestInterface|MockInterface, 1: ResponseInterface|MockInterface}
     */
    protected function createMockRequestResponse(string $path): array {
        $mockRequest = Mockery::mock(RequestInterface::class);
        $mockResponse = Mockery::mock(ResponseInterface::class);

        $mockRequest
            ->shouldReceive('getPath')
            ->once()
            ->andReturn($path);

        return [$mockRequest, $mockResponse];
    }

    protected function setTreeNodeForPath(string $path, DAV\INode $node): void {
        $this->mockTree
            ->shouldReceive('getNodeForPath')
            ->with($path)
            ->once()
            ->andReturn($node);
    }

    protected function mockVirtualFile(
        string $contentType,
        string $content,
        Carbon $lastModified,
        string $etag
    ): VirtualFile|MockInterface {
        $mock = Mockery::mock(VirtualFile::class);

        $mock
            ->shouldReceive('getContentType')
            ->once()
            ->andReturn($contentType);
        $mock
            ->shouldReceive('getSize')
            ->once()
            ->andReturn(strlen($content));
        $mock
            ->shouldReceive('getLastModifiedDateTime')
            ->once()
            ->andReturn($lastModified);
        $mock
            ->shouldReceive('getETag')
            ->once()
            ->andReturn($etag);

        $mock
            ->shouldReceive('writeToStream')
            ->withArgs(function (mixed $output) use ($content) {
                $this->assertIsResource($output);

                fwrite($output, $content);

                return true;
            });

        return $mock;
    }
}
