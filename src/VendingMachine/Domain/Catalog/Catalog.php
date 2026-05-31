<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Catalog;

use VendingMachine\Domain\Money\Money;

/**
 * The fixed menu the machine offers and the price of each product.
 *
 * Prices are a business decision that the service person does NOT change on a
 * refill (they only change quantities), so they live here rather than in the
 * mutable inventory. New products are added by extending this single list.
 */
final class Catalog
{
    /** @return array<string, Product> keyed by selector value */
    public static function default(): array
    {
        return self::index([
            new Product(ProductSelector::Water, Money::fromCents(65)),
            new Product(ProductSelector::Juice, Money::fromCents(100)),
            new Product(ProductSelector::Soda, Money::fromCents(150)),
        ]);
    }

    /**
     * @param list<Product> $products
     *
     * @return array<string, Product>
     */
    private static function index(array $products): array
    {
        $indexed = [];
        foreach ($products as $product) {
            $indexed[$product->selector->value] = $product;
        }

        return $indexed;
    }
}
