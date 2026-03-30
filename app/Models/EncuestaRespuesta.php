<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class EncuestaRespuesta extends Model
{
    use CrudTrait;

    protected $table = 'encuesta_respuestas';

    protected $fillable = [
        'encuesta_id',
        'envio_id',
        'empleado_id',
        'token',
        'estado',
        'puntaje_total',
        'respondido_en',
    ];

    protected $casts = [
        'respondido_en' => 'datetime',
    ];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class);
    }

    public function envio()
    {
        return $this->belongsTo(EncuestaEnvio::class, 'envio_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function items()
    {
        return $this->hasMany(EncuestaRespuestaItem::class, 'respuesta_id');
    }
}
