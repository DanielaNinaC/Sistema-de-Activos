<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Mantenimiento;
use App\Models\ModoFalla;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidacionesFormulariosTest extends TestCase
{
    use RefreshDatabase;

    private function loginComoAdmin(string $correo = 'admin.validaciones@example.com'): Usuario
    {
        $rol = Rol::firstOrCreate(['id_rol' => 1], ['nombre_rol' => 'Administrador']);
        $admin = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Admin',
            'apellido' => 'Validaciones',
            'correo' => $correo,
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
            'permiso_usuarios' => 1,
        ]);
        $admin->refresh();
        $this->actingAs($admin);

        return $admin;
    }

    private function categoria(string $nombre): Categoria
    {
        return Categoria::firstOrCreate(
            ['nombre_categoria' => $nombre],
            ['vida_util_meses' => 48, 'nivel_dependencia' => 'Media']
        );
    }

    private function activo(string $codigo, string $categoria = 'Laptops', ?string $adquisicion = null): ActivoTecnologico
    {
        $ubicacion = Ubicacion::firstOrCreate(['seccion' => 'Lab', 'aula' => 'A-1']);

        return ActivoTecnologico::create([
            'fk_id_categoria' => $this->categoria($categoria)->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => $codigo,
            'nombre_equipo' => 'Equipo ' . $codigo,
            'fecha_adquisicion' => $adquisicion ?? now()->subYear()->format('Y-m-d'),
        ]);
    }

    private function modoFalla(Categoria $categoria, string $nombre): ModoFalla
    {
        return ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => $nombre,
            'nivel_severidad' => 'alta',
            'valor_severidad_base' => 8,
            'nivel_msr' => 'muy_baja',
            'valor_msr_base' => 9,
        ]);
    }

    private function datosActivo(array $extra = []): array
    {
        $ubicacion = Ubicacion::firstOrCreate(['seccion' => 'Lab', 'aula' => 'A-1']);

        return array_merge([
            'fk_id_categoria' => $this->categoria('Laptops')->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'NUEVO-1',
            'nombre_equipo' => 'Equipo nuevo',
            'fecha_adquisicion' => now()->subYear()->format('Y-m-d'),
            'estado_operativo' => 'Operativo',
            'etapa_ciclo_vida' => 'Operación',
            // Ficha técnica mínima de una computadora (la categoría base es 'Laptops').
            'procesador' => 'Intel Core i5',
            'ram_gb' => 8,
        ], $extra);
    }

    public function test_el_activo_exige_fecha_de_adquisicion(): void
    {
        $this->loginComoAdmin();

        $this->post(route('admin.activos.registrar.store'), $this->datosActivo(['fecha_adquisicion' => null]))
            ->assertSessionHasErrors('fecha_adquisicion');

        $this->assertSame('El campo fecha de adquisición es obligatorio.', session('errors')->first('fecha_adquisicion'));
        $this->assertSame(0, ActivoTecnologico::count());
    }

    public function test_una_computadora_exige_procesador_y_ram_pero_otras_categorias_no(): void
    {
        $this->loginComoAdmin();

        $this->post(route('admin.activos.registrar.store'), $this->datosActivo([
            'codigo_patrimonial' => 'LAP-SIN-FICHA',
            'procesador' => null,
            'ram_gb' => null,
        ]))->assertSessionHasErrors(['procesador', 'ram_gb']);

        // Un servidor no necesita procesador ni RAM para registrarse.
        $this->post(route('admin.activos.registrar.store'), $this->datosActivo([
            'fk_id_categoria' => $this->categoria('Servidores')->id_categoria,
            'codigo_patrimonial' => 'SRV-SIN-CPU',
            'procesador' => null,
            'ram_gb' => null,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, ActivoTecnologico::count());
    }

    // ── Evaluación manual de riesgo ─────────────────────────────────────

    public function test_evaluacion_manual_rechaza_un_modo_de_falla_de_otra_categoria(): void
    {
        $this->loginComoAdmin();
        $activo = $this->activo('PC-1', 'Laptops');
        $modoAjeno = $this->modoFalla($this->categoria('Servidores'), 'Sobrecalentamiento');

        $response = $this->post(route('admin.riesgos.evaluacion.store'), [
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modoAjeno->id_modo_falla,
            'valor_severidad' => 5, 'valor_frecuencia' => 5, 'valor_msr' => 5,
            'fecha_evaluacion' => now()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('fk_id_modo_falla');
        $this->assertSame(0, EvaluacionRiesgo::count());
        $this->assertSame(
            'El modo de falla seleccionado no pertenece a la categoría de este activo.',
            session('errors')->first('fk_id_modo_falla')
        );
    }

    public function test_evaluacion_manual_acepta_un_modo_de_la_categoria_del_activo(): void
    {
        $this->loginComoAdmin();
        $activo = $this->activo('PC-2', 'Laptops');
        $modo = $this->modoFalla($activo->categoria, 'Falla de batería');

        $this->post(route('admin.riesgos.evaluacion.store'), [
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 5, 'valor_frecuencia' => 5, 'valor_msr' => 5,
            'fecha_evaluacion' => now()->format('Y-m-d'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, EvaluacionRiesgo::count());
    }

    public function test_evaluacion_manual_rechaza_fecha_futura(): void
    {
        $this->loginComoAdmin();
        $activo = $this->activo('PC-3', 'Laptops');
        $modo = $this->modoFalla($activo->categoria, 'Falla de pantalla');

        $this->post(route('admin.riesgos.evaluacion.store'), [
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 5, 'valor_frecuencia' => 5, 'valor_msr' => 5,
            'fecha_evaluacion' => now()->addDays(2)->format('Y-m-d'),
        ])->assertSessionHasErrors('fecha_evaluacion');
    }

    // ── Incidentes ──────────────────────────────────────────────────────

    private function datosIncidente(ActivoTecnologico $activo, Usuario $usuario, array $extra = []): array
    {
        return array_merge([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $usuario->id_usuario,
            'fecha_incidente' => now()->format('Y-m-d\TH:i'),
            'descripcion_falla' => 'No enciende',
            'estado_incidente' => 'Reportado',
            'prioridad' => 'Media',
        ], $extra);
    }

    public function test_incidente_rechaza_fecha_anterior_a_la_adquisicion_del_activo(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->activo('PC-4', 'Laptops', now()->subMonths(2)->format('Y-m-d'));

        $this->post(route('admin.incidentes.registrar.store'), $this->datosIncidente($activo, $admin, [
            'fecha_incidente' => now()->subMonths(6)->format('Y-m-d\TH:i'),
        ]))->assertSessionHasErrors('fecha_incidente');

        $this->assertSame(0, Incidente::count());
    }

    public function test_incidente_rechaza_fecha_futura(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->activo('PC-5', 'Laptops');

        $this->post(route('admin.incidentes.registrar.store'), $this->datosIncidente($activo, $admin, [
            'fecha_incidente' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ]))->assertSessionHasErrors('fecha_incidente');
    }

    public function test_incidente_valido_se_registra_y_rechaza_estados_fuera_del_catalogo(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->activo('PC-6', 'Laptops');

        $this->post(route('admin.incidentes.registrar.store'), $this->datosIncidente($activo, $admin))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Incidente::count());

        $this->post(route('admin.incidentes.registrar.store'), $this->datosIncidente($activo, $admin, ['estado_incidente' => 'Inventado']))
            ->assertSessionHasErrors('estado_incidente');
        $this->assertSame(1, Incidente::count());
    }

    public function test_los_mensajes_de_validacion_salen_en_espanol(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->activo('PC-7', 'Laptops');

        $this->post(route('admin.incidentes.registrar.store'), $this->datosIncidente($activo, $admin, ['descripcion_falla' => '']))
            ->assertSessionHasErrors('descripcion_falla');

        $this->assertSame('El campo descripción de la falla es obligatorio.', session('errors')->first('descripcion_falla'));
    }

    public function test_los_errores_por_campo_se_entregan_a_la_pagina_y_no_abren_el_modal_de_perfil(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->activo('PC-12', 'Laptops');
        $formulario = route('admin.incidentes.registrar');

        $this->from($formulario)->post(
            route('admin.incidentes.registrar.store'),
            $this->datosIncidente($activo, $admin, ['descripcion_falla' => ''])
        )->assertRedirect($formulario);

        $pagina = $this->get($formulario)->assertOk();
        $pagina->assertSee('id="errores-validacion"', false);
        $pagina->assertSee('El campo descripción de la falla es obligatorio.');
        // Los errores se muestran solo junto a cada campo, sin un resumen general.
        $pagina->assertDontSee('Por favor corrige los siguientes errores');
        // El modal "Editar Mi Perfil" solo se abre con errores del propio perfil.
        $pagina->assertDontSee('window.openProfileEditModal();', false);
    }

    public function test_los_errores_del_perfil_van_en_su_propia_bolsa_y_abren_su_modal(): void
    {
        $this->loginComoAdmin();
        $inicio = route('dashboard');

        $this->from($inicio)->post(route('perfil.update'), ['nombre' => '', 'correo' => 'correo-invalido'])
            ->assertRedirect($inicio)
            ->assertSessionHasErrors(['nombre', 'correo'], null, 'perfil');

        $pagina = $this->get($inicio)->assertOk();
        $pagina->assertDontSee('id="errores-validacion"', false);
        $pagina->assertSee('window.openProfileEditModal();', false);
    }

    // ── Mantenimientos ──────────────────────────────────────────────────

    private function datosMantenimiento(ActivoTecnologico $activo, Usuario $usuario, array $extra = []): array
    {
        return array_merge([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_asignado' => $usuario->id_usuario,
            'tipo_mantenimiento' => 'Preventivo',
            'estado_mantenimiento' => 'Programado',
            'fecha_programada' => now()->addDays(5)->format('Y-m-d'),
        ], $extra);
    }

    public function test_mantenimiento_rechaza_ejecucion_anterior_a_la_fecha_programada(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->activo('PC-8', 'Laptops');

        $this->post(route('admin.mantenimiento.programar.store'), $this->datosMantenimiento($activo, $admin, [
            'fecha_programada' => now()->format('Y-m-d'),
            'fecha_ejecucion' => now()->subDays(3)->format('Y-m-d'),
        ]))->assertSessionHasErrors('fecha_ejecucion');

        $this->assertSame(0, Mantenimiento::count());
    }

    public function test_mantenimiento_rechaza_incidente_de_otro_activo(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->activo('PC-9', 'Laptops');
        $otro = $this->activo('PC-10', 'Laptops');
        $incidenteAjeno = Incidente::create([
            'fk_id_activo' => $otro->id_activo,
            'fk_id_usuario_reporta' => $admin->id_usuario,
            'fecha_incidente' => now(),
            'descripcion_falla' => 'Falla',
            'estado_incidente' => 'Reportado',
            'prioridad' => 'Media',
        ]);

        $this->post(route('admin.mantenimiento.programar.store'), $this->datosMantenimiento($activo, $admin, [
            'fk_id_incidente' => $incidenteAjeno->id_incidente,
        ]))->assertSessionHasErrors('fk_id_incidente');

        $this->assertSame(0, Mantenimiento::count());
    }

    public function test_mantenimiento_rechaza_el_estado_completado_que_no_pertenece_al_vocabulario(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->activo('PC-11', 'Laptops');

        $this->post(route('admin.mantenimiento.programar.store'), $this->datosMantenimiento($activo, $admin, [
            'estado_mantenimiento' => 'Completado',
        ]))->assertSessionHasErrors('estado_mantenimiento');
    }

    // ── Activos ─────────────────────────────────────────────────────────

    public function test_activo_con_ip_invalida_no_queda_registrado_a_medias(): void
    {
        $this->loginComoAdmin();
        $servidores = $this->categoria('Servidores');

        $this->post(route('admin.activos.registrar.store'), $this->datosActivo([
            'fk_id_categoria' => $servidores->id_categoria,
            'codigo_patrimonial' => 'SRV-1',
            'direccion_ip' => '192.168.1.999',
        ]))->assertSessionHasErrors('direccion_ip');

        // Antes se creaba el activo y luego fallaba el detalle; el reintento
        // chocaba con "código patrimonial ya existe".
        $this->assertSame(0, ActivoTecnologico::where('codigo_patrimonial', 'SRV-1')->count());
    }

    public function test_activo_rechaza_valores_negativos_en_la_ficha_tecnica(): void
    {
        $this->loginComoAdmin();

        $this->post(route('admin.activos.registrar.store'), $this->datosActivo([
            'codigo_patrimonial' => 'LAP-NEG',
            'ram_gb' => -8,
        ]))->assertSessionHasErrors('ram_gb');

        $this->assertSame(0, ActivoTecnologico::where('codigo_patrimonial', 'LAP-NEG')->count());
    }

    public function test_activo_rechaza_fecha_de_adquisicion_futura_y_estado_invalido(): void
    {
        $this->loginComoAdmin();

        $this->post(route('admin.activos.registrar.store'), $this->datosActivo([
            'fecha_adquisicion' => now()->addMonth()->format('Y-m-d'),
            'estado_operativo' => 'Roto',
        ]))->assertSessionHasErrors(['fecha_adquisicion', 'estado_operativo']);
    }

    public function test_activo_valido_guarda_su_ficha_tecnica(): void
    {
        $this->loginComoAdmin();
        $servidores = $this->categoria('Servidores');

        $this->post(route('admin.activos.registrar.store'), $this->datosActivo([
            'fk_id_categoria' => $servidores->id_categoria,
            'codigo_patrimonial' => 'SRV-OK',
            'direccion_ip' => '192.168.1.30',
            'version_firmware' => 'v2.4',
        ]))->assertSessionHasNoErrors();

        $activo = ActivoTecnologico::where('codigo_patrimonial', 'SRV-OK')->firstOrFail();
        $this->assertSame('192.168.1.30', $activo->detalleEquipoRed->direccion_ip);
    }

    public function test_el_aviso_de_exito_aparece_una_sola_vez(): void
    {
        $this->loginComoAdmin();

        $html = $this->followingRedirects()
            ->post(route('admin.activos.registrar.store'), $this->datosActivo(['codigo_patrimonial' => 'UNICO-1']))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Activo tecnológico creado con éxito.'));
    }

    public function test_avisa_cuando_los_datos_tecnicos_no_tienen_donde_guardarse(): void
    {
        $this->loginComoAdmin();
        $camaras = $this->categoria('Cámaras CCTV');

        $respuesta = $this->post(route('admin.activos.registrar.store'), $this->datosActivo([
            'fk_id_categoria' => $camaras->id_categoria,
            'codigo_patrimonial' => 'CAM-1',
            'direccion_ip' => '192.168.1.40',
        ]));

        $respuesta->assertSessionHasNoErrors();
        $this->assertStringContainsString('no se guardaron', session('success'));
        $this->assertSame(1, ActivoTecnologico::where('codigo_patrimonial', 'CAM-1')->count());
    }

    // ── Usuarios ────────────────────────────────────────────────────────

    public function test_un_administrador_no_puede_desactivar_su_propia_cuenta(): void
    {
        $admin = $this->loginComoAdmin();

        $this->delete(route('admin.usuarios.destroy', $admin->id_usuario))
            ->assertSessionHas('error', 'No puedes desactivar tu propia cuenta.');

        $this->assertTrue((bool) $admin->fresh()->estado);
    }

    public function test_la_edicion_de_usuario_muestra_los_errores_de_validacion(): void
    {
        $admin = $this->loginComoAdmin();
        $editar = route('admin.usuarios.edit', $admin->id_usuario);

        $this->from($editar)->put(route('admin.usuarios.update', $admin->id_usuario), [
            'nombre' => '',
            'correo' => $admin->correo,
            'fk_id_rol' => 1,
        ])->assertRedirect($editar);

        $this->get($editar)
            ->assertOk()
            ->assertDontSee('Por favor corrige los siguientes errores')
            ->assertSee('El campo nombre es obligatorio.');
    }

    public function test_un_administrador_si_puede_desactivar_a_otro_administrador(): void
    {
        $this->loginComoAdmin();
        $otro = Usuario::create([
            'fk_id_rol' => 1,
            'nombre' => 'Otro',
            'apellido' => 'Admin',
            'correo' => 'otro.admin@example.com',
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
        ]);

        $this->delete(route('admin.usuarios.destroy', $otro->id_usuario))->assertSessionHas('success');

        $this->assertFalse((bool) $otro->fresh()->estado);
    }
}
