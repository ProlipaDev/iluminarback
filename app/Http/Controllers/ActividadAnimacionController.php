<?php

namespace App\Http\Controllers;

use App\Models\actividad_animacion;
use App\Models\Varios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActividadAnimacionController extends Controller
{
    /**
     * Display a listing of the resource.,
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $act = DB::SELECT("SELECT * FROM actividades_animaciones");
        return $act;
    }
    //api:post/conteoActividad
    public function conteoActividad(Request $request){

        if($request->tipo){
            DB::INSERT("INSERT INTO  actividad_historico(asignatura_id,idusuario,url,actividad,pagina,periodo_id,tipo) VALUES(?,?,?,?,?,?,?)",
            [$request->asignatura_id,$request->idusuario,$request->url,$request->actividad,$request->pagina,$request->periodo_id,$request->tipo]);
            return "se guardo correctamente";
        }else{
            if($request->usuario == null || $request->usuario == '' || $request->usuario == 'null'){
                return "no existe usuario";
            }
            DB::INSERT("INSERT INTO  actividad_historico(asignatura_id,idusuario,url,actividad,pagina,periodo_id,tipo) VALUES(?,?,?,?,?,?,?)",
            [$request->asignatura_id,$request->usuario,$request->url,$request->actividad,$request->pagina,$request->periodo_id,'1']);
            return "se guardo correctamente";
        }


    }
    //api:get/historicoActividades
    public function historicoActividades(Request $request){
        $historico = DB::SELECT("SELECT a.*,
        CONCAT(u.nombres, ' ',u.apellidos )AS persona, g.deskripsi AS rol, asig.nombreasignatura,i.nombreInstitucion,p.periodoescolar AS periodo
        FROM actividad_historico a
        LEFT JOIN asignatura asig ON asig.idasignatura = a.asignatura_id
        LEFT JOIN usuario u ON a.idusuario = u.idusuario
        LEFT JOIN sys_group_users g ON u.id_group = g.id
        LEFT JOIN institucion i ON u.institucion_idInstitucion = i.idInstitucion
        LEFT JOIN periodoescolar p ON p.idperiodoescolar = a.periodo_id
        WHERE a.idusuario <> 0
        AND a.tipo = '$request->tipo'
        ORDER BY a.id DESC
        ");
        return $historico;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\actividad_animacion  $actividad_animacion
     * @return \Illuminate\Http\Response
     */
    public function getAsignaturas()
    {
        $asignatura = DB::SELECT("SELECT * FROM asignatura WHERE estado = '1' and tipo_asignatura = '1'");
        return $asignatura;

    }
    // Obtener una asignatura para los PROYECTOS del strapi
    public function asignaturaIdProyectos($id)
    {
        $asignatura = DB::SELECT("SELECT * FROM asignatura WHERE idasignatura = $id ");
        return $asignatura;

    }

    public function temasUnidad(Request $request)
    {
        $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id, t.nombre_tema AS label, t.id_asignatura, t.unidad, t.id_unidad, a.nombreasignatura, t.clasificacion FROM temas t, asignatura a WHERE t.id_asignatura = a.idasignatura AND t.unidad = $request->unidad AND t.id_asignatura = $request->asignatura AND t.estado=1 ORDER BY  t.nombre_tema + 0 ASC");
        //     foreach ($temas as $key => $tema) {
        //         $validateUnidades = DB::SELECT("
        //             SELECT * FROM unidades_libros u
        //             WHERE u.id_unidad_libro = $tema->id_unidad
        //         ");

        //         if (count($validateUnidades) == 0) {
        //             unset($temas[$key]); // 🔥 esto sí elimina el tema del array
        //         }
        //     }
        // // 🔥 Reindexar para quitar las claves "0", "1", etc.
        // $temas = array_values($temas);
        return $temas;
    }


    public function temasUnidadID($id)
    {
        $temas = DB::SELECT("SELECT * FROM temas t WHERE t.id_unidad = $id ORDER BY t.nombre_tema + 0 ASC");

        return $temas;
    }


    public function actividades_x_Tema($id)
    {
        $temas = DB::SELECT("SELECT a.*, t.id as idtema, t.nombre_tema, asig.idasignatura, asig.nombreasignatura, t.id_unidad, ul.id_unidad_libro, ul.id_libro, ul.unidad, ul.nombre_unidad, u.nombres, u.apellidos,  lib.weblibro
        FROM actividades_animaciones a, temas t, asignatura asig, unidades_libros ul, usuario u, libro lib
        WHERE t.id = a.id_tema
        and lib.asignatura_idasignatura = asig.idasignatura
        and t.id_unidad = ul.id_unidad_libro
        and a.id_usuario = u.idusuario
        and t.id_asignatura = asig.idasignatura
        and a.id_tema = $id");

        return $temas;
    }
    public function actividades_x_Libro($id){
        $actividades= DB::SELECT("SELECT t.nombre_tema, a.*, u.nombres, u.apellidos, ul.nombre_unidad, ul.unidad, lib.weblibro
        FROM temas t, actividades_animaciones a, usuario u, unidades_libros ul, libro lib
        WHERE t.id_asignatura = $id
        and t.id_asignatura = lib.asignatura_idasignatura
        and a.id_tema = t.id
        and a.id_usuario = u.idusuario
        and ul.id_unidad_libro = t.id_unidad");
        return $actividades;
    }
    public function actividadesBuscarFechas($fecha)
    {
        $buscar = DB::SELECT("SELECT a.*, t.nombre_tema,  asig.idasignatura, asig.nombreasignatura, t.id_unidad, ul.id_unidad_libro, ul.id_libro, ul.unidad, ul.nombre_unidad, u.nombres, u.apellidos, lib.weblibro
        FROM actividades_animaciones a, temas t, asignatura asig, unidades_libros ul, usuario u, libro lib
        WHERE t.id = a.id_tema
        and lib.asignatura_idasignatura = asig.idasignatura
        and t.id_unidad = ul.id_unidad_libro
        and a.id_usuario = u.idusuario
        and t.id_asignatura = asig.idasignatura
        and a.created_at LIKE '$fecha%'
        ORDER BY a.id_item DESC");

        return $buscar;
    }

    public function actividades_libros_unidad($id_unidad)
    {
        $actividades = DB::SELECT('SELECT * FROM actividades_animaciones aa, temas t WHERE aa.id_tema = t.id AND aa.tipo = 0 AND t.id_unidad = ?',[$id_unidad]);

        return $actividades;
    }

    public function animaciones_libros_unidad($id_unidad)
    {
        $animaciones = DB::SELECT('SELECT * FROM actividades_animaciones aa, temas t WHERE aa.id_tema = t.id AND aa.tipo = 1 AND t.id_unidad = ?',[$id_unidad]);

        return $animaciones;
    }

    ////desglose temas
    public function actividades_libros_unidad_tema($id_tema)
    {
        $actividades = DB::SELECT('SELECT * FROM actividades_animaciones aa, temas t WHERE aa.id_tema = t.id AND aa.tipo = 0 AND t.id = ?',[$id_tema]);

        return $actividades;
    }

    public function animaciones_libros_unidad_tema($id_tema)
    {
        $animaciones = DB::SELECT('SELECT * FROM actividades_animaciones aa, temas t WHERE aa.id_tema = t.id AND aa.tipo = 1 AND t.id = ?',[$id_tema]);

        return $animaciones;
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
    public function store(Request $request)
    {
        if( $request->id_item ){
            $actividad = actividad_animacion::find($request->id_item);
        }else{
            $actividad = new actividad_animacion();
        }

        $actividad->id_usuario = $request->id_usuario;
        $actividad->id_tema = $request->id_tema;
        $actividad->tipo = $request->tipo;
        $actividad->link = $request->link;
        $actividad->page = $request->page;

        $actividad->save();

        return $actividad;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\actividad_animacion  $actividad_animacion
     * @return \Illuminate\Http\Response
     */
    public function show(actividad_animacion $actividad_animacion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\actividad_animacion  $actividad_animacion
     * @return \Illuminate\Http\Response
     */
    public function edit(actividad_animacion $actividad_animacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\actividad_animacion  $actividad_animacion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, actividad_animacion $actividad_animacion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\actividad_animacion  $actividad_animacion
     * @return \Illuminate\Http\Response
     */

    public function destroy(actividad_animacion $actividad_animacion)
    {

    }
    public function eliminaActividad($id_item)
    {
        $actividad = actividad_animacion::find($id_item);
        $actividad->delete();
    }
    public function carpetaActividades($id)
    {
        $actividad = DB::SELECT("SELECT weblibro from libro WHERE asignatura_idasignatura = $id");

        return $actividad;
    }

    ////VARIOS CONTROLLER NO FUNCIONA, SE AGREGA AQUI LOS METODOS
	public function f_deleteVarios(Request $request)
    {
        $dato = Varios::find($request->id);
        $dato->delete();
        return $dato;

    }
    public function f_publicVarios()
    {
        $dato = Varios::where('estado',1)
        ->orderby('updated_at','desc')
        ->get();
        return $dato;
    }

	public function f_todoVarios()
    {
        $query = DB::SELECT("SELECT  * from varios ORDER BY id DESC");
        // $query = Varios::all();
        return $query;
    }

    //INICIO METODOS JEYSON
    public function Procesar_MetodosGet_ActividadAnimacionController(Request $request)
    {
        $action = $request->query('action'); // Leer el parámetro `action` desde la URL
        switch ($action) {
            case 'Buscar_Actividades_x_Asignatura':
                return $this->Buscar_Actividades_x_Asignatura($request);
            default:
                return response()->json(['error' => 'Acción no válida'], 400);
        }
    }
    public function Buscar_Actividades_x_Asignatura($request)
    {
        $query = DB::SELECT("SELECT an.*, te.nombre_tema, te.id_asignatura, te.unidad, te.id_unidad, ul.nombre_unidad
            FROM actividades_animaciones an
            LEFT JOIN temas te ON an.id_tema = te.id
            LEFT JOIN unidades_libros ul ON te.id_unidad = ul.id_unidad_libro
            WHERE te.id_asignatura = $request->id_asignatura
            ORDER BY te.unidad ASC, te.nombre_tema ASC");
        // $query = Varios::all();
        return $query;
    }
    public function Transferencia_ActividadesyAnimaciones_Asignatura(Request $request)
    {
        $actividadesSeleccionadas = $request->Act_Anim_Seleccionadas;
        $asignaturaDestino = $request->asignatura_recibir_transferencia;
        $userCreated = $request->user_created;

        if (empty($actividadesSeleccionadas) || !is_array($actividadesSeleccionadas) || !$asignaturaDestino || !isset($asignaturaDestino['idasignatura']) || !$userCreated) {
            return response()->json([
                "status" => 0,
                "message" => "Datos incompletos: asegúrese de enviar Act_Anim_Seleccionadas (array), asignatura_recibir_transferencia (con idasignatura) y user_created."
            ]);
        }

        $transferidos = [];
        $noEncontrados = [];
        $yaRegistradas = [];

        // Obtener nombre de la asignatura destino una sola vez
        $asignaturaDestinoData = DB::table('asignatura')
            ->where('idasignatura', $asignaturaDestino['idasignatura'])
            ->first();

        foreach ($actividadesSeleccionadas as $actividadSel) {
            if (!isset($actividadSel['id_item'])) {
                $noEncontrados[] = [
                    'id_item' => null,
                    'razon' => 'Falta id_item en el elemento enviado'
                ];
                continue;
            }

            try {
                $idItem = $actividadSel['id_item'];

                $actividadOrigen = DB::table('actividades_animaciones')
                    ->where('id_item', $idItem)
                    ->first();

                if (!$actividadOrigen) {
                    $noEncontrados[] = [
                        'id_item' => $idItem,
                        'razon' => 'Actividad origen no encontrada en actividades_animaciones'
                    ];
                    continue;
                }

                $temaOrigen = DB::table('temas')->where('id', $actividadOrigen->id_tema)->first();
                if (!$temaOrigen) {
                    $noEncontrados[] = [
                        'id_item' => $idItem,
                        'razon' => 'Tema de origen no encontrado'
                    ];
                    continue;
                }

                $unidadOrigen = DB::table('unidades_libros')
                    ->where('id_unidad_libro', $temaOrigen->id_unidad)
                    ->where('unidad', $temaOrigen->unidad)
                    ->first();

                if (!$unidadOrigen) {
                    $noEncontrados[] = [
                        'id_item' => $idItem,
                        'razon' => 'Unidad de origen no encontrada en unidades_libros',
                        'nombre_tema_origen' => $temaOrigen->nombre_tema,
                    ];
                    continue;
                }

                $temaDestino = DB::table('temas')
                    ->where('nombre_tema', $temaOrigen->nombre_tema)
                    ->where('unidad', $temaOrigen->unidad)
                    ->where('id_asignatura', $asignaturaDestino['idasignatura'])
                    ->first();

                if (!$temaDestino) {
                    $noEncontrados[] = [
                        'id_item' => $idItem,
                        'nombre_tema_origen' => $temaOrigen->nombre_tema,
                        'nombre_unidad_origen' => $unidadOrigen->nombre_unidad,
                        'unidad_origen' => $unidadOrigen->unidad,
                        'asignatura_destino' => $asignaturaDestinoData->nombreasignatura ?? '',
                        'razon' => 'No se encontró el tema correspondiente en la asignatura destino'
                    ];
                    continue;
                }

                $unidadDestino = DB::table('unidades_libros')
                    ->where('id_unidad_libro', $temaDestino->id_unidad)
                    ->where('unidad', $temaDestino->unidad)
                    ->first();

                if (!$unidadDestino) {
                    $noEncontrados[] = [
                        'id_item' => $idItem,
                        'nombre_tema_origen' => $temaOrigen->nombre_tema,
                        'nombre_unidad_origen' => $unidadOrigen->nombre_unidad,
                        'nombre_tema_destino' => $temaDestino->nombre_tema,
                        'asignatura_destino' => $asignaturaDestinoData->nombreasignatura ?? '',
                        'razon' => 'No se encontró la unidad correspondiente en la asignatura destino'
                    ];
                    continue;
                }

                if ($unidadDestino->unidad != $unidadOrigen->unidad || trim($unidadDestino->nombre_unidad) != trim($unidadOrigen->nombre_unidad)) {
                    $noEncontrados[] = [
                        'id_item' => $idItem,
                        'nombre_tema_origen' => $temaOrigen->nombre_tema,
                        'nombre_unidad_origen' => $unidadOrigen->nombre_unidad,
                        'nombre_unidad_destino' => $unidadDestino->nombre_unidad,
                        'asignatura_destino' => $asignaturaDestinoData->nombreasignatura ?? '',
                        'razon' => 'La unidad destino no coincide con la unidad origen'
                    ];
                    continue;
                }

                $existe = DB::table('actividades_animaciones')
                    ->where('id_tema', $temaDestino->id)
                    ->where('tipo', $actividadOrigen->tipo)
                    ->where('link', $actividadOrigen->link)
                    ->where('page', $actividadOrigen->page)
                    ->where('estado', $actividadOrigen->estado)
                    ->exists();

                if ($existe) {
                    $yaRegistradas[] = [
                        'id_item_origen' => $actividadOrigen->id_item,
                        'nombre_tema_origen' => $temaOrigen->nombre_tema,
                        'nombre_tema_destino' => $temaDestino->nombre_tema,
                        'nombre_unidad_origen' => $unidadOrigen->nombre_unidad,
                        'nombre_unidad_destino' => $unidadDestino->nombre_unidad,
                        'asignatura_destino' => $asignaturaDestinoData->nombreasignatura ?? '',
                        'tipo' => $actividadOrigen->tipo == 0 ? 'Actividad' : 'Animación',
                        'link' => $actividadOrigen->link,
                        'page' => $actividadOrigen->page,
                        'razon' => 'Ya existía una actividad con los mismos datos en destino'
                    ];
                    continue;
                }

                $nuevoId = DB::table('actividades_animaciones')->insertGetId([
                    'id_usuario' => $userCreated,
                    'id_tema'    => $temaDestino->id,
                    'tipo'       => $actividadOrigen->tipo,
                    'link'       => $actividadOrigen->link,
                    'page'       => $actividadOrigen->page,
                    'estado'     => $actividadOrigen->estado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $transferidos[] = [
                    'nuevo_id_item' => $nuevoId,
                    'nombre_tema_origen' => $temaOrigen->nombre_tema,
                    'nombre_tema_destino' => $temaDestino->nombre_tema,
                    'nombre_unidad_origen' => $unidadOrigen->nombre_unidad,
                    'nombre_unidad_destino' => $unidadDestino->nombre_unidad,
                    'asignatura_destino' => $asignaturaDestinoData->nombreasignatura ?? '',
                    'tipo' => $actividadOrigen->tipo == 0 ? 'Actividad' : 'Animación',
                    'link' => $actividadOrigen->link,
                    'page' => $actividadOrigen->page,
                    'mensaje' => 'Transferido correctamente'
                ];

            } catch (\Exception $e) {
                $noEncontrados[] = [
                    'id_item' => $actividadSel['id_item'] ?? null,
                    'razon' => 'Error inesperado: ' . $e->getMessage()
                ];
                continue;
            }
        }

        return response()->json([
            "status" => 1,
            "message" => "Proceso finalizado",
            "total_procesados" => count($actividadesSeleccionadas),
            "total_transferidos" => count($transferidos),
            "total_ya_existian" => count($yaRegistradas),
            "total_no_transferidos" => count($noEncontrados),
            "transferidos" => $transferidos,
            "ya_existian" => $yaRegistradas,
            "no_transferidos" => $noEncontrados
        ]);
    }

    private function Restaurar_Autoincremento(){
        // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto
        $ultimoId  = actividad_animacion::max('id_item') + 1;
        DB::statement('ALTER TABLE actividades_animaciones AUTO_INCREMENT = ' . $ultimoId);
    }

    //FIN METODOS JEYSON
}
