<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;
use App\Models\Sucursal;

class Incapacidad extends Model
{
    use CrudTrait;

    protected $table = 'incapacidades';

    protected $fillable = [
        'cedula',
        'cliente_id',
        'sucursal_id',
        'fecha_inicio',
        'fecha_fin',
        'diagnostico',
        'codigo_cie10',
        'origen',
        'dias_incapacidad',
        'payload',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'payload' => 'array',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function programaCasos()
    {
        return $this->belongsToMany(ProgramaCaso::class, 'programa_caso_incapacidad');
    }
}
