<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public function sendTemplate(string $to, string $templateName, string $link, string $language = 'es'): ?string
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_number_id');
        if (! $token || ! $phoneId) {
            return null;
        }

        $endpoint = "https://graph.facebook.com/v19.0/{$phoneId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $link],
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($token)->post($endpoint, $payload);
        if (! $response->successful()) {
            return null;
        }

        return data_get($response->json(), 'messages.0.id');
    }
}
