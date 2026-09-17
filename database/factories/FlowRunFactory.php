<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Database\Factories;

use Grazulex\AutoBuilder\Models\Flow;
use Grazulex\AutoBuilder\Models\FlowRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlowRun>
 */
class FlowRunFactory extends Factory
{
    protected $model = FlowRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 hour', 'now');
        $completedAt = (clone $startedAt)->modify('+'.random_int(1, 60).' seconds');

        return [
            'flow_id' => Flow::factory(),
            'status' => 'completed',
            'payload' => [],
            'variables' => [],
            'logs' => [],
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ];
    }

    /**
     * Indicate that the run is running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'running',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the run completed successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the run failed.
     */
    public function failed(?string $error = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'failed',
            'error' => $error ?? $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the run is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'paused',
            'completed_at' => null,
        ]);
    }

    /**
     * Add payload to the run.
     */
    public function withPayload(array $payload): static
    {
        return $this->state(fn (array $attributes): array => [
            'payload' => $payload,
        ]);
    }

    /**
     * Add variables to the run.
     */
    public function withVariables(array $variables): static
    {
        return $this->state(fn (array $attributes): array => [
            'variables' => $variables,
        ]);
    }

    /**
     * Add logs to the run.
     */
    public function withLogs(array $logs = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'logs' => $logs ?: [
                ['level' => 'info', 'message' => 'Flow started', 'timestamp' => now()->toIso8601String()],
                ['level' => 'info', 'message' => 'Flow completed', 'timestamp' => now()->toIso8601String()],
            ],
        ]);
    }

    /**
     * Associate with a specific flow.
     */
    public function forFlow(Flow $flow): static
    {
        return $this->state(fn (array $attributes): array => [
            'flow_id' => $flow->id,
        ]);
    }
}
