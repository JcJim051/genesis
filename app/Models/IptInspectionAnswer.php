<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IptInspectionAnswer extends Model
{
    protected $fillable = [
        'inspection_id',
        'question_id',
        'respuesta',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function inspection()
    {
        return $this->belongsTo(IptInspection::class, 'inspection_id');
    }

    public function question()
    {
        return $this->belongsTo(IptTemplateQuestion::class, 'question_id');
    }
}
