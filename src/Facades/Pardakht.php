<?php

namespace Fiachehr\Pardakht\Facades;

use Fiachehr\Pardakht\Contracts\GatewayInterface;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\ValueObjects\PaymentResponse;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Fiachehr\Pardakht\ValueObjects\VerificationResponse;
use Illuminate\Support\Facades\Facade;

/**
 * Class Pardakht
 *
 * Facade for easy access to the payment gateway manager
 *
 * @method static GatewayInterface gateway(?string $name = null)
 * @method static PaymentResponse request(PaymentRequest $request, ?string $gateway = null)
 * @method static VerificationResponse verify(VerificationRequest $request, ?string $gateway = null)
 * @method static array available()
 * @method static \Fiachehr\Pardakht\Manager\GatewayManager extend(string $name, string $class)
 *
 * @see \Fiachehr\Pardakht\Manager\GatewayManager
 */
class Pardakht extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pardakht';
    }
}
