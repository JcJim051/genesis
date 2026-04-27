<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class ProgramaCaso extends Model
{
    use CrudTrait;

    protected $table = 'programa_casos';

    protected $fillable = [
        'empleado_id',
        'programa_id',
        'estado',
        'origen',
        'sugerido_por',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }

    public function incapacidades()
    {
        return $this->belongsToMany(Incapacidad::class, 'programa_caso_incapacidad');
    }

    public function historial()
    {
        return $this->hasMany(ProgramaCasoHistorial::class, 'programa_caso_id');
    }

    public function iptInspections()
    {
        return $this->hasMany(IptInspection::class, 'programa_caso_id')->orderByDesc('fecha_inspeccion');
    }
}
