<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['section_id', 'name'];

    // Disable auto-incrementing since UUIDs are used.
    public $incrementing = false;
    // 2. Tell Eloquent the primary key type is a string (UUID).
    protected $keyType = 'string';
    protected static function boot()
    {
        parent::boot();

        // When a model is being created, check if the ID is empty.
        // If it is, automatically assign a new UUID string to the ID field.
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function requisitions(): HasMany
    {
        return $this->hasMany(Requisition::class);
    }
}
