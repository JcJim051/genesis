<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class EncuestaPregunta extends Model
{
    use CrudTrait;

    protected $table = 'encuesta_preguntas';

    protected $fillable = [
        'encuesta_id',
        'texto',
        'orden',
    ];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class);
    }

    public function opciones()
    {
        return $this->hasMany(EncuestaOpcion::class, 'pregunta_id');
    }
}
