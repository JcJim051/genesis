<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class PausaParticipacionItem extends Model
{
    use CrudTrait;

    protected $table = 'pausa_participacion_items';

    protected $fillable = [
        'participacion_id',
        'pregunta_id',
        'opcion_id',
        'respuesta_texto',
    ];

    public function participacion()
    {
        return $this->belongsTo(PausaParticipacion::class, 'participacion_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(PausaPregunta::class, 'pregunta_id');
    }

    public function opcion()
    {
        return $this->belongsTo(PausaOpcion::class, 'opcion_id');
    }
}
