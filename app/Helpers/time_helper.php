<?php

use CodeIgniter\I18n\Time;

if (!function_exists('time_ago')) {
    /**
     * Converts a timestamp into "Time Ago" format.
     * Example: "2 hours ago", "Just now", "Yesterday"
     */
    function time_ago($datetime)
    {
        if (empty($datetime)) {
            return '';
        }

        try {
            // CI4 ki Time class use kar rahe hain jo timezone aur locale handle karti hai
            $time = Time::parse($datetime);
            return $time->humanize();
        } catch (\Exception $e) {
            // Agar date parse nahi ho payi to waisi hi return kar do
            return $datetime;
        }
    }
}
?>
