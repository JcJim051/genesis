<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cie10;
use Illuminate\Http\JsonResponse;
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
}
