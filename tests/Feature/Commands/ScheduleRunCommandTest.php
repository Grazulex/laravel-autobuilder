<?php

declare(strict_types=1);

use Grazulex\AutoBuilder\BuiltIn\Triggers\OnSchedule;
use Grazulex\AutoBuilder\Models\Flow;

// =============================================================================
// Basic Command Tests
// =============================================================================

describe('schedule run command', function () {
    it('runs without errors when no scheduled flows exist', function () {
        $this->artisan('autobuilder:schedule-run')
            ->expectsOutput('No scheduled flows found.')
            ->assertSuccessful();
    });

    it('shows help text', function () {
        $this->artisan('autobuilder:schedule-run --help')
            ->assertSuccessful();
    });

    it('supports dry-run option', function () {
        $this->artisan('autobuilder:schedule-run --dry-run')
            ->assertSuccessful();
    });
});

// =============================================================================
// Scheduled Flow Detection Tests
// =============================================================================

describe('scheduled flow detection', function () {
    it('finds active flows with OnSchedule trigger', function () {
        // Create a flow with OnSchedule trigger
        $flow = Flow::create([
            'name' => 'Scheduled Test Flow',
            'trigger_type' => OnSchedule::class,
            'trigger_config' => [
                'frequency' => 'everyMinute',
                'timezone' => 'UTC',
            ],
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnSchedule::class,
                        'config' => [
                            'frequency' => 'everyMinute',
                            'timezone' => 'UTC',
                        ],
                    ],
                ],
            ],
            'edges' => [],
            'active' => true,
        ]);

        $this->artisan('autobuilder:schedule-run')
            ->expectsOutputToContain('Found 1 scheduled flow(s).')
            ->assertSuccessful();
    });

    it('ignores inactive flows', function () {
        // Create an inactive flow
        Flow::create([
            'name' => 'Inactive Scheduled Flow',
            'trigger_type' => OnSchedule::class,
            'trigger_config' => [
                'frequency' => 'everyMinute',
            ],
            'nodes' => [],
            'edges' => [],
            'active' => false,
        ]);

        $this->artisan('autobuilder:schedule-run')
            ->expectsOutput('No scheduled flows found.')
            ->assertSuccessful();
    });

    it('ignores flows with non-schedule triggers', function () {
        // Create a flow with different trigger type
        Flow::create([
            'name' => 'Manual Trigger Flow',
            'trigger_type' => 'Grazulex\\AutoBuilder\\BuiltIn\\Triggers\\OnManualTrigger',
            'nodes' => [],
            'edges' => [],
            'active' => true,
        ]);

        $this->artisan('autobuilder:schedule-run')
            ->expectsOutput('No scheduled flows found.')
            ->assertSuccessful();
    });
});

// =============================================================================
// Dry Run Tests
// =============================================================================

describe('dry run', function () {
    it('runs successfully in dry-run mode with scheduled flows', function () {
        $flow = Flow::create([
            'name' => 'Dry Run Test Flow',
            'trigger_type' => OnSchedule::class,
            'trigger_config' => [
                'frequency' => 'everyMinute',
                'timezone' => 'UTC',
            ],
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'data' => [
                        'brick' => OnSchedule::class,
                        'config' => [
                            'frequency' => 'everyMinute',
                            'timezone' => 'UTC',
                        ],
                    ],
                ],
            ],
            'edges' => [],
            'active' => true,
        ]);

        // Dry-run should complete successfully and show scheduled flows
        $this->artisan('autobuilder:schedule-run --dry-run')
            ->expectsOutputToContain('Found 1 scheduled flow(s).')
            ->assertSuccessful();
    });

    it('shows flow count summary in dry-run mode', function () {
        $this->artisan('autobuilder:schedule-run --dry-run')
            ->expectsOutputToContain('flow(s)')
            ->assertSuccessful();
    });
});
