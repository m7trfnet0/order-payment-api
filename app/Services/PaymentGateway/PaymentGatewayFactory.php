<?php

namespace App\Services\PaymentGateway;

use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Create a payment gateway instance.
     *
     * @param string $gateway
     * @return PaymentGatewayInterface
     * @throws \InvalidArgumentException
     */
    public static function create(string $gateway): PaymentGatewayInterface
    {
        switch ($gateway) {
            case 'credit_card':
                return new CreditCardGateway();
            case 'paypal':
                return new PayPalGateway();
            case 'bank_transfer':
                return new BankTransferGateway();
            case 'stripe':
                return new StripeGateway();
            default:
                throw new InvalidArgumentException("Unsupported payment gateway: {$gateway}");
        }
    }
}
