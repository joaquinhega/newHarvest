<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sanction extends Model
{
    use HasFactory;

    protected $table = 'sanctions';

    protected $fillable = [
        'employee_id',
        'sanction_number',
        'type',
        'days_count',
        'date',
        'reason',
        'file_path',
        'status',
        'read_at',
        'signature_path',
        'signed_at',
        'borrado',
    ];

    protected $casts = [
        'date' => 'date',
        'days_count' => 'integer',
        'read_at' => 'datetime',
        'signed_at' => 'datetime',
        'borrado' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}