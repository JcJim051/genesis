<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class EncuestaOpcion extends Model
{
    use CrudTrait;

    protected $table = 'encuesta_opciones';

    protected $fillable = [
        'pregunta_id',
        'texto',
        'puntaje',
        'orden',
    ];

    public function pregunta()
    {
        return $this->belongsTo(EncuestaPregunta::class, 'pregunta_id');
    }
}
