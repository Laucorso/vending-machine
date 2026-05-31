<?php

declare(strict_types=1);

namespace App\VendingMachine\Audit;

use Illuminate\Log\LogManager;
use VendingMachine\Application\AuditLog;

final class LaravelAuditLog implements AuditLog
{
    public function __construct(private readonly LogManager $logger)
    {
    }

    /** @param array<string, mixed> $context */
    public function record(string $event, array $context = []): void
    {
        $this->logger->channel('audit')->info("vending.{$event}", $context);
    }
}