<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class ActaSeguimiento extends Model
{
    use CrudTrait;

    protected $table = 'actas_seguimiento';

    protected $fillable = [
        'reincorporacion_id',
        'created_by_user_id',
        'fecha_acta',
        'contenido',
    ];

    protected $casts = [
        'fecha_acta' => 'date',
    ];

    public function reincorporacion()
    {
        return $this->belongsTo(Reincorporacion::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
