<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InsertCoinHttpRequest;
use App\Http\Requests\ServiceHttpRequest;
use Illuminate\Http\JsonResponse;
use VendingMachine\Application\Action\InsertCoinAction;
use VendingMachine\Application\Action\ReturnCoinsAction;
use VendingMachine\Application\Action\SelectProductAction;
use VendingMachine\Application\Action\ServiceMachineAction;
use VendingMachine\Application\Request\InsertCoinRequest;
use VendingMachine\Application\Request\SelectProductRequest;
use VendingMachine\Application\Request\ServiceRequest;

/**
 * Thin delivery adapter: it translates HTTP to application Requests, invokes
 * the matching Action, and serialises the result DTO to JSON. It holds no
 * business logic. Domain failures are turned into 422 responses centrally in
 * bootstrap/app.php, so this stays clean.
 *
 * Actions are injected per-method; the container builds them from the bindings
 * declared in VendingMachineServiceProvider.
 */
final class VendingMachineController extends Controller
{
    public function insert(InsertCoinHttpRequest $request, InsertCoinAction $action): JsonResponse
    {
        $result = $action->execute(InsertCoinRequest::fromValue($request->coin()));

        return response()->json(['inserted_total' => $result->insertedTotal]);
    }

    public function returnCoins(ReturnCoinsAction $action): JsonResponse
    {
        $result = $action->execute();

        return response()->json(['returned' => $result->coins]);
    }

    public function select(string $selector, SelectProductAction $action): JsonResponse
    {
        $result = $action->execute(SelectProductRequest::fromCode($selector));

        return response()->json([
            'product' => $result->product,
            'change'  => $result->change,
        ]);
    }

    public function service(ServiceHttpRequest $request, ServiceMachineAction $action): JsonResponse
    {
        $action->execute(ServiceRequest::fromCounts(
            $request->productCounts(),
            $request->coinCounts(),
        ));

        return response()->json(['status' => 'serviced']);
    }
}
