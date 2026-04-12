<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpleadoPausaStat extends Model
{
    protected $table = 'empleado_pausa_stats';

    protected $fillable = [
        'empleado_id',
        'total_points',
        'total_completadas',
        'current_streak_weeks',
        'best_streak_weeks',
        'current_week_key',
        'current_week_count',
        'last_completed_at',
    ];

    protected $casts = [
        'last_completed_at' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }
}
