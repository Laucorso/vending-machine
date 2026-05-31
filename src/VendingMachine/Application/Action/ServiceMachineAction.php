<?php

declare(strict_types=1);

namespace VendingMachine\Application\Action;

use VendingMachine\Application\Request\ServiceRequest;
use VendingMachine\Domain\Vending\VendingMachine;
use VendingMachine\Domain\Vending\VendingMachineRepository;

/** Use case: a service person refills products and the change float. */
final readonly class ServiceMachineAction
{
    public function __construct(private VendingMachineRepository $machines) {}

    public function execute(ServiceRequest $request): void
    {
        $this->machines->mutate(
            static function (VendingMachine $machine) use ($request): void {
                $machine->service($request->toInventory(), $request->toCoinBank());
            },
        );
    }
}
