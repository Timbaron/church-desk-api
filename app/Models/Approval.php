<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['requisition_id', 'approver_id', 'status', 'comments'];

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
        'timestamp' => 'datetime',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
