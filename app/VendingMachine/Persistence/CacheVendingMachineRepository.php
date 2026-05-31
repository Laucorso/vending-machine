<?php

declare(strict_types=1);

namespace App\VendingMachine\Persistence;

use Illuminate\Contracts\Cache\Repository as Cache;
use VendingMachine\Domain\Vending\VendingMachine;
use VendingMachine\Domain\Vending\VendingMachineRepository;

/**
 * Persists the machine's state in Laravel's cache.
 *
 * The in-memory repository in src/ is fine for a single long-lived process
 * (a CLI run, the physical machine, tests). Behind stateless HTTP each request
 * is a fresh process, so the aggregate must survive between requests — this
 * adapter does exactly that, and is the only place that knows about Laravel.
 * Swapping the in-memory repo for this one required zero changes to the domain
 * or the Actions: that is the payoff of depending on the repository interface.
 */
final class CacheVendingMachineRepository implements VendingMachineRepository
{

    private const STATE_KEY = 'vending_machine.state';
    private const LOCK_KEY = 'vending_machine.lock';

    /** Max time the lock is held before auto-releasing (guards against a dead holder). */
    private const LOCK_TTL_SECONDS = 10;

    /** Max time a caller waits to acquire the lock before giving up. */
    private const LOCK_WAIT_SECONDS = 5;

    public function __construct(private readonly Cache $cache)
    {
    }

    public function get(): VendingMachine
    {
        $serialized = $this->cache->get(self::STATE_KEY);

        if (! is_string($serialized)) {
            return VendingMachine::empty();
        }

        return unserialize($serialized, ['allowed_classes' => true]);
    }

    public function save(VendingMachine $machine): void
    {
        $this->cache->forever(self::STATE_KEY, serialize($machine));
    }

    public function mutate(callable $operation): mixed
    {
        return $this->cache->lock(self::LOCK_KEY, self::LOCK_TTL_SECONDS)
            ->block(self::LOCK_WAIT_SECONDS, function () use ($operation) {
                $machine = $this->get();
                $result = $operation($machine);
                $this->save($machine);

                return $result;
            });
    }
}
