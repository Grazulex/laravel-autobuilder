<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Models;

use Grazulex\AutoBuilder\Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string|null $parent_id
 * @property-read int|null $flows_count
 */
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    use HasUlids;

    protected static function newFactory(): FolderFactory
    {
        return FolderFactory::new();
    }

    protected $table = 'autobuilder_folders';

    protected $fillable = [
        'name',
        'parent_id',
    ];

    protected static function booted(): void
    {
        // Deleting a folder never deletes its content: flows and sub-folders
        // move up to the parent folder (or to the root when there is none).
        static::deleting(function (Folder $folder): void {
            Flow::withTrashed()
                ->where('folder_id', $folder->id)
                ->update(['folder_id' => $folder->parent_id]);

            static::query()
                ->where('parent_id', $folder->id)
                ->update(['parent_id' => $folder->parent_id]);
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function flows(): HasMany
    {
        return $this->hasMany(Flow::class, 'folder_id');
    }

    /**
     * Get the IDs of every folder nested under this one (at any depth).
     *
     * @return array<int, string>
     */
    public function descendantIds(): array
    {
        $descendants = [];
        $parentIds = [$this->id];

        while ($parentIds !== []) {
            $parentIds = static::query()
                ->whereIn('parent_id', $parentIds)
                ->whereNotIn('id', $descendants)
                ->pluck('id')
                ->all();

            $descendants = [...$descendants, ...$parentIds];
        }

        return $descendants;
    }

    /**
     * Check whether this folder can be moved under the given parent
     * without creating a loop in the folder tree.
     */
    public function canMoveTo(?string $parentId): bool
    {
        if ($parentId === null) {
            return true;
        }

        if ($parentId === $this->id) {
            return false;
        }

        return ! in_array($parentId, $this->descendantIds(), true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
