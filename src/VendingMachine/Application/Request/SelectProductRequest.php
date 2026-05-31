<?php

declare(strict_types=1);

namespace VendingMachine\Application\Request;

use VendingMachine\Domain\Catalog\ProductSelector;

/** Input boundary for "press a product button". */
final readonly class SelectProductRequest
{
    private function __construct(public ProductSelector $selector)
    {
    }

    public static function fromCode(string $selector): self
    {
        return new self(ProductSelector::fromCode($selector));
    }
}
