<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flow extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $table = 'autobuilder_flows';

    protected $fillable = [
        'name',
        'description',
        'nodes',
        'edges',
        'viewport',
        'webhook_path',
        'active',
        'sync',
        'trigger_type',
        'trigger_config',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'viewport' => 'array',
        'trigger_config' => 'array',
        'active' => 'boolean',
        'sync' => 'boolean',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(FlowRun::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    public function duplicate(): static
    {
        $clone = $this->replicate();
        $clone->name = $this->name . ' (Copy)';
        $clone->active = false;
        $clone->webhook_path = null;
        $clone->save();

        return $clone;
    }

    public function export(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
        ];
    }

    public static function import(array $data): static
    {
        return static::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'nodes' => $data['nodes'],
            'edges' => $data['edges'],
            'active' => false,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
