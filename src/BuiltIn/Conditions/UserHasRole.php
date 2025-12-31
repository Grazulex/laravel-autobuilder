<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Database\Eloquent\Model;

class UserHasRole extends Condition
{
    public function name(): string
    {
        return 'User Has Role';
    }

    public function description(): string
    {
        return 'Checks if a user has a specific role (supports Spatie Permission).';
    }

    public function icon(): string
    {
        return 'shield-check';
    }

    public function category(): string
    {
        return 'Authorization';
    }

    public function fields(): array
    {
        return [
            Text::make('user_field')
                ->label('User Field in Payload')
                ->default('user')
                ->description('Field containing user ID or model'),

            Text::make('role')
                ->label('Role')
                ->placeholder('admin')
                ->supportsVariables()
                ->required(),
        ];
    }

    public function evaluate(FlowContext $context): bool
    {
        $user = $this->resolveUser($context);

        if (! $user) {
            return false;
        }

        $role = $this->resolveValue($this->config('role'), $context);

        // Support for Spatie Permission
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }

        // Fallback to role attribute
        if (isset($user->role)) {
            return $user->role === $role;
        }

        // Check roles relationship
        if (method_exists($user, 'roles')) {
            return $user->roles()->where('name', $role)->exists();
        }

        return false;
    }

    protected function resolveUser(FlowContext $context): ?Model
    {
        $userField = $this->config('user_field', 'user');
        $userData = $context->get($userField);

        if ($userData instanceof Model) {
            return $userData;
        }

        $userModel = config('auth.providers.users.model');

        if (is_numeric($userData)) {
            return $userModel::find($userData);
        }

        if (is_array($userData) && isset($userData['id'])) {
            return $userModel::find($userData['id']);
        }

        return null;
    }
}
