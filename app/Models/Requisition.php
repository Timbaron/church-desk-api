<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Requisition extends Model
{
    use HasFactory;

    // --- UUID Configuration (From Church Model Pattern) ---
    // 1. Disable auto-incrementing since UUIDs are used.
    public $incrementing = false;

    // 2. Tell Eloquent the primary key type is a string (UUID).
    protected $keyType = 'string';

    /**
     * Boot the model and add a UUID generation listener.
     */
    protected static function boot()
    {
        parent::boot();

        // When a model is being created, automatically assign a new UUID.
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
    // --------------------------------------------------------

    protected $fillable = [
        'title',
        'requested_by_id',
        'department_id',
        'section_id',
        'church_id',
        'amount_requested',
        'category',
        'purpose',
        'date_needed',
        'status',
        'attachments',
        'final_receipt_url',
    ];

    protected $casts = [
        'amount_requested' => 'float',
        'date_needed' => 'date',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function church()
    {
        return $this->belongsTo(Church::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function payment()
    {
        // A requisition has one payment disbursement record
        return $this->hasOne(Payment::class);
    }

    public function auditLogs()
    {
        // Requisitions can have many related audit logs
        return $this->hasMany(AuditLog::class);
    }
}
