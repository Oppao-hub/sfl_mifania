<?php

namespace App\Service;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PayPalService
{
    private ?PayPalHttpClient $client = null;

    public function __construct(
        private readonly string $clientId,
        private readonly string $secret,
        private readonly string $mode = 'sandbox',
    ) {
        if ($this->clientId !== '' && $this->secret !== '') {
            $normalizedMode = strtolower(trim($this->mode));
            $isLive = in_array($normalizedMode, ['live', 'production'], true);

            $environment = $isLive
                ? new ProductionEnvironment($this->clientId, $this->secret)
                : new SandboxEnvironment($this->clientId, $this->secret);

            $this->client = new PayPalHttpClient($environment);
        }
    }

    public function isConfigured(): bool
    {
        return $this->client !== null;
    }

    public function createOrder(float $amount): string
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'PHP',
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]],
        ];

        $response = $this->client()->execute($request);

        return $response->result->id;
    }

    public function captureOrder(string $orderId)
    {
        $request = new OrdersCaptureRequest($orderId);
        $request->prefer('return=representation');
        $response = $this->client()->execute($request);

        return $response->result;
    }

    private function client(): PayPalHttpClient
    {
        if ($this->client === null) {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }

        return $this->client;
    }
}
