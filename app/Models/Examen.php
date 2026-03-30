<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    use CrudTrait;

    protected $table = 'examenes';

    protected $fillable = [
        'cedula',
        'fecha_examen',
        'tipo_examen',
        'resultado_apto',
        'restricciones',
        'recomendaciones',
        'payload',
    ];

    protected $casts = [
        'fecha_examen' => 'date',
        'payload' => 'array',
    ];
}
