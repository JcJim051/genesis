<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IptTemplateQuestion extends Model
{
    protected $fillable = [
        'section_id',
        'texto',
        'tipo',
        'orden',
        'scorable',
        'si_score',
        'score_on_answer',
    ];

    protected $casts = [
        'scorable' => 'boolean',
        'si_score' => 'integer',
    ];

    public function section()
    {
        return $this->belongsTo(IptTemplateSection::class, 'section_id');
    }
}
