<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class IptInspection extends Model
{
    use CrudTrait;

    protected $fillable = [
        'programa_caso_id',
        'empleado_id',
        'cliente_id',
        'sucursal_id',
        'template_id',
        'tipo',
        'initial_inspection_id',
        'fecha_inspeccion',
        'puntaje_total',
        'nivel_riesgo',
        'fecha_proximo_seguimiento_sugerida',
        'hallazgos',
        'recomendaciones',
        'accion',
        'responsable',
        'estado',
        'seguimiento_exitoso',
        'created_by',
    ];

    protected $casts = [
        'fecha_inspeccion' => 'date',
        'fecha_proximo_seguimiento_sugerida' => 'date',
        'seguimiento_exitoso' => 'boolean',
        'puntaje_total' => 'integer',
    ];

    public function programaCaso()
    {
        return $this->belongsTo(ProgramaCaso::class, 'programa_caso_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function template()
    {
        return $this->belongsTo(IptTemplate::class, 'template_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function initialInspection()
    {
        return $this->belongsTo(self::class, 'initial_inspection_id');
    }

    public function followups()
    {
        return $this->hasMany(self::class, 'initial_inspection_id')->orderBy('fecha_inspeccion');
    }

    public function answers()
    {
        return $this->hasMany(IptInspectionAnswer::class, 'inspection_id');
    }

    public function requirements()
    {
        return $this->hasMany(IptInspectionRequirement::class, 'inspection_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
