<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PausaBadge extends Model
{
    protected $table = 'pausa_badges';

    protected $fillable = [
        'code',
        'nombre',
        'descripcion',
        'activo',
    ];

    public function empleados()
    {
        return $this->belongsToMany(Empleado::class, 'empleado_pausa_badges', 'badge_id', 'empleado_id')
            ->withPivot('awarded_at');
    }
}
