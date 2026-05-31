<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use PHPUnit\Framework\TestCase;
use VendingMachine\Domain\Catalog\Exception\ProductNotFoundException;
use VendingMachine\Domain\Catalog\ProductSelector;

final class ProductSelectorTest extends TestCase
{
    public function test_it_resolves_selectors_case_insensitively(): void
    {
        self::assertSame(ProductSelector::Water, ProductSelector::fromCode(' water '));
        self::assertSame(ProductSelector::Soda, ProductSelector::fromCode('SODA'));
    }

    public function test_it_rejects_unknown_selectors(): void
    {
        $this->expectException(ProductNotFoundException::class);

        ProductSelector::fromCode('COFFEE');
    }
}
