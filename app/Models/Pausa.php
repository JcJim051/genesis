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
        'cliente_id',
        'sucursal_id',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function formulario()
    {
        return $this->hasOne(PausaFormulario::class, 'pausa_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
