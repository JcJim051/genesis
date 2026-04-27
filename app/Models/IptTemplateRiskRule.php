<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IptTemplateRiskRule extends Model
{
    protected $fillable = [
        'template_id',
        'nivel',
        'min_score',
        'max_score',
        'followup_months',
        'orden',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'followup_months' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(IptTemplate::class, 'template_id');
    }
}
