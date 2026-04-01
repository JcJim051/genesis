<?php

namespace App\Http\Controllers;

use App\Models\TelegramActivationLink;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelegramActivationController extends Controller
{
    public function __invoke(Request $request, string $token): View
    {
        $link = TelegramActivationLink::where('token', $token)->firstOrFail();
        $empleadoId = $link->empleado_id;

        if (! $link->used_at) {
            $link->update(['used_at' => now()]);
        }

        $botUser = config('services.telegram.bot_username', 'genesis_col_bot');
        $payload = 'emp_' . $empleadoId;
        $startLink = 'https://t.me/' . $botUser . '?start=' . $payload;
        $tgLink = 'tg://resolve?domain=' . $botUser . '&start=' . $payload;

        return view('telegram.activate', [
            'botUser' => $botUser,
            'payload' => $payload,
            'startLink' => $startLink,
            'tgLink' => $tgLink,
        ]);
    }
}
