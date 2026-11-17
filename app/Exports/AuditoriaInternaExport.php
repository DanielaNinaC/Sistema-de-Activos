<?php

namespace App\Exports;

use App\Models\AuditoriaInterna;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Excel del Informe General de Auditoría Interna de TI: una hoja por sección
 * tabular. Cada hoja reutiliza GenericArrayExport (ya usado en todo el
 * módulo de Informes) en vez de reimplementar estilos — es la única clase
 * Export del sistema con más de una hoja, por eso no pasa por
 * ExportService::exportarExcel() (de una sola hoja).
 */
class AuditoriaInternaExport implements WithMultipleSheets
{
    public function __construct(
        private AuditoriaInterna $auditoria,
        private array $datos,
    ) {
    }

    public function sheets(): array
    {
        return [
            new GenericArrayExport($this->filasIndicadores(), ['Indicador', 'Valor', 'Meta', 'Cumple'], 'Indicadores'),
            new GenericArrayExport($this->filasHallazgos(), ['Código', 'Apartado', 'Clasificación', 'Condición', 'Responsable', 'Fecha Compromiso', 'Estado'], 'Hallazgos'),
            new GenericArrayExport($this->filasMuestra(), ['Activo', 'Nivel al Muestrear', 'Motivo', 'Encontrado', 'Ubicación OK', 'Estado OK'], 'Muestra'),
            new GenericArrayExport($this->filasPlanAccion(), ['Código', 'Responsable', 'Fecha Compromiso', 'Estado'], 'Plan de Acción'),
        ];
    }

    private function filasIndicadores(): array
    {
        $filas = [];
        foreach ($this->datos['indicadores'] as $clave => $ind) {
            if ($ind['informativo'] ?? false) {
                continue;
            }
            $filas[] = [
                $clave,
                $ind['valor'] ?? 'N/A',
                ($ind['meta']['op'] ?? '') . ' ' . ($ind['meta']['valor'] ?? ''),
                $ind['cumple'] === null ? 'N/A' : ($ind['cumple'] ? 'Sí' : 'No'),
            ];
        }

        return $filas;
    }

    private function filasHallazgos(): array
    {
        return $this->auditoria->hallazgos->sortBy(fn ($h) => $h->clasificacion !== 'NC_MAYOR')->map(fn ($h) => [
            $h->codigo, $h->apartado, $h->clasificacion, $h->condicion, $h->responsable,
            $h->fecha_compromiso?->format('d/m/Y') ?? '-', $h->estado,
        ])->values()->toArray();
    }

    private function filasMuestra(): array
    {
        return $this->auditoria->muestra->map(fn ($m) => [
            $m->activo->nombre_equipo ?? 'Activo eliminado',
            $m->nivel_riesgo_al_muestrear,
            $m->motivo_inclusion,
            $m->encontrado === null ? 'Sin verificar' : ($m->encontrado ? 'Sí' : 'No'),
            $m->ubicacion_coincide === null ? 'Sin verificar' : ($m->ubicacion_coincide ? 'Sí' : 'No'),
            $m->estado_coincide === null ? 'Sin verificar' : ($m->estado_coincide ? 'Sí' : 'No'),
        ])->toArray();
    }

    private function filasPlanAccion(): array
    {
        return $this->auditoria->hallazgos->where('estado', '!=', 'CERRADO')->map(fn ($h) => [
            $h->codigo, $h->responsable, $h->fecha_compromiso?->format('d/m/Y') ?? '-', $h->estado,
        ])->values()->toArray();
    }
}
