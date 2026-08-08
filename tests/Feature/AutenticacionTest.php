<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(int $estado = 1, string $correo = 'usuario@example.com'): Usuario
    {
        $rol = Rol::firstOrCreate(['id_rol' => 1], ['nombre_rol' => 'Administrador']);

        return Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Usuario',
            'apellido' => 'Prueba',
            'correo' => $correo,
            'contrasena' => Hash::make('secret123'),
            'estado' => $estado,
        ]);
    }

    /**
     * El código de seguridad se indexa en cache por session()->getId(), y cada
     * petición de prueba arranca una sesión nueva, así que la clave nunca
     * coincidiría. Se sustituye la cache para poder ejercitar el resto del
     * flujo de autenticación, que es lo que estas pruebas verifican.
     */
    private function omitirCaptcha(string $codigo = 'ABC12'): string
    {
        Cache::shouldReceive('get')->andReturn($codigo);
        Cache::shouldReceive('has')->andReturn(true);
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('forget')->andReturn(true);

        return $codigo;
    }

    public function test_login_con_recordarme_persiste_el_token_sin_error_de_columna(): void
    {
        $usuario = $this->crearUsuario();
        $codigo = $this->omitirCaptcha();

        $response = $this->post('/login', [
            'correo' => $usuario->correo,
            'contrasena' => 'secret123',
            'codigo_cache' => $codigo,
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($usuario);

        // Antes de existir la columna remember_token esto reventaba con
        // "Unknown column 'remember_token'" al intentar guardar el token.
        $this->assertNotNull($usuario->fresh()->remember_token);
    }

    public function test_login_sin_recordarme_no_genera_token_persistente(): void
    {
        $usuario = $this->crearUsuario(1, 'sinrecordar@example.com');
        $codigo = $this->omitirCaptcha();

        $response = $this->post('/login', [
            'correo' => $usuario->correo,
            'contrasena' => 'secret123',
            'codigo_cache' => $codigo,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNull($usuario->fresh()->remember_token);
    }

    public function test_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $usuario = $this->crearUsuario(0, 'desactivado@example.com');
        $codigo = $this->omitirCaptcha();

        $response = $this->post('/login', [
            'correo' => $usuario->correo,
            'contrasena' => 'secret123',
            'codigo_cache' => $codigo,
        ]);

        $response->assertSessionHasErrors('correo');
        $this->assertGuest();
    }

    public function test_sesion_abierta_se_cierra_si_la_cuenta_es_desactivada(): void
    {
        $usuario = $this->crearUsuario();

        $this->actingAs($usuario);
        $this->get(route('dashboard'))->assertOk();

        // El administrador desactiva la cuenta mientras la sesión sigue abierta.
        $usuario->update(['estado' => 0]);

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
