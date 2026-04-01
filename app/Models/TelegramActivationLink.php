<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramActivationLink extends Model
{
    protected $table = 'telegram_activation_links';

    protected $fillable = [
        'empleado_id',
        'token',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
