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

            $action = $this->pick('What would you like to do?', [
                'Insert a coin',
                'Buy a product',
                'Return my coins',
                'Refill the machine (service)',
                'Quit',
            ]);

            $shouldContinue = match ($action) {
                'Insert a coin' => $this->doInsert($insert),
                'Buy a product' => $this->doBuy($select),
                'Return my coins' => $this->doReturn($returnCoins),
                'Refill the machine (service)' => $this->doService($service),
                default => false,
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
        $coin = $this->pick('Which coin?', self::COINS, '0.25');
        $result = $insert->execute(InsertCoinRequest::fromValue($coin));

        $this->info(sprintf('Inserted %s. Balance is now %s.', $this->money((float) $coin), $this->money($result->insertedTotal)));

        return true;
    }

    private function doBuy(SelectProductAction $select): bool
    {
        $label = $this->pick('Which product?', array_keys(self::PRODUCTS));
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

        $this->info('Coins returned: '.$this->coins($result->coins));

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

    /**
     * A single-choice prompt that always yields a string, keeping the rest of
     * the command free of the array|string return type Laravel's choice() has.
     *
     * @param  list<string>  $options
     */
    private function pick(string $question, array $options, ?string $default = null): string
    {
        $answer = $this->choice($question, $options, $default ?? $options[0]);

        if (is_array($answer)) {
            $first = reset($answer);

            return is_string($first) ? $first : '';
        }

        return $answer;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2);
    }

    /** @param list<float> $coins */
    private function coins(array $coins): string
    {
        if ($coins === []) {
            return '(none)';
        }

        return implode(', ', array_map(fn (float $c): string => $this->money($c), $coins));
    }
}