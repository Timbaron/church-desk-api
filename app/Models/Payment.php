<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    public $timestamps = false; // Only using the 'timestamp' column

    protected $fillable = [
        'requisition_id',
        'amount_paid',
        'payment_method',
        'payment_date',
        'reference_number',
        'proof_file',
        'recorded_by_id'
    ];

    protected $casts = [
        'amount_paid' => 'float',
        'proof_file' => 'array',
        'timestamp' => 'datetime',
        'payment_date' => 'date:Y-m-d'
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }
}
