<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class EncuestaEnvio extends Model
{
    use CrudTrait;

    protected $table = 'encuesta_envios';

    protected $fillable = [
        'encuesta_id',
        'cliente_id',
        'sucursal_id',
        'fecha_envio',
        'fecha_expiracion',
        'procesado_en',
    ];

    protected $casts = [
        'fecha_envio' => 'date',
        'fecha_expiracion' => 'date',
        'procesado_en' => 'datetime',
    ];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function respuestas()
    {
        return $this->hasMany(EncuestaRespuesta::class, 'envio_id');
    }
}
