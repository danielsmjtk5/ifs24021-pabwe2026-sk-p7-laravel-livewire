<?php

namespace Tests\Unit\Middleware;

use App\Features\Stock\Models\Stock;
use App\Http\Middleware\EnsureStockOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureStockOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a request bound to a route with a single parameter.
     */
    private function requestWithParam(User $user, string $method, string $param, mixed $value): Request
    {
        $request = Request::create('/test', $method);
        $request->setUserResolver(fn () => $user);

        $route = new Route($method, '/test/{'.$param.'}', []);
        $route->bind($request);
        $route->setParameter($param, $value);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function assertForbidden(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    public function test_blocks_unauthenticated_request(): void
    {
        $middleware = new EnsureStockOwnership();
        $request = Request::create('/stocks', 'GET');

        try {
            $middleware->handle($request, fn () => new Response('OK'));
            $this->fail('Expected HttpException 401');
        } catch (HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
        }
    }

    public function test_request_without_route_parameters_passes(): void
    {
        $middleware = new EnsureStockOwnership();
        $member = User::factory()->member()->create();

        $request = Request::create('/stocks', 'GET');
        $request->setUserResolver(fn () => $member);

        $this->assertEquals(200, $middleware->handle($request, fn () => new Response('OK'))->getStatusCode());
    }

    public function test_route_user_parameter_ownership(): void
    {
        $middleware = new EnsureStockOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        // Member accessing own (model and raw id)
        $res = $middleware->handle($this->requestWithParam($member1, 'GET', 'user', $member1), fn () => new Response('OK'));
        $this->assertEquals(200, $res->getStatusCode());
        $res = $middleware->handle($this->requestWithParam($member1, 'GET', 'user', (string) $member1->id), fn () => new Response('OK'));
        $this->assertEquals(200, $res->getStatusCode());

        // Member accessing other user -> 403
        $this->assertForbidden(fn () => $middleware->handle($this->requestWithParam($member1, 'GET', 'user', $member2), fn () => new Response('OK')));

        // Admin accessing other user -> allowed
        $res = $middleware->handle($this->requestWithParam($admin, 'GET', 'user', $member2), fn () => new Response('OK'));
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function test_route_stock_parameter_ownership(): void
    {
        $middleware = new EnsureStockOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $stock = Stock::factory()->create(['user_id' => $member1->id]);

        // Owner GET (model + raw id)
        $this->assertEquals(200, $middleware->handle($this->requestWithParam($member1, 'GET', 'stock', $stock), fn () => new Response('OK'))->getStatusCode());
        $this->assertEquals(200, $middleware->handle($this->requestWithParam($member1, 'GET', 'stock', (string) $stock->id), fn () => new Response('OK'))->getStatusCode());

        // Owner may also write
        $this->assertEquals(200, $middleware->handle($this->requestWithParam($member1, 'DELETE', 'stock', $stock), fn () => new Response('OK'))->getStatusCode());

        // Other member GET -> 403
        $this->assertForbidden(fn () => $middleware->handle($this->requestWithParam($member2, 'GET', 'stock', $stock), fn () => new Response('OK')));

        // Admin GET -> allowed (read-only)
        $this->assertEquals(200, $middleware->handle($this->requestWithParam($admin, 'GET', 'stock', $stock), fn () => new Response('OK'))->getStatusCode());

        // Admin write (PUT / DELETE) -> 403
        $this->assertForbidden(fn () => $middleware->handle($this->requestWithParam($admin, 'PUT', 'stock', $stock), fn () => new Response('OK')));
        $this->assertForbidden(fn () => $middleware->handle($this->requestWithParam($admin, 'DELETE', 'stock', (string) $stock->id), fn () => new Response('OK')));
    }

    public function test_unknown_stock_id_is_passed_through(): void
    {
        $middleware = new EnsureStockOwnership();
        $member = User::factory()->member()->create();

        $res = $middleware->handle($this->requestWithParam($member, 'GET', 'stock', '999999'), fn () => new Response('OK'));
        $this->assertEquals(200, $res->getStatusCode());
    }
}
