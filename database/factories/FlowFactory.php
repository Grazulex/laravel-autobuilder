<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Database\Factories;

use Grazulex\AutoBuilder\Models\Flow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flow>
 */
class FlowFactory extends Factory
{
    protected $model = Flow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'nodes' => [],
            'edges' => [],
            'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1],
            'active' => false,
            'sync' => false,
        ];
    }

    /**
     * Indicate that the flow is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the flow is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the flow runs synchronously.
     */
    public function sync(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync' => true,
        ]);
    }

    /**
     * Add a webhook path to the flow.
     */
    public function withWebhook(?string $path = null): static
    {
        return $this->state(fn (array $attributes) => [
            'webhook_path' => $path ?? $this->faker->slug(),
        ]);
    }

    /**
     * Add a webhook with secret.
     */
    public function withWebhookSecret(string $secret, ?string $path = null): static
    {
        return $this->state(fn (array $attributes) => [
            'webhook_path' => $path ?? $this->faker->slug(),
            'trigger_config' => ['secret' => $secret],
        ]);
    }

    /**
     * Add sample nodes to the flow.
     */
    public function withNodes(array $nodes = []): static
    {
        return $this->state(fn (array $attributes) => [
            'nodes' => $nodes ?: [
                [
                    'id' => 'trigger-1',
                    'type' => 'trigger',
                    'position' => ['x' => 100, 'y' => 100],
                    'data' => ['brick' => 'OnWebhookReceived'],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'action',
                    'position' => ['x' => 100, 'y' => 250],
                    'data' => ['brick' => 'SetVariable'],
                ],
            ],
        ]);
    }

    /**
     * Add sample edges to the flow.
     */
    public function withEdges(array $edges = []): static
    {
        return $this->state(fn (array $attributes) => [
            'edges' => $edges ?: [
                [
                    'id' => 'edge-1',
                    'source' => 'trigger-1',
                    'target' => 'action-1',
                ],
            ],
        ]);
    }

    /**
     * Create a complete flow with nodes and edges.
     */
    public function complete(): static
    {
        return $this->withNodes()->withEdges();
    }
}
