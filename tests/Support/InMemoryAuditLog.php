<?php

declare(strict_types=1);

namespace Tests\Support;

use VendingMachine\Application\AuditLog;

final class InMemoryAuditLog implements AuditLog
{
    /** @var list<array{event: string, context: array<string, mixed>}> */
    public array $events = [];

    /** @param array<string, mixed> $context */
    public function record(string $event, array $context = []): void
    {
        $this->events[] = ['event' => $event, 'context' => $context];
    }
}