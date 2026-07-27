<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categorías (Si ya tienes, ignora, si no, las inserta)
        DB::table('categorias')->insertOrIgnore([
            ['id_categoria' => 1, 'nombre_categoria' => 'Servidor', 'vida_util_meses' => 60],
            ['id_categoria' => 2, 'nombre_categoria' => 'PC', 'vida_util_meses' => 48],
            ['id_categoria' => 3, 'nombre_categoria' => 'Impresora', 'vida_util_meses' => 60],
            ['id_categoria' => 4, 'nombre_categoria' => 'Proyector', 'vida_util_meses' => 72],
        ]);

        // 2. Ubicaciones
        DB::table('ubicaciones')->insertOrIgnore([
            ['id_ubicacion' => 1, 'seccion' => 'Administración', 'aula' => 'Dirección'],
            ['id_ubicacion' => 2, 'seccion' => 'Primaria', 'aula' => 'Aula 5'],
            ['id_ubicacion' => 3, 'seccion' => 'Sistemas', 'aula' => 'Sala de Servidores'],
        ]);

        // 3. Activos Tecnológicos (El núcleo)
        DB::table('activos_tecnologicos')->insertOrIgnore([
            [
                'id_activo' => 1, 'fk_id_categoria' => 1, 'fk_id_ubicacion' => 3,
                'codigo_patrimonial' => 'SRV-001', 'nombre_equipo' => 'Servidor Base de Datos',
                'marca' => 'Dell', 'modelo' => 'PowerEdge R740', 'fecha_adquisicion' => '2019-05-10',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación', 
                'criticidad_institucional' => 'Alta', 'observaciones' => 'Alberga el sistema de notas.'
            ],
            [
                'id_activo' => 2, 'fk_id_categoria' => 3, 'fk_id_ubicacion' => 1,
                'codigo_patrimonial' => 'IMP-010', 'nombre_equipo' => 'Impresora Dirección',
                'marca' => 'Epson', 'modelo' => 'L380', 'fecha_adquisicion' => '2018-08-15',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Mantenimiento',
                'criticidad_institucional' => 'Baja', 'observaciones' => 'Atascos frecuentes.'
            ],
            [
                'id_activo' => 3, 'fk_id_categoria' => 4, 'fk_id_ubicacion' => 2,
                'codigo_patrimonial' => 'PRY-022', 'nombre_equipo' => 'Proyector Aula 5',
                'marca' => 'Epson', 'modelo' => 'S17', 'fecha_adquisicion' => '2015-03-20',
                'estado_operativo' => 'Operativo', 'etapa_ciclo_vida' => 'Operación',
                'criticidad_institucional' => 'Media', 'observaciones' => 'Imagen ligeramente opaca.'
            ],
        ]);

        // 4. Detalles (Especialización 1 a 1)
        DB::table('detalles_equipo_red')->insertOrIgnore([
            'fk_id_activo' => 1, 'direccion_ip' => '192.168.1.10', 'version_firmware' => 'v2.1.4', 'tipo_equipo' => 'Servidor'
        ]);
        DB::table('detalles_equipo_red')->insertOrIgnore([
            'fk_id_activo' => 2, 'direccion_ip' => '192.168.1.20', 'version_firmware' => null, 'tipo_equipo' => 'Impresora de Red'
        ]);
        DB::table('detalles_proyector')->insertOrIgnore([
            'fk_id_activo' => 3, 'tipo_cable' => 'HDMI', 'calidad_imagen' => 7
        ]);

        // 5. Configuración AHP (Tus pesos exactos)
        DB::table('pesos_ahp')->insertOrIgnore([
            'id_config' => 1, 'peso_severidad' => 0.648, 'peso_frecuencia' => 0.230, 'peso_msr' => 0.122,
            'fecha_actualizacion' => now()
        ]);

        // 6. Modos de Falla
        DB::table('modos_falla')->insertOrIgnore([
            ['id_modo_falla' => 1, 'fk_id_categoria' => 1, 'nombre_modo_falla' => 'Caída del sistema operativo'],
            ['id_modo_falla' => 2, 'fk_id_categoria' => 3, 'nombre_modo_falla' => 'Atasco de papel mecánico'],
            ['id_modo_falla' => 3, 'fk_id_categoria' => 4, 'nombre_modo_falla' => 'Falla de lámpara'],
        ]);

        // 7. Evaluación de Riesgo (Calculado a mano con tu Ecuación 3)
        // Servidor: S=9, F=3, MSR=8 -> (0.648*9)+(0.230*3)+(0.122*8) = 5.83 + 0.69 + 0.97 = 7.49 (Alto)
        DB::table('evaluacion_riesgo')->insertOrIgnore([
            'id_evaluacion' => 1, 'fk_id_activo' => 1, 'fk_id_modo_falla' => 1,
            'valor_severidad' => 9, 'valor_frecuencia' => 3, 'valor_msr' => 8,
            'score_calculado' => 7.49, 'nivel_riesgo' => 'Alto', 'fecha_evaluacion' => now()
        ]);
        
        // Impresora: S=3, F=8, MSR=9 -> (0.648*3)+(0.230*8)+(0.122*9) = 1.94 + 1.84 + 1.09 = 4.87 (Medio)
        DB::table('evaluacion_riesgo')->insertOrIgnore([
            'id_evaluacion' => 2, 'fk_id_activo' => 2, 'fk_id_modo_falla' => 2,
            'valor_severidad' => 3, 'valor_frecuencia' => 8, 'valor_msr' => 9,
            'score_calculado' => 4.87, 'nivel_riesgo' => 'Medio', 'fecha_evaluacion' => now()
        ]);

        // Proyector: S=5, F=6, MSR=7 -> (0.648*5)+(0.230*6)+(0.122*7) = 3.24 + 1.38 + 0.85 = 5.47 (Medio)
        DB::table('evaluacion_riesgo')->insertOrIgnore([
            'id_evaluacion' => 3, 'fk_id_activo' => 3, 'fk_id_modo_falla' => 3,
            'valor_severidad' => 5, 'valor_frecuencia' => 6, 'valor_msr' => 7,
            'score_calculado' => 5.47, 'nivel_riesgo' => 'Medio', 'fecha_evaluacion' => now()
        ]);

        // 8. Log de Alertas (Para que veas las notificaciones en el sistema)
        DB::table('alertas')->insertOrIgnore([
            'fk_id_evaluacion' => 1, 'tipo_alerta' => 'Naranja', 'fecha_generacion' => now(), 'notificacion_enviada' => true
        ]);
        DB::table('alertas')->insertOrIgnore([
            'fk_id_evaluacion' => 2, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now(), 'notificacion_enviada' => false
        ]);
        DB::table('alertas')->insertOrIgnore([
            'fk_id_evaluacion' => 3, 'tipo_alerta' => 'Amarilla', 'fecha_generacion' => now(), 'notificacion_enviada' => false
        ]);
        
        // 9. Incidentes (Historial)
        DB::table('incidentes')->insertOrIgnore([
            'id_incidente' => 1, 'fk_id_activo' => 2, 'fk_id_usuario_reporta' => 1,
            'fecha_incidente' => now()->subDays(5), 'descripcion_falla' => 'Atasco masivo de papel en bandeja 1.',
            'estado_incidente' => 'Resuelto'
        ]);

        // 10. Plan de Tratamiento (ISO 31000)
        DB::table('plan_tratamiento_riesgo')->insertOrIgnore([
            'id_tratamiento' => 1, 'fk_id_evaluacion' => 1,
            'estrategia' => 'Mitigar', 'accion_preventiva' => 'Comprar disco SSD de respaldo y configurar RAID 1.',
            'estado_tratamiento' => 'En ejecución', 'fecha_compromiso' => now()->addMonth()
        ]);

        // 11. Mantenimientos
        DB::table('mantenimientos')->insertOrIgnore([
            'id_mantenimiento' => 1, 'fk_id_activo' => 1, 'fk_id_usuario_asignado' => 1,
            'id_evaluacion_origen' => 1, 'tipo_mantenimiento' => 'Preventivo',
            'fecha_programada' => now()->addWeek(), 'fecha_ejecucion' => null,
            'descripcion_trabajo' => 'Revisión de ventiladores y limpieza profunda.',
            'estado_mantenimiento' => 'Pendiente', 'evidencia_pdf' => null
        ]);
    }
}