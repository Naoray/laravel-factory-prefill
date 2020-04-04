<?php

namespace Naoray\LaravelFactoryPrefill\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class Habit extends Model
{
    protected $guarded = [];

    /**
     * Get user of habit.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BlongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
