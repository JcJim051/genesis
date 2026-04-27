<?php

namespace App\Support;

use App\Models\Empleado;
use App\Models\User;

class EmpleadoLink
{
    public static function canView(?Empleado $empleado = null, ?int $empleadoId = null, ?User $user = null): bool
    {
        $user = $user ?: backpack_user();
        if (! $user) {
            return false;
        }

        if (! $user->hasAnyRole([
            'Administrador',
            'Coordinador general',
            'Coordinador de planta',
            'Asesor externo general',
            'Asesor externo planta',
        ])) {
            return false;
        }

        if (TenantSelection::isAdminBypass($user)) {
            return true;
        }

        $empleado = $empleado ?: ($empleadoId ? Empleado::query()->select(['id', 'cliente_id', 'sucursal_id'])->find($empleadoId) : null);
        if (! $empleado) {
            return false;
        }

        $plantaIds = TenantSelection::plantaIds($user);
        if (! empty($plantaIds)) {
            return in_array((int) $empleado->sucursal_id, array_map('intval', $plantaIds), true);
        }

        $empresaIds = TenantSelection::empresaIds($user);
        if (empty($empresaIds)) {
            return false;
        }

        $clienteId = $empleado->cliente_id !== null ? (int) $empleado->cliente_id : null;
        $allowedEmpresaIds = array_map('intval', $empresaIds);

        if (in_array((int) $clienteId, $allowedEmpresaIds, true)) {
            return true;
        }

        return TenantSelection::selectedEmpresaIncludesUnassigned($user) && ($clienteId === null || (int) $clienteId === 0);
    }

    public static function render(?Empleado $empleado = null, ?string $text = null): string
    {
        $label = trim((string) ($text ?? $empleado?->nombre ?? ''));
        $safeLabel = e($label !== '' ? $label : '—');

        if (! $empleado || ! self::canView($empleado)) {
            return $safeLabel;
        }

        $url = backpack_url('empleado/' . $empleado->id . '/show');
        return '<a href="' . e($url) . '" class="text-decoration-none">' . $safeLabel . '</a>';
    }
}

