<?php

namespace App\Exceptions;

use RuntimeException;

class GiftCardProviderNotConfiguredException extends RuntimeException
{
    public function __construct(string $provider = 'Gift Card', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Gift card fulfillment is currently unavailable. Please try again later.",
            $code,
            $previous
        );
    }
}
