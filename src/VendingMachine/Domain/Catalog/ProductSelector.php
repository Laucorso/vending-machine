<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Catalog;

use VendingMachine\Domain\Catalog\Exception\ProductNotFoundException;

/** The physical buttons a customer can press to choose a product. */
enum ProductSelector: string
{
    case Water = 'WATER';
    case Juice = 'JUICE';
    case Soda = 'SODA';

    /**
     * Resolve a selector from raw input, raising a domain error (not a bare
     * ValueError) when the code is unknown.
     *
     * @throws ProductNotFoundException
     */
    public static function fromCode(string $code): self
    {
        return self::tryFrom(strtoupper(trim($code)))
            ?? throw ProductNotFoundException::forSelector($code);
    }

    public function displayName(): string
    {
        return ucfirst(strtolower($this->value));
    }
}
