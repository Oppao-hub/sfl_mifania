<?php

namespace App\Service;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class PayPalService
{
    private PayPalHttpClient $client;

    public function __construct(string $clientId, string $secret, string $mode = 'sandbox')
    {
        $environment = $mode === 'live'
            ? new ProductionEnvironment($clientId, $secret)
            : new SandboxEnvironment($clientId, $secret);

        $this->client = new PayPalHttpClient($environment);
    }

    public function createOrder(float $amount): string
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($amount, 2, '.', ''),
                ]
            ]],
        ];

        $response = $this->client->execute($request);
        return $response->result->id;
    }

    public function captureOrder(string $orderId)
    {
        $request = new OrdersCaptureRequest($orderId);
        $request->prefer('return=representation');
        $response = $this->client->execute($request);
        return $response->result;
    }
}
