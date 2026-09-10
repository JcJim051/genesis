<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cie10;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class Cie10LookupController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $cie10 = Cie10::findOrFail($id);
        return response()->json([
            'id' => $cie10->id,
            'codigo' => $cie10->codigo,
            'diagnostico' => $cie10->diagnostico,
        ]);
    }

    public function fetch(Request $request): JsonResponse
    {
        if (! backpack_user()) {
            abort(403);
        }

        $term = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        $query = Cie10::query();
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('codigo', 'like', '%' . $term . '%')
                    ->orWhere('diagnostico', 'like', '%' . $term . '%');
            });
        }

        $paginator = $query->orderBy('codigo')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'results' => $paginator->getCollection()->map(fn (Cie10 $cie10) => [
                'id' => $cie10->id,
                'text' => $cie10->selectLabel(),
            ])->values(),
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }
}
