<?php

namespace Naoray\LaravelFactoryPrefill\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];

    protected $hidden = ['password'];
}
