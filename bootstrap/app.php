<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use VendingMachine\Application\AuditLog;
use VendingMachine\Domain\VendingMachineException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->report(function (VendingMachineException $e): bool {
            app(AuditLog::class)->record('operation_failed', [
                'reason' => $e->errorCode()->value,
            ]);

            return false;
        });

        $exceptions->render(
            fn (VendingMachineException $e) => response()->json([
                'error' => $e->getMessage(),
                'code' => $e->errorCode()->value,
            ], 422),
        );
    })->create();
