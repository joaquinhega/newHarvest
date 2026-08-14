<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'cuil',
        'position',
        'hire_date',
        'birth_date',
        'phone',
        'address',
        'status',
        'borrado',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'birth_date' => 'date',
        'borrado' => 'boolean',
    ];

    /**
     * Relación 1:1 opcional con el Usuario autenticable.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_usuario');
    }

    /**
     * Accessor para obtener el nombre completo.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
    public function salaryReceipts(): HasMany
    {
        return $this->hasMany(SalaryReceipt::class);
    }

    public function sanctions(): HasMany
    {
        return $this->hasMany(Sanction::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}