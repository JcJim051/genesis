<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    public function sendMessage(string $chatId, string $text): ?int
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            return null;
        }

        $endpoint = "https://api.telegram.org/bot{$token}/sendMessage";
        $response = Http::post($endpoint, [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
        ]);

        if (! $response->successful()) {
            return null;
        }

        return data_get($response->json(), 'result.message_id');
    }
}
