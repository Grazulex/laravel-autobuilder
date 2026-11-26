<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Models;

use Grazulex\AutoBuilder\Database\Factories\FlowRunFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowRun extends Model
{
    /** @use HasFactory<FlowRunFactory> */
    use HasFactory;

    use HasUlids;

    protected static function newFactory(): FlowRunFactory
    {
        return FlowRunFactory::new();
    }

    protected $table = 'autobuilder_flow_runs';

    protected $fillable = [
        'id',
        'flow_id',
        'status',
        'payload',
        'variables',
        'logs',
        'error',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'variables' => 'array',
        'logs' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * ID of the node that made the run fail, read from the last error log entry
     */
    public function failedNodeId(): ?string
    {
        foreach (array_reverse($this->logs ?? []) as $log) {
            if (! is_array($log) || ($log['level'] ?? null) !== 'error') {
                continue;
            }

            $nodeId = $log['data']['node_id'] ?? null;

            if (is_string($nodeId) && $nodeId !== '') {
                return $nodeId;
            }
        }

        return null;
    }

    public function getDurationAttribute(): ?int
    {
        if (! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
