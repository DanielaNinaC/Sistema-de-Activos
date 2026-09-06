<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categorías
        DB::table('categorias')->insertOrIgnore([
            ['id_categoria' => 1, 'nombre_categoria' => 'Servidor', 'vida_util_meses' => 60],
            ['id_categoria' => 2, 'nombre_categoria' => 'PC', 'vida_util_meses' => 48],
            ['id_categoria' => 3, 'nombre_categoria' => 'Impresora', 'vida_util_meses' => 60],
            ['id_categoria' => 4, 'nombre_categoria' => 'Proyector', 'vida_util_meses' => 72],
            ['id_categoria' => 5, 'nombre_categoria' => 'Equipo de Red', 'vida_util_meses' => 60],
        ]);

        // 2. Ubicaciones
        DB::table('ubicaciones')->insertOrIgnore([
            ['id_ubicacion' => 1, 'seccion' => 'Administración', 'aula' => 'Dirección'],
            ['id_ubicacion' => 2, 'seccion' => 'Primaria', 'aula' => 'Aula 5'],
            ['id_ubicacion' => 3, 'seccion' => 'Sistemas', 'aula' => 'Sala de Servidores'],
            ['id_ubicacion' => 4, 'seccion' => 'Secundaria', 'aula' => 'Laboratorio 1'],
        ]);

        // 3. Activos Tecnológicos
        DB::table('activos_tecnologicos')->insertOrIgnore([
            [
                'id_activo' => 1, 'fk_id_categoria' => 1, 'fk_id_ubicacion' => 3,
                'codigo_patrimonial' => 'SRV-001', 'nombre_equipo' => 'Servidor Base de Datos',
                'marca' => 'Dell', 'modelo' => 'PowerEdge R740', 'fecha_adquisicion' => '2021-05-10',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Alta', 'observaciones' => 'Alberga el sistema de notas.'
            ],
            [
                'id_activo' => 2, 'fk_id_categoria' => 3, 'fk_id_ubicacion' => 1,
                'codigo_patrimonial' => 'IMP-010', 'nombre_equipo' => 'Impresora Dirección',
                'marca' => 'Epson', 'modelo' => 'L380', 'fecha_adquisicion' => '2020-08-15',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Mantenimiento',
                'criticidad_institucional' => 'Baja', 'observaciones' => 'Atascos frecuentes.'
            ],
            [
                'id_activo' => 3, 'fk_id_categoria' => 4, 'fk_id_ubicacion' => 2,
                'codigo_patrimonial' => 'PRY-022', 'nombre_equipo' => 'Proyector Aula 5',
                'marca' => 'Epson', 'modelo' => 'S17', 'fecha_adquisicion' => '2019-03-20',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Media', 'observaciones' => 'Imagen ligeramente opaca.'
            ],
            [
                'id_activo' => 4, 'fk_id_categoria' => 2, 'fk_id_ubicacion' => 2,
                'codigo_patrimonial' => 'PC-001', 'nombre_equipo' => 'PC Docente Aula 5',
                'marca' => 'HP', 'modelo' => 'ProDesk 400', 'fecha_adquisicion' => '2022-01-15',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Media', 'observaciones' => 'Equipo principal del docente.'
            ],
            [
                'id_activo' => 5, 'fk_id_categoria' => 2, 'fk_id_ubicacion' => 4,
                'codigo_patrimonial' => 'PC-002', 'nombre_equipo' => 'PC Lab Estudiante 1',
                'marca' => 'Lenovo', 'modelo' => 'ThinkCentre M70', 'fecha_adquisicion' => '2022-06-10',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Media', 'observaciones' => 'Uso intensivo de estudiantes.'
            ],
            [
                'id_activo' => 6, 'fk_id_categoria' => 2, 'fk_id_ubicacion' => 1,
                'codigo_patrimonial' => 'PC-003', 'nombre_equipo' => 'PC Secretaría',
                'marca' => 'Dell', 'modelo' => 'OptiPlex 3080', 'fecha_adquisicion' => '2023-02-01',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Alta', 'observaciones' => 'Equipo de atención al público.'
            ],
            [
                'id_activo' => 7, 'fk_id_categoria' => 4, 'fk_id_ubicacion' => 1,
                'codigo_patrimonial' => 'PRY-023', 'nombre_equipo' => 'Proyector Sala Reuniones',
                'marca' => 'BenQ', 'modelo' => 'MX560', 'fecha_adquisicion' => '2021-11-12',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Media', 'observaciones' => 'Uso en presentaciones institucionales.'
            ],
            [
                'id_activo' => 8, 'fk_id_categoria' => 4, 'fk_id_ubicacion' => 4,
                'codigo_patrimonial' => 'PRY-024', 'nombre_equipo' => 'Proyector Lab 1',
                'marca' => 'ViewSonic', 'modelo' => 'PA503W', 'fecha_adquisicion' => '2020-09-05',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Baja', 'observaciones' => 'Requiere limpieza de filtro.'
            ],
            [
                'id_activo' => 9, 'fk_id_categoria' => 5, 'fk_id_ubicacion' => 3,
                'codigo_patrimonial' => 'RED-001', 'nombre_equipo' => 'Switch Core Principal',
                'marca' => 'Cisco', 'modelo' => 'Catalyst 2960', 'fecha_adquisicion' => '2020-04-18',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Alta', 'observaciones' => 'Switch central del edificio.'
            ],
            [
                'id_activo' => 10, 'fk_id_categoria' => 5, 'fk_id_ubicacion' => 3,
                'codigo_patrimonial' => 'RED-002', 'nombre_equipo' => 'Router Firewall Edge',
                'marca' => 'MikroTik', 'modelo' => 'CCR1036', 'fecha_adquisicion' => '2021-08-22',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Alta', 'observaciones' => 'Enrutador y firewall perimetral.'
            ],
        ]);

        // 4. Detalles Computadora (Actualizado con los nuevos campos booleanos)
        DB::table('detalles_computadora')->insertOrIgnore([
            [
                'fk_id_activo' => 4, 'ram_gb' => 8, 'procesador' => 'Intel Core i5-10400',
                'disco_solido_gb' => 256, 'disco_rigido_gb' => 0, 'sistema_operativo' => 'Windows 11 Pro',
                'lector' => false, 'parlantes' => true, 'teclado_mouse' => true, 'monitor' => true, 'camara_web' => true
            ],
            [
                'fk_id_activo' => 5, 'ram_gb' => 16, 'procesador' => 'Intel Core i7-11700',
                'disco_solido_gb' => 512, 'disco_rigido_gb' => 1000, 'sistema_operativo' => 'Windows 10 Pro',
                'lector' => true, 'parlantes' => false, 'teclado_mouse' => true, 'monitor' => true, 'camara_web' => false
            ],
            [
                'fk_id_activo' => 6, 'ram_gb' => 8, 'procesador' => 'Intel Core i3-10100',
                'disco_solido_gb' => 240, 'disco_rigido_gb' => 500, 'sistema_operativo' => 'Windows 11 Home',
                'lector' => false, 'parlantes' => true, 'teclado_mouse' => true, 'monitor' => true, 'camara_web' => false
            ],
        ]);

        // 5. Detalles Proyector
        DB::table('detalles_proyector')->insertOrIgnore([
            ['fk_id_activo' => 3, 'tipo_cable' => 'HDMI', 'calidad_imagen' => 7],
            ['fk_id_activo' => 7, 'tipo_cable' => 'HDMI', 'calidad_imagen' => 9],
            ['fk_id_activo' => 8, 'tipo_cable' => 'VGA', 'calidad_imagen' => 6],
        ]);

        // 6. Detalles Equipo Red (Sin tipo_equipo por última migración)
        DB::table('detalles_equipo_red')->insertOrIgnore([
            ['fk_id_activo' => 1, 'direccion_ip' => '192.168.1.10', 'version_firmware' => 'v2.1.4'],
            ['fk_id_activo' => 9, 'direccion_ip' => '192.168.1.2', 'version_firmware' => '15.2(4)E'],
            ['fk_id_activo' => 10, 'direccion_ip' => '192.168.1.1', 'version_firmware' => 'RouterOS v7.11'],
        ]);

        // 7. Configuración AHP
        DB::table('pesos_ahp')->insertOrIgnore([
            [
                'id_config' => 1, 'peso_severidad' => 0.648, 'peso_frecuencia' => 0.230,
                'peso_msr' => 0.122, 'fecha_actualizacion' => '2026-01-15 10:00:00'
            ],
            [
                'id_config' => 2, 'peso_severidad' => 0.500, 'peso_frecuencia' => 0.300,
                'peso_msr' => 0.200, 'fecha_actualizacion' => '2026-03-01 09:30:00'
            ],
            [
                'id_config' => 3, 'peso_severidad' => 0.600, 'peso_frecuencia' => 0.250,
                'peso_msr' => 0.150, 'fecha_actualizacion' => '2026-06-10 14:00:00'
            ],
        ]);

        // 8. Modos de Falla (con valores base de Severidad y MSR)
        DB::table('modos_falla')->insertOrIgnore([
            ['id_modo_falla' => 1, 'fk_id_categoria' => 1, 'nombre_modo_falla' => 'Caída del sistema operativo', 'valor_severidad_base' => 10, 'valor_msr_base' => 4],
            ['id_modo_falla' => 2, 'fk_id_categoria' => 3, 'nombre_modo_falla' => 'Atasco de papel mecánico', 'valor_severidad_base' => 3, 'valor_msr_base' => 9],
            ['id_modo_falla' => 3, 'fk_id_categoria' => 4, 'nombre_modo_falla' => 'Falla de lámpara', 'valor_severidad_base' => 5, 'valor_msr_base' => 9],
            ['id_modo_falla' => 4, 'fk_id_categoria' => 2, 'nombre_modo_falla' => 'Falla de disco duro', 'valor_severidad_base' => 8, 'valor_msr_base' => 6],
            ['id_modo_falla' => 5, 'fk_id_categoria' => 5, 'nombre_modo_falla' => 'Pérdida de conectividad de red', 'valor_severidad_base' => 8, 'valor_msr_base' => 2],
        ]);

        // 9. Evaluación de Riesgo
        DB::table('evaluacion_riesgo')->insertOrIgnore([
            [
                'id_evaluacion' => 1, 'fk_id_activo' => 1, 'fk_id_modo_falla' => 1,
                'valor_severidad' => 9, 'valor_frecuencia' => 7, 'valor_msr' => 9,
                'score_calculado' => 8.55, 'nivel_riesgo' => 'Crítico', 'fecha_evaluacion' => now()
            ],
            [
                'id_evaluacion' => 2, 'fk_id_activo' => 2, 'fk_id_modo_falla' => 2,
                'valor_severidad' => 3, 'valor_frecuencia' => 8, 'valor_msr' => 9,
                'score_calculado' => 4.87, 'nivel_riesgo' => 'Medio', 'fecha_evaluacion' => now()
            ],
            [
                'id_evaluacion' => 3, 'fk_id_activo' => 3, 'fk_id_modo_falla' => 3,
                'valor_severidad' => 5, 'valor_frecuencia' => 6, 'valor_msr' => 7,
                'score_calculado' => 5.47, 'nivel_riesgo' => 'Medio', 'fecha_evaluacion' => now()
            ],
            [
                'id_evaluacion' => 4, 'fk_id_activo' => 4, 'fk_id_modo_falla' => 4,
                'valor_severidad' => 7, 'valor_frecuencia' => 4, 'valor_msr' => 6,
                'score_calculado' => 6.19, 'nivel_riesgo' => 'Alto', 'fecha_evaluacion' => now()
            ],
            [
                'id_evaluacion' => 5, 'fk_id_activo' => 9, 'fk_id_modo_falla' => 5,
                'valor_severidad' => 8, 'valor_frecuencia' => 2, 'valor_msr' => 7,
                'score_calculado' => 6.50, 'nivel_riesgo' => 'Alto', 'fecha_evaluacion' => now()
            ],
        ]);

        // 10. Alertas
        DB::table('alertas')->insertOrIgnore([
            ['id_alerta' => 1, 'fk_id_evaluacion' => 1, 'tipo_alerta' => 'Roja', 'fecha_generacion' => now(), 'notificacion_enviada' => true],
            ['id_alerta' => 2, 'fk_id_evaluacion' => 2, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now(), 'notificacion_enviada' => false],
            ['id_alerta' => 3, 'fk_id_evaluacion' => 4, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now(), 'notificacion_enviada' => true],
        ]);

        // 11. Incidentes
        DB::table('incidentes')->insertOrIgnore([
            [
                'id_incidente' => 1, 'fk_id_activo' => 2, 'fk_id_usuario_reporta' => 1,
                'fecha_incidente' => now()->subDays(5), 'descripcion_falla' => 'Atasco masivo de papel en bandeja 1.',
                'estado_incidente' => 'Resuelto'
            ],
            [
                'id_incidente' => 2, 'fk_id_activo' => 4, 'fk_id_usuario_reporta' => 2,
                'fecha_incidente' => now()->subDays(3), 'descripcion_falla' => 'Pantalla azul recurrente al iniciar Windows.',
                'estado_incidente' => 'En proceso'
            ],
            [
                'id_incidente' => 3, 'fk_id_activo' => 3, 'fk_id_usuario_reporta' => 3,
                'fecha_incidente' => now()->subDays(1), 'descripcion_falla' => 'El proyector no enciende la lámpara.',
                'estado_incidente' => 'Reportado'
            ],
        ]);

        // 12. Plan de Tratamiento (ISO 31000)
        DB::table('plan_tratamiento_riesgo')->insertOrIgnore([
            [
                'id_tratamiento' => 1, 'fk_id_evaluacion' => 1,
                'estrategia' => 'Mitigar', 'accion_preventiva' => 'Comprar disco SSD de respaldo y configurar RAID 1.',
                'estado_tratamiento' => 'En Proceso', 'fecha_compromiso' => now()->addMonth()
            ],
            [
                'id_tratamiento' => 2, 'fk_id_evaluacion' => 4,
                'estrategia' => 'Mitigar', 'accion_preventiva' => 'Reemplazar disco rígido por SSD M.2.',
                'estado_tratamiento' => 'Pendiente', 'fecha_compromiso' => now()->addWeeks(2)
            ],
            [
                'id_tratamiento' => 3, 'fk_id_evaluacion' => 5,
                'estrategia' => 'Mitigar', 'accion_preventiva' => 'Instalar un switch secundario para redundancia.',
                'estado_tratamiento' => 'Completado', 'fecha_compromiso' => now()->subWeek()
            ],
        ]);

        // 13. Mantenimientos
        DB::table('mantenimientos')->insertOrIgnore([
            [
                'id_mantenimiento' => 1, 'fk_id_activo' => 1, 'fk_id_usuario_asignado' => 2,
                'id_evaluacion_origen' => 1, 'tipo_mantenimiento' => 'Preventivo',
                'fecha_programada' => now()->addWeek(), 'fecha_ejecucion' => null,
                'descripcion_trabajo' => 'Revisión de ventiladores y limpieza profunda.',
                'estado_mantenimiento' => 'Pendiente', 'evidencia_pdf' => null
            ],
            [
                'id_mantenimiento' => 2, 'fk_id_activo' => 4, 'fk_id_usuario_asignado' => 2,
                'id_evaluacion_origen' => 4, 'tipo_mantenimiento' => 'Correctivo',
                'fecha_programada' => now()->subDay(), 'fecha_ejecucion' => now(),
                'descripcion_trabajo' => 'Cambio de módulo de memoria RAM defectuoso.',
                'estado_mantenimiento' => 'Finalizado', 'evidencia_pdf' => null
            ],
            [
                'id_mantenimiento' => 3, 'fk_id_activo' => 3, 'fk_id_usuario_asignado' => 2,
                'id_evaluacion_origen' => 3, 'tipo_mantenimiento' => 'Preventivo',
                'fecha_programada' => now()->addMonth(), 'fecha_ejecucion' => null,
                'descripcion_trabajo' => 'Calibración de lente y cambio de filtro de aire.',
                'estado_mantenimiento' => 'Pendiente', 'evidencia_pdf' => null
            ],
        ]);

        // 14. Logs de Auditoría
        DB::table('logs')->insertOrIgnore([
            [
                'id_log' => 1, 'id_usuario' => 1, 'accion_realizada' => 'Inicio de sesión',
                'tabla_afectada' => 'usuarios', 'registro_id' => 1,
                'fecha_hora' => now()->subDays(2), 'valor_anterior' => null, 'valor_nuevo' => 'Login exitoso'
            ],
            [
                'id_log' => 2, 'id_usuario' => 1, 'accion_realizada' => 'Creación de activo',
                'tabla_afectada' => 'activos_tecnologicos', 'registro_id' => 1,
                'fecha_hora' => now()->subDays(1), 'valor_anterior' => null, 'valor_nuevo' => 'Servidor Base de Datos'
            ],
            [
                'id_log' => 3, 'id_usuario' => 2, 'accion_realizada' => 'Actualización de evaluación de riesgo',
                'tabla_afectada' => 'evaluacion_riesgo', 'registro_id' => 1,
                'fecha_hora' => now(), 'valor_anterior' => 'Score: 6.0', 'valor_nuevo' => 'Score: 7.49'
            ],
        ]);

        // =====================================================================
        // 15. Datos adicionales para variedad en Evaluación e Informes: más
        // activos, modos de falla y evaluaciones con score en todos los
        // niveles (no solo Alto/Crítico). Todos los IDs son estrictamente
        // correlativos (evaluaciones 6..20, alertas 4..18, tratamientos 4..8).
        // =====================================================================

        // 15.1 Modos de falla adicionales (uno más por categoría, con
        // severidad/MSR distintos a los ya existentes para ampliar el rango
        // de scores posibles dentro de cada categoría).
        DB::table('modos_falla')->insertOrIgnore([
            ['id_modo_falla' => 6, 'fk_id_categoria' => 1, 'nombre_modo_falla' => 'Sobrecalentamiento por falla de refrigeración', 'valor_severidad_base' => 8, 'valor_msr_base' => 6],
            ['id_modo_falla' => 7, 'fk_id_categoria' => 2, 'nombre_modo_falla' => 'Falla de fuente de poder', 'valor_severidad_base' => 5, 'valor_msr_base' => 6],
            ['id_modo_falla' => 8, 'fk_id_categoria' => 3, 'nombre_modo_falla' => 'Falta de tóner o tinta', 'valor_severidad_base' => 1, 'valor_msr_base' => 9],
            ['id_modo_falla' => 9, 'fk_id_categoria' => 4, 'nombre_modo_falla' => 'Sobrecalentamiento de lámpara', 'valor_severidad_base' => 3, 'valor_msr_base' => 4],
            ['id_modo_falla' => 10, 'fk_id_categoria' => 5, 'nombre_modo_falla' => 'Configuración corrupta de firmware', 'valor_severidad_base' => 5, 'valor_msr_base' => 6],
        ]);

        // 15.2 Activos adicionales, distribuidos entre las 5 categorías.
        DB::table('activos_tecnologicos')->insertOrIgnore([
            [
                'id_activo' => 11, 'fk_id_categoria' => 1, 'fk_id_ubicacion' => 3,
                'codigo_patrimonial' => 'SRV-002', 'nombre_equipo' => 'Servidor de Archivos',
                'marca' => 'HPE', 'modelo' => 'ProLiant DL380', 'fecha_adquisicion' => '2024-04-12',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Alta', 'observaciones' => 'Almacenamiento compartido institucional.'
            ],
            [
                'id_activo' => 12, 'fk_id_categoria' => 2, 'fk_id_ubicacion' => 4,
                'codigo_patrimonial' => 'PC-004', 'nombre_equipo' => 'PC Biblioteca',
                'marca' => 'Acer', 'modelo' => 'Veriton X4670G', 'fecha_adquisicion' => '2024-02-20',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Media', 'observaciones' => 'Consulta de catálogo y préstamos.'
            ],
            [
                'id_activo' => 13, 'fk_id_categoria' => 2, 'fk_id_ubicacion' => 1,
                'codigo_patrimonial' => 'PC-005', 'nombre_equipo' => 'PC Sala de Profesores',
                'marca' => 'HP', 'modelo' => 'EliteDesk 800', 'fecha_adquisicion' => '2019-06-18',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Media', 'observaciones' => 'Uso compartido por varios docentes.'
            ],
            [
                'id_activo' => 14, 'fk_id_categoria' => 3, 'fk_id_ubicacion' => 4,
                'codigo_patrimonial' => 'IMP-011', 'nombre_equipo' => 'Impresora Laboratorio 1',
                'marca' => 'HP', 'modelo' => 'LaserJet Pro M404', 'fecha_adquisicion' => '2022-08-09',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Baja', 'observaciones' => 'Impresión de guías de laboratorio.'
            ],
            [
                'id_activo' => 15, 'fk_id_categoria' => 3, 'fk_id_ubicacion' => 1,
                'codigo_patrimonial' => 'IMP-012', 'nombre_equipo' => 'Impresora Multifuncional Profesores',
                'marca' => 'Canon', 'modelo' => 'imageCLASS MF267dw', 'fecha_adquisicion' => '2023-10-02',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Baja', 'observaciones' => 'Escaneo y copiado de material didáctico.'
            ],
            [
                'id_activo' => 16, 'fk_id_categoria' => 4, 'fk_id_ubicacion' => 1,
                'codigo_patrimonial' => 'PRY-025', 'nombre_equipo' => 'Proyector Auditorio',
                'marca' => 'Epson', 'modelo' => 'EB-X41', 'fecha_adquisicion' => '2024-03-14',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Media', 'observaciones' => 'Eventos y actos institucionales.'
            ],
            [
                'id_activo' => 17, 'fk_id_categoria' => 5, 'fk_id_ubicacion' => 4,
                'codigo_patrimonial' => 'RED-003', 'nombre_equipo' => 'Access Point Laboratorio',
                'marca' => 'Ubiquiti', 'modelo' => 'UniFi AP AC Lite', 'fecha_adquisicion' => '2023-05-27',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Alta', 'observaciones' => 'Cobertura wifi del laboratorio.'
            ],
            [
                'id_activo' => 18, 'fk_id_categoria' => 5, 'fk_id_ubicacion' => 4,
                'codigo_patrimonial' => 'RED-004', 'nombre_equipo' => 'Switch Secundario Laboratorio',
                'marca' => 'TP-Link', 'modelo' => 'TL-SG1024', 'fecha_adquisicion' => '2022-01-11',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Alta', 'observaciones' => 'Distribución de red en el laboratorio.'
            ],
        ]);

        // 15.3 Evaluaciones de riesgo adicionales (IDs correlativos 6 a 20)
        DB::table('evaluacion_riesgo')->insertOrIgnore([
            ['id_evaluacion' => 6, 'fk_id_activo' => 5, 'fk_id_modo_falla' => 7, 'valor_severidad' => 5, 'valor_frecuencia' => 2, 'valor_msr' => 6, 'score_calculado' => 5.57, 'nivel_riesgo' => 'Medio', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(45)],
            ['id_evaluacion' => 7, 'fk_id_activo' => 6, 'fk_id_modo_falla' => 4, 'valor_severidad' => 8, 'valor_frecuencia' => 9, 'valor_msr' => 6, 'score_calculado' => 8.24, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(38)],
            ['id_evaluacion' => 8, 'fk_id_activo' => 7, 'fk_id_modo_falla' => 3, 'valor_severidad' => 5, 'valor_frecuencia' => 6, 'valor_msr' => 9, 'score_calculado' => 5.54, 'nivel_riesgo' => 'Medio', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(30)],
            ['id_evaluacion' => 9, 'fk_id_activo' => 8, 'fk_id_modo_falla' => 9, 'valor_severidad' => 3, 'valor_frecuencia' => 2, 'valor_msr' => 4, 'score_calculado' => 3.42, 'nivel_riesgo' => 'Medio', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(25)],
            ['id_evaluacion' => 10, 'fk_id_activo' => 10, 'fk_id_modo_falla' => 10, 'valor_severidad' => 5, 'valor_frecuencia' => 9, 'valor_msr' => 6, 'score_calculado' => 6.78, 'nivel_riesgo' => 'Alto', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(20)],
            ['id_evaluacion' => 11, 'fk_id_activo' => 11, 'fk_id_modo_falla' => 1, 'valor_severidad' => 10, 'valor_frecuencia' => 2, 'valor_msr' => 4, 'score_calculado' => 7.82, 'nivel_riesgo' => 'Alto', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(15)],
            ['id_evaluacion' => 12, 'fk_id_activo' => 11, 'fk_id_modo_falla' => 6, 'valor_severidad' => 8, 'valor_frecuencia' => 6, 'valor_msr' => 6, 'score_calculado' => 7.72, 'nivel_riesgo' => 'Alto', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(12)],
            ['id_evaluacion' => 13, 'fk_id_activo' => 12, 'fk_id_modo_falla' => 7, 'valor_severidad' => 5, 'valor_frecuencia' => 6, 'valor_msr' => 6, 'score_calculado' => 6.26, 'nivel_riesgo' => 'Alto', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(10)],
            ['id_evaluacion' => 14, 'fk_id_activo' => 13, 'fk_id_modo_falla' => 4, 'valor_severidad' => 8, 'valor_frecuencia' => 2, 'valor_msr' => 6, 'score_calculado' => 7.03, 'nivel_riesgo' => 'Alto', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(8)],
            ['id_evaluacion' => 15, 'fk_id_activo' => 14, 'fk_id_modo_falla' => 2, 'valor_severidad' => 3, 'valor_frecuencia' => 2, 'valor_msr' => 9, 'score_calculado' => 3.88, 'nivel_riesgo' => 'Medio', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(6)],
            ['id_evaluacion' => 16, 'fk_id_activo' => 15, 'fk_id_modo_falla' => 8, 'valor_severidad' => 1, 'valor_frecuencia' => 2, 'valor_msr' => 9, 'score_calculado' => 2.90, 'nivel_riesgo' => 'Bajo', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(5)],
            ['id_evaluacion' => 17, 'fk_id_activo' => 16, 'fk_id_modo_falla' => 3, 'valor_severidad' => 5, 'valor_frecuencia' => 9, 'valor_msr' => 9, 'score_calculado' => 6.06, 'nivel_riesgo' => 'Alto', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(4)],
            ['id_evaluacion' => 18, 'fk_id_activo' => 17, 'fk_id_modo_falla' => 5, 'valor_severidad' => 8, 'valor_frecuencia' => 2, 'valor_msr' => 2, 'score_calculado' => 6.67, 'nivel_riesgo' => 'Alto', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(3)],
            ['id_evaluacion' => 19, 'fk_id_activo' => 18, 'fk_id_modo_falla' => 10, 'valor_severidad' => 5, 'valor_frecuencia' => 2, 'valor_msr' => 6, 'score_calculado' => 5.57, 'nivel_riesgo' => 'Medio', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(2)],
            ['id_evaluacion' => 20, 'fk_id_activo' => 2, 'fk_id_modo_falla' => 8, 'valor_severidad' => 1, 'valor_frecuencia' => 6, 'valor_msr' => 9, 'score_calculado' => 3.59, 'nivel_riesgo' => 'Medio', 'vigente' => true, 'fecha_evaluacion' => now()->subDays(1)],
        ]);

        // 15.4 Alertas correspondientes (IDs correlativos 4 a 18)
        DB::table('alertas')->insertOrIgnore([
            ['id_alerta' => 4, 'fk_id_evaluacion' => 6, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now()->subDays(45), 'notificacion_enviada' => true],
            ['id_alerta' => 5, 'fk_id_evaluacion' => 7, 'tipo_alerta' => 'Roja', 'fecha_generacion' => now()->subDays(38), 'notificacion_enviada' => true],
            ['id_alerta' => 6, 'fk_id_evaluacion' => 8, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now()->subDays(30), 'notificacion_enviada' => false],
            ['id_alerta' => 7, 'fk_id_evaluacion' => 9, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now()->subDays(25), 'notificacion_enviada' => true],
            ['id_alerta' => 8, 'fk_id_evaluacion' => 10, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now()->subDays(20), 'notificacion_enviada' => true],
            ['id_alerta' => 9, 'fk_id_evaluacion' => 11, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now()->subDays(15), 'notificacion_enviada' => false],
            ['id_alerta' => 10, 'fk_id_evaluacion' => 12, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now()->subDays(12), 'notificacion_enviada' => true],
            ['id_alerta' => 11, 'fk_id_evaluacion' => 13, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now()->subDays(10), 'notificacion_enviada' => true],
            ['id_alerta' => 12, 'fk_id_evaluacion' => 14, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now()->subDays(8), 'notificacion_enviada' => false],
            ['id_alerta' => 13, 'fk_id_evaluacion' => 15, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now()->subDays(6), 'notificacion_enviada' => true],
            ['id_alerta' => 14, 'fk_id_evaluacion' => 16, 'tipo_alerta' => 'Verde', 'fecha_generacion' => now()->subDays(5), 'notificacion_enviada' => true],
            ['id_alerta' => 15, 'fk_id_evaluacion' => 17, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now()->subDays(4), 'notificacion_enviada' => false],
            ['id_alerta' => 16, 'fk_id_evaluacion' => 18, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now()->subDays(3), 'notificacion_enviada' => true],
            ['id_alerta' => 17, 'fk_id_evaluacion' => 19, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now()->subDays(2), 'notificacion_enviada' => true],
            ['id_alerta' => 18, 'fk_id_evaluacion' => 20, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now()->subDays(1), 'notificacion_enviada' => false],
        ]);

        // 15.5 Incidentes adicionales (IDs correlativos 4 a 9)
        DB::table('incidentes')->insertOrIgnore([
            [
                'id_incidente' => 4, 'fk_id_activo' => 6, 'fk_id_usuario_reporta' => 2,
                'fecha_incidente' => now()->subDays(10), 'descripcion_falla' => 'Disco duro con sonidos extraños y lentitud extrema al iniciar.',
                'estado_incidente' => 'Resuelto'
            ],
            [
                'id_incidente' => 5, 'fk_id_activo' => 11, 'fk_id_usuario_reporta' => 1,
                'fecha_incidente' => now()->subDays(7), 'descripcion_falla' => 'El servidor se reinicia solo por sobrecalentamiento.',
                'estado_incidente' => 'En proceso'
            ],
            [
                'id_incidente' => 6, 'fk_id_activo' => 10, 'fk_id_usuario_reporta' => 3,
                'fecha_incidente' => now()->subDays(4), 'descripcion_falla' => 'Pérdida intermitente de conexión a internet en todo el edificio.',
                'estado_incidente' => 'Resuelto'
            ],
            [
                'id_incidente' => 7, 'fk_id_activo' => 14, 'fk_id_usuario_reporta' => 2,
                'fecha_incidente' => now()->subDays(2), 'descripcion_falla' => 'Atasco de papel recurrente en la bandeja trasera.',
                'estado_incidente' => 'Reportado'
            ],
            [
                'id_incidente' => 8, 'fk_id_activo' => 17, 'fk_id_usuario_reporta' => 1,
                'fecha_incidente' => now()->subDays(6), 'descripcion_falla' => 'Los usuarios reportan desconexión frecuente del wifi del laboratorio.',
                'estado_incidente' => 'En proceso'
            ],
            [
                'id_incidente' => 9, 'fk_id_activo' => 16, 'fk_id_usuario_reporta' => 3,
                'fecha_incidente' => now()->subDays(8), 'descripcion_falla' => 'La lámpara del proyector se apaga sola tras 20 minutos de uso.',
                'estado_incidente' => 'Resuelto'
            ],
        ]);

        // 15.6 Mantenimientos adicionales (IDs correlativos 4 a 8)
        DB::table('mantenimientos')->insertOrIgnore([
            [
                'id_mantenimiento' => 4, 'fk_id_activo' => 6, 'fk_id_usuario_asignado' => 2,
                'id_evaluacion_origen' => 7, 'tipo_mantenimiento' => 'Correctivo',
                'fecha_programada' => now()->subDays(9), 'fecha_ejecucion' => now()->subDays(8),
                'descripcion_trabajo' => 'Reemplazo de disco duro por fallas de lectura.',
                'estado_mantenimiento' => 'Finalizado', 'evidencia_pdf' => null
            ],
            [
                'id_mantenimiento' => 5, 'fk_id_activo' => 10, 'fk_id_usuario_asignado' => 2,
                'id_evaluacion_origen' => 10, 'tipo_mantenimiento' => 'Correctivo',
                'fecha_programada' => now()->subDays(3), 'fecha_ejecucion' => now()->subDays(2),
                'descripcion_trabajo' => 'Actualización de firmware y reinicio del enrutador.',
                'estado_mantenimiento' => 'Finalizado', 'evidencia_pdf' => null
            ],
            [
                'id_mantenimiento' => 6, 'fk_id_activo' => 11, 'fk_id_usuario_asignado' => 2,
                'id_evaluacion_origen' => 11, 'tipo_mantenimiento' => 'Preventivo',
                'fecha_programada' => now()->addWeeks(2), 'fecha_ejecucion' => null,
                'descripcion_trabajo' => 'Limpieza del sistema de ventilación y cambio de pasta térmica.',
                'estado_mantenimiento' => 'Pendiente', 'evidencia_pdf' => null
            ],
            [
                'id_mantenimiento' => 7, 'fk_id_activo' => 16, 'fk_id_usuario_asignado' => 2,
                'id_evaluacion_origen' => 17, 'tipo_mantenimiento' => 'Programado',
                'fecha_programada' => now()->addDays(10), 'fecha_ejecucion' => null,
                'descripcion_trabajo' => 'Revisión y calibración de la lámpara del proyector.',
                'estado_mantenimiento' => 'Programado', 'evidencia_pdf' => null
            ],
            [
                'id_mantenimiento' => 8, 'fk_id_activo' => 17, 'fk_id_usuario_asignado' => 2,
                'id_evaluacion_origen' => 18, 'tipo_mantenimiento' => 'Inspección',
                'fecha_programada' => now()->subDay(), 'fecha_ejecucion' => now(),
                'descripcion_trabajo' => 'Inspección técnica y actualización de firmware del access point.',
                'estado_mantenimiento' => 'Finalizado', 'evidencia_pdf' => null
            ],
        ]);

        // 15.7 Planes de tratamiento adicionales (IDs correlativos 4 a 8)
        DB::table('plan_tratamiento_riesgo')->insertOrIgnore([
            [
                'id_tratamiento' => 4, 'fk_id_evaluacion' => 7,
                'estrategia' => 'Mitigar', 'accion_preventiva' => 'Migrar el disco duro a SSD y programar respaldo diario automático.',
                'estado_tratamiento' => 'Pendiente', 'origen' => 'automatico', 'fecha_compromiso' => now()->addDays(3)
            ],
            [
                'id_tratamiento' => 5, 'fk_id_evaluacion' => 10,
                'estrategia' => 'Mitigar', 'accion_preventiva' => 'Actualizar firmware del enrutador y revisar reglas de firewall.',
                'estado_tratamiento' => 'En Proceso', 'origen' => 'automatico', 'fecha_compromiso' => now()->addDays(15)
            ],
            [
                'id_tratamiento' => 6, 'fk_id_evaluacion' => 12,
                'estrategia' => 'Mitigar', 'accion_preventiva' => 'Instalar sistema de refrigeración adicional en el rack del servidor.',
                'estado_tratamiento' => 'Completado', 'origen' => 'manual', 'fecha_compromiso' => now()->subWeek()
            ],
            [
                'id_tratamiento' => 7, 'fk_id_evaluacion' => 14,
                'estrategia' => 'Mitigar', 'accion_preventiva' => 'Reemplazar el disco duro mecánico por una unidad de estado sólido.',
                'estado_tratamiento' => 'Pendiente', 'origen' => 'automatico', 'fecha_compromiso' => now()->addDays(15)
            ],
            [
                'id_tratamiento' => 8, 'fk_id_evaluacion' => 18,
                'estrategia' => 'Transferir', 'accion_preventiva' => 'Contratar soporte externo especializado en redes inalámbricas.',
                'estado_tratamiento' => 'En Proceso', 'origen' => 'manual', 'fecha_compromiso' => now()->addDays(15)
            ],
        ]);

        // 15.8 Logs de auditoría adicionales (IDs correlativos 4 a 6)
        DB::table('logs')->insertOrIgnore([
            [
                'id_log' => 4, 'id_usuario' => 2, 'accion_realizada' => 'Registro de incidente',
                'tabla_afectada' => 'incidentes', 'registro_id' => 4,
                'fecha_hora' => now()->subDays(10), 'valor_anterior' => null, 'valor_nuevo' => 'Disco duro con fallas'
            ],
            [
                'id_log' => 5, 'id_usuario' => 1, 'accion_realizada' => 'Evaluación de riesgo ejecutada',
                'tabla_afectada' => 'evaluacion_riesgo', 'registro_id' => 7,
                'fecha_hora' => now()->subDays(38), 'valor_anterior' => null, 'valor_nuevo' => 'Score: 8.24 (Crítico)'
            ],
            [
                'id_log' => 6, 'id_usuario' => 3, 'accion_realizada' => 'Actualización de plan de tratamiento',
                'tabla_afectada' => 'plan_tratamiento_riesgo', 'registro_id' => 5,
                'fecha_hora' => now()->subDays(5), 'valor_anterior' => 'Pendiente', 'valor_nuevo' => 'En Proceso'
            ],
        ]);
    }
}