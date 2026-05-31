<?php

declare(strict_types=1);

namespace VendingMachine\Domain;

enum ErrorCode: string
{
    case InvalidCoin = 'INVALID_COIN';
    case ProductNotFound = 'PRODUCT_NOT_FOUND';
    case OutOfStock = 'OUT_OF_STOCK';
    case InsufficientFunds = 'INSUFFICIENT_FUNDS';
    case InsufficientChange = 'INSUFFICIENT_CHANGE';
}
