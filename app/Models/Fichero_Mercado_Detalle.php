<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fichero_Mercado_Detalle extends Model
{
    use HasFactory;
    protected $table = "fichero_mercado_detalle";
    protected $primaryKey = 'fmd_id';
    protected $fillable = [
        'fm_id',
        'fmd_nombre_libro',
        'fmd_niveles_editoriales',
        'created_at',
        'updated_at'
    ];
}
