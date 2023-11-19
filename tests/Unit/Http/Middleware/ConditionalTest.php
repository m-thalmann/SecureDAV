<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\Conditional;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ConditionalTest extends TestCase {
    const CONFIG_KEY = 'tests.test_value';

    protected Conditional $middleware;
    protected Response $testResponse;

    protected function setUp(): void {
        parent::setUp();

        $this->middleware = new Conditional();
        $this->testResponse = response('test response');
    }

    public function testHandleReturnsNotFoundResponseIfConfiguredValueIsFalse(): void {
        config([static::CONFIG_KEY => false]);

        $this->expectException(NotFoundHttpException::class);

        $this->middleware->handle(
            new Request(),
            fn() => $this->testResponse,
            static::CONFIG_KEY,
            default: true
        );
    }

    public function testHandleReturnsNotFoundResponseIfConfiguredValueIsNotFoundAndDefaultIsFalse(): void {
        $this->expectException(NotFoundHttpException::class);

        $this->middleware->handle(
            new Request(),
            fn() => $this->testResponse,
            static::CONFIG_KEY,
            default: false
        );
    }

    public function testHandleReturnsNextIfConfiguredValueIsTrue(): void {
        config([static::CONFIG_KEY => true]);

        $response = $this->middleware->handle(
            new Request(),
            fn() => $this->testResponse,
            static::CONFIG_KEY,
            default: false
        );

        $this->assertEquals($this->testResponse, $response);
    }

    public function testHandleReturnsNextIfConfiguredValueIsNotFoundAndDefaultIsTrue(): void {
        $response = $this->middleware->handle(
            new Request(),
            fn() => $this->testResponse,
            static::CONFIG_KEY,
            default: true
        );

        $this->assertEquals($this->testResponse, $response);
    }
}
