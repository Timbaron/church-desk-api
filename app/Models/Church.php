<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'subscription_status', 'subscription_ends_at'];

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

    protected $casts = [
        'subscription_ends_at' => 'datetime',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function requisitions(): HasMany
    {
        return $this->hasMany(Requisition::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
