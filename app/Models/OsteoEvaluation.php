<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class OsteoEvaluation extends Model
{
    use CrudTrait;

    protected $fillable = [
        'programa_caso_id',
        'empleado_id',
        'cliente_id',
        'sucursal_id',
        'template_id',
        'fecha_valoracion',
        'estado',
        'evaluador',
        'licencia',
        'cargo_profesional',
        'observaciones',
        'created_by',
    ];

    protected $casts = [
        'fecha_valoracion' => 'date',
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
        return $this->belongsTo(OsteoTemplate::class, 'template_id');
    }

    public function answers()
    {
        return $this->hasMany(OsteoEvaluationAnswer::class, 'evaluation_id');
    }
}

