<?php

declare(strict_types=1);

namespace VendingMachine\Infrastructure\Persistence;

use VendingMachine\Domain\Vending\VendingMachine;
use VendingMachine\Domain\Vending\VendingMachineRepository;

/**
 * Keeps the single machine's state in process memory.
 *
 * Adequate for a self-contained physical machine and for tests. Swapping in a
 * Redis/SQL implementation later requires no change anywhere else, because
 * callers depend on the VendingMachineRepository interface, not on this class.
 */
final class InMemoryVendingMachineRepository implements VendingMachineRepository
{
    private VendingMachine $machine;

    public function __construct(?VendingMachine $machine = null)
    {
        $this->machine = $machine ?? VendingMachine::empty();
    }

    public function get(): VendingMachine
    {
        return $this->machine;
    }

    public function save(VendingMachine $machine): void
    {
        $this->machine = $machine;
    }
}
