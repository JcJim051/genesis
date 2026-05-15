<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsteoTemplateSection extends Model
{
    protected $fillable = [
        'template_id',
        'titulo',
        'orden',
    ];

    public function template()
    {
        return $this->belongsTo(OsteoTemplate::class, 'template_id');
    }

    public function fields()
    {
        return $this->hasMany(OsteoTemplateField::class, 'section_id')->orderBy('orden');
    }
}

