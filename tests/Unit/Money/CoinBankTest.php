<?php

declare(strict_types=1);

namespace Tests\Unit\Money;

use VendingMachine\Domain\Money\Coin;
use VendingMachine\Domain\Money\CoinBank;
use VendingMachine\Domain\Money\CoinCollection;
use PHPUnit\Framework\TestCase;

final class CoinBankTest extends TestCase
{
    public function test_it_deposits_and_withdraws_coins(): void
    {
        $bank = CoinBank::empty();
        $bank->deposit(new CoinCollection(Coin::OneEuro, Coin::OneEuro));
        $bank->withdraw(new CoinCollection(Coin::OneEuro));

        self::assertSame(1, $bank->quantityOf(Coin::OneEuro));
    }

    public function test_a_copy_is_independent_of_the_original(): void
    {
        $bank = CoinBank::fromCounts([100 => 2]);
        $copy = $bank->copy();
        $copy->withdraw(new CoinCollection(Coin::OneEuro));

        self::assertSame(2, $bank->quantityOf(Coin::OneEuro));
        self::assertSame(1, $copy->quantityOf(Coin::OneEuro));
    }

    public function test_it_cannot_withdraw_coins_it_does_not_hold(): void
    {
        $this->expectException(\LogicException::class);

        CoinBank::empty()->withdraw(new CoinCollection(Coin::FiveCents));
    }
}
