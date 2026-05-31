<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use VendingMachine\Application\Action\InsertCoinAction;
use VendingMachine\Application\Action\ReturnCoinsAction;
use VendingMachine\Application\Action\SelectProductAction;
use VendingMachine\Application\Action\ServiceMachineAction;
use VendingMachine\Application\Request\InsertCoinRequest;
use VendingMachine\Application\Request\SelectProductRequest;
use VendingMachine\Application\Request\ServiceRequest;
use VendingMachine\Domain\VendingMachineException;

/**
 * `php artisan vending:demo` — runs the three specification examples using the
 * same Actions the HTTP layer uses, resolved from the container.
 */
final class VendingDemoCommand extends Command
{
    protected $signature = 'vending:demo';

    protected $description = 'Run the vending machine specification examples';

    public function handle(
        InsertCoinAction $insert,
        ReturnCoinsAction $returnCoins,
        SelectProductAction $select,
        ServiceMachineAction $service,
    ): int {
        $service->execute(ServiceRequest::fromCounts(
            productCounts: ['WATER' => 5, 'JUICE' => 5, 'SODA' => 5],
            coinCounts: [100 => 10, 25 => 10, 10 => 10, 5 => 10],
        ));

        try {
            $insert->execute(InsertCoinRequest::fromValue(1));
            $insert->execute(InsertCoinRequest::fromValue(0.25));
            $insert->execute(InsertCoinRequest::fromValue(0.25));
            $r1 = $select->execute(SelectProductRequest::fromCode('SODA'));
            $this->line("Example 1  -> {$r1->product}, change: {$this->formatCoins($r1->change)}");

            $insert->execute(InsertCoinRequest::fromValue(0.10));
            $insert->execute(InsertCoinRequest::fromValue(0.10));
            $r2 = $returnCoins->execute();
            $this->line("Example 2  -> returned: {$this->formatCoins($r2->coins)}");

            $insert->execute(InsertCoinRequest::fromValue(1));
            $r3 = $select->execute(SelectProductRequest::fromCode('WATER'));
            $this->line("Example 3  -> {$r3->product}, change: {$this->formatCoins($r3->change)}");
        } catch (VendingMachineException $e) {
            $this->error("Machine refused the operation: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @param list<float> $coins */
    private function formatCoins(array $coins): string
    {
        if ($coins === []) {
            return '(no change)';
        }

        return implode(', ', array_map(static fn (float $coin): string => (string) $coin, $coins));
    }
}