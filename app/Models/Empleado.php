<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use CrudTrait;

    protected $fillable = [
        'cliente_id',
        'sucursal_id',
        'nombre',
        'cedula',
        'eps',
        'arl',
        'fondo_pensiones',
        'cargo',
        'telefono',
        'direccion',
        'correo_electronico',
        'telegram_chat_id',
        'telegram_username',
        'tipo_contrato',
        'edad',
        'lateralidad',
        'genero',
        'fecha_nacimiento',
        'fecha_ingreso',
        'fecha_retiro',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function cargos()
    {
        return $this->hasMany(EmpleadoCargo::class, 'empleado_id');
    }

    public function areas()
    {
        return $this->hasMany(EmpleadoArea::class, 'empleado_id');
    }

    public function programaCasos()
    {
        return $this->hasMany(ProgramaCaso::class, 'empleado_id');
    }

    public function reincorporaciones()
    {
        return $this->hasMany(Reincorporacion::class, 'empleado_id');
    }

    public function pausaParticipaciones()
    {
        return $this->hasMany(PausaParticipacion::class, 'empleado_id');
    }

    public function getClienteNombre(): string
    {
        return (string) ($this->cliente?->nombre ?? '');
    }

    public function getSucursalNombre(): string
    {
        return (string) ($this->sucursal?->nombre ?? '');
    }

    public function getCargoActual(): string
    {
        $cargo = $this->cargos()
            ->orderByRaw('fecha_fin is null desc')
            ->orderByDesc('fecha_inicio')
            ->first();

        return (string) ($cargo?->cargo ?? '');
    }

    public function getAreaActual(): string
    {
        $area = $this->areas()
            ->orderByRaw('fecha_fin is null desc')
            ->orderByDesc('fecha_inicio')
            ->first();

        return (string) ($area?->area ?? '');
    }

    public function getAntiguedadCargoActual(): string
    {
        $cargo = $this->cargos()
            ->orderByRaw('fecha_fin is null desc')
            ->orderByDesc('fecha_inicio')
            ->first();

        if (! $cargo?->fecha_inicio) {
            return '';
        }

        $inicio = $cargo->fecha_inicio;
        $fin = $cargo->fecha_fin ?? now();
        $days = $inicio->diffInDays($fin);

        return $days . ' días';
    }

    public function getCargosHistorial(): string
    {
        $items = $this->cargos()
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(function ($item) {
                $fin = $item->fecha_fin ? $item->fecha_fin->format('Y-m-d') : 'Actual';
                return $item->cargo . ' (' . $item->fecha_inicio->format('Y-m-d') . ' → ' . $fin . ')';
            })
            ->all();

        return implode('<br>', $items);
    }

    public function getAreasHistorial(): string
    {
        $items = $this->areas()
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(function ($item) {
                $fin = $item->fecha_fin ? $item->fecha_fin->format('Y-m-d') : 'Actual';
                return $item->area . ' (' . $item->fecha_inicio->format('Y-m-d') . ' → ' . $fin . ')';
            })
            ->all();

        return implode('<br>', $items);
    }
}
