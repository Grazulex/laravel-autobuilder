<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Policies;

use Grazulex\AutoBuilder\Models\Flow;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

class FlowPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(?Authenticatable $user, string $ability): ?bool
    {
        // Super admins bypass all checks
        if ($user && $this->isSuperAdmin($user)) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any flows.
     */
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can view the flow.
     */
    public function view(?Authenticatable $user, Flow $flow): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can create flows.
     */
    public function create(?Authenticatable $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can update the flow.
     */
    public function update(?Authenticatable $user, Flow $flow): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can delete the flow.
     */
    public function delete(?Authenticatable $user, Flow $flow): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can duplicate the flow.
     */
    public function duplicate(?Authenticatable $user, Flow $flow): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can activate the flow.
     */
    public function activate(?Authenticatable $user, Flow $flow): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can deactivate the flow.
     */
    public function deactivate(?Authenticatable $user, Flow $flow): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can run the flow.
     */
    public function run(?Authenticatable $user, Flow $flow): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can export the flow.
     */
    public function export(?Authenticatable $user, Flow $flow): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Determine whether the user can import flows.
     */
    public function import(?Authenticatable $user): bool
    {
        return $this->hasAccess($user);
    }

    /**
     * Check if user has basic access to AutoBuilder.
     */
    protected function hasAccess(?Authenticatable $user): bool
    {
        $gate = config('autobuilder.authorization.gate');

        // If no gate configured, allow all authenticated users
        if (! $gate) {
            return $user !== null;
        }

        // Check the gate if user is authenticated
        if ($user) {
            return \Illuminate\Support\Facades\Gate::forUser($user)->allows($gate);
        }

        return false;
    }

    /**
     * Check if user is a super admin.
     */
    protected function isSuperAdmin(Authenticatable $user): bool
    {
        $superAdmins = config('autobuilder.authorization.super_admins', []);

        if (empty($superAdmins)) {
            return false;
        }

        $userId = $user->getAuthIdentifier();

        return in_array($userId, $superAdmins, false);
    }
}
