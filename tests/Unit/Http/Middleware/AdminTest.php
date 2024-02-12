<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\Admin;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminTest extends TestCase {
    use LazilyRefreshDatabase;

    protected Admin $middleware;
    protected Response $testResponse;

    protected function setUp(): void {
        parent::setUp();

        $this->middleware = new Admin();
        $this->testResponse = response('test response');
    }

    public function testHandleThrowsForbiddenExceptionIfUserIsNotAdmin(): void {
        $user = $this->createUser();

        $request = new Request();
        $request->setUserResolver(fn() => $user);

        try {
            $response = $this->middleware->handle(
                $request,
                fn() => $this->testResponse
            );
        } catch (HttpException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getStatusCode());
            $this->assertEquals(
                'You are not allowed to access this page.',
                $e->getMessage()
            );

            return;
        }

        $this->fail('Expected HttpException was not thrown.');
    }

    public function testHandleReturnsNextIfUserIsAdmin(): void {
        $user = $this->createUser();
        $user->forceFill(['is_admin' => true])->save();

        $request = new Request();
        $request->setUserResolver(fn() => $user);

        $response = $this->middleware->handle(
            $request,
            fn() => $this->testResponse
        );

        $this->assertEquals($this->testResponse, $response);
    }
}
