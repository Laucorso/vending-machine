<?php

declare(strict_types=1);

namespace VendingMachine\Domain;

/**
 * Marker interface implemented by every exception the domain can raise.
 */
interface VendingMachineException extends \Throwable
{
    public function errorCode(): ErrorCode;
}
