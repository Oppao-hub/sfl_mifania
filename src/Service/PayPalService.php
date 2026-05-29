<?php

namespace App\Service;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;

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
            if (str_contains($normalizedMode, 'sandbox')) {
                $normalizedMode = 'sandbox';
            }
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

    public function getOrder(string $orderId)
    {
        $request = new OrdersGetRequest($orderId);
        $response = $this->client()->execute($request);

        return $response->result;
    }

    /**
     * Returns the captured amount in PHP after verifying or completing capture.
     */
    public function resolveCapturedAmount(string $orderId, ?float $expectedAmount = null): float
    {
        $order = $this->getOrder($orderId);
        $status = strtoupper((string) ($order->status ?? ''));

        if ($status !== 'COMPLETED') {
            $order = $this->captureOrder($orderId);
            $status = strtoupper((string) ($order->status ?? ''));
        }

        if ($status !== 'COMPLETED') {
            throw new \RuntimeException('PayPal payment is not completed yet.');
        }

        $paidAmount = $this->extractCapturedAmount($order);
        if ($paidAmount <= 0) {
            throw new \RuntimeException('PayPal payment amount is invalid.');
        }

        if ($expectedAmount !== null && abs($paidAmount - $expectedAmount) > 0.01) {
            throw new \RuntimeException('PayPal payment amount does not match the expected value.');
        }

        return $paidAmount;
    }

    private function extractCapturedAmount(object $order): float
    {
        $units = $order->purchase_units ?? [];
        if (!is_array($units) || count($units) === 0) {
            throw new \RuntimeException('PayPal order has no purchase units.');
        }

        $unit = $units[0];
        $captures = $unit->payments->captures ?? null;
        if (is_array($captures) && count($captures) > 0) {
            $capture = $captures[0];
            $value = $capture->amount->value ?? null;
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        $fallback = $unit->amount->value ?? null;
        if (is_numeric($fallback)) {
            return (float) $fallback;
        }

        throw new \RuntimeException('Unable to read PayPal capture amount.');
    }

    private function client(): PayPalHttpClient
    {
        if ($this->client === null) {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }

        return $this->client;
    }
}
