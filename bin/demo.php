<?php

declare(strict_types=1);

use VendingMachine\Application\Action\InsertCoinAction;
use VendingMachine\Application\Action\ReturnCoinsAction;
use VendingMachine\Application\Action\SelectProductAction;
use VendingMachine\Application\Action\ServiceMachineAction;
use VendingMachine\Application\Request\InsertCoinRequest;
use VendingMachine\Application\Request\SelectProductRequest;
use VendingMachine\Application\Request\ServiceRequest;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\VendingMachineException;
use VendingMachine\Infrastructure\Persistence\InMemoryVendingMachineRepository;

require __DIR__.'/../vendor/autoload.php';

$machines = new InMemoryVendingMachineRepository;
$insert = new InsertCoinAction($machines);
$returnCoins = new ReturnCoinsAction($machines);
$select = new SelectProductAction($machines, new ChangeCalculator);
$service = new ServiceMachineAction($machines);

$fmt = static fn (array $coins): string => $coins === [] ? '(no change)' : implode(', ', $coins);

echo "=== Vending Machine demo ===\n\n";

// A service person stocks the machine and loads the change float.
$service->execute(ServiceRequest::fromCounts(
    productCounts: ['WATER' => 5, 'JUICE' => 5, 'SODA' => 5],
    coinCounts: [100 => 10, 25 => 10, 10 => 10, 5 => 10],
));

try {
    // Example 1: 1, 0.25, 0.25, GET-SODA -> SODA
    $insert->execute(InsertCoinRequest::fromValue(1));
    $insert->execute(InsertCoinRequest::fromValue(0.25));
    $insert->execute(InsertCoinRequest::fromValue(0.25));
    $r1 = $select->execute(SelectProductRequest::fromCode('SODA'));
    echo "Example 1  -> {$r1->product}, change: {$fmt($r1->change)}\n";

    // Example 2: 0.10, 0.10, RETURN-COIN -> 0.10, 0.10
    $insert->execute(InsertCoinRequest::fromValue(0.10));
    $insert->execute(InsertCoinRequest::fromValue(0.10));
    $r2 = $returnCoins->execute();
    echo "Example 2  -> returned: {$fmt($r2->coins)}\n";

    // Example 3: 1, GET-WATER -> WATER, 0.25, 0.10
    $insert->execute(InsertCoinRequest::fromValue(1));
    $r3 = $select->execute(SelectProductRequest::fromCode('WATER'));
    echo "Example 3  -> {$r3->product}, change: {$fmt($r3->change)}\n";
} catch (VendingMachineException $e) {
    echo "Machine refused the operation: {$e->getMessage()}\n";
}

echo "\nDone.\n";
