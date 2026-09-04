<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    protected static function booted(): void
    {
        static::updated(function (self $receipt) {
            if (
                ! $receipt->wasChanged('employer_signed_at') ||
                empty($receipt->employer_signed_at) ||
                $receipt->status !== 'firmado_empresa'
            ) {
                return;
            }

            $user = Auth::user();
            $occurredAt = $receipt->employer_signed_at;
            $userId = $user?->id_usuario;
            $userName = $user ? trim("{$user->first_name} {$user->last_name}") : null;

            // El mismo usuario + instante de firma identifica el lote completo,
            // ya que signBatch() aplica el mismo timestamp a todos los recibos.
            $batchId = $userId
                ? (string) Str::uuid($userId . '|' . $occurredAt->format('Y-m-d H:i:s.u'))
                : null;

            $fileHash = null;
            if ($receipt->file_path && Storage::disk('public')->exists($receipt->file_path)) {
                $fileHash = hash_file('sha256', Storage::disk('public')->path($receipt->file_path));
            }

            SalaryReceiptAudit::create([
                'salary_receipt_id' => $receipt->id,
                'batch_id' => $batchId,
                'user_id' => $userId,
                'user_name' => $userName,
                'event' => 'firma_empresa',
                'file_hash' => $fileHash,
                'metadata' => [
                    'modo' => config('newharvest.firma_modo', 'simulado'),
                    'status_anterior' => $receipt->getOriginal('status'),
                    'status_nuevo' => $receipt->status,
                ],
                'occurred_at' => $occurredAt,
            ]);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function concepts(): HasMany
    {
        return $this->hasMany(SalaryReceiptConcept::class)->orderBy('sort_order');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(SalaryReceiptAudit::class, 'salary_receipt_id')->orderByDesc('occurred_at');
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