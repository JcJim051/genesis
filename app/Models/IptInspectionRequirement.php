<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IptInspectionRequirement extends Model
{
    protected $fillable = [
        'inspection_id',
        'requirement_id',
        'aplica',
    ];

    protected $casts = [
        'aplica' => 'boolean',
    ];

    public function inspection()
    {
        return $this->belongsTo(IptInspection::class, 'inspection_id');
    }

    public function requirement()
    {
        return $this->belongsTo(IptTemplateRequirement::class, 'requirement_id');
    }
}
