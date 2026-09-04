<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryReceiptAudit extends Model
{
    protected $table = 'salary_receipt_audits';

    protected $fillable = [
        'salary_receipt_id',
        'batch_id',
        'user_id',
        'user_name',
        'event',
        'file_hash',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(SalaryReceipt::class, 'salary_receipt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_usuario');
    }
}
