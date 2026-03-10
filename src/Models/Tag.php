<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Models;

use Grazulex\AutoBuilder\Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasUlids;

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    protected $table = 'autobuilder_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag): void {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function flows(): BelongsToMany
    {
        return $this->belongsToMany(Flow::class, 'autobuilder_flow_tag');
    }
}
