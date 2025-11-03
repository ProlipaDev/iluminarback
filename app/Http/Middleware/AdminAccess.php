<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // DEBUG: Log para debugging
        \Log::info('AdminAccess Middleware - Verificando acceso', [
            'url' => $request->url(),
            'is_authenticated' => Auth::check(),
            'user_id' => Auth::check() ? Auth::user()->idusuario : null,
            'user_id_group' => Auth::check() ? Auth::user()->id_group : null,
        ]);

        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            \Log::info('AdminAccess Middleware - Usuario no autenticado');
            
            // Si es una petición AJAX, devolver respuesta JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            
            // Si no es AJAX, redirigir a página de acceso denegado
            return redirect()->route('acceso.denegado');
        }

        // Verificar que el usuario tenga id_group = 1 (administrador)
        $user = Auth::user();
        \Log::info('AdminAccess Middleware - Verificando permisos', [
            'user_idusuario' => $user->idusuario ?? 'NO_DEFINIDO',
            'user_id_group' => $user->id_group ?? 'NO_DEFINIDO',
            'expected_id_group' => 1
        ]);
        
        if (!isset($user->id_group) || !in_array($user->id_group, [1, 26])) {
            \Log::info('AdminAccess Middleware - Acceso denegado por id_group incorrecto');
            
            // Si es una petición AJAX, devolver respuesta JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Acceso denegado. Solo administradores pueden acceder a esta sección'
                ], 403);
            }
            
            // Si no es AJAX, redirigir a página de acceso denegado
            return redirect()->route('acceso.denegado');
        }

        // Verificar que el usuario esté en la tabla permisos_super
        $tienePermisoSuper = DB::table('permisos_super')
            ->where('usuario_id', $user->idusuario)
            ->where('id_group', $user->id_group)
            ->exists();

        \Log::info('AdminAccess Middleware - Verificando permisos_super', [
            'usuario_id' => $user->idusuario,
            'id_group' => $user->id_group,
            'tiene_permiso_super' => $tienePermisoSuper
        ]);

        if (!$tienePermisoSuper) {
            \Log::info('AdminAccess Middleware - Acceso denegado: usuario no está en permisos_super');
            
            // Si es una petición AJAX, devolver respuesta JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Acceso denegado. Usuario no tiene permisos super para acceder a esta sección'
                ], 403);
            }
            
            // Si no es AJAX, redirigir a página de acceso denegado
            return redirect()->route('acceso.denegado');
        }

        \Log::info('AdminAccess Middleware - Acceso permitido');
        return $next($request);
    }
}
