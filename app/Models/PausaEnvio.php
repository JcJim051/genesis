<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class PausaEnvio extends Model
{
    use CrudTrait;

    protected $table = 'pausa_envios';

    protected $fillable = [
        'pausa_id',
        'cliente_id',
        'sucursal_id',
        'fecha_envio',
        'fecha_expiracion',
        'procesado_en',
        'programado_para',
        'programado_modo',
        'programado_solo_no_completados',
    ];

    protected $casts = [
        'fecha_envio' => 'date',
        'fecha_expiracion' => 'date',
        'procesado_en' => 'datetime',
        'programado_para' => 'datetime',
        'programado_solo_no_completados' => 'boolean',
    ];

    public function pausa()
    {
        return $this->belongsTo(Pausa::class, 'pausa_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function participaciones()
    {
        return $this->hasMany(PausaParticipacion::class, 'envio_id');
    }
}
