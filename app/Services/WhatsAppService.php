<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends WhatsApp text messages via Meta's WhatsApp Cloud API.
 *
 * If credentials are not set in .env, the message is logged instead — so
 * the order flow never fails even on a fresh install.
 *
 * Required .env keys:
 *   WHATSAPP_TOKEN          — Meta Cloud API access token
 *   WHATSAPP_PHONE_ID       — sender phone number ID
 *   WHATSAPP_ADMIN_PHONE    — your admin phone (with country code, no +)
 *   WHATSAPP_API_VERSION    — optional, default v20.0
 */
class WhatsAppService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.whatsapp.token'))
            && ! empty(config('services.whatsapp.phone_id'));
    }

    public function send(string $toPhone, string $message): bool
    {
        $toPhone = $this->normalisePhone($toPhone);
        if (! $toPhone) {
            Log::warning('WhatsApp: missing recipient phone', ['msg' => $message]);
            return false;
        }

        if (! $this->isConfigured()) {
            // Graceful fallback — log it so dev sees what would have been sent.
            Log::info('WhatsApp (NOT CONFIGURED, logged only)', [
                'to'      => $toPhone,
                'message' => $message,
            ]);
            return false;
        }

        $apiVersion = config('services.whatsapp.api_version', 'v20.0');
        $phoneId    = config('services.whatsapp.phone_id');
        $token      = config('services.whatsapp.token');

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->post("https://graph.facebook.com/{$apiVersion}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $toPhone,
                    'type'              => 'text',
                    'text'              => [
                        'preview_url' => false,
                        'body'        => $message,
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('WhatsApp send failed', [
                'to'       => $toPhone,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp exception: ' . $e->getMessage(), ['to' => $toPhone]);
            return false;
        }
    }

    /**
     * Force E.164-ish format without leading + (Cloud API requirement).
     * "03001234567" → "923001234567" (assumes Pakistan country code if 0-prefixed).
     */
    public function normalisePhone(?string $raw): ?string
    {
        if (! $raw) return null;
        $clean = preg_replace('/\D+/', '', $raw);
        if (! $clean) return null;

        $defaultCountry = config('services.whatsapp.default_country', '92');

        if (str_starts_with($clean, '0')) {
            $clean = $defaultCountry . substr($clean, 1);
        } elseif (strlen($clean) <= 10) {
            $clean = $defaultCountry . $clean;
        }

        return $clean;
    }
}
