<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class PausaEvento extends Model
{
    use CrudTrait;

    protected $table = 'pausa_eventos';

    protected $fillable = [
        'participacion_id',
        'tipo',
        'timestamp',
        'metadata',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    public function participacion()
    {
        return $this->belongsTo(PausaParticipacion::class, 'participacion_id');
    }
}
