<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Drives the bounded context through the real HTTP stack: routes, container
 * bindings, the cache-backed repository and the exception handler. The array
 * cache driver keeps state across requests within a single test, which is what
 * lets us insert coins in one call and buy in the next.
 */
final class VendingApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->postJson('/api/service', [
            'products' => ['WATER' => 5, 'JUICE' => 5, 'SODA' => 5],
            'coins' => [100 => 10, 25 => 10, 10 => 10, 5 => 10],
        ])->assertOk();
    }

    public function test_buy_soda_with_exact_change(): void
    {
        $this->postJson('/api/coins', ['coin' => 1])->assertOk();
        $this->postJson('/api/coins', ['coin' => 0.25])->assertOk();
        $this->postJson('/api/coins', ['coin' => 0.25])->assertOk();

        $this->postJson('/api/products/SODA')
            ->assertOk()
            ->assertExactJson(['product' => 'Soda', 'change' => []]);
    }

    public function test_return_coin_gives_back_what_was_inserted(): void
    {
        $this->postJson('/api/coins', ['coin' => 0.10]);
        $this->postJson('/api/coins', ['coin' => 0.10]);

        $this->postJson('/api/coins/return')
            ->assertOk()
            ->assertExactJson(['returned' => [0.10, 0.10]]);
    }

    public function test_buy_water_returns_change(): void
    {
        $this->postJson('/api/coins', ['coin' => 1]);

        $this->postJson('/api/products/water')
            ->assertOk()
            ->assertExactJson(['product' => 'Water', 'change' => [0.25, 0.10]]);
    }

    public function test_unknown_product_is_a_422(): void
    {
        $this->postJson('/api/products/COFFEE')->assertStatus(422);
    }

    public function test_invalid_coin_is_a_422(): void
    {
        $this->postJson('/api/coins', ['coin' => 0.5])->assertStatus(422);
    }
}
