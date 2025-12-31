<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Conditions;

use Grazulex\AutoBuilder\Bricks\Condition;
use Grazulex\AutoBuilder\Fields\Text;
use Grazulex\AutoBuilder\Flow\FlowContext;
use Illuminate\Database\Eloquent\Model;

class UserHasPermission extends Condition
{
    public function name(): string
    {
        return 'User Has Permission';
    }

    public function description(): string
    {
        return 'Checks if a user has a specific permission (supports Spatie Permission).';
    }

    public function icon(): string
    {
        return 'key';
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

            Text::make('permission')
                ->label('Permission')
                ->placeholder('edit articles')
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

        $permission = $this->resolveValue($this->config('permission'), $context);

        // Support for Spatie Permission
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo($permission);
        }

        // Laravel Gate fallback
        if (method_exists($user, 'can')) {
            return $user->can($permission);
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
