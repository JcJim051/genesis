<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return backpack_auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'roles' => $this->normalizeArrayInput($this->input('roles')),
            'empresas' => $this->normalizeArrayInput($this->input('empresas')),
            'plantas' => $this->normalizeArrayInput($this->input('plantas')),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('user') ?? $this->id ?? null;
        $passwordRule = $id ? 'nullable|min:8|confirmed' : 'required|min:8|confirmed';

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'password' => $passwordRule,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'empresas' => 'nullable|array',
            'empresas.*' => 'exists:clientes,id',
            'plantas' => 'nullable|array',
            'plantas.*' => 'exists:sucursals,id',
        ];
    }

    private function normalizeArrayInput($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        return (array) $value;
    }
}
