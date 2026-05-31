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
        return array_map('intval', $this->validated()['products'] ?? []);
    }

    /** @return array<int, int> */
    public function coinCounts(): array
    {
        $coins = [];
        foreach ($this->validated()['coins'] ?? [] as $value => $quantity) {
            $coins[(int) $value] = (int) $quantity;
        }

        return $coins;
    }
}
