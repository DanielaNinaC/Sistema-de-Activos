<?php

namespace Database\Seeders;

use App\Support\Catalogo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de Modos de Falla: 25 de las Tablas 19-24 de la tesis (6 familias
 * documentadas) + Switch (fuera del documento, clasificado con la misma
 * metodología de anclaje directo a la Tabla 27 (Severidad) y la Tabla 29
 * (Monitoreo/MSR) para mantener consistencia).
 *
 * Idempotente: usa updateOrInsert por (fk_id_categoria, nombre_modo_falla),
 * así que correr `php artisan db:seed --class=ModosFallaSeeder` varias veces
 * no duplica el catálogo.
 */
class ModosFallaSeeder extends Seeder
{
    public function run(): void
    {
        $this->renombrarEntradasLegadas();
        $this->sembrarCatalogo();
    }

    /**
     * "Computadoras de escritorio" ya tenía 5 filas cargadas antes de este
     * catálogo oficial (de una clasificación cualitativa previa, sin anclar
     * a las Tablas 27/29), con nombres/niveles que no coinciden exactamente
     * con los de este documento — y ya tienen 151 evaluaciones reales cada
     * una (evaluacion_riesgo tiene ON DELETE CASCADE hacia modos_falla, así
     * que borrarlas se llevaría ese historial). En vez de duplicar, se
     * renombra/corrige en el mismo registro para conservar el historial:
     *   - "Retrasos operativos por arranque de terminales" (151 evaluaciones)
     *     -> renombrado al nombre oficial de este catálogo.
     * Y se borra únicamente la entrada duplicada sin evaluaciones:
     *   - "Retrasos operativos críticos en el inicio de sesión y arranque
     *     de terminales" (0 evaluaciones) -> huérfana, se elimina.
     * El resto de los valores (nivel_severidad/nivel_msr) de las 4 entradas
     * de Computadoras se corrige en sembrarCatalogo() vía updateOrInsert,
     * una vez que el nombre ya coincide con el catálogo oficial.
     */
    private function renombrarEntradasLegadas(): void
    {
        $idCategoria = DB::table('categorias')->where('nombre_categoria', 'Computadoras de escritorio')->value('id_categoria');
        if (!$idCategoria) {
            return;
        }

        DB::table('modos_falla')
            ->where('fk_id_categoria', $idCategoria)
            ->where('nombre_modo_falla', 'Retrasos operativos por arranque de terminales')
            ->update(['nombre_modo_falla' => 'Retrasos operativos críticos en inicio de sesión y arranque']);

        // Salvaguarda: solo borra la fila duplicada si de verdad no tiene
        // ninguna evaluación real enganchada (por el CASCADE, borrar una que
        // sí tenga evaluaciones se llevaría ese historial por delante).
        $duplicado = DB::table('modos_falla')
            ->where('fk_id_categoria', $idCategoria)
            ->where('nombre_modo_falla', 'Retrasos operativos críticos en el inicio de sesión y arranque de terminales')
            ->first();

        if ($duplicado) {
            $tieneEvaluaciones = DB::table('evaluacion_riesgo')->where('fk_id_modo_falla', $duplicado->id_modo_falla)->exists();
            if (!$tieneEvaluaciones) {
                DB::table('modos_falla')->where('id_modo_falla', $duplicado->id_modo_falla)->delete();
            }
        }
    }

