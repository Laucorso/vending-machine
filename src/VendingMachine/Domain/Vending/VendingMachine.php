<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Vending;

use VendingMachine\Domain\Catalog\Catalog;
use VendingMachine\Domain\Catalog\Exception\ProductNotFoundException;
use VendingMachine\Domain\Catalog\Exception\ProductOutOfStockException;
use VendingMachine\Domain\Catalog\Product;
use VendingMachine\Domain\Catalog\ProductInventory;
use VendingMachine\Domain\Catalog\ProductSelector;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Money\Coin;
use VendingMachine\Domain\Money\CoinBank;
use VendingMachine\Domain\Money\CoinCollection;
use VendingMachine\Domain\Money\Exception\InsufficientChangeException;
use VendingMachine\Domain\Money\Money;
use VendingMachine\Domain\Vending\Exception\InsufficientFundsException;

/**
 * Aggregate root. Owns and protects all machine state:
 *   - the coins a customer has inserted but not yet spent,
 *   - the product inventory,
 *   - the coin bank used to give change.
 *
 * Every business invariant (you can't buy without enough money, you can't
 * vend a sold-out item, the machine never takes money it can't give change
 * for) is enforced here, so no caller can put the machine into an invalid
 * state. The application layer only orchestrates; it never reaches inside.
 */
final class VendingMachine
{
    private CoinCollection $insertedCoins;

    private function __construct(
        private ProductInventory $inventory,
        private CoinBank $coinBank,
    ) {
        $this->insertedCoins = CoinCollection::empty();
    }

    /** A brand-new, empty machine wired to the default catalog. */
    public static function empty(): self
    {
        return new self(
            new ProductInventory(Catalog::default()),
            CoinBank::empty(),
        );
    }

    // -- Customer operations -------------------------------------------------

    public function insertCoin(Coin $coin): void
    {
        $this->insertedCoins = $this->insertedCoins->add($coin);
    }

    /** Give the customer back exactly the coins they inserted, and reset. */
    public function returnInsertedCoins(): CoinCollection
    {
        $returned = $this->insertedCoins;
        $this->insertedCoins = CoinCollection::empty();

        return $returned;
    }

    /**
     * Attempt to buy a product. On success the product is dispensed, the
     * inserted coins move into the bank and any change is handed back.
     *
     * If anything fails (not enough money, sold out, no change possible) the
     * machine state is left untouched and the customer keeps their balance,
     * exactly like a real machine.
     *
     * @throws ProductNotFoundException
     * @throws ProductOutOfStockException
     * @throws InsufficientFundsException
     * @throws InsufficientChangeException
     */
    public function vend(ProductSelector $selector, ChangeCalculator $changeCalculator): VendOutcome
    {
        $product = $this->inventory->productFor($selector);

        $this->guardInStock($selector);
        $this->guardEnoughFunds($product);

        $changeDue = $this->insertedCoins->total()->subtract($product->price);

        // Project the change against the bank *after* the inserted coins land
        // in it, then validate feasibility before mutating anything.
        $projectedBank = $this->coinBank->copy();
        $projectedBank->deposit($this->insertedCoins);
        $change = $changeCalculator->calculate($changeDue, $projectedBank);

        // Commit: all checks passed, so apply the state transition atomically.
        $this->inventory->dispenseOne($selector);
        $this->coinBank->deposit($this->insertedCoins);
        $this->coinBank->withdraw($change);
        $this->insertedCoins = CoinCollection::empty();

        return new VendOutcome($product, $change);
    }

    // -- Service operations --------------------------------------------------

    /** A service person refills products and resets the change float. */
    public function service(ProductInventory $inventory, CoinBank $coinBank): void
    {
        $this->inventory = $inventory;
        $this->coinBank = $coinBank;
    }

    // -- State inspection ----------------------------------------------------

    public function insertedAmount(): Money
    {
        return $this->insertedCoins->total();
    }

    /** The total amount the machine can currently give as change. */
    public function availableChange(): Money
    {
        return $this->coinBank->total();
    }

    private function guardInStock(ProductSelector $selector): void
    {
        if (! $this->inventory->isInStock($selector)) {
            throw ProductOutOfStockException::forSelector($selector);
        }
    }

    private function guardEnoughFunds(Product $product): void
    {
        if ($this->insertedCoins->total()->isLessThan($product->price)) {
            throw InsufficientFundsException::needMoreFor(
                $product,
                $this->insertedCoins->total(),
            );
        }
    }
}
