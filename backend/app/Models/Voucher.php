<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'vouchers';

    protected $fillable = [
        'remito_code',
        'company_id',
        'company_name',
        'user_id',
        'passenger_name',
        'origin',
        'pickup_time',
        'destination',
        'dropoff_time',
        'wait_time',
        'date',
        'amount',
        'observation',
        'signature_path',
        'status',
        'borrado',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'borrado' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_usuario');
    }
}