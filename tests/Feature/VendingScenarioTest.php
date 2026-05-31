<?php

declare(strict_types=1);

namespace Tests\Feature;

use VendingMachine\Application\Action\InsertCoinAction;
use VendingMachine\Application\Action\ReturnCoinsAction;
use VendingMachine\Application\Action\SelectProductAction;
use VendingMachine\Application\Action\ServiceMachineAction;
use VendingMachine\Application\Request\InsertCoinRequest;
use VendingMachine\Application\Request\SelectProductRequest;
use VendingMachine\Application\Request\ServiceRequest;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Infrastructure\Persistence\InMemoryVendingMachineRepository;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the full stack the way an external driver (HTTP, CLI, hardware)
 * would: raw input -> Request -> Action -> Repository -> Result DTO.
 */
final class VendingScenarioTest extends TestCase
{
    private InMemoryVendingMachineRepository $machines;
    private InsertCoinAction $insert;
    private ReturnCoinsAction $return;
    private SelectProductAction $select;
    private ServiceMachineAction $service;

    protected function setUp(): void
    {
        $this->machines = new InMemoryVendingMachineRepository();
        $this->insert = new InsertCoinAction($this->machines);
        $this->return = new ReturnCoinsAction($this->machines);
        $this->select = new SelectProductAction($this->machines, new ChangeCalculator());
        $this->service = new ServiceMachineAction($this->machines);
    }

    public function test_example_1_buy_soda_with_exact_change(): void
    {
        $this->stockMachine(products: ['SODA' => 1], coins: []);

        $this->insert->execute(InsertCoinRequest::fromValue(1));
        $this->insert->execute(InsertCoinRequest::fromValue(0.25));
        $this->insert->execute(InsertCoinRequest::fromValue(0.25));

        $result = $this->select->execute(SelectProductRequest::fromCode('SODA'));

        self::assertSame('Soda', $result->product);
        self::assertSame([], $result->change);
    }

    public function test_example_2_return_coin(): void
    {
        $this->insert->execute(InsertCoinRequest::fromValue(0.10));
        $this->insert->execute(InsertCoinRequest::fromValue(0.10));

        $result = $this->return->execute();

        self::assertSame([0.10, 0.10], $result->coins);
    }

    public function test_example_3_buy_water_with_change(): void
    {
        $this->stockMachine(products: ['WATER' => 1], coins: [25 => 1, 10 => 1]);

        $this->insert->execute(InsertCoinRequest::fromValue(1));

        $result = $this->select->execute(SelectProductRequest::fromCode('water')); // normalized to WATER

        self::assertSame('Water', $result->product);
        self::assertSame([0.25, 0.10], $result->change);
    }

    public function test_balance_survives_across_actions(): void
    {
        $first = $this->insert->execute(InsertCoinRequest::fromValue(0.25));
        $second = $this->insert->execute(InsertCoinRequest::fromValue(0.25));

        self::assertSame(0.25, $first->insertedTotal);
        self::assertSame(0.50, $second->insertedTotal);
    }

    /**
     * @param array<string, int> $products
     * @param array<int, int>    $coins
     */
    private function stockMachine(array $products, array $coins): void
    {
        $this->service->execute(ServiceRequest::fromCounts($products, $coins));
    }
}
