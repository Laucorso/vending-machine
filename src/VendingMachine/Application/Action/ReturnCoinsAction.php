<?php

declare(strict_types=1);

namespace VendingMachine\Application\Action;

use VendingMachine\Application\Dto\CoinReturn;
use VendingMachine\Domain\Vending\VendingMachine;
use VendingMachine\Domain\Vending\VendingMachineRepository;

/** Use case: a customer presses RETURN-COIN. */
final readonly class ReturnCoinsAction
{
    public function __construct(private VendingMachineRepository $machines)
    {
    }

    public function execute(): CoinReturn
    {
        $outcome = $this->machines->mutate(
            fn (VendingMachine $machine) => $machine->returnInsertedCoins(),
        );

        return CoinReturn::from($outcome);
    }
}
