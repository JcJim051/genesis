<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\TenantSelection;
use Illuminate\Http\Request;

class TenantScopeController extends Controller
{
    public function update(Request $request)
    {
        $scope = (string) $request->input('scope', 'all');

        if ($scope === 'all') {
            session(['tenant_scope' => 'all']);
            return back()->with('success', 'Vista configurada para todas tus asignaciones.');
        }

        [$mode, $id] = array_pad(explode(':', $scope, 2), 2, null);
        $id = $id ? (int) $id : 0;

        if (! in_array($mode, ['empresa', 'planta'], true) || $id <= 0) {
            return back()->with('error', 'Selección de alcance inválida.');
        }

        if (! TenantSelection::isAllowedScope($mode, $id)) {
            return back()->with('error', 'No tienes permisos para ese alcance.');
        }

        session(['tenant_scope' => $mode . ':' . $id]);

        return back()->with('success', 'Alcance actualizado: ' . TenantSelection::humanLabel());
    }
}
