<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\BuiltIn\Triggers;

use Grazulex\AutoBuilder\Bricks\Trigger;
use Grazulex\AutoBuilder\Fields\KeyValue;
use Grazulex\AutoBuilder\Fields\Textarea;

class OnManualTrigger extends Trigger
{
    public function name(): string
    {
        return 'Manual Trigger';
    }

    public function description(): string
    {
        return 'Allows manual triggering of a flow via API or UI.';
    }

    public function icon(): string
    {
        return 'play';
    }

    public function category(): string
    {
        return 'Manual';
    }

    public function fields(): array
    {
        return [
            KeyValue::make('default_payload')
                ->label('Default Payload')
                ->description('Default values when triggered manually'),

            Textarea::make('description')
                ->label('Instructions')
                ->description('Instructions for users who trigger this flow')
                ->rows(3),
        ];
    }

    public function register(): void
    {
        // No automatic registration needed - triggered via API
    }

    public function samplePayload(): array
    {
        $defaultPayload = $this->config('default_payload', []);

        return array_merge([
            'triggered_at' => now()->toIso8601String(),
            'triggered_by' => 'user',
        ], $defaultPayload);
    }
}
