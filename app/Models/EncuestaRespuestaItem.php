<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class EncuestaRespuestaItem extends Model
{
    use CrudTrait;

    protected $table = 'encuesta_respuesta_items';

    protected $fillable = [
        'respuesta_id',
        'pregunta_id',
        'opcion_id',
        'puntaje',
    ];

    public function respuesta()
    {
        return $this->belongsTo(EncuestaRespuesta::class, 'respuesta_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(EncuestaPregunta::class, 'pregunta_id');
    }

    public function opcion()
    {
        return $this->belongsTo(EncuestaOpcion::class, 'opcion_id');
    }
}
