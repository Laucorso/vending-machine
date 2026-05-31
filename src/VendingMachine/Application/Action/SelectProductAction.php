<?php

declare(strict_types=1);

namespace VendingMachine\Application\Action;

use VendingMachine\Application\Dto\VendResult;
use VendingMachine\Application\Request\SelectProductRequest;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Vending\VendingMachine;
use VendingMachine\Domain\Vending\VendingMachineRepository;

/** Use case: a customer selects a product to buy. */
final readonly class SelectProductAction
{
    public function __construct(
        private VendingMachineRepository $machines,
        private ChangeCalculator $changeCalculator,
    ) {
    }

    public function execute(SelectProductRequest $request): VendResult
    {
        $outcome = $this->machines->mutate(
            fn (VendingMachine $machine) => $machine->vend($request->selector, $this->changeCalculator),
        );

        return VendResult::from($outcome);
    }
}
