<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'fk_id_rol',
        'nombre',
        'apellido',
        'correo',
        'contrasena',
        'estado',
    ];

    protected $hidden = [
        'contrasena',
    ];

    /**
     * Sobrescribir nombre del campo de contraseña para Laravel Auth
     */
    public function getAuthPasswordName()
    {
        return 'contrasena';
    }

    /**
     * Obtener la contraseña del usuario.
     */
    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    /**
     * Relación con el modelo Rol
     */
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'fk_id_rol', 'id_rol');
    }

    /**
     * Relación con los logs generados por el usuario.
     */
    public function logs()
    {
        return $this->hasMany(Log::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Determina si el usuario tiene rol de administrador.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        // Preferir verificación por ID (configurable)
        $adminId = config('roles.admin');
        if ($adminId) {
            return $this->hasRoleId((int) $adminId);
        }

        // Fallback: verificar por nombre de rol (compatibilidad)
        if (!$this->relationLoaded('rol')) {
            $this->load('rol');
        }

        if (!$this->rol) {
            return false;
        }

        $name = strtolower(trim($this->rol->nombre_rol ?? ''));
        return in_array($name, ['admin', 'administrator', 'administrador']);
    }

    /**
     * Comprueba si el usuario tiene el `fk_id_rol` dado.
     */
    public function hasRoleId(int $id): bool
    {
        return (int) ($this->fk_id_rol ?? $this->fk_id_rol) === $id;
    }

    /**
     * Comprueba si el usuario es soporte.
     */
    public function isSoporte(): bool
    {
        $id = config('roles.soporte');
        return $id ? $this->hasRoleId((int) $id) : false;
    }

    /**
     * Comprueba si el usuario es director.
     */
    public function isDirector(): bool
    {
        $id = config('roles.director');
        return $id ? $this->hasRoleId((int) $id) : false;
    }
}
