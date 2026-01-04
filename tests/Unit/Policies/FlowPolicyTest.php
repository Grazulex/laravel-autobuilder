<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Policies\FlowPolicy;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    // Reset config for each test
    config(['autobuilder.authorization.gate' => null]);
    config(['autobuilder.authorization.super_admins' => []]);
});

// Create a simple test user
class TestUser extends Authenticatable
{
    public $id;

    public function __construct(int $id = 1)
    {
        $this->id = $id;
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }
}

// =============================================================================
// Basic Access Tests
// =============================================================================

describe('basic access', function () {
    it('allows authenticated user when no gate configured', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;

        expect($policy->viewAny($user))->toBeTrue();
        expect($policy->create($user))->toBeTrue();
    });

    it('denies access to guest when no gate configured', function () {
        $policy = new FlowPolicy;

        expect($policy->viewAny(null))->toBeFalse();
        expect($policy->create(null))->toBeFalse();
    });

    it('checks gate when configured', function () {
        config(['autobuilder.authorization.gate' => 'access-autobuilder']);

        // Define the gate to allow access
        Gate::define('access-autobuilder', fn ($user) => true);

        $policy = new FlowPolicy;
        $user = new TestUser;

        expect($policy->viewAny($user))->toBeTrue();
    });

    it('denies access when gate fails', function () {
        config(['autobuilder.authorization.gate' => 'access-autobuilder']);

        // Define the gate to deny access
        Gate::define('access-autobuilder', fn ($user) => false);

        $policy = new FlowPolicy;
        $user = new TestUser;

        expect($policy->viewAny($user))->toBeFalse();
    });
});

// =============================================================================
// Super Admin Tests
// =============================================================================

describe('super admin', function () {
    it('bypasses all checks for super admin', function () {
        config(['autobuilder.authorization.gate' => 'access-autobuilder']);
        config(['autobuilder.authorization.super_admins' => [1]]);

        // Define gate to deny
        Gate::define('access-autobuilder', fn ($user) => false);

        $policy = new FlowPolicy;
        $user = new TestUser(1);
        $flow = new Flow;

        // Super admin should bypass all checks
        expect($policy->before($user, 'view'))->toBeTrue();
    });

    it('does not bypass for non-super-admin', function () {
        config(['autobuilder.authorization.super_admins' => [999]]);

        $policy = new FlowPolicy;
        $user = new TestUser(1);

        // Regular user returns null to continue with normal checks
        expect($policy->before($user, 'view'))->toBeNull();
    });
});

// =============================================================================
// CRUD Policy Methods
// =============================================================================

describe('crud methods', function () {
    it('has view policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;
        $flow = new Flow;

        expect($policy->view($user, $flow))->toBeTrue();
    });

    it('has update policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;
        $flow = new Flow;

        expect($policy->update($user, $flow))->toBeTrue();
    });

    it('has delete policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;
        $flow = new Flow;

        expect($policy->delete($user, $flow))->toBeTrue();
    });
});

// =============================================================================
// Flow-Specific Methods
// =============================================================================

describe('flow-specific methods', function () {
    it('has duplicate policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;
        $flow = new Flow;

        expect($policy->duplicate($user, $flow))->toBeTrue();
    });

    it('has activate policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;
        $flow = new Flow;

        expect($policy->activate($user, $flow))->toBeTrue();
    });

    it('has deactivate policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;
        $flow = new Flow;

        expect($policy->deactivate($user, $flow))->toBeTrue();
    });

    it('has run policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;
        $flow = new Flow;

        expect($policy->run($user, $flow))->toBeTrue();
    });

    it('has export policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;
        $flow = new Flow;

        expect($policy->export($user, $flow))->toBeTrue();
    });

    it('has import policy method', function () {
        $policy = new FlowPolicy;
        $user = new TestUser;

        expect($policy->import($user))->toBeTrue();
    });
});
