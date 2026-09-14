<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DarajaService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * OAuth token — required on every subsequent Daraja call, expires in ~1 hour.
     */
    public function getAccessToken(): ?string
    {
        $response = Http::withBasicAuth(
            config('services.mpesa.consumer_key'),
            config('services.mpesa.consumer_secret')
        )->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

        if ($response->failed()) {
            Log::error('Daraja token request failed', ['body' => $response->body()]);
            return null;
        }

        return $response->json('access_token');
    }

    /**
     * Trigger an STK Push — this is what makes the customer's phone ring
     * with a PIN prompt. Returns the checkout_request_id used to match
     * the eventual callback, or null on failure.
     */
    public function stkPush(string $phone, float $amount, string $orderNumber): ?array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return null;
        }

        $shortcode = config('services.mpesa.shortcode');
        $passkey = config('services.mpesa.passkey');
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        $response = Http::withToken($token)->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round($amount), // sandbox rejects decimals
            'PartyA' => $this->formatPhone($phone),
            'PartyB' => $shortcode,
            'PhoneNumber' => $this->formatPhone($phone),
            'CallBackURL' => config('services.mpesa.callback_url'),
            'AccountReference' => $orderNumber,
            'TransactionDesc' => "SokoTrack order {$orderNumber}",
        ]);

        if ($response->failed()) {
            Log::error('Daraja STK push failed', ['body' => $response->body()]);
            return null;
        }

        return $response->json();
    }

    /**
     * Daraja requires phone numbers in 2547XXXXXXXX format, no leading 0 or +.
     */
    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone); // strip non-digits

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            $phone = '254' . $phone;
        }

        return $phone;
    }
}
