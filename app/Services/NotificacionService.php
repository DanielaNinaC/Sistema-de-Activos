<?php

namespace App\Services;

use App\Models\Alerta;
use App\Models\Incidente;
use App\Models\Mantenimiento;
use App\Models\Usuario;
use App\Support\Catalogo;
use Carbon\Carbon;

class NotificacionService
{
    /**
     * Obtiene todas las notificaciones relevantes agrupadas y formateadas para el usuario autenticado.
     *
     * @param Usuario|null $usuario
     * @return array
     */
    public function obtenerParaUsuario(?Usuario $usuario): array
    {
        if (!$usuario) {
            return [
                'total' => 0,
                'items' => [],
                'conteoPorCategoria' => [
                    'riesgos' => 0,
                    'incidentes' => 0,
                    'mantenimientos' => 0,
                ]
            ];
        }

        $items = [];
        $conteoRiesgos = 0;
        $conteoIncidentes = 0;
        $conteoMantenimientos = 0;

        // 1. Alertas de Riesgo FMEA
        if ($usuario->tieneAccesoModulo('riesgos')) {
            try {
                $alertas = Alerta::with(['evaluacion.activo', 'evaluacion.modoFalla'])
                    ->orderByDesc('fecha_generacion')
                    ->limit(6)
                    ->get();

                $conteoRiesgos = $alertas->count();

                foreach ($alertas as $alerta) {
                    $equipo = $alerta->evaluacion->activo->nombre_equipo ?? 'Activo Tecnológico';
                    $codigo = $alerta->evaluacion->activo->codigo_patrimonial ?? '';
                    $tipo = ucfirst($alerta->tipo_alerta ?? 'Alerta');
                    $fecha = $alerta->fecha_generacion ? Carbon::parse($alerta->fecha_generacion) : ($alerta->created_at ?? now());
                    $score = $alerta->evaluacion->score_calculado ?? null;

                    // Colores según tipo de alerta
                    $badgeClass = 'bg-rose-100 text-rose-700 border-rose-200';
                    $iconColor = 'text-rose-600 bg-rose-50';
                    if (in_array(strtolower($tipo), ['amarilla', 'media'])) {
                        $badgeClass = 'bg-amber-100 text-amber-700 border-amber-200';
                        $iconColor = 'text-amber-600 bg-amber-50';
                    } elseif (in_array(strtolower($tipo), ['naranja', 'alta'])) {
                        $badgeClass = 'bg-orange-100 text-orange-700 border-orange-200';
                        $iconColor = 'text-orange-600 bg-orange-50';
                    }

                    $items[] = [
                        'id' => 'alerta-' . $alerta->id_alerta,
                        'categoria' => 'riesgos',
                        'tipo_label' => 'Alerta FMEA ' . $tipo,
                        'titulo' => $equipo . ($codigo ? " ({$codigo})" : ''),
                        'descripcion' => ($alerta->evaluacion->modoFalla->nombre_falla ?? 'Alerta detectada') . ($score ? " • Score: " . number_format($score, 2) : ''),
                        'fecha' => $fecha->diffForHumans(),
                        'fecha_raw' => $fecha->toIso8601String(),
                        'url' => route('admin.riesgos.alertas'),
                        'badge_class' => $badgeClass,
                        'icon_color' => $iconColor,
                        'icono' => 'riesgos',
                    ];
                }
            } catch (\Exception $e) {
                // Silencioso ante tablas vacías
            }
        }

        // 2. Incidentes Abiertos / En Proceso
        if ($usuario->tieneAccesoModulo('incidentes')) {
            try {
                $incidentes = Incidente::with(['activo', 'modoFalla'])
                    ->whereNotIn('estado_incidente', Catalogo::estadosIncidenteCerrados())
                    ->orderByDesc('fecha_incidente')
                    ->limit(6)
                    ->get();

                $conteoIncidentes = $incidentes->count();

                foreach ($incidentes as $inc) {
                    $equipo = $inc->activo->nombre_equipo ?? 'Activo Tecnológico';
                    $fecha = $inc->fecha_incidente ? Carbon::parse($inc->fecha_incidente) : ($inc->created_at ?? now());

                    $items[] = [
                        'id' => 'incidente-' . $inc->id_incidente,
                        'categoria' => 'incidentes',
                        'tipo_label' => 'Incidente ' . ($inc->estado_incidente ?? 'Reportado'),
                        'titulo' => $equipo,
                        'descripcion' => $inc->descripcion_falla ? \Illuminate\Support\Str::limit($inc->descripcion_falla, 60) : 'Falla reportada en el equipo',
                        'fecha' => $fecha->diffForHumans(),
                        'fecha_raw' => $fecha->toIso8601String(),
                        'url' => route('admin.incidentes.seguimiento'),
                        'badge_class' => 'bg-amber-100 text-amber-800 border-amber-200',
                        'icon_color' => 'text-amber-600 bg-amber-50',
                        'icono' => 'incidentes',
                    ];
                }
            } catch (\Exception $e) {
                // Silencioso
            }
        }

        // 3. Mantenimientos Pendientes / Programados
        if ($usuario->tieneAccesoModulo('mantenimiento')) {
            try {
                $mantenimientos = Mantenimiento::with('activo')
                    ->whereIn('estado_mantenimiento', Catalogo::estadosMantenimientoAbiertos())
                    ->orderBy('fecha_programada', 'asc')
                    ->limit(6)
                    ->get();

                $conteoMantenimientos = $mantenimientos->count();

                foreach ($mantenimientos as $mant) {
                    $equipo = $mant->activo->nombre_equipo ?? 'Activo Tecnológico';
                    $fechaProg = $mant->fecha_programada ? Carbon::parse($mant->fecha_programada) : null;
                    $fechaTexto = $fechaProg ? 'Programado: ' . $fechaProg->format('d/m/Y') : ($mant->created_at ? $mant->created_at->diffForHumans() : 'Pendiente');

                    $items[] = [
                        'id' => 'mantenimiento-' . $mant->id_mantenimiento,
                        'categoria' => 'mantenimiento',
                        'tipo_label' => 'Mantenimiento ' . ($mant->tipo_mantenimiento ?? 'Preventivo'),
                        'titulo' => $equipo,
                        'descripcion' => ($mant->descripcion_trabajo ? \Illuminate\Support\Str::limit($mant->descripcion_trabajo, 55) : 'Servicio técnico programado') . " • Estado: {$mant->estado_mantenimiento}",
                        'fecha' => $fechaTexto,
                        'fecha_raw' => $fechaProg ? $fechaProg->toIso8601String() : now()->toIso8601String(),
                        'url' => route('admin.mantenimiento.cronograma'),
                        'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'icon_color' => 'text-emerald-600 bg-emerald-50',
                        'icono' => 'mantenimiento',
                    ];
                }
            } catch (\Exception $e) {
                // Silencioso
            }
        }

        // Ordenar items cronológicamente (más recientes primero)
        usort($items, function ($a, $b) {
            return strcmp($b['fecha_raw'] ?? '', $a['fecha_raw'] ?? '');
        });

        return [
            'total' => count($items),
            'items' => $items,
            'conteoPorCategoria' => [
                'riesgos' => $conteoRiesgos,
                'incidentes' => $conteoIncidentes,
                'mantenimientos' => $conteoMantenimientos,
            ]
        ];
    }
}