    private function sembrarCatalogo(): void
    {
        $catalogo = [
            'Computadoras de escritorio' => [
                ['Congelamiento del sistema operativo durante clases', 'moderada', 'baja',
                    'Interrumpe la clase pero no hay pérdida total de datos ni incumplimiento legal — "funciona con limitaciones / interrupción parcial".',
                    'Se detecta por reporte directo del docente durante la clase (Registros de Incidencias implica seguimiento activo de soporte).'],
                ['Retrasos operativos críticos en inicio de sesión y arranque', 'baja', 'muy_baja',
                    'Genera retraso pero el equipo sigue operable; hay proceso alterno (esperar, reiniciar).',
                    'No hay monitoreo activo de rendimiento; se detecta solo por inventario/antigüedad, de forma retrospectiva.'],
                ['Falla de periféricos o dispositivos de entrada/salida', 'moderada', 'baja',
                    'Limita el desarrollo de actividades académicas/administrativas, no las detiene por completo.',
                    'Detectable en rondas de soporte técnico tras reporte de usuario.'],
                ['Daño de la fuente de alimentación o de la placa madre', 'alta', 'muy_baja',
                    'Implica pérdida total del activo antes de cumplir su ciclo de vida — degradación severa del servicio que presta ese equipo.',
                    'No existe protección eléctrica ni alerta previa; se descubre cuando el componente ya se quemó.'],
            ],
            'Laptops' => [
                ['Reducción de la autonomía de la batería', 'baja', 'baja',
                    'Inconveniente menor con proceso alterno directo (usar conectado a corriente).',
                    'Se detecta gradualmente por reporte técnico del usuario.'],
                ['Daño físico de carcasa, pantalla o componentes internos', 'moderada', 'muy_baja',
                    'Indisponibilidad temporal de un solo equipo, con costo de reparación — no interrumpe servicios institucionales completos.',
                    'No hay forma de anticipar caídas/golpes; se detecta después del hecho.'],
                ['Pérdida de conectividad inalámbrica', 'moderada', 'moderada',
                    'Interrumpe acceso a plataformas institucionales — funciona con limitaciones.',
                    'Existen logs de error del adaptador, pero requieren revisión manual de soporte técnico.'],
                ['Saturación del almacenamiento interno', 'baja', 'muy_baja',
                    'Degradación gradual con proceso alterno (liberar espacio); no detiene la operación de inmediato.',
                    'No hay alertas de espacio disponible; se detecta en auditorías posteriores.'],
                ['Sobrecalentamiento del equipo', 'alta', 'muy_baja',
                    'Puede causar daño permanente de componentes y apagados inesperados en medio de uso — degradación severa.',
                    'Sin sensores ni alertas; se detecta cuando ya ocurrió el apagado o el daño.'],
            ],
            'Tablets' => [
                ['El sistema operativo no soporta nuevas aplicaciones o actualizaciones', 'baja', 'muy_baja',
                    'Incompatibilidad puntual con alternativa disponible (app anterior u otro dispositivo).',
                    'Se descubre solo al intentar instalar; no hay alerta de obsolescencia previa.'],
                ['Pérdida de respuesta o funcionamiento de la pantalla táctil', 'moderada', 'muy_baja',
                    'Inutiliza el dispositivo para su función educativa durante la clase, pero es un solo activo, no un servicio institucional completo.',
                    'Se detecta únicamente cuando el estudiante reporta que no responde.'],
                ['Degradación acelerada de la batería', 'baja', 'muy_baja',
                    'Inconveniente menor, con proceso alterno directo (mantenerlo conectado).',
                    'Sin monitoreo; se detecta por percepción subjetiva del usuario (fuente: Entrevistas).'],
            ],
            'Impresoras' => [
                ['Atasco frecuente del papel o falla del mecanismo de alimentación', 'moderada', 'baja',
                    'Retrasa documentos administrativos y evaluaciones — impacto en productividad, no interrupción total.',
                    'Se detecta en el momento de uso, por inspección directa.'],
                ['El equipo deja de imprimir por agotamiento de consumibles', 'alta', 'muy_baja',
                    'Suspende emisión de documentación oficial — afecta directamente procesos formales de la institución.',
                    'La causa raíz es literalmente "inexistencia de alertas predictivas" — coincide de forma exacta con la ancla de MSR Muy Baja.'],
                ['Pérdida de comunicación y colapso de la cola de impresión de red', 'moderada', 'moderada',
                    'Genera saturación de red y gasto de insumos, pero no detiene la operación institucional.',
                    'Hay logs de red, pero requieren revisión manual de soporte técnico.'],
                ['Impresión con baja calidad (manchas, líneas o impresión incompleta)', 'baja', 'baja',
                    'Afecta la presentación de documentos, no su emisión; hay proceso alterno (reimprimir/limpiar).',
                    'Detección inmediata y visual por el propio usuario al imprimir.'],
            ],
            'Proyectores' => [
                ['La lámpara de proyección deja de funcionar', 'moderada', 'muy_baja',
                    'Interrumpe la clase de forma parcial y temporal — no hay pérdida de datos ni incumplimiento legal.',
                    'La causa raíz dice explícitamente "sin alerta técnica previa" — coincide de forma exacta con Muy Baja.'],
                ['Degradación de la calidad de imagen, distorsión cromática o manchas', 'baja', 'baja',
                    'Inconveniente perceptible con proceso alterno (limpieza, ajuste).',
                    'Detectable en rondas de mantenimiento preventivo de soporte técnico.'],
                ['Apagado automático por sobrecalentamiento', 'moderada', 'alta',
                    'Interrumpe la clase temporalmente mientras se enfría el equipo.',
                    'El propio apagado automático es un mecanismo de detección y auto-protección antes del colapso — coincide con la ancla de MSR Alta ("alertas automáticas... antes del colapso del servicio").'],
                ['Fallas en el reconocimiento de señales de video de entrada (HDMI/VGA)', 'baja', 'baja',
                    'Retraso menor al iniciar clase, con solución rápida (reconectar cable).',
                    'El docente lo nota de inmediato al intentar proyectar.'],
            ],
            'Cámaras CCTV' => [
                ['Pérdida de continuidad en el monitoreo y grabación de zonas críticas', 'alta', 'muy_baja',
                    'Degradación severa de un servicio de seguridad institucional completo, no de un solo activo.',
                    'Se detecta de forma reactiva en auditorías posteriores; no hay alerta en tiempo real documentada.'],
                ['Pérdida de grabaciones almacenadas por falta de espacio en el NVR', 'alta', 'muy_baja',
                    'Coincide de forma literal con la ancla de Severidad Alta: "afectación directa a la integridad de la información de auditoría".',
                    'No existe alerta de espacio ni política; se descubre cuando ya falta la evidencia.'],
                ['Degradación de visibilidad de video (imagen borrosa, sucia o con ruido)', 'moderada', 'baja',
                    'Dificulta identificar personas/eventos, pero el sistema sigue grabando — funciona con limitaciones.',
                    'Detectable en rondas de inspección de soporte técnico.'],
                ['Desincronización de la fecha y hora en las grabaciones', 'alta', 'muy_baja',
                    'Compromete la validez probatoria de la evidencia — afecta directamente la integridad de la información de auditoría.',
                    'No hay sincronización activa ni alerta; se descubre al revisar evidencia de un incidente.'],
                ['Vulnerabilidad a alteración o borrado de videos por credenciales por defecto', 'muy_alta', 'muy_baja',
                    'Riesgo de pérdida/manipulación total de evidencia crítica de seguridad institucional — el peor escenario de la escala.',
                    'No hay control documental de contraseñas ni rotación periódica; ninguna alerta de acceso indebido.'],
            ],
            // Switch no forma parte de las 6 familias del documento original;
            // se clasifica con la misma metodología (anclaje directo a las
            // Tablas 27/29) para mantener consistencia con el resto del catálogo.
            'Switch' => [
                ['Fallo total del switch (pérdida completa de conectividad del segmento de red)', 'alta', 'baja',
                    'Interrumpe la conectividad de red de todo un segmento o edificio de forma simultánea, afectando múltiples servicios institucionales — degradación severa del servicio.',
                    'Se detecta rápido por la multiplicidad de reportes de usuarios que pierden conexión a la vez, sin necesidad de monitoreo automatizado, pero sin alertas ni logs previos que lo anticipen.'],
                ['Fallo intermitente o daño de puertos individuales', 'moderada', 'baja',
                    'Afecta solo a los dispositivos conectados a ese puerto específico; el switch sigue funcionando con limitaciones para el resto de la red.',
                    'Detectable por reporte directo del usuario afectado o en rondas de soporte técnico.'],
                ['Inestabilidad o reinicios espontáneos por sobrecalentamiento', 'moderada', 'muy_baja',
                    'Genera cortes de conectividad breves e intermitentes, con impacto en productividad, sin llegar a pérdida total del servicio.',
                    'Sin sensores de temperatura monitoreados; se detecta solo cuando ya provocó una interrupción.'],
                ['Pérdida o corrupción de la configuración de red (VLAN, seguridad de puertos) sin respaldo', 'alta', 'muy_baja',
                    'Puede dejar segmentos de red mal segmentados o sin las políticas de seguridad aplicadas, afectando la integridad y disponibilidad de los servicios de red conectados.',
                    'No existe respaldo automático de configuración ni alerta de cambios no autorizados; se descubre al notar fallas de segmentación o seguridad.'],
            ],
            // Servidores, Router y Puntos de Acceso (AP) tampoco forman parte
            // del documento original; se clasifican con la misma metodología
            // que Switch, para que ninguna categoría quede sin al menos un
            // modo de falla definido.
            'Servidores' => [
                ['Caída del sistema operativo o de los servicios institucionales alojados', 'muy_alta', 'muy_baja',
                    'Interrumpe simultáneamente todos los sistemas institucionales que dependen del servidor (ej. sistema de notas, archivos compartidos) — coincide con la ancla de interrupción total de actividades críticas.',
                    'Sin monitoreo activo del servicio; se detecta solo cuando los usuarios reportan que no pueden acceder a los sistemas.'],
                ['Falla de disco duro con pérdida de datos por falta de redundancia (RAID)', 'muy_alta', 'muy_baja',
                    'Implica pérdida total de datos académicos y administrativos críticos almacenados — coincide de forma literal con la ancla de Severidad Muy Alta.',
                    'No hay monitoreo S.M.A.R.T. ni alertas de salud del disco; se descubre cuando el disco ya falló.'],
                ['Sobrecalentamiento por falla del sistema de refrigeración', 'alta', 'muy_baja',
                    'Puede causar apagados forzados o daño permanente de componentes del servidor — degradación severa del servicio.',
                    'Sin sensores de temperatura monitoreados activamente; se detecta cuando ya ocurrió el apagado o el daño.'],
            ],
            'Router' => [
                ['Fallo total del router (pérdida de conectividad a Internet y entre redes)', 'alta', 'baja',
                    'Interrumpe el acceso a Internet y la comunicación entre redes institucionales para toda la institución — degradación severa de servicios.',
                    'Se detecta rápido por la multiplicidad de reportes simultáneos de usuarios sin conexión, sin monitoreo automatizado previo.'],
                ['Pérdida o corrupción de la configuración de enrutamiento y firewall sin respaldo', 'alta', 'muy_baja',
                    'Puede dejar la red sin las reglas de seguridad perimetral aplicadas o sin acceso a servicios externos — afecta la integridad y disponibilidad de la red institucional.',
                    'No existe respaldo automático de configuración; se descubre al notar fallas de conectividad o de seguridad.'],
                ['Vulnerabilidad por firmware desactualizado o credenciales de acceso por defecto', 'alta', 'muy_baja',
                    'Riesgo de acceso no autorizado a la red institucional completa a través del punto de entrada a Internet.',
                    'No hay control documental de actualizaciones de firmware ni rotación de credenciales; ninguna alerta de acceso indebido.'],
            ],
            'Puntos de Acceso (AP)' => [
                ['Fallo total del punto de acceso (pérdida de cobertura Wi-Fi en la zona)', 'moderada', 'baja',
                    'Afecta la conectividad inalámbrica solo en la zona cubierta por ese equipo; el resto de la red institucional sigue operando — funciona con limitaciones.',
                    'Detectable por reporte de los usuarios de esa zona al perder señal Wi-Fi.'],
                ['Degradación de señal o interferencia que reduce el rendimiento de la red inalámbrica', 'baja', 'muy_baja',
                    'Genera lentitud perceptible pero no corta la conectividad; existe proceso alterno (acercarse a otro punto de acceso o usar red cableada).',
                    'Sin monitoreo de calidad de señal; se detecta por percepción subjetiva de los usuarios.'],
                ['Sobrecalentamiento o reinicios espontáneos del equipo', 'moderada', 'muy_baja',
                    'Genera cortes de conectividad breves e intermitentes en la zona cubierta.',
                    'Sin sensores de temperatura monitoreados; se detecta solo cuando ya provocó una interrupción.'],
            ],
        ];

        $idsCategorias = DB::table('categorias')->pluck('id_categoria', 'nombre_categoria');
        $faltantes = [];

        foreach ($catalogo as $nombreCategoria => $modos) {
            $idCategoria = $idsCategorias[$nombreCategoria] ?? null;
            if (!$idCategoria) {
                $faltantes[] = $nombreCategoria;
                continue;
            }

            foreach ($modos as [$nombre, $severidad, $msr, $justSeveridad, $justMsr]) {
                DB::table('modos_falla')->updateOrInsert(
                    ['fk_id_categoria' => $idCategoria, 'nombre_modo_falla' => $nombre],
                    [
                        'nivel_severidad' => $severidad,
                        'nivel_msr' => $msr,
                        'valor_severidad_base' => Catalogo::valorSeveridad($severidad),
                        'valor_msr_base' => Catalogo::valorMsr($msr),
                        'justificacion_severidad' => $justSeveridad,
                        'justificacion_msr' => $justMsr,
                    ]
                );
            }
        }

        if ($faltantes) {
            throw new \RuntimeException('Categorías no encontradas en la BD: ' . implode(', ', $faltantes));
        }
    }
}
