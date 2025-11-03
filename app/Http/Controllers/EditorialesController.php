<?php

namespace App\Http\Controllers;

use App\Models\Editoriales;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EditorialesController extends Controller
{
    public function VerifcarMetodosGet_Editoriales(Request $request)
    {
        $action = $request->query('action'); // Leer el parámetro `action` desde la URL

        switch ($action) {
            case 'GetTablaComentarioEditoriales':
                return $this->GetTablaComentarioEditoriales();
            case 'Get_EditorialesContador':
                return $this->Get_EditorialesContador($request);
            case 'Get_Editoriales':
                return $this->Get_Editoriales();
            case 'Get_EditorialesActivo':
                return $this->Get_EditorialesActivo();
            case 'Get_Editoriales_xfiltro':
                return $this->Get_Editoriales_xfiltro($request);
            default:
                return response()->json(['error' => 'Acción no válida'], 400);
        }
    }
    public function VerifcarMetodosPost_Editoriales(Request $request)
    {
        $action = $request->input('action'); // Recibir el parámetro 'action'

        switch ($action) {
            case 'Post_Registrar_modificar_Editoriales':
                return $this->Post_Registrar_modificar_Editoriales($request);
            case 'ActualizarComentarioEditoriales':
                return $this->ActualizarComentarioEditoriales($request);
            case 'Desactivar_Editoriales':
                return $this->Desactivar_Editoriales($request);
            case 'Post_Eliminar_Editoriales':
                return $this->Post_Eliminar_Editoriales($request);
            default:
                return response()->json(['error' => 'Acción no válida'], 400);
        }
    }
    public function GetTablaComentarioEditoriales()
    {
        $commentQuery = DB::select("
            SELECT table_comment
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = ?
        ", ['editoriales']);

        $comment = !empty($commentQuery) ? $commentQuery[0]->table_comment : null;

        return $comment;
    }

    public function Get_EditorialesContador(Request $request){
        $valormasuno = $request->conteo+1;
        $query = DB::SELECT("SELECT edi_id FROM editoriales LIMIT $valormasuno");
        $conteogeneral = count($query);
        if($conteogeneral<$valormasuno){
            return response()->json(['mensaje' => 'data_menor', 'conteo' => $conteogeneral]);
        }else if($conteogeneral==$valormasuno){
            return response()->json(['mensaje' => 'data_igual', 'conteo' => $conteogeneral]);
        }
    }

    public function Get_Editoriales(){
        $query = DB::SELECT("SELECT ed.*, CONCAT(user_edit.nombres,' ',user_edit.apellidos) as name_user_edit,
        CONCAT(user_created.nombres,' ',user_created.apellidos) as name_user_created
        FROM editoriales ed
        LEFT JOIN usuario user_edit ON ed.user_edit = user_edit.idusuario
        LEFT JOIN usuario user_created ON ed.user_created = user_created.idusuario
        ORDER BY ed.edi_id ASC");
        return $query;
    }

    public function Get_EditorialesActivo(){
        $query = DB::SELECT("SELECT ed.*
        FROM editoriales ed
        WHERE ed.edi_estado = '1'
        ORDER BY created_at DESC");
        return $query;
    }

    public function Get_Editoriales_xfiltro(Request $request){
        if ($request->busqueda == 'undefined' || $request->busqueda == 'codigo' || $request->busqueda == '' || $request->busqueda == null) {
            $query = DB::SELECT("SELECT ed.*, ed.edi_id as codigoanterior FROM editoriales ed
            WHERE ed.edi_id LIKE '%$request->razonbusqueda%'
            ORDER BY ed.created_at DESC
            ");
            return $query;
        }
        if ($request->busqueda == 'nombre') {
            $query = DB::SELECT("SELECT ed.*, ed.edi_id as codigoanterior FROM editoriales ed
            WHERE ed.edi_nombre LIKE '%$request->razonbusqueda%'
            ORDER BY ed.created_at DESC
            ");
            return $query;
        }
    }

    public function Post_Registrar_modificar_Editoriales(Request $request)
    {
        try {
            $this->ActualizarAutoIncrementableje();
            $esNuevo = empty($request->edi_id); // Verificamos si se está creando
            // Si se está editando, buscar el registro por ID. Si no, crear nuevo.
            $const_editoriales = $esNuevo ? new Editoriales() : Editoriales::findOrFail($request->edi_id);
            $const_editoriales->edi_nombre = $request->edi_nombre;
            $const_editoriales->user_edit = $request->user_edit;
            $const_editoriales->updated_at = now();
            // Si es nuevo, asignar user_created
            if ($esNuevo) {
                $const_editoriales->user_created = $request->user_created;
                $const_editoriales->user_edit = $request->user_edit;
                $const_editoriales->updated_at = now();
            }
            $const_editoriales->save();

            return response()->json([
                'status' => 1,
                'message' => $esNuevo ? 'Se ha registrado correctamente' : 'Se ha editado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Ocurrió un error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function ActualizarComentarioEditoriales(Request $request)
    {
        DB::beginTransaction();
        try {
            // Obtener el comentario de la solicitud
            $comentario = $request->Actualizar_comentario;
            $tabla = 'editoriales';

            // Escapar el comentario para evitar problemas de inyección SQL
            $comentarioEscapado = DB::getPdo()->quote($comentario);

            // Actualizar el comentario de la tabla
            // DB::statement('ALTER TABLE ' . $tabla . ' COMMENT = ?', [$comentario]);
            DB::statement("ALTER TABLE {$tabla} COMMENT = {$comentarioEscapado}");

            DB::commit();
            return response()->json(["status" => "1", 'message' => 'Comentario actualizado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["status" => "0", 'message' => 'Error al actualizar el comentario: ' . $e->getMessage()], 500);
        }
    }

    public function Desactivar_Editoriales(Request $request)
    {
        try {
            // Validar que venga el ID
            if (!$request->edi_id) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No se ha proporcionado un edi_id válido.'
                ], 400);
            }
            // Buscar la editorial
            $const_editoriales = Editoriales::find($request->edi_id);

            if (!$const_editoriales) {
                return response()->json([
                    'status' => 0,
                    'message' => 'El edi_id no existe en la base de datos.'
                ], 404);
            }
            // Actualizar el estado
            $const_editoriales->edi_estado = $request->edi_estado;
            $const_editoriales->save();
            // Generar mensaje según el estado
            $mensaje = $request->edi_estado == 1 ? 'La editorial se activó correctamente.' : 'La editorial se inactivo correctamente.';
            return response()->json([
                'status' => 1,
                'message' => $mensaje,
                'data' => $const_editoriales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error al actualizar el estado de la editorial: ' . $e->getMessage()
            ], 500);
        }
    }

    public function Post_Eliminar_Editoriales(Request $request)
    {
        //return $request;
        //$Rol = Rol::destroy($request->id);
        $const_editoriales = Editoriales::findOrFail($request->edi_id);
        $const_editoriales->delete();

        // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto
        $this->ActualizarAutoIncrementableje();

        return $const_editoriales;
        //Esta función obtendra el id de la tarea que hayamos seleccionado y la borrará de nuestra BD
    }

    public function ActualizarAutoIncrementableje()
    {
        // Reajustar el autoincremento - estas 2 lineas permite reajustar el autoincrementable por defecto
        $ultimoId  = Editoriales::max('edi_id') + 1;
        DB::statement('ALTER TABLE editoriales AUTO_INCREMENT = ' . $ultimoId);
    }
}
