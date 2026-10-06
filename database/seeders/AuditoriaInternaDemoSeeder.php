<?php

namespace Database\Seeders;

use App\Models\AuditoriaInterna;
use App\Models\HallazgoAuditoria;
use App\Models\Usuario;
use App\Services\AuditoriaInternaService;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración para el módulo de Auditoría Interna de TI.
 * NO se ejecuta automáticamente desde DatabaseSeeder (no se llama con
 * $this->call()) — solo a pedido explícito:
 *
 *   php artisan db:seed --class=AuditoriaInternaDemoSeeder
 */
class AuditoriaInternaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $servicio = app(AuditoriaInternaService::class);

        $auditor = Usuario::where('fk_id_rol', (int) config('roles.admin'))->first();
        if (!$auditor) {
            $this->command?->warn('No hay ningún usuario con rol Administrador; no se puede sembrar la auditoría demo.');

            return;
        }

        $gestion = (int) now()->year;

        $auditoria = AuditoriaInterna::create([
            'codigo' => $servicio->generarCodigoAuditoria($gestion),
            'gestion' => $gestion,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'fecha_corte' => now()->toDateString(),
            'objetivo' => 'Evaluar el grado de cumplimiento del sistema de gestión de activos tecnológicos frente a los criterios de auditoría aplicables durante la gestión.',
            'alcance' => 'Inventario, mantenimiento, incidentes, roles y accesos del sistema SIATEC del Lycée Franco-Bolivien.',
            'antecedentes' => 'Primera auditoría interna de TI formal registrada en el sistema.',
            'participantes' => $auditor->nombre . ' ' . ($auditor->apellido ?? '') . ' (Auditor)',
            'estado' => 'BORRADOR',
            'fk_id_auditor' => $auditor->id_usuario,
        ]);

        $servicio->generarMuestra($auditoria);

        HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id,
            'codigo' => $servicio->generarCodigoHallazgo($auditoria),
            'apartado' => '7.1',
            'requerimiento_ref' => 5,
            'condicion' => 'Se observaron activos cuya vida útil contable ya fue superada sin evidencia de plan de renovación.',
            'criterio' => 'ISO 55001:2014 7.6',
            'causa' => 'Ausencia de un proceso periódico de revisión de vida útil remanente.',
            'efecto' => 'Riesgo de continuidad operativa por uso de equipos obsoletos.',
            'evidencia' => 'Reporte RA-01 — Inventario General de Activos Tecnológicos.',
            'recomendacion' => 'Definir un cronograma anual de renovación basado en el reporte de vida útil remanente.',
            'clasificacion' => 'OBSERVACION',
            'responsable' => 'Encargado de TI',
            'fecha_compromiso' => now()->addDays((int) config('auditoria.plazos_dias.NC_MENOR'))->toDateString(),
        ]);

        $this->command?->info("Auditoría demo {$auditoria->codigo} creada con muestra de {$auditoria->muestra()->count()} activos y 1 hallazgo de ejemplo.");
    }
}
