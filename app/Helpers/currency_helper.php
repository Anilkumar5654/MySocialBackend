<?php

/**
 * 💰 MySocial Multi-Currency Helper (Full Updated)
 * Base Currency: INR (Database mein hamesha INR save karein)
 */

if (!function_exists('format_currency')) {
    /**
     * Amount ko User ki pasand ki currency mein symbol ke saath convert karta hai.
     */
    function format_currency($amount, $target_currency = 'INR') {
        // Static cache taaki ek hi request mein baar-baar DB hit na ho
        static $exchange_rates = [];

        $amount = (float)$amount;

        // 1. Agar target INR hai (No conversion needed)
        if (strtoupper($target_currency) === 'INR') {
            return '₹' . number_format($amount, 2, '.', ',');
        }

        // 2. Exchange rate fetch karein (sirf ek baar)
        if (!isset($exchange_rates[$target_currency])) {
            $db = \Config\Database::connect();
            $rateRow = $db->table('exchange_rates')
                         ->where('currency_code', strtoupper($target_currency))
                         ->get()
                         ->getRow();
            
            $exchange_rates[$target_currency] = $rateRow;
        }

        $rateData = $exchange_rates[$target_currency];

        // 3. Agar rate nahi mila toh INR hi dikhao (Safety Fallback)
        if (!$rateData) {
            return '₹' . number_format($amount, 2, '.', ',');
        }

        // 4. Conversion Logic: INR Amount * Rate (e.g., 100 * 0.012 = 1.20 USD)
        $convertedAmount = $amount * (float)$rateData->rate_to_base;
        $symbol = $rateData->symbol ?? '$';

        return $symbol . number_format($convertedAmount, 2, '.', ',');
    }
}

if (!function_exists('convert_amount')) {
    /**
     * Sirf raw converted number deta hai (Math calculations ya Analytics ke liye)
     */
    function convert_amount($amount, $target_currency = 'INR') {
        if (strtoupper($target_currency) === 'INR') return (float)$amount;

        $db = \Config\Database::connect();
        $rate = $db->table('exchange_rates')
                   ->where('currency_code', strtoupper($target_currency))
                   ->get()
                   ->getRow();

        return $rate ? ((float)$amount * (float)$rate->rate_to_base) : (float)$amount;
    }
}

/**
 * Frontend ke liye Currency Object (React Native state management ke liye easy)
 */
if (!function_exists('get_currency_object')) {
    function get_currency_object($amount, $target_currency = 'INR') {
        $formatted = format_currency($amount, $target_currency);
        
        // Symbol alag karne ke liye logic
        $symbol = preg_replace('/[0-9., ]/', '', $formatted);
        $value = preg_replace('/[^0-9.]/', '', $formatted);

        return [
            'raw_base' => (float)$amount,
            'converted_value' => (float)$value,
            'currency_code' => strtoupper($target_currency),
            'symbol' => $symbol,
            'display' => $formatted
        ];
    }
}
