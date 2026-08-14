<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id_usuario';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id_usuario',
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'letter',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'active' => 'boolean',
        'password' => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'user_id', 'id_usuario');
    }

    public function combustibles(): HasMany
    {
        return $this->hasMany(Combustible::class, 'user_id', 'id_usuario');
    }
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'user_id', 'id_usuario');
    }
}
