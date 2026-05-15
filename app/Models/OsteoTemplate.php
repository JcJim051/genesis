<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class OsteoTemplate extends Model
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
        return $this->belongsTo(Cliente::class);
    }

    public function sections()
    {
        return $this->hasMany(OsteoTemplateSection::class, 'template_id')->orderBy('orden');
    }
}

