<?php

// Parámetros del módulo de Auditoría Interna de TI (reportes RA-01..RA-07 e
// Informe General). Única fuente de verdad: nada de esto se repite hardcodeado
// en controladores ni en vistas. Sigue el mismo patrón plano de config/roles.php.
return [

    // Prefijo del código autogenerado del Informe General: INF-AUD-TI-AAAA-NN.
    'codigo_prefijo' => env('AUDITORIA_CODIGO_PREFIJO', 'INF-AUD-TI'),

    // Tamaño de la muestra de verificación física del inventario.
    'muestra' => [
        'porcentaje' => (int) env('AUDITORIA_MUESTRA_PORCENTAJE', 10),
        'minimo' => (int) env('AUDITORIA_MUESTRA_MINIMO', 30),
        'incluir_siempre_niveles' => ['Crítico', 'Alto'],
    ],

    // IDs de categorías consideradas "de red" para el reporte RA-07. Vacío por
    // defecto: se configura según el catálogo real de categorías de la
    // institución antes de usar ese reporte.
    'categorias_red' => [],

    // Umbrales (% de indicadores calculables dentro de meta) para el semáforo
    // por apartado del Informe General.
    'semaforo' => [
        'verde' => 85,
        'amarillo' => 60,
    ],

    // Metas propuestas por indicador. Editables sin tocar código.
    'metas' => [
        'exactitud_inventario' => ['op' => '>=', 'valor' => 95],
        'activos_sin_ubicacion_pct' => ['op' => '<=', 'valor' => 2],
        'vida_util_vencida_pct' => ['op' => '<=', 'valor' => 15],
        'cumplimiento_mantenimiento' => ['op' => '>=', 'valor' => 90],
        'preventivo_pct' => ['op' => '>=', 'valor' => 70],
        'tmr_incidentes_dias' => ['op' => '<=', 'valor' => 5],
        'incidentes_abiertos_pct' => ['op' => '<=', 'valor' => 5],
        'admins_sistema' => ['op' => '<=', 'valor' => 3],
        'hallazgos_anteriores_cerrados' => ['op' => '>=', 'valor' => 80],
    ],

    // Plazos de compromiso sugeridos según la clasificación del hallazgo.
    'plazos_dias' => [
        'NC_MAYOR' => 30,
        'NC_MENOR' => 90,
    ],

    // Catálogo de criterios de referencia por apartado (7.1..7.6), ofrecido
    // como lista desplegable en la ficha de hallazgo; también acepta texto libre.
    'criterios' => [
        '7.1' => ['ISO/IEC 27001:2022 A.5.9', 'ISO 55001:2014 7.6'],
        '7.2' => ['ISO/IEC 27001:2022 A.7.13', 'ISO 55001:2014 8.1'],
        '7.3' => ['ISO/IEC 27001:2022 A.7.13', 'ISO 55001:2014 8.1'],
        '7.4' => ['ISO/IEC 27001:2022 A.5.24–A.5.28', 'ISO 55001:2014 10.1'],
        '7.5' => ['ISO/IEC 27001:2022 A.5.15–A.5.18', 'NAG 275'],
        '7.6' => ['ISO 19011:2018 §6.5', 'NAG 275', 'ISACA ITAF 2401'],
    ],

];
