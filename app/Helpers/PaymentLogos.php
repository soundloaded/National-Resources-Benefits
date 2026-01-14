<?php

namespace App\Helpers;

class PaymentLogos
{
    /**
     * Payment gateway logos from open source repositories
     * Using logos from: 
     * - https://github.com/AkariSeki/payment-gateway-logos (MIT License)
     * - CDN sources with permissive licenses
     */
    protected static array $logos = [
        // Card Networks
        'visa' => 'https://raw.githubusercontent.com/AkariSeki/payment-gateway-logos/main/logos/visa.svg',
        'mastercard' => 'https://raw.githubusercontent.com/AkariSeki/payment-gateway-logos/main/logos/mastercard.svg',
        'amex' => 'https://raw.githubusercontent.com/AkariSeki/payment-gateway-logos/main/logos/amex.svg',
        'discover' => 'https://raw.githubusercontent.com/AkariSeki/payment-gateway-logos/main/logos/discover.svg',
        
        // Payment Processors
        'stripe' => 'https://cdn.brandfetch.io/idxAg10C0L/theme/dark/symbol.svg',
        'paypal' => 'https://cdn.brandfetch.io/idAYg-1Bxc/theme/dark/logo.svg',
        'paystack' => 'https://cdn.brandfetch.io/id1sGxUdWc/theme/dark/logo.svg',
        'flutterwave' => 'https://cdn.brandfetch.io/idPIXoV7N1/theme/dark/logo.svg',
        'monnify' => 'https://monnify.com/assets/img/svg/logo.svg',
        'square' => 'https://cdn.brandfetch.io/idLZGpMKs-/theme/dark/logo.svg',
        'razorpay' => 'https://cdn.brandfetch.io/idHOWbcHUJ/theme/dark/logo.svg',
        
        // Bank Transfer
        'bank_transfer' => 'https://cdn-icons-png.flaticon.com/512/2830/2830284.png',
        'wire_transfer' => 'https://cdn-icons-png.flaticon.com/512/3190/3190615.png',
        'ach' => 'https://cdn-icons-png.flaticon.com/512/2830/2830284.png',
        
        // Manual Methods
        'check' => 'https://cdn-icons-png.flaticon.com/512/1570/1570953.png',
        'cash' => 'https://cdn-icons-png.flaticon.com/512/2489/2489756.png',
        'money_order' => 'https://cdn-icons-png.flaticon.com/512/2830/2830289.png',
        
        // Crypto
        'bitcoin' => 'https://cdn.brandfetch.io/idzKXDp3FR/theme/dark/symbol.svg',
        'ethereum' => 'https://cdn.brandfetch.io/idpRDxhMsb/theme/dark/symbol.svg',
        'usdt' => 'https://cdn.brandfetch.io/idVqBohZGu/theme/dark/symbol.svg',
        'coinbase' => 'https://cdn.brandfetch.io/idIHcPqeIn/theme/dark/logo.svg',
        
        // Mobile Money (Africa)
        'mpesa' => 'https://cdn.brandfetch.io/idk5OIivqb/theme/dark/logo.svg',
        'mtn_momo' => 'https://cdn.brandfetch.io/idw_nDXrAq/theme/dark/logo.svg',
        'airtel_money' => 'https://cdn.brandfetch.io/idhnvQCBYW/theme/dark/logo.svg',
        
        // Digital Wallets
        'apple_pay' => 'https://cdn.brandfetch.io/idnrCPuv87/theme/dark/logo.svg',
        'google_pay' => 'https://cdn.brandfetch.io/id2sE33H04/theme/dark/logo.svg',
        'samsung_pay' => 'https://cdn.brandfetch.io/idiJZAMM3d/theme/dark/logo.svg',
        
        // Default
        'default' => 'https://cdn-icons-png.flaticon.com/512/1086/1086741.png',
    ];

    /**
     * Local fallback paths (for when you want to store logos locally)
     */
    protected static array $localPaths = [
        'stripe' => '/images/payment/stripe.svg',
        'paypal' => '/images/payment/paypal.svg',
        'paystack' => '/images/payment/paystack.svg',
        'flutterwave' => '/images/payment/flutterwave.svg',
        'monnify' => '/images/payment/monnify.svg',
        'default' => '/images/payment/default.svg',
    ];

    /**
     * Get logo URL for a payment gateway
     */
    public static function get(string $gateway, bool $preferLocal = false): string
    {
        $gateway = strtolower(str_replace([' ', '-'], '_', $gateway));
        
        if ($preferLocal && isset(self::$localPaths[$gateway])) {
            $localPath = public_path(self::$localPaths[$gateway]);
            if (file_exists($localPath)) {
                return asset(self::$localPaths[$gateway]);
            }
        }

        return self::$logos[$gateway] ?? self::$logos['default'];
    }

    /**
     * Get all available logos
     */
    public static function all(): array
    {
        return self::$logos;
    }

    /**
     * Get logos for common payment processors
     */
    public static function getProcessors(): array
    {
        return [
            'stripe' => self::$logos['stripe'],
            'paypal' => self::$logos['paypal'],
            'paystack' => self::$logos['paystack'],
            'flutterwave' => self::$logos['flutterwave'],
            'monnify' => self::$logos['monnify'],
            'square' => self::$logos['square'],
            'razorpay' => self::$logos['razorpay'],
        ];
    }

    /**
     * Check if logo exists for gateway
     */
    public static function exists(string $gateway): bool
    {
        $gateway = strtolower(str_replace([' ', '-'], '_', $gateway));
        return isset(self::$logos[$gateway]);
    }

    /**
     * Generate a placeholder logo with gateway initials
     */
    public static function placeholder(string $name): string
    {
        return "https://api.dicebear.com/7.x/initials/svg?" . http_build_query([
            'seed' => $name,
            'size' => 100,
            'backgroundColor' => '0ea5e9',
            'textColor' => 'ffffff',
        ]);
    }
}
