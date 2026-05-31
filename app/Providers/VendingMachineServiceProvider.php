<?php

declare(strict_types=1);

namespace App\Providers;

use App\VendingMachine\Persistence\CacheVendingMachineRepository;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use VendingMachine\Domain\Money\ChangeCalculator;
use VendingMachine\Domain\Vending\VendingMachineRepository;

/**
 * Wires the bounded context into the framework's container.
 *
 * This is the seam between Laravel and the domain: the only Laravel-aware
 * decisions (which persistence adapter, how the change strategy is shared)
 * live here. Actions are auto-resolved by the container because their
 * dependencies are bound below.
 */
final class VendingMachineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChangeCalculator::class);
        $this->app->bind(
            VendingMachineRepository::class,
            static fn (Application $app): CacheVendingMachineRepository => new CacheVendingMachineRepository(
                $app->make(Cache::class),
            ),
        );
    }
}
