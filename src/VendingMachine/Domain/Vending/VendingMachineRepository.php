<?php

declare(strict_types=1);

namespace VendingMachine\Domain\Vending;

/**
 * Persists the single machine's state between operations.
 *
 * A physical machine keeps its state in memory; a fleet of networked machines
 * might keep it in Redis or a database. The domain depends only on this
 * interface, so the storage technology is an infrastructure choice that never
 * leaks into business logic.
 */
interface VendingMachineRepository
{
    public function get(): VendingMachine;

    public function save(VendingMachine $machine): void;

    public function mutate(callable $operation): mixed;
}
