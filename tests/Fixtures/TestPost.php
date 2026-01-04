<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Simple test model for testing CRUD actions.
 */
class TestPost extends Model
{
    use SoftDeletes;

    protected $table = 'test_posts';

    protected $fillable = [
        'title',
        'content',
        'status',
        'views',
    ];

    protected $casts = [
        'views' => 'integer',
    ];
}
