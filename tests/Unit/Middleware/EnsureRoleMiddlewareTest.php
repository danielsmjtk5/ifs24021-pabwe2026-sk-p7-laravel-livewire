<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_user_with_required_role(): void
    {
        $middleware = new EnsureRole();
        $user = User::factory()->admin()->create();

        $request = Request::create('/admin/users', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocks_user_with_different_role(): void
    {
        $middleware = new EnsureRole();
        $user = User::factory()->member()->create();

        $request = Request::create('/admin/users', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => new Response('OK', 200), 'admin');
    }

    public function test_allows_any_of_multiple_roles(): void
    {
        $middleware = new EnsureRole();
        $member = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        foreach ([$member, $admin] as $user) {
            $request = Request::create('/stocks', 'GET');
            $request->setUserResolver(fn () => $user);

            // Pipe-separated (role:admin|member)
            $response = $middleware->handle($request, fn () => new Response('OK', 200), 'admin|member');
            $this->assertEquals(200, $response->getStatusCode());

            // Comma-separated (role:admin,member)
            $response = $middleware->handle($request, fn () => new Response('OK', 200), 'admin', 'member');
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    public function test_blocks_role_not_in_list(): void
    {
        $middleware = new EnsureRole();
        $user = User::factory()->create(['role' => 'guest']);

        $request = Request::create('/stocks', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => new Response('OK', 200), 'admin|member');
    }

    public function test_blocks_unauthenticated_user(): void
    {
        $middleware = new EnsureRole();
        $request = Request::create('/admin/users', 'GET');

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => new Response('OK', 200), 'admin');
    }
}

