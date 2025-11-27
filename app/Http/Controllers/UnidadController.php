<?php

namespace App\Http\Controllers;

use App\Models\Libro;
use DB;
use App\Models\unidad;
use App\Models\Temas;
use Illuminate\Http\Request;

class UnidadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }
    public function libro_enUnidad()
    {
        $libros = DB::SELECT("SELECT l.idlibro, l.nombrelibro ,
        CONCAT(l.nombrelibro,' - ', p.pro_codigo) as nombrecodigo
        FROM libro l
        LEFT JOIN libros_series ls ON l.idlibro = ls.idLibro
        LEFT JOIN 1_4_cal_producto p ON ls.codigo_liquidacion = p.pro_codigo
        WHERE l.Estado_idEstado = '1'
        AND p.ifcombo = '0'
        ORDER BY l.nombrelibro ASC");
        return $libros;
        // -- and l.asignatura_idasignatura = a.idasignatura
        // -- and a.area_idarea = ar.idarea
        // -- and ar.tipoareas_idtipoarea =  t.idtipoarea
        // -- and t.idtipoarea = '1'
    }
    public function unidadesX_Libro($id)
    {
        $unidades = DB::SELECT("SELECT ul.*
        FROM unidades_libros ul
        WHERE ul.id_libro = $id
        AND estado = '1'
        ");
        return $unidades;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateUnidades(Request $request){
        if($request->porImportacionUnidades) { return $this->porImportacionUnidades($request); }
        if( $request->id_unidad_libro ){
            $dato = unidad::find($request->id_unidad_libro);
        }else{
            $dato = new unidad();
        }
        $dato->id_libro = $request->id_libro;
        $dato->unidad = $request->unidad;
        $dato->nombre_unidad = $request->nombre_unidad;
        $dato->pag_inicio = $request->pag_inicio;
        $dato->pag_fin = $request->pag_fin;
        $dato->txt_nombre_unidad = $request->txt_nombre_unidad;
        $dato->user_created = $request->user_created;
        $dato->estado = '1';
        $dato->save();
        // $datos = DB::UPDATE("UPDATE unidades_libros SET id_libro = $request->id_libro, unidad = $request->unidad, nombre_unidad = '$request->nombre_unidad', pag_inicio = $request->pag_inicio, pag_fin = $request->pag_fin, estado = $request->estado WHERE id_unidad_libro =  $request->id_unidad_libro");

        return $dato;

    }
    //api:postupdateUnidades?porImportacionUnidades=1
    public function porImportacionUnidades($request){
        $datos_array = json_decode($request->datos_array);
        $arrayUnidadesYaExistentes = [];
        $contador = 0;

        try {
            // Inicia transacción
            DB::beginTransaction();

            foreach($datos_array as $key => $item) {
                $validarAsignatura = Libro::where('idlibro', $item->id_libro)
                ->select('libro.idlibro')
                ->first();
                if(!$validarAsignatura){
                    return ["status" => "0", "message" => "No existe el libro con id ".$item->id_libro];
                }
                // validar que la unidad y el nombre de unidad y el libro no existan
                $validarUnidad = unidad::where('id_libro', $item->id_libro)
                ->where('unidad', $item->unidad)
                ->where('nombre_unidad', $item->nombre_unidad)
                ->first();
                if($validarUnidad){
                    $item->message = "La unidad y nombre de unidad ya existen";
                    $arrayUnidadesYaExistentes[] = $item;
                    continue; // Si ya existe, saltar a la siguiente iteración
                }
                // Creación de nueva unidad
                $dato = new unidad();
                $dato->id_libro          = $item->id_libro;
                $dato->unidad            = $item->unidad;
                $dato->nombre_unidad     = $item->nombre_unidad;
                $dato->pag_inicio        = $item->pag_inicio;
                $dato->pag_fin           = $item->pag_fin;
                $dato->txt_nombre_unidad = $item->txt_nombre_unidad;
                $dato->user_created      = $request->user_created;
                $dato->estado            = '1';
                $dato->save();
                // Guardado del modelo
                if ($dato) {
                    $contador++; // Solo incrementa si la inserción fue exitosa
                }
            }
            // Confirmar transacción si todo salió bien
            DB::commit();

            return ["status" => "1", "message" => "Se importó correctamente", "contador" => $contador, "unidades_ya_existentes" => $arrayUnidadesYaExistentes];
        } catch (\Exception $e) {
            // En caso de error, deshacer la transacción
            DB::rollback();
            return ["status" => "0", "message" => $e->getMessage()];
        }
    }

    public function store(Request $request)
    {
        $datos = unidades_libros::where('id_unidad_libro', '=', $request->$id_unidad_libro)->first();

        $datos->update($request->all());
        return $datos;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\unidad  $unidad
     * @return \Illuminate\Http\Response
     */
    public function show(unidad $unidad)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\unidad  $unidad
     * @return \Illuminate\Http\Response
     */
    public function edit(unidad $unidad)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\unidad  $unidad
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, unidad $unidad)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\unidad  $unidad
     * @return \Illuminate\Http\Response
     */
    public function destroy(unidad $unidad)
    {
        //
    }
    public function buscar_id_unidad($id)
    {
        //buscar si existen temas registrados
        $dato = DB::SELECT("SELECT *
        FROM temas
        WHERE id_unidad = $id
        LIMIT 1");
        return $dato;
    }
    public function eliminar_id_unidad($unidad)
    {
        $dato = unidad::find( $unidad );
        $dato->delete();
        return $dato;
    }
    public function transferenciaUnidades(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validar los datos recibidos
            $request->validate([
                'libro_a_transferir' => 'required|integer',
                'libro_recibir_transferencia' => 'required|integer',
            ]);

            // 🔍 Verificar si el libro receptor ya tiene unidades registradas
            $existenUnidades = unidad::where('id_libro', $request->libro_recibir_transferencia)->exists();
            if ($existenUnidades) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'El libro al que desea transferir las unidades ya tiene unidades registradas. Por razones de integridad de datos, no se puede realizar la transferencia automática.  Por favor, registre o actualice las unidades manualmente en este libro.'
                ], 200);
            }

            // 🔄 Obtener unidades del libro que se va a transferir
            $unidades = unidad::where('id_libro', $request->libro_a_transferir)->get();

            // Validar que existan unidades para transferir
            if ($unidades->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => 'El libro que va a transferir las unidades no tiene registros para transferir.'
                ], 200);
            }

            // 📦 Recorrer las unidades y duplicarlas en el nuevo libro
            foreach ($unidades as $unidad) {
                $nuevaUnidad = new unidad();
                $nuevaUnidad->id_libro = $request->libro_recibir_transferencia;
                $nuevaUnidad->unidad = $unidad->unidad;
                $nuevaUnidad->nombre_unidad = $unidad->nombre_unidad;
                $nuevaUnidad->txt_nombre_unidad = $unidad->txt_nombre_unidad;
                $nuevaUnidad->pag_inicio = $unidad->pag_inicio;
                $nuevaUnidad->pag_fin = $unidad->pag_fin;
                $nuevaUnidad->estado = $unidad->estado;
                $nuevaUnidad->created_at = now();
                $nuevaUnidad->updated_at = now();
                $nuevaUnidad->save();
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Transferencia de unidades completada exitosamente.'
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => 'Ocurrió un error al realizar la transferencia: ' . $th->getMessage()
            ], 500);
        }
    }
}
