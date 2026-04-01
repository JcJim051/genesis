<?php

namespace App\Exports;

use App\Models\PausaParticipacion;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PausaParticipacionesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private Builder $query)
    {
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Pausa',
            'Nombre',
            'Cédula',
            'Correo',
            'Telegram',
            'Estado',
            'Fecha participación',
            'Tiempo activo (s)',
            'Cambios pestaña',
            'Link',
        ];
    }

    public function map($row): array
    {
        $link = url('/pausas/' . $row->token);

        return [
            $row->id,
            optional($row->envio?->pausa)->nombre,
            optional($row->empleado)->nombre,
            optional($row->empleado)->cedula,
            optional($row->empleado)->correo_electronico,
            optional($row->empleado)->telegram_chat_id,
            $row->estado,
            optional($row->respondido_en ?? $row->created_at)?->format('Y-m-d H:i'),
            $row->tiempo_activo_total,
            $row->tab_switch_count,
            $link,
        ];
    }
}
