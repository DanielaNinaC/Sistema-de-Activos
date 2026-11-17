<?php

namespace App\Exports;

use App\Exports\Sheets\CamarasSheet;
use App\Exports\Sheets\CarritosSheet;
use App\Exports\Sheets\ComputadorasSheet;
use App\Exports\Sheets\GenericaSheet;
use App\Exports\Sheets\ImpresorasSheet;
use App\Exports\Sheets\ProyectoresSheet;
use App\Exports\Sheets\SwitchesSheet;
use App\Models\ActivoTecnologico;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador de Inventario de Activos Tecnológicos a Excel.
 * Genera un libro de trabajo estructurado en pestañas especializadas
 * según la categoría del equipo, con diseño ejecutivo, semáforo de estado,
 * fórmulas de conteo y formato condicional.
 */
class InventarioActivosExport implements WithMultipleSheets
{
    private ?string $buscar;

    public function __construct(?string $buscar = null)
    {
        $this->buscar = $buscar;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $query = ActivoTecnologico::with([
            'categoria',
            'ubicacion',
            'detalleComputadora',
            'detalleEquipoRed',
            'detalleProyector',
        ]);

        if ($this->buscar) {
            $buscar = $this->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_patrimonial', 'like', "%{$buscar}%")
                  ->orWhere('nombre_equipo', 'like', "%{$buscar}%")
                  ->orWhere('marca', 'like', "%{$buscar}%")
                  ->orWhere('modelo', 'like', "%{$buscar}%")
                  ->orWhere('estado_operativo', 'like', "%{$buscar}%")
                  ->orWhereHas('categoria', fn($c) => $c->where('nombre_categoria', 'like', "%{$buscar}%"))
                  ->orWhereHas('ubicacion', fn($u) => $u->where('seccion', 'like', "%{$buscar}%")->orWhere('aula', 'like', "%{$buscar}%"));
            });
        }

        $activos = $query->orderBy('id_activo')->get();

        // Clasificación de activos por pestaña especializada
        $proyectores = collect();
        $carritos = collect();
        $computadoras = collect();
        $impresoras = collect();
        $switches = collect();
        $camaras = collect();
        $otrasCategorias = [];

        foreach ($activos as $activo) {
            $cat = mb_strtolower(trim($activo->categoria->nombre_categoria ?? ''));

            if (str_contains($cat, 'proyector')) {
                $proyectores->push($activo);
            } elseif (str_contains($cat, 'laptop') || str_contains($cat, 'tablet') || str_contains($cat, 'carrito') || str_contains($cat, 'movil') || str_contains($cat, 'móvil')) {
                $carritos->push($activo);
            } elseif (str_contains($cat, 'computadora') || str_contains($cat, 'pc') || str_contains($cat, 'escritorio')) {
                $computadoras->push($activo);
            } elseif (str_contains($cat, 'impresora') || str_contains($cat, 'printer')) {
                $impresoras->push($activo);
            } elseif (str_contains($cat, 'switch') || str_contains($cat, 'router') || str_contains($cat, 'punto') || str_contains($cat, 'ap') || str_contains($cat, 'servidor') || str_contains($cat, 'red')) {
                $switches->push($activo);
            } elseif (str_contains($cat, 'cámara') || str_contains($cat, 'camara') || str_contains($cat, 'cctv')) {
                $camaras->push($activo);
            } else {
                $nombreCat = $activo->categoria->nombre_categoria ?? 'Sin Categoría';
                if (!isset($otrasCategorias[$nombreCat])) {
                    $otrasCategorias[$nombreCat] = collect();
                }
                $otrasCategorias[$nombreCat]->push($activo);
            }
        }

        $sheets = [];

        // 1. Pestaña "PROYECTORES"
        if ($proyectores->isNotEmpty() || empty($this->buscar)) {
            $sheets[] = new ProyectoresSheet($proyectores);
        }

        // 2. Pestaña "CARRITOS" (Laptops / Tablets / Móviles)
        if ($carritos->isNotEmpty() || empty($this->buscar)) {
            $sheets[] = new CarritosSheet($carritos);
        }

        // 3. Pestaña "COMPUTADORAS"
        if ($computadoras->isNotEmpty() || empty($this->buscar)) {
            $sheets[] = new ComputadorasSheet($computadoras);
        }

        // 4. Pestaña "IMPRESORAS"
        if ($impresoras->isNotEmpty() || empty($this->buscar)) {
            $sheets[] = new ImpresorasSheet($impresoras);
        }

        // 5. Pestaña "SWITCHES" (Switches / Routers / Servidores / Red)
        if ($switches->isNotEmpty() || empty($this->buscar)) {
            $sheets[] = new SwitchesSheet($switches);
        }

        // 6. Pestaña "CÁMARAS" (CCTV)
        if ($camaras->isNotEmpty() || empty($this->buscar)) {
            $sheets[] = new CamarasSheet($camaras);
        }

        // 7. Pestañas dinámicas para cualquier otra categoría no contemplada
        foreach ($otrasCategorias as $nombreCat => $activosCat) {
            $sheets[] = new GenericaSheet($nombreCat, $activosCat);
        }

        // Fallback: Si no hay ninguna pestaña creada por filtro muy restrictivo, crear al menos Proyectores vacía
        if (empty($sheets)) {
            $sheets[] = new ProyectoresSheet(collect());
        }

        return $sheets;
    }
}
