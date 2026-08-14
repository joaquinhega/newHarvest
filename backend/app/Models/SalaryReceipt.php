<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryReceipt extends Model
{
    use HasFactory;

    protected $table = 'salary_receipts';

    protected $fillable = [
        'employee_id',
        'period',
        'gross_amount',
        'deductions_amount',
        'net_amount',
        'status',
        'file_path',
        'employer_signature_path',
        'employer_signed_at',
        'employee_signature_path',
        'employee_signed_at',
        'legal_accepted',
        'borrado',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'deductions_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'employer_signed_at' => 'datetime',
        'employee_signed_at' => 'datetime',
        'legal_accepted' => 'boolean',
        'borrado' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}