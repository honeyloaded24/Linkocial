<?php

namespace App\Exceptions;

use RuntimeException;

class GiftCardProviderNotConfiguredException extends RuntimeException
{
    public function __construct(string $provider = 'Prestmit', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            "The {$provider} gift card provider is not configured. Real API access is awaiting approval.",
            $code,
            $previous
        );
    }
}
