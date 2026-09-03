<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryReceiptConcept extends Model
{
    use HasFactory;

    protected $table = 'salary_receipt_concepts';

    protected $fillable = [
        'salary_receipt_id',
        'code',
        'description',
        'quantity',
        'remunerative_amount',
        'non_remunerative_amount',
        'deduction_amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'remunerative_amount' => 'decimal:2',
        'non_remunerative_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function salaryReceipt(): BelongsTo
    {
        return $this->belongsTo(SalaryReceipt::class);
    }
}
