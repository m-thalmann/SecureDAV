<?php

namespace Tests\Unit\WebDav;

use App\WebDav;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Sabre\DAV;
use Sabre\HTTP\Request as SabreRequest;
use Sabre\HTTP\Response as SabreResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ServerTest extends TestCase {
    protected WebDavServerTestClass|MockInterface $server;

    protected WebDav\AuthBackend|MockInterface $authBackend;
    protected WebDav\LocksBackend|MockInterface $locksBackend;
    protected string $basePath = 'dav-test';
    protected DAV\Tree $tree;

    protected function setUp(): void {
        parent::setUp();

        $this->authBackend = Mockery::mock(WebDav\AuthBackend::class);
        $this->locksBackend = Mockery::mock(WebDav\LocksBackend::class);
        $this->tree = new DAV\Tree(new DAV\SimpleCollection('root'));
    }

    public function testConstructorSetsBaseUriAndPlugins(): void {
        config(['app.debug' => false]);

        $this->createServer();

        $basePath = str($this->basePath)
            ->finish('/')
            ->start('/');

        $this->assertEquals($basePath, $this->server->getBaseUri());

        $this->assertInstanceOf(
            DAV\Auth\Plugin::class,
            $this->server->getPlugin('auth')
        );
        $this->assertInstanceOf(
            DAV\Locks\Plugin::class,
            $this->server->getPlugin('locks')
        );

        $this->assertFalse($this->server->debugExceptions);
    }

    public function testConstructorSetsDebugMode(): void {
        config(['app.debug' => true]);

        $this->createServer();

        $this->assertTrue($this->server->debugExceptions);
    }

    public function testSetRequestSetsTheUrlAndAdditionalDataInsideOfUnitTest(): void {
        $this->createServer(['getFullUrl']);

        $requestMethod = 'GET';
        $requestBody = 'test body'; // should be a resource, but lets ignore that for now
        $requestHeaders = [
            'x-test-header' => ['test'],
        ];

        /**
         * @var Request|MockInterface
         */
        $request = Mockery::mock(Request::class);

        $request
            ->shouldReceive('method')
            ->withNoArgs()
            ->once()
            ->andReturn($requestMethod);
        $request
            ->shouldReceive('getContent')
            ->with(true)
            ->once()
            ->andReturn($requestBody);
        $request->headers = new HeaderBag($requestHeaders);

        $expectedUrl = "{$this->basePath}/test/";

        $this->server
            ->shouldReceive('getFullUrl')
            ->with($request)
            ->once()
            ->andReturn($expectedUrl);

        /**
         * @var SabreRequest|MockInterface
         */
        $serverHttpRequestSpy = Mockery::spy($this->server->httpRequest);
        $this->server->httpRequest = $serverHttpRequestSpy;

        $this->server->setRequest($request);

        $serverHttpRequestSpy
            ->shouldHaveReceived('setUrl')
            ->with($expectedUrl)
            ->once();

        $serverHttpRequestSpy
            ->shouldHaveReceived('setMethod')
            ->with($requestMethod)
            ->once();

        $serverHttpRequestSpy
            ->shouldHaveReceived('setBody')
            ->with($requestBody)
            ->once();

        $serverHttpRequestSpy
            ->shouldHaveReceived('setHeaders')
            ->with($requestHeaders)
            ->once();
    }

    public function testGetResponseReturnsANormalResponseForNonResource(): void {
        $this->createServer();

        $responseBody = 'test body';
        $responseStatus = 200;
        $responseHeaders = [
            'x-test-header' => ['test'],
        ];

        /**
         * @var SabreResponse|MockInterface
         */
        $serverHttpResponseMock = Mockery::mock($this->server->httpResponse);
        $this->server->httpResponse = $serverHttpResponseMock;

        $serverHttpResponseMock
            ->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($responseBody);
        $serverHttpResponseMock
            ->shouldReceive('getStatus')
            ->withNoArgs()
            ->once()
            ->andReturn($responseStatus);
        $serverHttpResponseMock
            ->shouldReceive('getHeaders')
            ->withNoArgs()
            ->once()
            ->andReturn($responseHeaders);

        $response = $this->server->getResponse();

        $this->assertInstanceOf(Response::class, $response);

        /**
         * @var array
         */
        $receivedResponseHeaders = $response->headers->all();

        $this->assertEquals($responseBody, $response->getContent());
        $this->assertEquals($responseStatus, $response->getStatusCode());
        $this->assertHasSubArray($responseHeaders, $receivedResponseHeaders);
    }

    public function testGetResponseReturnsAStreamedResponseForResource(): void {
        $this->createServer();

        $responseBodyContent = 'test body';
        $responseBody = $this->createStream($responseBodyContent);

        $responseStatus = 200;
        $responseHeaders = [
            'x-test-header' => ['test'],
        ];

        /**
         * @var SabreResponse|MockInterface
         */
        $serverHttpResponseMock = Mockery::mock($this->server->httpResponse);
        $this->server->httpResponse = $serverHttpResponseMock;

        $serverHttpResponseMock
            ->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($responseBody);
        $serverHttpResponseMock
            ->shouldReceive('getStatus')
            ->withNoArgs()
            ->once()
            ->andReturn($responseStatus);
        $serverHttpResponseMock
            ->shouldReceive('getHeaders')
            ->withNoArgs()
            ->once()
            ->andReturn($responseHeaders);

        $response = $this->server->getResponse();

        $this->assertInstanceOf(StreamedResponse::class, $response);

        /**
         * @var array
         */
        $receivedResponseHeaders = $response->headers->all();

        $receivedResponseBody = $this->getStreamedResponseContent($response);

        $this->assertEquals($responseBodyContent, $receivedResponseBody);
        $this->assertEquals($responseStatus, $response->getStatusCode());
        $this->assertHasSubArray($responseHeaders, $receivedResponseHeaders);

        fclose($responseBody);
    }

    public function testGetResponseIncludesCorsHeadersIfEnabled(): void {
        $this->createServer(['getCorsHeaders']);

        $requestOrigin = 'http://test.com';

        config(['webdav.cors.enabled' => true]);

        $mockHeaders = ['x-test' => ['true']];

        $this->server
            ->shouldReceive('getCorsHeaders')
            ->with($requestOrigin)
            ->once()
            ->andReturn($mockHeaders);

        /**
         * @var SabreRequest|MockInterface
         */
        $serverHttpRequestMock = Mockery::mock($this->server->httpRequest);
        $this->server->httpRequest = $serverHttpRequestMock;

        $serverHttpRequestMock
            ->shouldReceive('getHeader')
            ->with('Origin')
            ->once()
            ->andReturn($requestOrigin);

        /**
         * @var SabreResponse|MockInterface
         */
        $serverHttpResponseMock = Mockery::mock($this->server->httpResponse);
        $this->server->httpResponse = $serverHttpResponseMock;

        $serverHttpResponseMock
            ->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn(null);
        $serverHttpResponseMock
            ->shouldReceive('getStatus')
            ->withNoArgs()
            ->once()
            ->andReturn(200);
        $serverHttpResponseMock
            ->shouldReceive('getHeaders')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $response = $this->server->getResponse();

        /**
         * @var array
         */
        $responseHeaders = $response->headers->all();

        $this->assertHasSubArray($mockHeaders, $responseHeaders);
    }

    public function testGetResponseDoesNotIncludeCorsHeadersIfDisabled(): void {
        $this->createServer(['getCorsHeaders']);

        config(['webdav.cors.enabled' => false]);

        $this->server->shouldNotReceive('getCorsHeaders');

        /**
         * @var SabreResponse|MockInterface
         */
        $serverHttpResponseMock = Mockery::mock($this->server->httpResponse);
        $this->server->httpResponse = $serverHttpResponseMock;

        $serverHttpResponseMock
            ->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn(null);
        $serverHttpResponseMock
            ->shouldReceive('getStatus')
            ->withNoArgs()
            ->once()
            ->andReturn(200);
        $serverHttpResponseMock
            ->shouldReceive('getHeaders')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->server->getResponse();
    }

    public function testGetFullUrlReturnsTheUrlWithTrailingSlashAndQuery(): void {
        $this->createServer();

        $requestPathInfo = "{$this->basePath}/test";
        $requestQueryString = 'test=1';

        /**
         * @var Request|MockInterface
         */
        $request = Mockery::mock(Request::class);

        $request
            ->shouldReceive('getPathInfo')
            ->withNoArgs()
            ->once()
            ->andReturn($requestPathInfo);
        $request
            ->shouldReceive('getQueryString')
            ->withNoArgs()
            ->once()
            ->andReturn($requestQueryString);

        $expectedUrl = "{$requestPathInfo}/?$requestQueryString";

        $this->assertEquals($expectedUrl, $this->server->getFullUrl($request));
    }

    public function testGetFullUrlReturnsTheUrlWithoutQuery(): void {
        $this->createServer();

        $requestPathInfo = "{$this->basePath}/test";

        /**
         * @var Request|MockInterface
         */
        $request = Mockery::mock(Request::class);

        $request
            ->shouldReceive('getPathInfo')
            ->withNoArgs()
            ->once()
            ->andReturn($requestPathInfo);
        $request
            ->shouldReceive('getQueryString')
            ->withNoArgs()
            ->once()
            ->andReturn(null);

        $expectedUrl = "{$requestPathInfo}/";

        $this->assertEquals($expectedUrl, $this->server->getFullUrl($request));
    }

    public function testGetCorsHeadersReturnsTheConfiguredHeaders(): void {
        $this->createServer();

        $requestOrigin = 'http://test.com';

        $exposeHeaders = 'X-Exposed-Header';
        $allowedHeaders = 'X-Allowed-Header';

        config([
            'webdav.cors.allowed_origins' => [
                $requestOrigin,
                'https://other-origin.com',
            ],
        ]);
        config(['webdav.cors.expose_headers' => $exposeHeaders]);
        config(['webdav.cors.allowed_headers' => $allowedHeaders]);

        $expectedHeaders = [
            'Access-Control-Allow-Origin' => $requestOrigin,
            'Access-Control-Allow-Methods' => join(
                ', ',
                WebDav\Server::METHODS
            ),
            'Access-Control-Expose-Headers' => $exposeHeaders,
            'Access-Control-Allow-Headers' => $allowedHeaders,
            'Access-Control-Allow-Credentials' => 'true',
        ];

        $headers = $this->server->getCorsHeaders($requestOrigin);

        $this->assertEquals($expectedHeaders, $headers);
    }

    public function testGetCorsHeadersReturnsNullAllowOriginHeaderWhenNoMatch(): void {
        $this->createServer();

        $requestOrigin = 'http://test.com';

        config([
            'webdav.cors.allowed_origins' => ['https://other-origin.com'],
        ]);

        $headers = $this->server->getCorsHeaders($requestOrigin);

        $this->assertNull($headers['Access-Control-Allow-Origin']);
    }

    public function testGetCorsHeadersReturnsRequestOriginIfWildcardIsConfigured(): void {
        $this->createServer();

        $requestOrigin = 'http://test.com';

        config([
            'webdav.cors.allowed_origins' => ['http://other-origin.com', '*'],
        ]);

        $headers = $this->server->getCorsHeaders($requestOrigin);

        $this->assertEquals(
            $requestOrigin,
            $headers['Access-Control-Allow-Origin']
        );
    }

    public function testGetCorsHeadersReturnsWildcardIfWildcardIsConfiguredAndRequestOriginIsNull(): void {
        $this->createServer();

        $requestOrigin = null;

        config(['webdav.cors.allowed_origins' => ['*']]);

        $headers = $this->server->getCorsHeaders($requestOrigin);

        $this->assertEquals('*', $headers['Access-Control-Allow-Origin']);
    }

    protected function createServer(array $mockedMethods = []): void {
        if (count($mockedMethods) === 0) {
            $this->server = new WebDavServerTestClass(
                $this->authBackend,
                $this->locksBackend,
                $this->basePath,
                $this->tree
            );

            return;
        }

        /**
         * @var WebDavServerTestClass|MockInterface
         */
        $this->server = Mockery::mock(
            WebDavServerTestClass::class .
                '[' .
                join(',', $mockedMethods) .
                ']',
            [
                $this->authBackend,
                $this->locksBackend,
                $this->basePath,
                $this->tree,
            ]
        );

        $this->server->makePartial();
    }
}

class WebDavServerTestClass extends WebDav\Server {
    public function getFullUrl(Request $request): string {
        return parent::getFullUrl($request);
    }

    public function addPlugin(DAV\ServerPlugin $plugin): void {
        parent::addPlugin($plugin);
    }
}
