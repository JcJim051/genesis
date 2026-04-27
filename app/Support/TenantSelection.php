<?php

namespace App\Support;

use App\Models\Cliente;
use App\Models\Sucursal;
use App\Models\User;

class TenantSelection
{
    public static function isPlatformAdmin(?User $user = null): bool
    {
        $user = $user ?: backpack_user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrador');
    }

    public static function isScopedSelection(?User $user = null): bool
    {
        $scope = self::currentScope($user);
        return $scope['mode'] !== 'all';
    }

    public static function isAdminBypass(?User $user = null): bool
    {
        return self::isPlatformAdmin($user) && ! self::isScopedSelection($user);
    }

    public static function empresaIds(?User $user = null): array
    {
        $user = $user ?: backpack_user();
        if (! $user) {
            return [];
        }

        $scope = self::currentScope($user);
        if ($scope['mode'] === 'empresa' && $scope['empresa_id']) {
            return [$scope['empresa_id']];
        }

        if ($scope['mode'] === 'planta' && $scope['planta_id']) {
            $empresaId = (int) Sucursal::whereKey($scope['planta_id'])->value('cliente_id');
            return $empresaId ? [$empresaId] : [];
        }

        if ($user->hasRole('Administrador')) {
            return Cliente::query()->pluck('id')->all();
        }

        return $user->empresas()->pluck('clientes.id')->all();
    }

    public static function plantaIds(?User $user = null): array
    {
        $user = $user ?: backpack_user();
        if (! $user) {
            return [];
        }

        $scope = self::currentScope($user);
        if ($scope['mode'] === 'planta' && $scope['planta_id']) {
            return [$scope['planta_id']];
        }

        if ($scope['mode'] === 'empresa') {
            return [];
        }

        if ($user->hasRole('Administrador')) {
            return [];
        }

        return $user->plantas()->pluck('sucursals.id')->all();
    }

    public static function currentScope(?User $user = null): array
    {
        $user = $user ?: backpack_user();
        $scope = (string) session('tenant_scope', 'all');

        if ($scope === 'all') {
            return ['mode' => 'all', 'empresa_id' => null, 'planta_id' => null];
        }

        [$mode, $id] = array_pad(explode(':', $scope, 2), 2, null);
        $id = $id ? (int) $id : null;

        if (! in_array($mode, ['empresa', 'planta'], true) || ! $id) {
            return ['mode' => 'all', 'empresa_id' => null, 'planta_id' => null];
        }

        if (! self::isAllowedScope($mode, $id, $user)) {
            return ['mode' => 'all', 'empresa_id' => null, 'planta_id' => null];
        }

        return [
            'mode' => $mode,
            'empresa_id' => $mode === 'empresa' ? $id : (int) Sucursal::whereKey($id)->value('cliente_id'),
            'planta_id' => $mode === 'planta' ? $id : null,
        ];
    }

    public static function scopeOptions(?User $user = null): array
    {
        $user = $user ?: backpack_user();
        if (! $user) {
            return [];
        }

        $items = [
            ['value' => 'all', 'label' => 'Todas mis asignaciones'],
        ];

        $empresas = $user->hasRole('Administrador')
            ? Cliente::query()->orderBy('nombre')->get(['id', 'nombre'])
            : $user->empresas()->orderBy('nombre')->get(['clientes.id', 'nombre']);

        foreach ($empresas as $empresa) {
            $items[] = [
                'value' => 'empresa:' . $empresa->id,
                'label' => 'Empresa · ' . $empresa->nombre,
            ];
        }

        $plantas = $user->hasRole('Administrador')
            ? Sucursal::query()->with('cliente')->orderBy('nombre')->get(['id', 'cliente_id', 'nombre'])
            : $user->plantas()->with('cliente')->orderBy('nombre')->get(['sucursals.id', 'sucursals.cliente_id', 'sucursals.nombre']);

        foreach ($plantas as $planta) {
            $items[] = [
                'value' => 'planta:' . $planta->id,
                'label' => 'Planta · ' . ($planta->cliente?->nombre ? ($planta->cliente->nombre . ' / ') : '') . $planta->nombre,
            ];
        }

        return $items;
    }

    public static function isAllowedScope(string $mode, int $id, ?User $user = null): bool
    {
        $user = $user ?: backpack_user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Administrador')) {
            return $mode === 'empresa'
                ? Cliente::whereKey($id)->exists()
                : Sucursal::whereKey($id)->exists();
        }

        if ($mode === 'empresa') {
            return $user->empresas()->where('clientes.id', $id)->exists();
        }

        return $user->plantas()->where('sucursals.id', $id)->exists();
    }

    public static function humanLabel(?User $user = null): string
    {
        $scope = self::currentScope($user);

        if ($scope['mode'] === 'empresa' && $scope['empresa_id']) {
            $name = Cliente::whereKey($scope['empresa_id'])->value('nombre');
            return $name ? ('Empresa: ' . $name) : 'Empresa seleccionada';
        }

        if ($scope['mode'] === 'planta' && $scope['planta_id']) {
            $planta = Sucursal::with('cliente')->find($scope['planta_id']);
            if ($planta) {
                $prefix = $planta->cliente?->nombre ? $planta->cliente->nombre . ' / ' : '';
                return 'Planta: ' . $prefix . $planta->nombre;
            }
            return 'Planta seleccionada';
        }

        return 'Todas mis asignaciones';
    }

    public static function selectedEmpresaIncludesUnassigned(?User $user = null): bool
    {
        $scope = self::currentScope($user);
        if ($scope['mode'] !== 'empresa' || ! $scope['empresa_id']) {
            return false;
        }

        $name = (string) Cliente::whereKey($scope['empresa_id'])->value('nombre');
        return strtoupper(trim($name)) === 'SIN EMPRESA';
    }
}
