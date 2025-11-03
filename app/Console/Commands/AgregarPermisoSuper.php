<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AgregarPermisoSuper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permisos:super {action} {usuario_id?} {id_group?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar permisos super. Acciones: add, remove, list';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'add':
                return $this->addPermiso();
            case 'remove':
                return $this->removePermiso();
            case 'list':
                return $this->listPermisos();
            default:
                $this->error('Acción no válida. Use: add, remove, list');
                return 1;
        }
    }

    private function addPermiso()
    {
        $usuario_id = $this->argument('usuario_id');
        $id_group = $this->argument('id_group');

        if (!$usuario_id) {
            $usuario_id = $this->ask('Ingrese el usuario_id:');
        }

        if (!$id_group) {
            $id_group = $this->ask('Ingrese el id_group:');
        }

        // Verificar si ya existe
        $existe = DB::table('permisos_super')
            ->where('usuario_id', $usuario_id)
            ->where('id_group', $id_group)
            ->exists();

        if ($existe) {
            $this->warn("El permiso ya existe para usuario_id: $usuario_id, id_group: $id_group");
            return 1;
        }

        // Agregar permiso
        DB::table('permisos_super')->insert([
            'usuario_id' => $usuario_id,
            'id_group' => $id_group,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->info("✅ Permiso super agregado exitosamente para usuario_id: $usuario_id, id_group: $id_group");
        return 0;
    }

    private function removePermiso()
    {
        $usuario_id = $this->argument('usuario_id');
        $id_group = $this->argument('id_group');

        if (!$usuario_id) {
            $usuario_id = $this->ask('Ingrese el usuario_id:');
        }

        if (!$id_group) {
            $id_group = $this->ask('Ingrese el id_group:');
        }

        $deleted = DB::table('permisos_super')
            ->where('usuario_id', $usuario_id)
            ->where('id_group', $id_group)
            ->delete();

        if ($deleted) {
            $this->info("✅ Permiso super eliminado exitosamente para usuario_id: $usuario_id, id_group: $id_group");
        } else {
            $this->warn("❌ No se encontró el permiso para usuario_id: $usuario_id, id_group: $id_group");
        }

        return 0;
    }

    private function listPermisos()
    {
        $permisos = DB::table('permisos_super as ps')
            ->leftJoin('usuario as u', 'ps.usuario_id', '=', 'u.idusuario')
            ->select(
                'ps.id',
                'ps.usuario_id',
                'ps.id_group',
                'u.nombres',
                'u.apellidos',
                'u.email',
                'ps.created_at'
            )
            ->get();

        if ($permisos->isEmpty()) {
            $this->info('No hay permisos super configurados.');
            return 0;
        }

        $headers = ['ID', 'Usuario ID', 'ID Group', 'Nombres', 'Apellidos', 'Email', 'Creado'];
        $rows = $permisos->map(function ($permiso) {
            return [
                $permiso->id,
                $permiso->usuario_id,
                $permiso->id_group,
                $permiso->nombres ?? 'N/A',
                $permiso->apellidos ?? 'N/A',
                $permiso->email ?? 'N/A',
                $permiso->created_at
            ];
        })->toArray();

        $this->table($headers, $rows);
        return 0;
    }
}
