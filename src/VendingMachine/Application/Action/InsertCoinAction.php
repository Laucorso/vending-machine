<?php

declare(strict_types=1);

namespace VendingMachine\Application\Action;

use VendingMachine\Application\Dto\InsertResult;
use VendingMachine\Application\Request\InsertCoinRequest;
use VendingMachine\Domain\Vending\VendingMachine;
use VendingMachine\Domain\Vending\VendingMachineRepository;

/** Use case: a customer inserts one coin. */
final readonly class InsertCoinAction
{
    public function __construct(private VendingMachineRepository $machines) {}

    public function execute(InsertCoinRequest $request): InsertResult
    {
        $insertedTotal = $this->machines->mutate(
            function (VendingMachine $machine) use ($request) {
                $machine->insertCoin($request->coin);

                return $machine->insertedAmount();
            },
        );

        return InsertResult::from($insertedTotal);
    }
}
