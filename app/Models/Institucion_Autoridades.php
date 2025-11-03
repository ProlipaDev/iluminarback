<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institucion_Autoridades extends Model
{
    use HasFactory;

    protected $table  ='institucion_autoridades';

    protected $primaryKey = 'ina_id';
    public $timestamps = false;

    protected $fillable = [
        'ina_nombre',
        'ina_estado',
        'user_created',
        'user_edit',
        'updated_at',
    ];
}
