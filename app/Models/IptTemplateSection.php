<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IptTemplateSection extends Model
{
    protected $fillable = [
        'template_id',
        'titulo',
        'orden',
    ];

    public function template()
    {
        return $this->belongsTo(IptTemplate::class, 'template_id');
    }

    public function questions()
    {
        return $this->hasMany(IptTemplateQuestion::class, 'section_id')->orderBy('orden');
    }
}
