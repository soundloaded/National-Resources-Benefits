<?php

namespace App\Helpers;

use App\Models\Setting;

class Currency
{
    /**
     * All supported currencies with their symbols and details
     */
    protected static array $currencies = [
        // Major Fiat Currencies
        'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'EUR' => ['symbol' => '€', 'name' => 'Euro', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'GBP' => ['symbol' => '£', 'name' => 'British Pound', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'JPY' => ['symbol' => '¥', 'name' => 'Japanese Yen', 'decimal_places' => 0, 'symbol_position' => 'before'],
        'CNY' => ['symbol' => '¥', 'name' => 'Chinese Yuan', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'CHF' => ['symbol' => 'CHF', 'name' => 'Swiss Franc', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'AUD' => ['symbol' => 'A$', 'name' => 'Australian Dollar', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'CAD' => ['symbol' => 'C$', 'name' => 'Canadian Dollar', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'NZD' => ['symbol' => 'NZ$', 'name' => 'New Zealand Dollar', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'HKD' => ['symbol' => 'HK$', 'name' => 'Hong Kong Dollar', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'SGD' => ['symbol' => 'S$', 'name' => 'Singapore Dollar', 'decimal_places' => 2, 'symbol_position' => 'before'],
        
        // African Currencies
        'NGN' => ['symbol' => '₦', 'name' => 'Nigerian Naira', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'ZAR' => ['symbol' => 'R', 'name' => 'South African Rand', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'KES' => ['symbol' => 'KSh', 'name' => 'Kenyan Shilling', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'GHS' => ['symbol' => 'GH₵', 'name' => 'Ghanaian Cedi', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'EGP' => ['symbol' => 'E£', 'name' => 'Egyptian Pound', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'MAD' => ['symbol' => 'MAD', 'name' => 'Moroccan Dirham', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'TZS' => ['symbol' => 'TSh', 'name' => 'Tanzanian Shilling', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'UGX' => ['symbol' => 'USh', 'name' => 'Ugandan Shilling', 'decimal_places' => 0, 'symbol_position' => 'before'],
        'XOF' => ['symbol' => 'CFA', 'name' => 'West African CFA Franc', 'decimal_places' => 0, 'symbol_position' => 'after'],
        'XAF' => ['symbol' => 'FCFA', 'name' => 'Central African CFA Franc', 'decimal_places' => 0, 'symbol_position' => 'after'],
        
        // Asian Currencies
        'INR' => ['symbol' => '₹', 'name' => 'Indian Rupee', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'KRW' => ['symbol' => '₩', 'name' => 'South Korean Won', 'decimal_places' => 0, 'symbol_position' => 'before'],
        'THB' => ['symbol' => '฿', 'name' => 'Thai Baht', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'MYR' => ['symbol' => 'RM', 'name' => 'Malaysian Ringgit', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'IDR' => ['symbol' => 'Rp', 'name' => 'Indonesian Rupiah', 'decimal_places' => 0, 'symbol_position' => 'before'],
        'PHP' => ['symbol' => '₱', 'name' => 'Philippine Peso', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'VND' => ['symbol' => '₫', 'name' => 'Vietnamese Dong', 'decimal_places' => 0, 'symbol_position' => 'after'],
        'PKR' => ['symbol' => '₨', 'name' => 'Pakistani Rupee', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'BDT' => ['symbol' => '৳', 'name' => 'Bangladeshi Taka', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'LKR' => ['symbol' => 'Rs', 'name' => 'Sri Lankan Rupee', 'decimal_places' => 2, 'symbol_position' => 'before'],
        
        // Middle Eastern Currencies
        'AED' => ['symbol' => 'د.إ', 'name' => 'UAE Dirham', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'SAR' => ['symbol' => '﷼', 'name' => 'Saudi Riyal', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'QAR' => ['symbol' => 'QR', 'name' => 'Qatari Riyal', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'KWD' => ['symbol' => 'KD', 'name' => 'Kuwaiti Dinar', 'decimal_places' => 3, 'symbol_position' => 'before'],
        'BHD' => ['symbol' => 'BD', 'name' => 'Bahraini Dinar', 'decimal_places' => 3, 'symbol_position' => 'before'],
        'OMR' => ['symbol' => 'OMR', 'name' => 'Omani Rial', 'decimal_places' => 3, 'symbol_position' => 'before'],
        'ILS' => ['symbol' => '₪', 'name' => 'Israeli Shekel', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'TRY' => ['symbol' => '₺', 'name' => 'Turkish Lira', 'decimal_places' => 2, 'symbol_position' => 'before'],
        
        // Latin American Currencies
        'BRL' => ['symbol' => 'R$', 'name' => 'Brazilian Real', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'MXN' => ['symbol' => 'MX$', 'name' => 'Mexican Peso', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'ARS' => ['symbol' => 'AR$', 'name' => 'Argentine Peso', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'CLP' => ['symbol' => 'CL$', 'name' => 'Chilean Peso', 'decimal_places' => 0, 'symbol_position' => 'before'],
        'COP' => ['symbol' => 'CO$', 'name' => 'Colombian Peso', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'PEN' => ['symbol' => 'S/', 'name' => 'Peruvian Sol', 'decimal_places' => 2, 'symbol_position' => 'before'],
        
        // European Currencies (non-Euro)
        'SEK' => ['symbol' => 'kr', 'name' => 'Swedish Krona', 'decimal_places' => 2, 'symbol_position' => 'after'],
        'NOK' => ['symbol' => 'kr', 'name' => 'Norwegian Krone', 'decimal_places' => 2, 'symbol_position' => 'after'],
        'DKK' => ['symbol' => 'kr', 'name' => 'Danish Krone', 'decimal_places' => 2, 'symbol_position' => 'after'],
        'PLN' => ['symbol' => 'zł', 'name' => 'Polish Zloty', 'decimal_places' => 2, 'symbol_position' => 'after'],
        'CZK' => ['symbol' => 'Kč', 'name' => 'Czech Koruna', 'decimal_places' => 2, 'symbol_position' => 'after'],
        'HUF' => ['symbol' => 'Ft', 'name' => 'Hungarian Forint', 'decimal_places' => 0, 'symbol_position' => 'after'],
        'RON' => ['symbol' => 'lei', 'name' => 'Romanian Leu', 'decimal_places' => 2, 'symbol_position' => 'after'],
        'RUB' => ['symbol' => '₽', 'name' => 'Russian Ruble', 'decimal_places' => 2, 'symbol_position' => 'after'],
        'UAH' => ['symbol' => '₴', 'name' => 'Ukrainian Hryvnia', 'decimal_places' => 2, 'symbol_position' => 'after'],
        
        // Cryptocurrencies
        'BTC' => ['symbol' => '₿', 'name' => 'Bitcoin', 'decimal_places' => 8, 'symbol_position' => 'before'],
        'ETH' => ['symbol' => 'Ξ', 'name' => 'Ethereum', 'decimal_places' => 8, 'symbol_position' => 'before'],
        'USDT' => ['symbol' => '₮', 'name' => 'Tether', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'USDC' => ['symbol' => 'USDC', 'name' => 'USD Coin', 'decimal_places' => 2, 'symbol_position' => 'before'],
        'XRP' => ['symbol' => 'XRP', 'name' => 'Ripple', 'decimal_places' => 6, 'symbol_position' => 'before'],
        'LTC' => ['symbol' => 'Ł', 'name' => 'Litecoin', 'decimal_places' => 8, 'symbol_position' => 'before'],
    ];

    /**
     * Get the default/platform currency code
     */
    public static function getDefaultCode(): string
    {
        return Setting::get('currency_code', 'USD');
    }

    /**
     * Get the default/platform currency symbol
     */
    public static function getSymbol(?string $code = null): string
    {
        $code = $code ?? self::getDefaultCode();
        return self::$currencies[$code]['symbol'] ?? Setting::get('currency_symbol', '$');
    }

    /**
     * Get currency name
     */
    public static function getName(?string $code = null): string
    {
        $code = $code ?? self::getDefaultCode();
        return self::$currencies[$code]['name'] ?? $code;
    }

    /**
     * Get decimal places for a currency
     */
    public static function getDecimalPlaces(?string $code = null): int
    {
        $code = $code ?? self::getDefaultCode();
        return self::$currencies[$code]['decimal_places'] ?? 2;
    }

    /**
     * Format amount with currency symbol
     */
    public static function format(float|int|string $amount, ?string $code = null, bool $showCode = false): string
    {
        $code = $code ?? self::getDefaultCode();
        $currency = self::$currencies[$code] ?? self::$currencies['USD'];
        
        $formattedAmount = number_format(
            (float) $amount,
            $currency['decimal_places'],
            '.',
            ','
        );

        $symbol = $currency['symbol'];
        $position = $currency['symbol_position'];

        if ($position === 'after') {
            $result = $formattedAmount . ' ' . $symbol;
        } else {
            $result = $symbol . $formattedAmount;
        }

        if ($showCode) {
            $result .= ' ' . $code;
        }

        return $result;
    }

    /**
     * Format for display without symbol (just number)
     */
    public static function formatNumber(float|int|string $amount, ?string $code = null): string
    {
        $code = $code ?? self::getDefaultCode();
        $decimals = self::getDecimalPlaces($code);
        
        return number_format((float) $amount, $decimals, '.', ',');
    }

    /**
     * Get all currencies as options array for selects
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::$currencies as $code => $data) {
            $options[$code] = "{$data['symbol']} - {$data['name']} ({$code})";
        }
        return $options;
    }

    /**
     * Get fiat currencies only
     */
    public static function getFiatOptions(): array
    {
        $crypto = ['BTC', 'ETH', 'USDT', 'USDC', 'XRP', 'LTC'];
        return array_filter(self::getOptions(), fn($v, $k) => !in_array($k, $crypto), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get crypto currencies only
     */
    public static function getCryptoOptions(): array
    {
        $crypto = ['BTC', 'ETH', 'USDT', 'USDC', 'XRP', 'LTC'];
        return array_filter(self::getOptions(), fn($v, $k) => in_array($k, $crypto), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Check if currency code is valid
     */
    public static function isValid(string $code): bool
    {
        return isset(self::$currencies[$code]);
    }

    /**
     * Get currency details
     */
    public static function get(string $code): ?array
    {
        return self::$currencies[$code] ?? null;
    }

    /**
     * Get all currencies
     */
    public static function all(): array
    {
        return self::$currencies;
    }

    /**
     * Get prefix for form fields (Filament compatible)
     */
    public static function getPrefix(?string $code = null): string
    {
        $code = $code ?? self::getDefaultCode();
        $currency = self::$currencies[$code] ?? self::$currencies['USD'];
        
        return $currency['symbol_position'] === 'before' ? $currency['symbol'] : '';
    }

    /**
     * Get suffix for form fields (Filament compatible)
     */
    public static function getSuffix(?string $code = null): string
    {
        $code = $code ?? self::getDefaultCode();
        $currency = self::$currencies[$code] ?? self::$currencies['USD'];
        
        return $currency['symbol_position'] === 'after' ? $currency['symbol'] : '';
    }
}
