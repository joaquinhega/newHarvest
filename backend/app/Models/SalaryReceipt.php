<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryReceipt extends Model
{
    use HasFactory;

    protected $table = 'salary_receipts';

    protected $fillable = [
        'employee_id',
        'period',
        'gross_amount',
        'non_remunerative_amount',
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
        'non_remunerative_amount' => 'decimal:2',
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

    public function concepts(): HasMany
    {
        return $this->hasMany(SalaryReceiptConcept::class)->orderBy('sort_order');
    }

    /**
     * Recalcula gross_amount, non_remunerative_amount, deductions_amount y net_amount
     * a partir de los conceptos cargados. Se usa cada vez que se guardan conceptos,
     * para que los 3 (ahora 4) totales nunca queden desincronizados con el detalle.
     */
    public function recalculateTotalsFromConcepts(): self
    {
        $concepts = $this->relationLoaded('concepts') ? $this->concepts : $this->concepts()->get();

        $gross = $concepts->sum('remunerative_amount');
        $nonRemunerative = $concepts->sum('non_remunerative_amount');
        $deductions = $concepts->sum('deduction_amount');

        $this->gross_amount = $gross;
        $this->non_remunerative_amount = $nonRemunerative;
        $this->deductions_amount = $deductions;
        $this->net_amount = $gross + $nonRemunerative - $deductions;

        return $this;
    }
}