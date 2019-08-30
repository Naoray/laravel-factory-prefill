<?php

namespace Naoray\LaravelFactoryPrefill\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * Get my owner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get my previous owner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function previousOwner()
    {
        return $this->belongsTo(User::class);
    }
}
