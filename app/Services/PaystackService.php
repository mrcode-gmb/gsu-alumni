<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaystackService
{
    public function __construct(
        protected HttpFactory $http,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializeTransaction(array $payload): array
    {
        return $this->request('post', '/transaction/initialize', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        return $this->request('get', "/transaction/verify/{$reference}");
    }

    public function isValidWebhookSignature(string $payload, ?string $signature): bool
    {
        $signature = trim((string) $signature);

        if ($signature === '') {
            return false;
        }

        $secret = (string) config('services.paystack.webhook_secret');

        if ($secret === '') {
            throw new RuntimeException('Paystack webhook verification is not configured.');
        }

        $expectedSignature = hash_hmac('sha512', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function request(string $method, string $endpoint, array $payload = []): array
    {
        $secretKey = (string) config('services.paystack.secret_key');

        if ($secretKey === '') {
            throw new RuntimeException('Paystack is not configured. Add your Paystack keys to the environment before continuing.');
        }

        try {
            $response = $this->http
                ->baseUrl((string) config('services.paystack.base_url'))
                ->acceptJson()
                ->asJson()
                ->withToken($secretKey)
                ->timeout((int) config('services.paystack.timeout', 15))
                ->send($method, $endpoint, $payload === [] ? [] : ['json' => $payload])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::error('Paystack request failed.', [
                'endpoint' => $endpoint,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('We could not communicate with Paystack right now. Please try again shortly.');
        }

        /** @var array<string, mixed> $responsePayload */
        $responsePayload = $response->json();

        Log::info('Paystack API response received.', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'response' => $responsePayload,
        ]);

        if (($responsePayload['status'] ?? false) !== true) {
            Log::warning('Paystack API returned an unsuccessful response.', [
                'endpoint' => $endpoint,
                'response' => $responsePayload,
            ]);

            throw new RuntimeException((string) ($responsePayload['message'] ?? 'Paystack request was not successful.'));
        }

        return $responsePayload;
    }
}
