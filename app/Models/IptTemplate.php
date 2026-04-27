<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class IptTemplate extends Model
{
    use CrudTrait;

    protected $fillable = [
        'cliente_id',
        'nombre_publico',
        'codigo',
        'segmento',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function sections()
    {
        return $this->hasMany(IptTemplateSection::class, 'template_id')->orderBy('orden');
    }

    public function riskRules()
    {
        return $this->hasMany(IptTemplateRiskRule::class, 'template_id')->orderBy('orden');
    }

    public function requirements()
    {
        return $this->hasMany(IptTemplateRequirement::class, 'template_id')->orderBy('orden');
    }

    public function inspections()
    {
        return $this->hasMany(IptInspection::class, 'template_id');
    }
}
