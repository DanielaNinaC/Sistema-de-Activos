<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lleva a la base de datos los parámetros que vivían como constantes y listas
 * literales en el código: vocabularios de estados/tipos/prioridades y las
 * tablas de la tesis que parametrizan el motor FMEA (Tablas 8, 20, 21, 22 y 29).
 *
 * Cada catálogo usa su valor textual como llave primaria (clave natural). Las
 * columnas que hoy guardan ese texto (p. ej. incidentes.estado_incidente)
 * pasan a apuntar a él con una llave foránea en la migración siguiente, de
 * modo que los datos y las consultas existentes siguen siendo idénticos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Motor FMEA ──────────────────────────────────────────────────

        Schema::create('niveles_dependencia', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->unsignedTinyInteger('valor');
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('niveles_severidad', function (Blueprint $table) {
            $table->string('clave', 50)->primary();
            $table->string('etiqueta', 100);
            $table->unsignedTinyInteger('valor');
            $table->text('descripcion');
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('niveles_msr', function (Blueprint $table) {
            $table->string('clave', 50)->primary();
            $table->string('etiqueta', 100);
            $table->unsignedTinyInteger('valor');
            $table->text('descripcion');
            $table->unsignedTinyInteger('orden');
        });

        // Reglas de frecuencia evaluadas en orden; la última (sin operador) es el valor por defecto.
        Schema::create('niveles_frecuencia', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->unsignedTinyInteger('valor');
            $table->decimal('desgaste_minimo', 5, 2)->nullable();
            $table->unsignedTinyInteger('incidentes_minimo')->nullable();
            $table->string('operador', 3)->nullable();
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('tipos_alerta', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->string('color_hex', 7);
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('prioridades_incidente', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->string('descripcion')->nullable();
            $table->unsignedTinyInteger('peso');
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('niveles_riesgo', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->string('criticidad', 100);
            $table->decimal('puntaje_minimo', 4, 2);
            $table->string('tipo_alerta', 100);
            $table->string('prioridad_sugerida', 100);
            // Alto/Crítico: se les asigna un plan de tratamiento y cuentan como riesgos graves en tableros e informes.
            $table->boolean('requiere_tratamiento')->default(false);
            // Solo Crítico: dispara correo/aviso inmediato a Administración y Soporte.
            $table->boolean('notifica_alerta')->default(false);
            $table->unsignedTinyInteger('orden');

            $table->foreign('tipo_alerta')->references('nombre')->on('tipos_alerta')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('prioridad_sugerida')->references('nombre')->on('prioridades_incidente')->cascadeOnUpdate()->restrictOnDelete();
        });

        // ── Tratamiento de riesgo ───────────────────────────────────────

        Schema::create('estrategias_tratamiento', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->boolean('genera_mantenimiento')->default(false);
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('estados_tratamiento', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->boolean('es_abierto')->default(false);
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('origenes_tratamiento', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('tratamientos_sugeridos', function (Blueprint $table) {
            $table->string('nivel_riesgo', 100)->primary();
            $table->string('estrategia', 100);
            $table->text('accion_preventiva');
            $table->unsignedSmallInteger('dias_plazo');
            $table->unsignedTinyInteger('orden');

            $table->foreign('nivel_riesgo')->references('nombre')->on('niveles_riesgo')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('estrategia')->references('nombre')->on('estrategias_tratamiento')->cascadeOnUpdate()->restrictOnDelete();
        });

        // ── Activos, incidentes y mantenimiento ─────────────────────────

        Schema::create('estados_operativos', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('etapas_ciclo_vida', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('motivos_baja', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('estados_incidente', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->string('descripcion')->nullable();
            $table->boolean('cierra_incidente')->default(false);
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('tipos_mantenimiento', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->string('descripcion')->nullable();
            $table->unsignedTinyInteger('orden');
        });

        Schema::create('estados_mantenimiento', function (Blueprint $table) {
            $table->string('nombre', 100)->primary();
            $table->boolean('es_abierto')->default(false);
            $table->boolean('es_finalizado')->default(false);
            $table->unsignedTinyInteger('orden');
        });

        $this->sembrar();
    }

    private function sembrar(): void
    {
        $filas = fn (array $nombres, array $extra = []) => collect($nombres)->values()->map(
            fn ($nombre, $i) => array_merge(['nombre' => $nombre], $extra[$i] ?? [], ['orden' => $i + 1])
        )->all();

        // Tabla 8 — Dependencia institucional
        DB::table('niveles_dependencia')->insert($filas(['Alta', 'Media', 'Baja'], [
            ['valor' => 9], ['valor' => 5], ['valor' => 2],
        ]));

        // Tabla 20 — Severidad
        DB::table('niveles_severidad')->insert([
            ['clave' => 'muy_alta', 'etiqueta' => 'Muy Alta', 'valor' => 10, 'orden' => 1, 'descripcion' => 'Pérdida total de datos académicos críticos / incumplimiento legal grave / interrupción total de actividades >24h'],
            ['clave' => 'alta', 'etiqueta' => 'Alta', 'valor' => 8, 'orden' => 2, 'descripcion' => 'Degradación severa de servicios / afectación a integridad de información de auditoría'],
            ['clave' => 'moderada', 'etiqueta' => 'Moderada', 'valor' => 5, 'orden' => 3, 'descripcion' => 'Activo funciona con limitaciones / impacto en productividad administrativa / interrupción parcial de una clase'],
            ['clave' => 'baja', 'etiqueta' => 'Baja', 'valor' => 3, 'orden' => 4, 'descripcion' => 'Inconveniente menor, proceso alterno manual'],
            ['clave' => 'insignificante', 'etiqueta' => 'Insignificante', 'valor' => 1, 'orden' => 5, 'descripcion' => 'No existe impacto perceptible'],
        ]);

        // Tabla 22 — Monitoreo / MSR
        DB::table('niveles_msr')->insert([
            ['clave' => 'muy_baja', 'etiqueta' => 'Muy Baja', 'valor' => 9, 'orden' => 1, 'descripcion' => 'No existen alertas, se detecta solo cuando el servicio colapsa totalmente'],
            ['clave' => 'baja', 'etiqueta' => 'Baja', 'valor' => 7, 'orden' => 2, 'descripcion' => 'Detección solo por inspección o reporte manual directo (rondas de soporte, reporte del usuario), sin logs ni alertas'],
            ['clave' => 'moderada', 'etiqueta' => 'Moderada', 'valor' => 6, 'orden' => 3, 'descripcion' => 'Registra logs pero requiere revisión manual'],
            ['clave' => 'alta', 'etiqueta' => 'Alta', 'valor' => 4, 'orden' => 4, 'descripcion' => 'Alertas automáticas antes del colapso'],
            ['clave' => 'muy_alta', 'etiqueta' => 'Muy Alta', 'valor' => 2, 'orden' => 5, 'descripcion' => 'Mitigación autónoma o aviso instantáneo'],
        ]);

        // Tabla 21 — Frecuencia (desgaste % e incidentes)
        DB::table('niveles_frecuencia')->insert([
            ['nombre' => 'Muy Alta', 'valor' => 9, 'desgaste_minimo' => 80, 'incidentes_minimo' => 3, 'operador' => 'AND', 'orden' => 1],
            ['nombre' => 'Moderada', 'valor' => 6, 'desgaste_minimo' => 40, 'incidentes_minimo' => 1, 'operador' => 'OR', 'orden' => 2],
            ['nombre' => 'Baja', 'valor' => 2, 'desgaste_minimo' => null, 'incidentes_minimo' => null, 'operador' => null, 'orden' => 3],
        ]);

        // Semáforo de alertas
        DB::table('tipos_alerta')->insert($filas(['Verde', 'Amarilla', 'Naranja', 'Roja'], [
            ['color_hex' => '#22c55e'], ['color_hex' => '#eab308'], ['color_hex' => '#f97316'], ['color_hex' => '#ef4444'],
        ]));

        DB::table('prioridades_incidente')->insert($filas(['Alta', 'Media', 'Baja'], [
            ['peso' => 1, 'descripcion' => 'Urgente / Interrupción crítica'],
            ['peso' => 2, 'descripcion' => 'Impacto funcional moderado'],
            ['peso' => 3, 'descripcion' => 'Inconveniente menor'],
        ]));

        // Tabla 29 — Niveles de riesgo según el score
        DB::table('niveles_riesgo')->insert([
            ['nombre' => 'Bajo', 'criticidad' => 'Baja', 'puntaje_minimo' => 0, 'tipo_alerta' => 'Verde', 'prioridad_sugerida' => 'Baja', 'requiere_tratamiento' => false, 'notifica_alerta' => false, 'orden' => 1],
            ['nombre' => 'Medio', 'criticidad' => 'Media', 'puntaje_minimo' => 3, 'tipo_alerta' => 'Amarilla', 'prioridad_sugerida' => 'Media', 'requiere_tratamiento' => false, 'notifica_alerta' => false, 'orden' => 2],
            ['nombre' => 'Alto', 'criticidad' => 'Alta', 'puntaje_minimo' => 6, 'tipo_alerta' => 'Naranja', 'prioridad_sugerida' => 'Alta', 'requiere_tratamiento' => true, 'notifica_alerta' => false, 'orden' => 3],
            ['nombre' => 'Crítico', 'criticidad' => 'Crítica', 'puntaje_minimo' => 8, 'tipo_alerta' => 'Roja', 'prioridad_sugerida' => 'Alta', 'requiere_tratamiento' => true, 'notifica_alerta' => true, 'orden' => 4],
        ]);

        DB::table('estrategias_tratamiento')->insert($filas(['Mitigar', 'Transferir', 'Evitar', 'Aceptar'], [
            ['genera_mantenimiento' => true], ['genera_mantenimiento' => false], ['genera_mantenimiento' => false], ['genera_mantenimiento' => false],
        ]));

        DB::table('estados_tratamiento')->insert($filas(['Pendiente', 'En Proceso', 'Completado'], [
            ['es_abierto' => true], ['es_abierto' => true], ['es_abierto' => false],
        ]));

        DB::table('origenes_tratamiento')->insert($filas(['automatico', 'manual']));

        // Columna "Acción del Sistema" de la Tabla 29: solo Alto/Crítico generan borrador.
        DB::table('tratamientos_sugeridos')->insert([
            ['nivel_riesgo' => 'Crítico', 'estrategia' => 'Mitigar', 'accion_preventiva' => 'Priorizar atención inmediata; evaluar retiro temporal o intervención urgente del activo.', 'dias_plazo' => 3, 'orden' => 1],
            ['nivel_riesgo' => 'Alto', 'estrategia' => 'Mitigar', 'accion_preventiva' => 'Notificar al administrador, priorizar el mantenimiento e incluir el activo en el seguimiento de riesgos.', 'dias_plazo' => 15, 'orden' => 2],
        ]);

        DB::table('estados_operativos')->insert($filas(['Operativo', 'En Mantenimiento', 'Inoperativo', 'De Baja']));
        DB::table('etapas_ciclo_vida')->insert($filas(['Operación', 'Mantenimiento', 'Renovación / Retiro']));
        DB::table('motivos_baja')->insert($filas([
            'Obsolescencia', 'Daño irreparable', 'Robo o extravío', 'Reemplazo tecnológico', 'Fin de vida útil', 'Otro',
        ]));

        DB::table('estados_incidente')->insert($filas(['Reportado', 'En proceso', 'En espera', 'Resuelto', 'Cerrado'], [
            ['cierra_incidente' => false, 'descripcion' => 'Nuevo incidente registrado'],
            ['cierra_incidente' => false, 'descripcion' => 'En revisión o diagnóstico'],
            ['cierra_incidente' => false, 'descripcion' => 'Pendiente de repuestos o soporte externo'],
            ['cierra_incidente' => true, 'descripcion' => 'Pasa al historial'],
            ['cierra_incidente' => true, 'descripcion' => 'Pasa al historial'],
        ]));

        DB::table('tipos_mantenimiento')->insert($filas(['Preventivo', 'Correctivo', 'Programado', 'Inspección'], [
            ['descripcion' => 'Programado / Rutina'],
            ['descripcion' => 'Reparación / Falla'],
            ['descripcion' => null],
            ['descripcion' => 'Evaluación técnica'],
        ]));

        DB::table('estados_mantenimiento')->insert($filas(['Programado', 'Pendiente', 'En ejecución', 'Finalizado', 'Cancelado'], [
            ['es_abierto' => true, 'es_finalizado' => false],
            ['es_abierto' => true, 'es_finalizado' => false],
            ['es_abierto' => true, 'es_finalizado' => false],
            ['es_abierto' => false, 'es_finalizado' => true],
            ['es_abierto' => false, 'es_finalizado' => false],
        ]));
    }

    public function down(): void
    {
        foreach ([
            'tratamientos_sugeridos', 'niveles_riesgo', 'estados_mantenimiento', 'tipos_mantenimiento',
            'estados_incidente', 'motivos_baja', 'etapas_ciclo_vida', 'estados_operativos',
            'origenes_tratamiento', 'estados_tratamiento', 'estrategias_tratamiento',
            'prioridades_incidente', 'tipos_alerta', 'niveles_frecuencia', 'niveles_msr',
            'niveles_severidad', 'niveles_dependencia',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
