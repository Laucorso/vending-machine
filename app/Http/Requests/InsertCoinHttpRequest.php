<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HTTP-layer validation for inserting a coin. It only guards shape (a value is
 * present and numeric); whether the value is an *accepted* denomination is a
 * domain rule, enforced by Coin::fromDecimal so there is a single source of
 * truth for it.
 */
final class InsertCoinHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'coin' => ['required', 'numeric'],
        ];
    }

    public function coin(): int|float|string
    {
        /** @var int|float|string $coin */
        $coin = $this->validated('coin');

        return $coin;
    }
}
