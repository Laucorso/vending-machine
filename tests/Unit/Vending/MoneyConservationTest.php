<?php

declare(strict_types=1);

namespace Tests\Unit\Vending;

use Tests\TestCase;
use VendingMachine\Domain\Catalog\Catalog;
use VendingMachine\Domain\Catalog\ProductInventory;
use VendingMachine\Domain\Catalog\ProductSelector;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Money\Coin;
use VendingMachine\Domain\Money\CoinBank;
use VendingMachine\Domain\Vending\VendingMachine;
use VendingMachine\Domain\VendingMachineException;

/**
 * Property-based tests of the machine's money invariants.
 *
 * Instead of asserting a handful of hand-picked cases, these drive a thousand
 * randomised-but-deterministic scenarios and assert that the *laws* of the
 * machine hold for every one of them:
 *
 *   1. Customer conservation — on a sale, money inserted == price + change.
 *   2. Machine conservation  — the machine never creates or destroys money:
 *                              bank_after == bank_before + inserted - change.
 *   3. No partial state       — after a sale nothing is left "inserted".
 *   4. Atomic refusal         — if the machine refuses (no funds, sold out, no
 *                              change possible) the customer's balance and the
 *                              bank are left exactly as they were.
 *
 * The seed is fixed so a failure is always reproducible. (A dedicated PBT
 * library such as Eris would shrink failing cases automatically; a seeded loop
 * keeps the dependency surface at zero, which is the right trade-off here.)
 */
final class MoneyConservationTest extends TestCase
{
    private const ITERATIONS = 1000;

    private const SEED = 20260526;

    private ChangeCalculator $change;

    protected function setUp(): void
    {
        $this->change = new ChangeCalculator;
    }

    public function test_money_is_conserved_on_sale_and_balance_is_preserved_on_refusal(): void
    {
        mt_srand(self::SEED);

        $coins = [Coin::FiveCents, Coin::TenCents, Coin::TwentyFiveCents, Coin::OneEuro];
        $selectors = ProductSelector::cases();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $machine = $this->randomlyStockedMachine();
            $bankBefore = $machine->availableChange()->cents;

            $insertedCents = 0;
            foreach ($this->randomCoins($coins) as $coin) {
                $machine->insertCoin($coin);
                $insertedCents += $coin->value;
            }

            $selector = $selectors[mt_rand(0, count($selectors) - 1)];

            try {
                $outcome = $machine->vend($selector, $this->change);

                $changeCents = $outcome->change->total()->cents;

                // (1) Customer conservation.
                self::assertSame(
                    $insertedCents,
                    $outcome->product->price->cents + $changeCents,
                    "Inserted money must equal price plus change (iteration {$i}).",
                );

                // (2) Machine conservation: no money created or destroyed.
                self::assertSame(
                    $bankBefore + $insertedCents - $changeCents,
                    $machine->availableChange()->cents,
                    "The machine must neither create nor destroy money (iteration {$i}).",
                );

                // (3) No partial state left behind after a successful sale.
                self::assertTrue(
                    $machine->insertedAmount()->isZero(),
                    "Nothing should remain inserted after a sale (iteration {$i}).",
                );
            } catch (VendingMachineException) {
                // (4) Any refusal must be atomic: balance and bank untouched.
                self::assertSame(
                    $insertedCents,
                    $machine->insertedAmount()->cents,
                    "Balance must be preserved when the machine refuses (iteration {$i}).",
                );
                self::assertSame(
                    $bankBefore,
                    $machine->availableChange()->cents,
                    "The bank must be untouched when the machine refuses (iteration {$i}).",
                );
            }
        }
    }

    private function randomlyStockedMachine(): VendingMachine
    {
        $machine = VendingMachine::empty();
        $machine->service(
            new ProductInventory(Catalog::default(), [
                ProductSelector::Water->value => mt_rand(0, 3),
                ProductSelector::Juice->value => mt_rand(0, 3),
                ProductSelector::Soda->value => mt_rand(0, 3),
            ]),
            CoinBank::fromCounts([
                5 => mt_rand(0, 10),
                10 => mt_rand(0, 10),
                25 => mt_rand(0, 10),
                100 => mt_rand(0, 5),
            ]),
        );

        return $machine;
    }

    /**
     * @param  list<Coin>  $coins
     * @return list<Coin>
     */
    private function randomCoins(array $coins): array
    {
        $picked = [];
        $count = mt_rand(0, 6);
        for ($j = 0; $j < $count; $j++) {
            $picked[] = $coins[mt_rand(0, count($coins) - 1)];
        }

        return $picked;
    }
}
