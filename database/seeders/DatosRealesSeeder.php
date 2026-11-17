<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos reales del inventario institucional (reemplaza a DatosPruebaSeeder).
 * Se construye de forma incremental, tabla por tabla, en el mismo orden de
 * dependencias de FK: categorias -> ubicaciones -> activos_tecnologicos ->
 * detalles_* -> pesos_ahp -> modos_falla -> evaluacion_riesgo -> alertas ->
 * incidentes -> plan_tratamiento_riesgo -> mantenimientos -> logs.
 *
 * Usa updateOrCreate/insertOrIgnore para poder re-ejecutarse sin duplicar
 * datos ya cargados: php artisan db:seed --class=DatosRealesSeeder
 */
class DatosRealesSeeder extends Seeder
{
    use \Database\Seeders\Concerns\SincronizaNormalizacion;

    public function run(): void
    {
        $this->categorias();
        $this->ubicaciones();
        $this->pesosAhp();
        $this->activosComputadoras();
        $this->activosLaptops();
        $this->activosTablets();
        $this->modosFallaComputadoras();
    }

    private function categorias(): void
    {
        // Vida útil y justificación según tabla de referencia institucional
        // provista por el usuario (función institucional, criticidad, vida
        // útil estimada y su justificación documental).
        $categorias = [
            [
                'nombre_categoria' => 'Servidores',
                'vida_util_meses' => 60, // 5 años
                'nivel_dependencia' => 'Alta',
                'justificacion_dependencia' => 'Equipo crítico cuya renovación suele establecerse alrededor de 5 años, aunque puede extenderse con mantenimiento y soporte.',
            ],
            [
                'nombre_categoria' => 'Router',
                'vida_util_meses' => 60, // 5 años
                'nivel_dependencia' => 'Alta',
                'justificacion_dependencia' => 'Los routers empresariales suelen presentar ciclos de renovación de aproximadamente 5–7 años, condicionados por soporte y evolución tecnológica.',
            ],
            [
                'nombre_categoria' => 'Switch',
                'vida_util_meses' => 60, // 5 años
                'nivel_dependencia' => 'Alta',
                'justificacion_dependencia' => 'Los equipos de red suelen manejarse con ciclos de 5–7 años; para un entorno educativo se puede establecer 5 años como referencia de renovación.',
            ],
            [
                'nombre_categoria' => 'Puntos de Acceso (AP)',
                'vida_util_meses' => 60, // 5 años
                'nivel_dependencia' => 'Alta',
                'justificacion_dependencia' => 'Los AP tienen ciclos de renovación vinculados principalmente a la evolución de estándares Wi-Fi, capacidad y soporte del fabricante.',
            ],
            [
                'nombre_categoria' => 'Computadoras de escritorio',
                'vida_util_meses' => 60, // 5 años
                'nivel_dependencia' => 'Alta',
                'justificacion_dependencia' => 'Diversas instituciones utilizan 4–5 años como ciclo de renovación; otras establecen 3 años por obsolescencia tecnológica. Para tu sistema, 5 años es defendible como vida útil institucional.',
            ],
            [
                'nombre_categoria' => 'Laptops',
                'vida_util_meses' => 48, // 4 años
                'nivel_dependencia' => 'Alta',
                'justificacion_dependencia' => 'La movilidad genera mayor desgaste y los ciclos tecnológicos son más cortos. Samsung, por ejemplo, informa 4 años de duración promedio para laptops; Utah establece 3–4 años.',
            ],
            [
                'nombre_categoria' => 'Tablets',
                'vida_util_meses' => 48, // 4 años
                'nivel_dependencia' => 'Media',
                'justificacion_dependencia' => 'Se recomienda una vida útil de 3–4 años para tablets por desgaste, batería y evolución tecnológica.',
            ],
            [
                'nombre_categoria' => 'Impresoras',
                'vida_util_meses' => 72, // 6 años
                'nivel_dependencia' => 'Media',
                'justificacion_dependencia' => 'Santa Fe Community College establece 6 años para impresoras; otros inventarios tecnológicos sitúan impresoras y periféricos alrededor de 5 años.',
            ],
            [
                'nombre_categoria' => 'Proyectores',
                'vida_util_meses' => 72, // 6 años
                'nivel_dependencia' => 'Media',
                'justificacion_dependencia' => 'Se encuentra respaldado por políticas institucionales que establecen 6 años para proyectores; los equipos audiovisuales pueden alcanzar entre 5 y 8 años.',
            ],
            [
                'nombre_categoria' => 'Cámaras CCTV',
                'vida_util_meses' => 60, // 5 años
                'nivel_dependencia' => 'Alta',
                // Justificación no fue proporcionada por el usuario para esta categoría.
                'justificacion_dependencia' => null,
            ],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categorias')->updateOrInsert(
                ['nombre_categoria' => $categoria['nombre_categoria']],
                $categoria
            );
        }
    }

    private function ubicaciones(): void
    {
        // Primer llenado real de ubicaciones (sección, aula). La clave de
        // idempotencia es el par (seccion, aula): hay aulas con el mismo
        // nombre en secciones distintas (ej. "10" en BCD/Primaria/Secundaria)
        // y son ubicaciones distintas.
        $ubicaciones = [
            // Maternal
            ['seccion' => 'Maternal', 'aula' => 'P2'],
            ['seccion' => 'Maternal', 'aula' => 'P3'],
            ['seccion' => 'Maternal', 'aula' => 'P4'],
            ['seccion' => 'Maternal', 'aula' => 'P5'],
            ['seccion' => 'Maternal', 'aula' => 'P6'],
            ['seccion' => 'Maternal', 'aula' => 'P7'],
            ['seccion' => 'Maternal', 'aula' => 'P9'],
            ['seccion' => 'Maternal', 'aula' => 'P12'],
            ['seccion' => 'Maternal', 'aula' => 'Sala de Profesores Maternal'],
            ['seccion' => 'Maternal', 'aula' => 'Sala de Artes - Maternal'],

            // BCD
            ['seccion' => 'BCD', 'aula' => '10'],

            // Primaria
            ['seccion' => 'Primaria', 'aula' => '10'],
            ['seccion' => 'Primaria', 'aula' => '19'],
            ['seccion' => 'Primaria', 'aula' => '20'],
            ['seccion' => 'Primaria', 'aula' => '21'],
            ['seccion' => 'Primaria', 'aula' => '22'],
            ['seccion' => 'Primaria', 'aula' => '23'],
            ['seccion' => 'Primaria', 'aula' => '24'],
            ['seccion' => 'Primaria', 'aula' => '25'],
            ['seccion' => 'Primaria', 'aula' => '26'],
            ['seccion' => 'Primaria', 'aula' => '27'],
            ['seccion' => 'Primaria', 'aula' => '28'],
            ['seccion' => 'Primaria', 'aula' => '29'],
            ['seccion' => 'Primaria', 'aula' => '30'],
            ['seccion' => 'Primaria', 'aula' => '31'],
            ['seccion' => 'Primaria', 'aula' => '32'],
            ['seccion' => 'Primaria', 'aula' => '33'],
            ['seccion' => 'Primaria', 'aula' => 'S1'],
            ['seccion' => 'Primaria', 'aula' => 'S2'],
            ['seccion' => 'Primaria', 'aula' => 'S3'],
            ['seccion' => 'Primaria', 'aula' => 'S4'],
            ['seccion' => 'Primaria', 'aula' => 'S5'],
            ['seccion' => 'Primaria', 'aula' => 'P1 Intermedio'],
            ['seccion' => 'Primaria', 'aula' => 'Bloques de Primaria'],
            ['seccion' => 'Primaria', 'aula' => 'Bloques Mixto'],
            ['seccion' => 'Primaria', 'aula' => 'Profesores Primaria - Tablets'],

            // Secundaria
            ['seccion' => 'Secundaria', 'aula' => '7'],
            ['seccion' => 'Secundaria', 'aula' => '8'],
            ['seccion' => 'Secundaria', 'aula' => '9'],
            ['seccion' => 'Secundaria', 'aula' => '10'],
            ['seccion' => 'Secundaria', 'aula' => '11'],
            ['seccion' => 'Secundaria', 'aula' => '12'],
            ['seccion' => 'Secundaria', 'aula' => '13'],
            ['seccion' => 'Secundaria', 'aula' => '14'],
            ['seccion' => 'Secundaria', 'aula' => '15'],
            ['seccion' => 'Secundaria', 'aula' => '16'],
            ['seccion' => 'Secundaria', 'aula' => '17'],
            ['seccion' => 'Secundaria', 'aula' => '18'],
            ['seccion' => 'Secundaria', 'aula' => '20'],
            ['seccion' => 'Secundaria', 'aula' => '21'],
            ['seccion' => 'Secundaria', 'aula' => '23'],
            ['seccion' => 'Secundaria', 'aula' => '26'],
            ['seccion' => 'Secundaria', 'aula' => '27'],
            ['seccion' => 'Secundaria', 'aula' => 'EPS'],
            ['seccion' => 'Secundaria', 'aula' => 'Artes Plásticas'],
            ['seccion' => 'Secundaria', 'aula' => 'Música'],
            ['seccion' => 'Secundaria', 'aula' => 'A01'],
            ['seccion' => 'Secundaria', 'aula' => 'A02'],
            ['seccion' => 'Secundaria', 'aula' => 'A03'],
            ['seccion' => 'Secundaria', 'aula' => 'B01'],
            ['seccion' => 'Secundaria', 'aula' => 'B02'],
            ['seccion' => 'Secundaria', 'aula' => 'B03'],
            ['seccion' => 'Secundaria', 'aula' => 'B04'],
            ['seccion' => 'Secundaria', 'aula' => 'B05'],
            ['seccion' => 'Secundaria', 'aula' => 'Lab. Ciencias'],
            ['seccion' => 'Secundaria', 'aula' => 'Laptops Bloque Secundaria Matemáticas'],
            ['seccion' => 'Secundaria', 'aula' => 'Laptops Bloque Secundaria Planta Baja'],
            ['seccion' => 'Secundaria', 'aula' => 'Laptops Bloque Secundaria Planta Alta'],
            ['seccion' => 'Secundaria', 'aula' => 'Laptops Bloque Secundaria EXAO'],
            ['seccion' => 'Secundaria', 'aula' => 'Laptops Bloque Secundaria CDI'],
            ['seccion' => 'Secundaria', 'aula' => 'Laboratorio Secundaria S-19'],
            ['seccion' => 'Secundaria', 'aula' => 'Laboratorio EXAO'],
            ['seccion' => 'Secundaria', 'aula' => 'CDI'],
            ['seccion' => 'Secundaria', 'aula' => 'Wiñai'],

            // Artes Plásticas
            ['seccion' => 'Artes Plásticas', 'aula' => 'Laboratorio MAC'],

            // Enfermería
            ['seccion' => 'Enfermería', 'aula' => 'Enfermería'],

            // Personal
            ['seccion' => 'Personal', 'aula' => 'Sala de Profesores'],

            // Vida Escolar
            ['seccion' => 'Vida Escolar', 'aula' => 'Vida Escolar'],

            // Dirección
            ['seccion' => 'Dirección', 'aula' => 'DIR-SEC'],
            ['seccion' => 'Dirección', 'aula' => 'Dirección'],
            ['seccion' => 'Dirección', 'aula' => 'Dirección Adjunta'],
            ['seccion' => 'Dirección', 'aula' => 'Dirección Primaria'],
            ['seccion' => 'Dirección', 'aula' => 'Subdirección Primaria'],
            ['seccion' => 'Dirección', 'aula' => 'Subdirección'],
            ['seccion' => 'Dirección', 'aula' => 'Subdirección Adjunta'],
            ['seccion' => 'Dirección', 'aula' => 'P. Fam. 1'],
            ['seccion' => 'Dirección', 'aula' => 'P. Fam. 2'],

            // Comunicación
            ['seccion' => 'Comunicación', 'aula' => 'Comunicación'],

            // Administración
            ['seccion' => 'Administración', 'aula' => 'Caja'],
            ['seccion' => 'Administración', 'aula' => 'DAF'],

            // Nuevas secciones detectadas al procesar el inventario real de
            // computadoras (COMPUTADORAS.xlsx) que no estaban en la lista
            // original de ubicaciones.
            ['seccion' => 'Góndola', 'aula' => 'Vero'],
            ['seccion' => 'Informática', 'aula' => 'Sarah'],
            ['seccion' => 'Seguridad', 'aula' => null],

            // Detectadas al procesar el lote de laptops del mismo Excel.
            ['seccion' => 'Psicóloga', 'aula' => null],
            ['seccion' => 'Comunicación', 'aula' => 'Sala de Reuniones'],
            ['seccion' => 'Comunicación', 'aula' => 'Teatro'],
        ];

        foreach ($ubicaciones as $ubicacion) {
            DB::table('ubicaciones')->updateOrInsert(
                ['seccion' => $ubicacion['seccion'], 'aula' => $ubicacion['aula']],
                $ubicacion
            );
        }
    }

    /**
     * Configuración de pesos AHP (Nivel 1: Severidad/Frecuencia/Monitoreo)
     * a partir de la matriz de comparación pareada provista por el usuario.
     * Se verificó que cada columna suma 1.000 y que Wi es el promedio de
     * cada fila (método del vector propio aproximado):
     *   Severidad: (0.652+0.667+0.625)/3 = 0.648
     *   Frecuencia: (0.218+0.222+0.250)/3 = 0.230
     *   Monitoreo (MSR): (0.130+0.111+0.125)/3 = 0.122
     *
     * peso_riesgo_tecnico/peso_dependencia (ponderación jerárquica de Nivel
     * 2, Subscore técnico vs. Dependencia institucional) no fueron provistos
     * por el usuario; se usan los valores por defecto ya establecidos en el
     * sistema (ver migración add_pesos_jerarquicos_to_pesos_ahp_table y
     * MotorFmeaService::calcularScoreJerarquico).
     *
     * MotorFmeaService toma siempre la única fila con vigente=true, así que
     * este método desactiva cualquier configuración previa antes de marcar
     * esta como vigente.
     */
    private function pesosAhp(): void
    {
        DB::table('pesos_ahp')->update(['vigente' => false]);

        DB::table('pesos_ahp')->updateOrInsert(
            [
                'peso_severidad' => 0.648,
                'peso_frecuencia' => 0.230,
                'peso_msr' => 0.122,
            ],
            [
                'peso_riesgo_tecnico' => 0.750,
                'peso_dependencia' => 0.250,
                'vigente' => true,
                'fecha_actualizacion' => now(),
            ]
        );
    }

    /**
     * Modos de falla de la familia "Equipos de Soporte Académico
     * (Computadoras)", provistos por el usuario con Causa Raíz, Efecto y
     * Fuente de Información. El esquema real (modos_falla) no tiene columnas
     * propias para causa/efecto/fuente: solo nombre_modo_falla y un nivel de
     * severidad/MSR tomado de listas fijas (Tabla 20/Tabla 22 de la tesis,
     * ver los catálogos niveles_severidad/niveles_msr), cada uno con su propio
     * campo de justificación en texto libre. Causa raíz, efecto y fuente se
     * concatenan en esas justificaciones para no perder el dato.
     *
     * El usuario no indicó nivel de severidad/MSR explícito, así que se
     * infirió de la descripción cualitativa de cada modo (documentado en el
     * comentario de cada fila) — a revisar/ajustar por el responsable de la
     * tesis si no coincide con su criterio experto.
     */
    private function modosFallaComputadoras(): void
    {
        $idCategoria = DB::table('categorias')
            ->where('nombre_categoria', 'Computadoras de escritorio')
            ->value('id_categoria');

        if (!$idCategoria) {
            throw new \RuntimeException('No se encontró la categoría "Computadoras de escritorio". Ejecuta categorias() primero.');
        }

        $modos = [
            [
                'nombre_modo_falla' => 'Congelamiento del sistema operativo durante clases',
                'causa' => 'Degradación mecánica interna y acumulación excesiva de polvo en sistemas de disipación térmica.',
                'efecto' => 'Interrupción del avance pedagógico, frustración docente y pérdida de horas académicas prácticas.',
                'fuente' => 'Registros de Incidencias',
                // Disruptivo pero recuperable con reinicio (no hay pérdida de
                // hardware/datos) -> severidad moderada. El sobrecalentamiento
                // por polvo no se monitorea de forma proactiva en el parque
                // informático -> capacidad de detección muy baja.
                'nivel_severidad' => 'moderada',
                'nivel_msr' => 'muy_baja',
            ],
            [
                'nombre_modo_falla' => 'Retrasos operativos críticos en el inicio de sesión y arranque de terminales',
                'causa' => 'Obsolescencia de hardware, unidades de disco mecánico desgastadas y memoria RAM insuficiente.',
                'efecto' => 'Retrasos en el desarrollo de clases y actividades administrativas.',
                'fuente' => 'Inventario de Activos',
                // Genera demoras pero el equipo sigue siendo utilizable ->
                // severidad baja. La obsolescencia es progresiva y se puede
                // anticipar con la antigüedad registrada en el inventario ->
                // capacidad de detección moderada.
                'nivel_severidad' => 'baja',
                'nivel_msr' => 'moderada',
            ],
            [
                'nombre_modo_falla' => 'Falla de periféricos o dispositivos de entrada/salida',
                'causa' => 'Desgaste por uso continuo, manipulación inadecuada o conexiones defectuosas.',
                'efecto' => 'Limitación para el desarrollo de actividades académicas y administrativas que requieren interacción con el equipo.',
                'fuente' => 'Soporte Técnico',
                // Limita el uso del equipo pero el periférico suele
                // reemplazarse rápido -> severidad baja. El desgaste físico
                // (cables, teclas) suele notarse en revisiones de soporte
                // antes de la falla total -> capacidad de detección moderada.
                'nivel_severidad' => 'baja',
                'nivel_msr' => 'moderada',
            ],
            [
                'nombre_modo_falla' => 'Daño de la fuente de alimentación o de la placa madre',
                'causa' => 'Sobrecargas eléctricas, fluctuaciones de voltaje y ausencia de sistemas de protección eléctrica.',
                'efecto' => 'Quemado de fuentes de poder y pérdida total del activo tecnológico antes de cumplir su ciclo de vida.',
                'fuente' => 'Informes de Auditoría Pasados',
                // Implica pérdida total del activo antes de su vida útil ->
                // severidad muy alta. La causa raíz señala explícitamente la
                // ausencia de protección eléctrica, es decir, no existe forma
                // de anticipar el evento -> capacidad de detección muy baja.
                'nivel_severidad' => 'muy_alta',
                'nivel_msr' => 'muy_baja',
            ],
        ];

        foreach ($modos as $m) {
            DB::table('modos_falla')->updateOrInsert(
                [
                    'fk_id_categoria' => $idCategoria,
                    'nombre_modo_falla' => $m['nombre_modo_falla'],
                ],
                [
                    'valor_severidad_base' => \App\Support\Catalogo::valorSeveridad($m['nivel_severidad']),
                    'valor_msr_base' => \App\Support\Catalogo::valorMsr($m['nivel_msr']),
                    'nivel_severidad' => $m['nivel_severidad'],
                    'nivel_msr' => $m['nivel_msr'],
                    'justificacion_severidad' => "Causa raíz: {$m['causa']} Efecto/consecuencia: {$m['efecto']} Fuente: {$m['fuente']}.",
                    'justificacion_msr' => "Capacidad de detección estimada según la causa raíz: {$m['causa']} Fuente: {$m['fuente']}.",
                ]
            );
        }
    }

    /**
     * Activos extraídos del inventario real "COMPUTADORAS.xlsx" (Trabajo de
     * Grado II). El archivo original mezclaba, dentro de una misma hoja
     * organizada por sección/aula, computadoras de escritorio, laptops
     * (sección con sufijo "(LAPTOPS)"/"LAPTOS BLOQUE..." o texto "laptop" en
     * campos como Monitor/S.O.) y tablets Android. Cada categoría se separó
     * en su propio archivo JSON de datos y se carga con el método
     * correspondiente, todos apoyados en este helper común.
     *
     * fk_id_categoria y fk_id_ubicacion se resuelven en tiempo de ejecución
     * contra las tablas ya sembradas (categorias/ubicaciones).
     *
     * La clave de coincidencia para el upsert es `origen_externo` (no
     * `codigo_patrimonial`): el código patrimonial puede cambiar entre
     * corridas (p. ej. al renumerar códigos sintéticos), y usarlo como clave
     * duplicaría el activo en vez de actualizarlo. `origen_externo` es una
     * referencia estable a la fila de origen del Excel que no cambia.
     *
     * Antes de aplicar los valores finales, libera todos los códigos
     * patrimoniales afectados a un valor temporal único (fase 1). Sin esto,
     * reasignar códigos entre filas existentes puede chocar contra la
     * restricción UNIQUE de forma transitoria (fila A pasa a tener el código
     * que la fila B todavía conserva, antes de que a B le toque actualizarse).
     *
     * @param  bool  $conDetalleComputadora  Si true, además de activos_tecnologicos
     *         escribe una fila en detalles_computadora (aplica a Computadoras
     *         y Laptops; Tablets no tiene tabla de detalle propia en el sistema).
     */
    private function cargarActivosDesdeJson(string $nombreCategoria, string $archivoJson, bool $conDetalleComputadora): void
    {
        $idCategoria = DB::table('categorias')
            ->where('nombre_categoria', $nombreCategoria)
            ->value('id_categoria');

        if (!$idCategoria) {
            throw new \RuntimeException("No se encontró la categoría \"{$nombreCategoria}\". Ejecuta categorias() primero.");
        }

        $mapaUbicaciones = DB::table('ubicaciones')->get()
            ->mapWithKeys(fn ($u) => [$u->seccion . '|' . ($u->aula ?? '') => $u->id_ubicacion]);

        $ruta = __DIR__ . '/data/' . $archivoJson;
        $registros = json_decode(file_get_contents($ruta), true);

        $sinUbicacion = [];
        $porAplicar = [];

        foreach ($registros as $r) {
            $claveUbicacion = $r['seccion'] . '|' . ($r['aula'] ?? '');
            $idUbicacion = $mapaUbicaciones[$claveUbicacion] ?? null;

            if (!$idUbicacion) {
                $sinUbicacion[] = $claveUbicacion;
                continue;
            }

            $porAplicar[$r['clave_origen']] = [
                'activo' => [
                    'fk_id_categoria' => $idCategoria,
                    'fk_id_ubicacion' => $idUbicacion,
                    'codigo_patrimonial' => $r['codigo_patrimonial'],
                    'nombre_equipo' => $r['nombre_equipo'],
                    'marca' => $r['marca'] ?? null,
                    'modelo' => $r['modelo'] ?? null,
                    'fecha_adquisicion' => $r['fecha_adquisicion'],
                    'estado_operativo' => $r['estado_operativo'],
                    'etapa_ciclo_vida' => $r['etapa_ciclo_vida'],
                    // criticidad_institucional NO se incluye a propósito: es
                    // un caché de solo lectura que recalcula MotorFmeaService
                    // en cada evaluación FMEA (ver comentario en ese servicio
                    // y en el modelo ActivoTecnologico). Incluirlo aquí lo
                    // pisaría con NULL en cada re-siembra, borrando el valor
                    // real calculado por el motor de riesgo.
                    'observaciones' => $r['observaciones'],
                ],
                'detalle' => $conDetalleComputadora ? [
                    'procesador' => $r['procesador'],
                    'ram_gb' => $r['ram_gb'],
                    'disco_solido_gb' => $r['disco_solido_gb'],
                    'disco_rigido_gb' => $r['disco_rigido_gb'],
                    'sistema_operativo' => $r['sistema_operativo'],
                    'lector' => $r['lector'],
                    'parlantes' => $r['parlantes'],
                    'teclado_mouse' => $r['teclado_mouse'],
                    'monitor' => $r['monitor'],
                    'camara_web' => $r['camara_web'],
                ] : null,
            ];
        }

        // Fase 1: liberar códigos patrimoniales de las filas ya existentes
        // (identificadas por origen_externo) para evitar choques transitorios
        // con la restricción UNIQUE al reasignar códigos en la fase 2.
        DB::table('activos_tecnologicos')
            ->whereIn('origen_externo', array_keys($porAplicar))
            ->get(['id_activo', 'origen_externo'])
            ->each(fn ($existente) => DB::table('activos_tecnologicos')
                ->where('id_activo', $existente->id_activo)
                ->update(['codigo_patrimonial' => 'TMP-' . $existente->id_activo]));

        // Fase 2: upsert con los valores finales.
        foreach ($porAplicar as $claveOrigen => $datos) {
            DB::table('activos_tecnologicos')->updateOrInsert(
                ['origen_externo' => $claveOrigen],
                array_merge(['origen_externo' => $claveOrigen], $datos['activo'])
            );

            if ($datos['detalle'] === null) {
                continue;
            }

            $idActivo = DB::table('activos_tecnologicos')
                ->where('origen_externo', $claveOrigen)
                ->value('id_activo');

            DB::table('detalles_computadora')->updateOrInsert(
                ['fk_id_activo' => $idActivo],
                array_merge(['fk_id_activo' => $idActivo], $datos['detalle'])
            );
        }

        if ($sinUbicacion) {
            fwrite(STDERR, "AVISO [{$nombreCategoria}]: activos sin ubicación resuelta (omitidos): " . implode(', ', array_unique($sinUbicacion)) . PHP_EOL);
        }
    }

    /** Computadoras de escritorio (Windows o macOS, sin marca de laptop). */
    private function activosComputadoras(): void
    {
        $this->cargarActivosDesdeJson('Computadoras de escritorio', 'computadoras_escritorio.json', conDetalleComputadora: true);
    }

    /**
     * Laptops: filas identificadas por sección "(LAPTOPS)"/"LAPTOS BLOQUE...",
     * por texto "laptop" en Monitor/S.O./Extra, o por aula con sufijo "LAP".
     * Sí llevan detalles_computadora, igual que las de escritorio (así lo
     * hace la app: store() crea DetalleComputadora para categorías que
     * contengan "computadora", "pc" o "laptop" en el nombre).
     */
    private function activosLaptops(): void
    {
        $this->cargarActivosDesdeJson('Laptops', 'laptops.json', conDetalleComputadora: true);
    }

    /**
     * Tablets (Android). No tienen tabla de detalle propia en el sistema
     * (store() solo crea detalle para computadora/pc/laptop, red/servidor/
     * impresora o proyector) — el procesador, RAM y sistema operativo del
     * Excel se conservan en el campo "observaciones" para no perder el dato.
     */
    private function activosTablets(): void
    {
        $this->cargarActivosDesdeJson('Tablets', 'tablets.json', conDetalleComputadora: false);
    }
}
