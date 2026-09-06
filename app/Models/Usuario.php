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
        'permiso_dashboard',
        'permiso_activos',
        'permiso_incidentes',
        'permiso_riesgos',
        'permiso_mantenimiento',
        'permiso_informes',
        'permiso_usuarios',
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

    /**
     * Verifica si el usuario tiene acceso a un módulo específico.
     *
     * @param string $modulo  Clave del módulo (dashboard, activos, incidentes, riesgos, mantenimiento, informes, usuarios)
     * @return bool
     */
    public function tieneAccesoModulo(string $modulo): bool
    {
        $campo = 'permiso_' . $modulo;
        return (bool) ($this->{$campo} ?? false);
    }

    /**
     * Retorna el mapeo de permisos por defecto según el rol.
     *
     * @param int $rolId
     * @return array
     */
    public static function permisosDefaultPorRol(int $rolId): array
    {
        $adminId    = (int) config('roles.admin', 1);
        $soporteId  = (int) config('roles.soporte', 2);
        $directorId = (int) config('roles.director', 3);

        $mapeo = [
            $adminId => [
                'permiso_dashboard'      => true,
                'permiso_activos'        => true,
                'permiso_incidentes'     => true,
                'permiso_riesgos'        => true,
                'permiso_mantenimiento'  => true,
                'permiso_informes'       => true,
                'permiso_usuarios'       => true,
            ],
            $soporteId => [
                'permiso_dashboard'      => true,
                'permiso_activos'        => true,
                'permiso_incidentes'     => true,
                'permiso_riesgos'        => true,
                'permiso_mantenimiento'  => true,
                'permiso_informes'       => false,
                'permiso_usuarios'       => false,
            ],
            $directorId => [
                'permiso_dashboard'      => true,
                'permiso_activos'        => false,
                'permiso_incidentes'     => false,
                'permiso_riesgos'        => false,
                'permiso_mantenimiento'  => false,
                'permiso_informes'       => true,
                'permiso_usuarios'       => false,
            ],
        ];

        // Default: todo habilitado excepto usuarios
        return $mapeo[$rolId] ?? [
            'permiso_dashboard'      => true,
            'permiso_activos'        => true,
            'permiso_incidentes'     => true,
            'permiso_riesgos'        => true,
            'permiso_mantenimiento'  => true,
            'permiso_informes'       => true,
            'permiso_usuarios'       => false,
        ];
    }
}
