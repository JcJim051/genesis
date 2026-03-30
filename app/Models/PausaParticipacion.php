<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class PausaParticipacion extends Model
{
    use CrudTrait;

    protected $table = 'pausa_participaciones';

    protected $fillable = [
        'envio_id',
        'empleado_id',
        'token',
        'estado',
        'tiempo_activo_total',
        'tab_switch_count',
        'respondido_en',
        'whatsapp_message_id',
    ];

    protected $casts = [
        'respondido_en' => 'datetime',
    ];

    public function envio()
    {
        return $this->belongsTo(PausaEnvio::class, 'envio_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function items()
    {
        return $this->hasMany(PausaParticipacionItem::class, 'participacion_id');
    }

    public function eventos()
    {
        return $this->hasMany(PausaEvento::class, 'participacion_id');
    }
}
