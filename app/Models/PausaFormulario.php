<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class PausaFormulario extends Model
{
    use CrudTrait;

    protected $table = 'pausa_formularios';

    protected $fillable = [
        'pausa_id',
    ];

    public function pausa()
    {
        return $this->belongsTo(Pausa::class, 'pausa_id');
    }

    public function preguntas()
    {
        return $this->hasMany(PausaPregunta::class, 'formulario_id');
    }

    public function getNombreAttribute(): string
    {
        return (string) ($this->pausa?->nombre ?? ('Pausa #' . $this->pausa_id));
    }
}
