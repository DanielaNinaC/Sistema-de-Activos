<?php

namespace Database\Seeders;

use App\Models\OrigenMantenimiento;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            1 => 'Administrador',
            2 => 'Soporte',
            3 => 'Director',
        ];

        foreach ($roles as $id => $nombre) {
            Rol::firstOrCreate(
                ['id_rol' => $id],
                ['nombre_rol' => $nombre]
            );
        }

        $usuarios = [
            [
                'id_usuario' => 1,
                'fk_id_rol' => 1,
                'nombre' => 'Franco',
                'apellido' => 'Admin',
                'correo' => 'admin@ejemplo.com',
                'contrasena' => Hash::make('password123'),
                'estado' => true,
            ],
            [
                'id_usuario' => 2,
                'fk_id_rol' => 2,
                'nombre' => 'Carlos',
                'apellido' => 'Soporte',
                'correo' => 'soporte@ejemplo.com',
                'contrasena' => Hash::make('password123'),
                'estado' => true,
            ],
            [
                'id_usuario' => 3,
                'fk_id_rol' => 3,
                'nombre' => 'María',
                'apellido' => 'Directora',
                'correo' => 'director@ejemplo.com',
                'contrasena' => Hash::make('password123'),
                'estado' => true,
            ],
        ];

        foreach ($usuarios as $usr) {
            Usuario::updateOrCreate(
                ['correo' => $usr['correo']],
                $usr
            );
        }

        // Orígenes de Mantenimiento
        $origenes = [
            1 => 'Preventivo',
            2 => 'Evaluación de riesgos',
            3 => 'Incidente',
        ];

        foreach ($origenes as $id => $nombre) {
            OrigenMantenimiento::firstOrCreate(
                ['id_origen' => $id],
                ['nombre' => $nombre]
            );
        }

        $this->call(DatosPruebaSeeder::class);
    }
}

