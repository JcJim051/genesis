<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsteoEvaluationAnswer extends Model
{
    protected $fillable = [
        'evaluation_id',
        'field_id',
        'lado',
        'valor',
        'observacion',
    ];

    public function evaluation()
    {
        return $this->belongsTo(OsteoEvaluation::class, 'evaluation_id');
    }

    public function field()
    {
        return $this->belongsTo(OsteoTemplateField::class, 'field_id');
    }
}

