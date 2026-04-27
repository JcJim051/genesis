<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IptTemplateRequirement extends Model
{
    protected $fillable = [
        'template_id',
        'nombre',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(IptTemplate::class, 'template_id');
    }
}
