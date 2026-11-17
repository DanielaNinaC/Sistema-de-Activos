<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · CONTRAER (4/4). Los dos catálogos cuyo nombre no seguía el estándar
 * (plural y en español, nombrando la entidad) se renombran al final, cuando el
 * código de la Fase 4 ya apunta a los nombres nuevos.
 *
 *   estados_operativos   → estados_activo        (el activo tiene un *estado*)
 *   origen_mantenimiento → origenes_mantenimiento (plural)
 *
 * Schema::rename conserva datos, índices y FK (MySQL y SQLite).
 */
return new class extends Migration
{
    private const RENOMBRES = [
        'estados_operativos' => 'estados_activo',
        'origen_mantenimiento' => 'origenes_mantenimiento',
    ];

    public function up(): void
    {
        foreach (self::RENOMBRES as $antes => $despues) {
            if (N::tiene($antes) && !Schema::hasTable($despues)) {
                Schema::rename($antes, $despues);
            }
        }
    }

    public function down(): void
    {
        foreach (self::RENOMBRES as $antes => $despues) {
            if (N::tiene($despues) && !Schema::hasTable($antes)) {
                Schema::rename($despues, $antes);
            }
        }
    }
};
