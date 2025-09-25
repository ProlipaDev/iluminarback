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
        $temas = DB::SELECT("SELECT t.nombre_tema, t.id AS id, t.nombre_tema AS label, t.id_asignatura, t.unidad, a.nombreasignatura, t.clasificacion FROM temas t, asignatura a WHERE t.id_asignatura = a.idasignatura AND t.unidad = $request->unidad AND t.id_asignatura = $request->asignatura AND t.estado=1 ORDER BY  t.nombre_tema + 0 ASC");

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
    public function Transferencia_ActividadesyAnimaciones_Asignatura(Request $request)
    {
        $origen = $request->asignatura_a_transferir;
        $destino = $request->asignatura_recibir_transferencia;

        if (!$origen || !$destino) {
            return response()->json([
                "status" => 0,
                "message" => "Asignaturas no válidas"
            ]);
        }

        // 1. Obtener todos los temas de la asignatura origen
        $TemasyUnidadesOrigen = DB::table('temas')
            ->where('id_asignatura', $origen["idasignatura"])
            ->get();

        // 2. Añadir actividades a cada tema
        foreach ($TemasyUnidadesOrigen as $tema) {
            $tema->actividades = DB::table('actividades_animaciones')
                ->where('id_tema', $tema->id)
                ->get();
        }

        // 3. Obtener todas las actividades de los temas origen
        $idsTemasOrigen = $TemasyUnidadesOrigen->pluck('id')->toArray();
        $ActividadesOrigen = [];
        if (!empty($idsTemasOrigen)) {
            $ActividadesOrigen = DB::table('actividades_animaciones')
                ->whereIn('id_tema', $idsTemasOrigen)
                ->get();
        }

        // 4. Eliminar previamente todas las actividades existentes de la asignatura destino
        $temasDestino = DB::table('temas')
            ->where('id_asignatura', $destino["idasignatura"])
            ->pluck('id')
            ->toArray();

        if (!empty($temasDestino)) {
            DB::table('actividades_animaciones')
                ->whereIn('id_tema', $temasDestino)
                ->delete();
        }
        $this->Restaurar_Autoincremento();
        // 5. Preparar arrays para seguimiento
        $transferidos = []; // actividades clonadas exitosamente
        $noEncontrados = []; // temas que no se encontraron en destino

        foreach ($ActividadesOrigen as $actividad) {
            $temaOrigen = DB::table('temas')->where('id', $actividad->id_tema)->first();
            if (!$temaOrigen) {
                $noEncontrados[] = [
                    'actividad_id' => $actividad->id_item ?? null,
                    'razon' => 'Tema origen no encontrado',
                    'actividad' => $actividad
                ];
                continue;
            }

            // Buscar el tema equivalente en destino
            $temaDestino = DB::table('temas')
                ->where('nombre_tema', $temaOrigen->nombre_tema)
                ->where('unidad', $temaOrigen->unidad)
                ->where('id_asignatura', $destino["idasignatura"])
                ->first();

            if (!$temaDestino) {
                $noEncontrados[] = [
                    'tema_origen_id' => $temaOrigen->id,
                    'nombre_tema' => $temaOrigen->nombre_tema,
                    'unidad' => $temaOrigen->unidad,
                    'razon' => 'No se encontró el tema con su unidad en la asignatura destino. (Verificar la existencia del tema con el mismo nombre y unidad en la asignatura destino)',
                ];
                continue;
            }

            // Insertar nueva actividad en el tema destino
            $nuevoId = DB::table('actividades_animaciones')->insertGetId([
                "id_usuario" => $actividad->id_usuario,
                "id_tema"    => $temaDestino->id,
                "tipo"       => $actividad->tipo,
                "link"       => $actividad->link,
                "page"       => $actividad->page,
                "estado"     => $actividad->estado,
                "created_at" => $actividad->created_at,
                "updated_at" => $actividad->updated_at,
            ]);

            $transferidos[] = [
                'actividad_origen_id' => $actividad->id_item ?? null,
                'tema_origen_id'      => $temaOrigen->id,
                'tema_destino_id'     => $temaDestino->id,
                'nuevo_actividad_id'  => $nuevoId
            ];
        }

        // 6. Enriquecer transferidos con nombre_tema destino
        $transferidosConNombre = [];
        if (!empty($transferidos)) {
            $idsTemasDestino = collect($transferidos)->pluck('tema_destino_id')->toArray();

            $temasDestinoInfo = DB::table('temas')
                ->whereIn('id', $idsTemasDestino)
                ->pluck('nombre_tema', 'id');

            foreach ($transferidos as $t) {
                $transferidosConNombre[] = [
                    'actividad_origen_id' => $t['actividad_origen_id'],
                    'tema_origen_id'      => $t['tema_origen_id'],
                    'tema_destino_id'     => $t['tema_destino_id'],
                    'nuevo_actividad_id'  => $t['nuevo_actividad_id'],
                    'nombre_tema'         => $temasDestinoInfo[$t['tema_destino_id']] ?? null
                ];
            }
        }

        return response()->json([
            "status" => 1,
            "message" => "Transferencia completada",
            "TemasyUnidadesOrigen" => $TemasyUnidadesOrigen,
            "ActividadesOrigen" => $ActividadesOrigen,
            "Transferidos" => $transferidosConNombre,
            "NoEncontrados" => $noEncontrados
        ]);
    }

    private function Restaurar_Autoincremento(){
        // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto
        $ultimoId  = actividad_animacion::max('id_item') + 1;
        DB::statement('ALTER TABLE actividades_animaciones AUTO_INCREMENT = ' . $ultimoId);
    }

    //FIN METODOS JEYSON
}
