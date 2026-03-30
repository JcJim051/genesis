<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class DiagnosticoProgramaMap extends Model
{
    use CrudTrait;

    protected $table = 'diagnostico_programa_map';

    protected $fillable = [
        'cie10_id',
        'codigo_cie10',
        'diagnostico_texto',
        'programa_id',
        'regla_activa',
        'prioridad',
    ];

    protected $casts = [
        'regla_activa' => 'boolean',
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }

    public function cie10()
    {
        return $this->belongsTo(Cie10::class, 'cie10_id');
    }
}
