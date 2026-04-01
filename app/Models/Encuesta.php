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
        'cliente_id',
        'sucursal_id',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function preguntas()
    {
        return $this->hasMany(EncuestaPregunta::class, 'encuesta_id');
    }
}
