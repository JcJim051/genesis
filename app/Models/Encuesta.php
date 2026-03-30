<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    use CrudTrait;

    protected $table = 'encuestas';

    protected $fillable = [
        'titulo',
        'programa_id',
        'umbral_puntaje',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }

    public function preguntas()
    {
        return $this->hasMany(EncuestaPregunta::class, 'encuesta_id');
    }
}
