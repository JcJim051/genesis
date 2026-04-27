<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class ColombiaHoliday extends Model
{
    use CrudTrait;

    protected $fillable = [
        'fecha',
        'nombre',
        'anio',
        'activo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'activo' => 'boolean',
        'anio' => 'integer',
    ];
}
