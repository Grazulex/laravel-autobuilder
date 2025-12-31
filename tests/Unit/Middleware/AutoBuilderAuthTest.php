<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Http\Middleware\AutoBuilderAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    config(['autobuilder.authorization.gate' => 'access-autobuilder']);
    config(['autobuilder.authorization.super_admins' => []]);
});

it('rejects unauthenticated users with JSON response', function () {
    $middleware = new AutoBuilderAuth();
    $request = Request::create('/autobuilder/api/flows', 'GET');
    $request->headers->set('Accept', 'application/json');

    $response = $middleware->handle($request, fn () => response()->json(['success' => true]));

    expect($response->getStatusCode())->toBe(401)
        ->and(json_decode($response->getContent(), true))->toHaveKey('message', 'Unauthenticated.');
});

it('allows authenticated users through', function () {
    $middleware = new AutoBuilderAuth();
    $request = Request::create('/autobuilder/api/flows', 'GET');

    // Create a mock user
    $user = new class
    {
        public int $id = 1;
    };
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => response()->json(['success' => true]));

    expect($response->getStatusCode())->toBe(200);
});

it('allows super admins through', function () {
    config(['autobuilder.authorization.super_admins' => [1]]);

    $middleware = new AutoBuilderAuth();
    $request = Request::create('/autobuilder/api/flows', 'GET');

    $user = new class
    {
        public int $id = 1;
    };
    $request->setUserResolver(fn () => $user);

    // Define gate that would normally deny access
    Gate::define('access-autobuilder', fn () => false);

    $response = $middleware->handle($request, fn () => response()->json(['success' => true]));

    expect($response->getStatusCode())->toBe(200);
});

it('denies access when gate fails', function () {
    $middleware = new AutoBuilderAuth();
    $request = Request::create('/autobuilder/api/flows', 'GET');
    $request->headers->set('Accept', 'application/json');

    $user = new class
    {
        public int $id = 999;
    };
    $request->setUserResolver(fn () => $user);

    // Define gate that denies access
    Gate::define('access-autobuilder', fn () => false);

    $response = $middleware->handle($request, fn () => response()->json(['success' => true]));

    expect($response->getStatusCode())->toBe(403);
});

it('allows access when gate passes', function () {
    $middleware = new AutoBuilderAuth();
    $request = Request::create('/autobuilder/api/flows', 'GET');

    $user = new class
    {
        public int $id = 999;
    };
    $request->setUserResolver(fn () => $user);

    // Define gate that allows access
    Gate::define('access-autobuilder', fn () => true);

    $response = $middleware->handle($request, fn () => response()->json(['success' => true]));

    expect($response->getStatusCode())->toBe(200);
});

it('skips gate check when gate is null', function () {
    config(['autobuilder.authorization.gate' => null]);

    $middleware = new AutoBuilderAuth();
    $request = Request::create('/autobuilder/api/flows', 'GET');

    $user = new class
    {
        public int $id = 999;
    };
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => response()->json(['success' => true]));

    expect($response->getStatusCode())->toBe(200);
});
