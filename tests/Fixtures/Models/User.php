<?php

namespace Naoray\LaravelFactoryPrefill\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];

    /**
     * Get all habits.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function habits()
    {
        return $this->hasMany(Habit::class);
    }

    /**
     * Get all my cars.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
