<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fichero_Mercado_Autoridades extends Model
{
    use HasFactory;
    protected $table = "fichero_mercado_autoridades";
    protected $primaryKey = 'fma_id';
    protected $fillable = [
        'fm_id',
        'usuario_cargo_asignado',
        'fma_cargo',
        'created_at',
        'updated_at'
    ];
}
