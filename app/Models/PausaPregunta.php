<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class PausaPregunta extends Model
{
    use CrudTrait;

    protected $table = 'pausa_preguntas';

    protected $fillable = [
        'formulario_id',
        'texto',
        'tipo',
        'orden',
    ];

    public function formulario()
    {
        return $this->belongsTo(PausaFormulario::class, 'formulario_id');
    }

    public function opciones()
    {
        return $this->hasMany(PausaOpcion::class, 'pregunta_id');
    }
}
