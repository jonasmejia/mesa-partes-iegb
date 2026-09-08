<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'dni',
        'telefono',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo' => 'boolean',
        'password' => 'hashed',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Historial completo de cargos y áreas asignadas al usuario.
     */
    public function userCargos(): HasMany
    {
        return $this->hasMany(
            UserCargo::class,
            'user_id'
        );
    }

    /**
     * Asignaciones activas y vigentes del usuario.
     */
    public function asignacionesActuales(): HasMany
    {
        return $this->hasMany(
            UserCargo::class,
            'user_id'
        )
            ->where('activo', true)
            ->where('fecha_inicio', '<=', today())
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', today());
            });
    }

    /**
     * Notificaciones dirigidas al usuario.
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(
            Notificacion::class,
            'user_id'
        );
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(
            Auditoria::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Solo usuarios activos.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
