<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsteoTemplateField extends Model
{
    protected $fillable = [
        'section_id',
        'key_name',
        'label',
        'tipo',
        'options_json',
        'meta_json',
        'required',
        'orden',
    ];

    protected $casts = [
        'options_json' => 'array',
        'meta_json' => 'array',
        'required' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(OsteoTemplateSection::class, 'section_id');
    }
}

