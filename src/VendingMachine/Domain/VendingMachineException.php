<?php

declare(strict_types=1);

namespace VendingMachine\Domain;

/**
 * Marker interface implemented by every exception the domain can raise.
 *
 * It lets the boundary (CLI, HTTP controller, hardware driver...) catch all
 * expected business failures with a single `catch (VendingMachineException $e)`
 * while still letting unexpected/infrastructure errors bubble up.
 */
interface VendingMachineException extends \Throwable
{
}
