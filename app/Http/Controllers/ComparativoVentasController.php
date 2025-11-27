<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComparativoVentasController extends Controller
{
    /**
     * Obtiene los detalles de las prefacturas (f_detalle_venta)
     */
    public function getDetallesPrefacturas(Request $request)
    {
        try {
            $ventas = json_decode($request->ventas, true);
            
            if (empty($ventas)) {
                return response()->json([
                    'detalles' => []
                ]);
            }

            // Construir consulta para obtener detalles de múltiples ventas
            $query = DB::table('f_detalle_venta as dv')
                ->join('1_4_cal_producto as p', 'dv.pro_codigo', '=', 'p.pro_codigo')
                ->select(
                    'dv.det_ven_codigo',
                    'dv.ven_codigo',
                    'dv.id_empresa',
                    'dv.pro_codigo',
                    'dv.det_ven_cantidad',
                    'dv.det_ven_valor_u',
                    'dv.det_ven_cantidad_despacho',
                    'dv.det_ven_dev',
                    'p.pro_nombre as nombre'
                );

            // Filtrar por las ventas específicas
            $query->where(function ($q) use ($ventas) {
                foreach ($ventas as $venta) {
                    $q->orWhere(function ($subQ) use ($venta) {
                        $subQ->where('dv.ven_codigo', $venta['ven_codigo'])
                             ->where('dv.id_empresa', $venta['id_empresa']);
                    });
                }
            });

            $detalles = $query->get();

            return response()->json([
                'detalles' => $detalles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener detalles de prefacturas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los detalles de las facturas reales (f_detalle_venta_agrupado)
     * Incluye información de facturación cruzada
     */
    public function getDetallesFacturas(Request $request)
    {
        try {
            $facturas = json_decode($request->facturas, true);
            
            if (empty($facturas)) {
                return response()->json([
                    'detalles' => []
                ]);
            }

            // Construir consulta para obtener detalles de múltiples facturas
            // Incluye JOIN con f_venta_agrupado para obtener datos de facturación cruzada
            $query = DB::table('f_detalle_venta_agrupado as dva')
                ->join('f_venta_agrupado as fva', function($join) {
                    $join->on('dva.id_factura', '=', 'fva.id_factura')
                         ->on('dva.id_empresa', '=', 'fva.id_empresa');
                })
                ->join('1_4_cal_producto as p', 'dva.pro_codigo', '=', 'p.pro_codigo')
                ->select(
                    'dva.det_ven_codigo',
                    'dva.id_factura',
                    'dva.id_empresa',
                    'dva.pro_codigo',
                    'dva.det_ven_cantidad',
                    'dva.det_ven_valor_u',
                    'p.pro_nombre as nombre',
                    'fva.factura_cruzada',
                    'fva.empresa_cruzada',
                    'fva.ven_desc_por'
                );

            // Filtrar por las facturas específicas
            $query->where(function ($q) use ($facturas) {
                foreach ($facturas as $factura) {
                    $q->orWhere(function ($subQ) use ($factura) {
                        $subQ->where('dva.id_factura', $factura['id_factura'])
                             ->where('dva.id_empresa', $factura['id_empresa']);
                    });
                }
            });

            $detalles = $query->get();

            return response()->json([
                'detalles' => $detalles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener detalles de facturas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene un comparativo completo entre prefacturas y facturas reales
     */
    public function getComparativoCompleto(Request $request)
    {
        try {
            $ventas = json_decode($request->ventas, true);
            $facturas = json_decode($request->facturas, true);

            // Obtener detalles de prefacturas
            $detallesPrefacturas = $this->obtenerDetallesPrefacturasInterno($ventas);
            
            // Obtener detalles de facturas
            $detallesFacturas = $this->obtenerDetallesFacturasInterno($facturas);

            // Agrupar por producto
            $comparativo = $this->generarComparativo($detallesPrefacturas, $detallesFacturas);

            return response()->json([
                'success' => true,
                'comparativo' => $comparativo,
                'detalles_prefacturas' => $detallesPrefacturas,
                'detalles_facturas' => $detallesFacturas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar comparativo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método interno para obtener detalles de prefacturas
     */
    private function obtenerDetallesPrefacturasInterno($ventas)
    {
        if (empty($ventas)) {
            return [];
        }

        $query = DB::table('f_detalle_venta as dv')
            ->join('1_4_cal_producto as p', 'dv.pro_codigo', '=', 'p.pro_codigo')
            ->select(
                'dv.pro_codigo',
                'p.pro_nombre as nombre',
                DB::raw('SUM(dv.det_ven_cantidad) as cantidad_total'),
                DB::raw('AVG(dv.det_ven_valor_u) as valor_unitario_promedio'),
                DB::raw('SUM(dv.det_ven_cantidad * dv.det_ven_valor_u) as valor_total')
            );

        $query->where(function ($q) use ($ventas) {
            foreach ($ventas as $venta) {
                $q->orWhere(function ($subQ) use ($venta) {
                    $subQ->where('dv.ven_codigo', $venta['ven_codigo'])
                         ->where('dv.id_empresa', $venta['id_empresa']);
                });
            }
        });

        $query->groupBy('dv.pro_codigo', 'p.pro_nombre');

        return $query->get()->toArray();
    }

    /**
     * Método interno para obtener detalles de facturas
     */
    private function obtenerDetallesFacturasInterno($facturas)
    {
        if (empty($facturas)) {
            return [];
        }

        $query = DB::table('f_detalle_venta_agrupado as dva')
            ->join('1_4_cal_producto as p', 'dva.pro_codigo', '=', 'p.pro_codigo')
            ->select(
                'dva.pro_codigo',
                'p.pro_nombre as nombre',
                DB::raw('SUM(dva.det_ven_cantidad) as cantidad_total'),
                DB::raw('AVG(dva.det_ven_valor_u) as valor_unitario_promedio'),
                DB::raw('SUM(dva.det_ven_cantidad * dva.det_ven_valor_u) as valor_total')
            );

        $query->where(function ($q) use ($facturas) {
            foreach ($facturas as $factura) {
                $q->orWhere(function ($subQ) use ($factura) {
                    $subQ->where('dva.id_factura', $factura['id_factura'])
                         ->where('dva.id_empresa', $factura['id_empresa']);
                });
            }
        });

        $query->groupBy('dva.pro_codigo', 'p.pro_nombre');

        return $query->get()->toArray();
    }

    /**
     * Genera el comparativo entre prefacturas y facturas
     */
    private function generarComparativo($detallesPrefacturas, $detallesFacturas)
    {
        $comparativo = [];

        // Crear índices por producto
        $prefacturasPorProducto = [];
        foreach ($detallesPrefacturas as $detalle) {
            $prefacturasPorProducto[$detalle->pro_codigo] = $detalle;
        }

        $facturasPorProducto = [];
        foreach ($detallesFacturas as $detalle) {
            $facturasPorProducto[$detalle->pro_codigo] = $detalle;
        }

        // Obtener todos los códigos únicos
        $todosCodigos = array_unique(array_merge(
            array_keys($prefacturasPorProducto),
            array_keys($facturasPorProducto)
        ));

        // Generar comparativo
        foreach ($todosCodigos as $codigo) {
            $prefac = $prefacturasPorProducto[$codigo] ?? null;
            $fact = $facturasPorProducto[$codigo] ?? null;

            $comparativo[] = [
                'pro_codigo' => $codigo,
                'nombre' => $prefac->nombre ?? $fact->nombre ?? 'Sin nombre',
                'cant_prefacturas' => $prefac->cantidad_total ?? 0,
                'valor_u_prefacturas' => $prefac->valor_unitario_promedio ?? 0,
                'total_prefacturas' => $prefac->valor_total ?? 0,
                'cant_facturas' => $fact->cantidad_total ?? 0,
                'valor_u_facturas' => $fact->valor_unitario_promedio ?? 0,
                'total_facturas' => $fact->valor_total ?? 0,
                'diferencia' => ($prefac->cantidad_total ?? 0) - ($fact->cantidad_total ?? 0),
            ];
        }

        return $comparativo;
    }
}
