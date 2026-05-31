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
use VendingMachine\Domain\Vending\VendingMachineRepository;
use VendingMachine\Domain\VendingMachineException;

/**
 * `php artisan vending:play` — an interactive console front-end for the machine.
 *
 * It is intentionally a *thin* adapter: every operation goes through the same
 * Application Actions the HTTP controller uses. The console and the API are two
 * skins over one set of use cases, which is the whole point of keeping business
 * logic out of the delivery layer.
 */
final class VendingPlayCommand extends Command
{
    protected $signature = 'vending:play';

    protected $description = 'Interactively operate the vending machine';

    /** Products offered, with their price, for the buy menu. */
    private const PRODUCTS = [
        'Water — 0.65' => 'WATER',
        'Juice — 1.00' => 'JUICE',
        'Soda  — 1.50' => 'SODA',
    ];

    /** Coins the machine accepts. */
    private const COINS = ['0.05', '0.10', '0.25', '1.00'];

    public function handle(
        VendingMachineRepository $machines,
        InsertCoinAction $insert,
        ReturnCoinsAction $returnCoins,
        SelectProductAction $select,
        ServiceMachineAction $service,
    ): int {
        $this->resetMachine($service);

        $this->info('Vending machine ready. Pick an action; press Ctrl+C any time to exit.');
        $this->newLine();

        while (true) {
            $machine = $machines->get();
            $this->line(sprintf(
                '<comment>Balance:</comment> %s   <comment>Change float:</comment> %s',
                $this->money($machine->insertedAmount()->toDecimal()),
                $this->money($machine->availableChange()->toDecimal()),
            ));

            $action = $this->choice('What would you like to do?', [
                'Insert a coin',
                'Buy a product',
                'Return my coins',
                'Refill the machine (service)',
                'Quit',
            ], 0);

            $shouldContinue = match ($action) {
                'Insert a coin'                => $this->doInsert($insert),
                'Buy a product'                => $this->doBuy($select),
                'Return my coins'              => $this->doReturn($returnCoins),
                'Refill the machine (service)' => $this->doService($service),
                'Quit'                         => false,
            };

            $this->newLine();

            if (! $shouldContinue) {
                $this->info('Goodbye!');

                return self::SUCCESS;
            }
        }
    }

    private function doInsert(InsertCoinAction $insert): bool
    {
        $coin = $this->choice('Which coin?', self::COINS, 2);
        $result = $insert->execute(InsertCoinRequest::fromValue($coin));

        $this->info(sprintf('Inserted %s. Balance is now %s.', $this->money((float) $coin), $this->money($result->insertedTotal)));

        return true;
    }

    private function doBuy(SelectProductAction $select): bool
    {
        $label = $this->choice('Which product?', array_keys(self::PRODUCTS));
        $code = self::PRODUCTS[$label];

        try {
            $result = $select->execute(SelectProductRequest::fromCode($code));
        } catch (VendingMachineException $e) {
            $this->error($e->getMessage());

            return true;
        }

        $this->info(sprintf('Dispensed %s. Change returned: %s', $result->product, $this->coins($result->change)));

        return true;
    }

    private function doReturn(ReturnCoinsAction $returnCoins): bool
    {
        $result = $returnCoins->execute();

        $this->info('Coins returned: ' . $this->coins($result->coins));

        return true;
    }

    private function doService(ServiceMachineAction $service): bool
    {
        $this->resetMachine($service);
        $this->info('Machine refilled: 5 of each product and a fresh coin float.');

        return true;
    }

    private function resetMachine(ServiceMachineAction $service): void
    {
        $service->execute(ServiceRequest::fromCounts(
            productCounts: ['WATER' => 5, 'JUICE' => 5, 'SODA' => 5],
            coinCounts: [100 => 10, 25 => 10, 10 => 10, 5 => 10],
        ));
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2);
    }

    private function coins(array $coins): string
    {
        if ($coins === []) {
            return '(none)';
        }

        return implode(', ', array_map(fn (float $c): string => $this->money($c), $coins));
    }
}