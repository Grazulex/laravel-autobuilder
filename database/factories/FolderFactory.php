<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Database\Factories;

use Grazulex\AutoBuilder\Models\Folder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    protected $model = Folder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst((string) $this->faker->unique()->word()),
            'parent_id' => null,
        ];
    }

    /**
     * Indicate that the folder is nested inside another folder.
     */
    public function childOf(Folder $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
        ]);
    }
}
