<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Editoriales extends Model
{
    use HasFactory;

    protected $table  ='editoriales';

    protected $primaryKey = 'edi_id';
    public $timestamps = false;

    protected $fillable = [
        'edi_nombre',
        'edi_estado',
        'user_created',
        'user_edit',
        'updated_at',
    ];
}
