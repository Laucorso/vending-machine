<?php

declare(strict_types=1);

namespace VendingMachine\Application;

interface AuditLog
{
    /** @param array<string, mixed> $context */
    public function record(string $event, array $context = []): void;
}