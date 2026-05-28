<?php

namespace App\Service;

/**
 * Single source of truth for Philippines delivery options (web + API).
 */
final class DeliveryOptionsCatalog
{
    /**
     * @return list<array{
     *     id: string,
     *     name: string,
     *     estimate: string,
     *     fee: string,
     *     feeAmount: float,
     *     description: string
     * }>
     */
    public static function all(): array
    {
        $options = [
            [
                'id' => 'jt-express',
                'name' => 'J&T Express',
                'estimate' => '2–4 business days nationwide',
                'fee' => '₱89.00',
                'description' => 'Door-to-door courier across Luzon, Visayas, and Mindanao',
            ],
            [
                'id' => 'ninja-van',
                'name' => 'Ninja Van Philippines',
                'estimate' => '2–5 business days',
                'fee' => '₱79.00',
                'description' => 'Reliable parcel delivery to most PH provinces',
            ],
            [
                'id' => 'lbc',
                'name' => 'LBC Express',
                'estimate' => '3–5 business days',
                'fee' => '₱95.00',
                'description' => 'Trusted nationwide shipping with branch pickup available',
            ],
            [
                'id' => 'flash-express',
                'name' => 'Flash Express Philippines',
                'estimate' => '2–4 business days',
                'fee' => '₱85.00',
                'description' => 'Fast domestic delivery for major cities and towns',
            ],
            [
                'id' => 'grab-express',
                'name' => 'Grab Express',
                'estimate' => 'Same day to next day (Metro Manila & key cities)',
                'fee' => '₱149.00',
                'description' => 'On-demand delivery in Metro Manila, Cebu, and Davao',
            ],
            [
                'id' => 'philpost-economy',
                'name' => 'PHLPost (Economy)',
                'estimate' => '7–14 business days',
                'fee' => '₱59.00',
                'description' => 'Budget-friendly standard mail via Philippine Post',
            ],
            [
                'id' => 'store-pickup',
                'name' => 'Store Pickup (Mifania)',
                'estimate' => 'Ready in 1–2 business days',
                'fee' => '₱0.00',
                'description' => 'Pick up at our store — location details sent after order confirmation',
            ],
        ];

        return array_map(static function (array $option): array {
            $option['feeAmount'] = self::parseFeeAmount($option['fee']);

            return $option;
        }, $options);
    }

    /**
     * @return array{id: string, name: string, estimate: string, fee: string, feeAmount: float, description: string}|null
     */
    public static function findById(string $id): ?array
    {
        foreach (self::all() as $option) {
            if ($option['id'] === $id) {
                return $option;
            }
        }

        return null;
    }

    public static function parseFeeAmount(string $fee): float
    {
        $normalized = preg_replace('/[^0-9.]/', '', $fee) ?? '0';
        $amount = (float) $normalized;

        return max(0, $amount);
    }

    public static function defaultId(): string
    {
        return 'jt-express';
    }
}
