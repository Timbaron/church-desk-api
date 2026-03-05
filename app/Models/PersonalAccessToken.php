<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Support\Str;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    // Disable auto-incrementing since UUIDs are used.
    public $incrementing = false;
    // Tell Eloquent the primary key type is a string (UUID).
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        // Automatically assign a new UUID on creating
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
