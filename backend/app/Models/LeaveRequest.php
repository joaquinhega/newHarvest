<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'leave_requests';

    protected $fillable = [
        'employee_id',
        'type',
        'days_count',
        'start_date',
        'end_date',
        'diagnosis',
        'attachment_path',
        'status',
        'approved_by_user_id',
        'action_at',
        'borrado',
    ];

    protected $casts = [
        'days_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'action_at' => 'datetime',
        'borrado' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id', 'id_usuario');
    }
}