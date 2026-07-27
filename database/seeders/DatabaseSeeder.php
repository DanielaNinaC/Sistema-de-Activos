<?php

namespace Database\Seeders;

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

        $rolAdmin = Rol::findOrFail(1);

        Usuario::updateOrCreate(
            ['correo' => 'admin@ejemplo.com'],
            [
                'fk_id_rol' => $rolAdmin->id_rol,
                'nombre' => 'Franco',
                'apellido' => 'Admin',
                'correo' => 'admin@ejemplo.com',
                'contrasena' => Hash::make('password123'),
                'estado' => true,
            ]
        );
    }
}
