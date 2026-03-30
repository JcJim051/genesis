<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    public function rules(): array
    {
        return [
            'cliente_id' => 'required|exists:clientes,id',
            'sucursal_id' => 'required|exists:sucursals,id',
            'nombre' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:50',
            'eps' => 'nullable|string|max:255',
            'arl' => 'nullable|string|max:255',
            'fondo_pensiones' => 'nullable|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|max:255',
            'tipo_contrato' => 'nullable|string|max:255',
            'edad' => 'nullable|integer|min:0|max:120',
            'lateralidad' => 'nullable|string|max:50',
            'genero' => 'nullable|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
            'fecha_ingreso' => 'nullable|date',
            'fecha_retiro' => 'nullable|date',
        ];
    }
}
