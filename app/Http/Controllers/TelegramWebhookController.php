<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramService $telegram)
    {
        $secret = config('services.telegram.webhook_secret');
        if ($secret) {
            $header = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if (! hash_equals($secret, (string) $header)) {
                return response()->json(['ok' => false], 403);
            }
        }

        $message = $request->input('message', []);
        $text = (string) ($message['text'] ?? '');
        $chatId = data_get($message, 'chat.id');
        $username = data_get($message, 'from.username');

        if ($chatId && preg_match('/^\\/start\\s+emp_(\\d+)$/', trim($text), $matches)) {
            $empleadoId = (int) $matches[1];
            $empleado = Empleado::find($empleadoId);
            if ($empleado) {
                $empleado->telegram_chat_id = (string) $chatId;
                if ($username) {
                    $empleado->telegram_username = (string) $username;
                }
                $empleado->save();

                $telegram->sendMessage((string) $chatId, "Tu bot fue activado correctamente. Ahora puedes recibir informacion por este canal.");
            }
        }

        return response()->json(['ok' => true]);
    }
}
