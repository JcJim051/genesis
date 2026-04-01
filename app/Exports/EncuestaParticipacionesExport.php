<?php

namespace App\Exports;

use App\Models\EncuestaRespuesta;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EncuestaParticipacionesExport implements FromQuery, WithHeadings, WithMapping
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
            'Encuesta',
            'Nombre',
            'Cédula',
            'Correo',
            'Telegram',
            'Estado',
            'Puntaje',
            'Fecha participación',
            'Link',
        ];
    }

    public function map($row): array
    {
        $link = url('/encuestas/' . $row->token);

        return [
            $row->id,
            optional($row->encuesta)->titulo,
            optional($row->empleado)->nombre,
            optional($row->empleado)->cedula,
            optional($row->empleado)->correo_electronico,
            optional($row->empleado)->telegram_chat_id,
            $row->estado,
            $row->puntaje_total,
            optional($row->respondido_en ?? $row->created_at)?->format('Y-m-d H:i'),
            $link,
        ];
    }
}
