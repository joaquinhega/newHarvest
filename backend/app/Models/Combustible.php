<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Combustible extends Model
{
    use HasFactory;

    protected $table = 'combustibles';

    protected $fillable = [
        'remito_code',
        'user_id',
        'driver_name',
        'plate',
        'date',
        'amount',
        'status',
        'borrado',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'borrado' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_usuario');
    }
}