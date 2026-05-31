<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HTTP-layer validation for a service visit: product counts keyed by selector
 * and coin counts keyed by coin value (in cents).
 */
final class ServiceHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'products' => ['array'],
            'products.*' => ['integer', 'min:0'],
            'coins' => ['array'],
            'coins.*' => ['integer', 'min:0'],
        ];
    }

    /** @return array<string, int> */
    public function productCounts(): array
    {
        /** @var array<string, mixed> $products */
        $products = $this->validated()['products'] ?? [];

        $result = [];
        foreach ($products as $selector => $quantity) {
            $result[(string) $selector] = is_numeric($quantity) ? (int) $quantity : 0;
        }

        return $result;
    }

    /** @return array<int, int> */
    public function coinCounts(): array
    {
        /** @var array<int|string, mixed> $coins */
        $coins = $this->validated()['coins'] ?? [];

        $result = [];
        foreach ($coins as $value => $quantity) {
            $result[(int) $value] = is_numeric($quantity) ? (int) $quantity : 0;
        }

        return $result;
    }
}
