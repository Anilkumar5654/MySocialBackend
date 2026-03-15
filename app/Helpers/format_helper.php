<?php

/**
 * Ye function numbers ko (K, M) mein convert karta hai
 * Subscribers aur Watch Time dono ke liye best hai.
 */
if (!function_exists('format_number_k')) {
    function format_number_k($num) {
        // Agar number nahi hai ya zero hai
        if (!$num || !is_numeric($num)) return '0';

        if ($num >= 1000000) {
            // 10 Lakh se upar ke liye (M)
            $val = round($num / 1000000, 1);
            return ($val == (int)$val ? (int)$val : $val) . 'M';
        }
        
        if ($num >= 1000) {
            // 1 hazar se upar ke liye (K)
            $val = round($num / 1000, 1);
            return ($val == (int)$val ? (int)$val : $val) . 'K';
        }
        
        // Agar 1000 se kam hai (e.g. 500 ya 450.5)
        return (fmod($num, 1) !== 0.0) ? number_format($num, 1) : $num;
    }
}
