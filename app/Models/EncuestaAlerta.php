<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class EncuestaAlerta extends Model
{
    use CrudTrait;

    protected $table = 'encuesta_alertas';

    protected $fillable = [
        'encuesta_id',
        'programa_id',
        'empleado_id',
        'cliente_id',
        'sucursal_id',
        'puntaje',
        'estado',
        'asignado_a',
    ];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class);
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function asignado()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }
}
