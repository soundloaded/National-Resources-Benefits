<?php

use App\Helpers\Currency;
use App\Helpers\Avatar;
use App\Helpers\PaymentLogos;

if (!function_exists('currency_symbol')) {
    /**
     * Get currency symbol
     */
    function currency_symbol(?string $code = null): string
    {
        return Currency::getSymbol($code);
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format amount with currency
     */
    function format_currency(float|int|string $amount, ?string $code = null): string
    {
        return Currency::format($amount, $code);
    }
}

if (!function_exists('gravatar')) {
    /**
     * Get gravatar URL
     */
    function gravatar(?string $email, int $size = 200): string
    {
        return Avatar::gravatar($email, $size);
    }
}

if (!function_exists('payment_logo')) {
    /**
     * Get payment gateway logo URL
     */
    function payment_logo(string $gateway): string
    {
        return PaymentLogos::get($gateway);
    }
}
