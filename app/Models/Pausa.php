<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Pausa extends Model
{
    use CrudTrait;

    protected $table = 'pausas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'video_url',
        'tiempo_minimo_segundos',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function formulario()
    {
        return $this->hasOne(PausaFormulario::class, 'pausa_id');
    }
}
