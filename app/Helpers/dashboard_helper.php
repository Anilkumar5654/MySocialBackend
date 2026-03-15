<?php

if (!function_exists('dashboard_time_badge')) {
    function dashboard_time_badge($datetime)
    {
        if (empty($datetime)) return '';

        $timestamp = strtotime($datetime);
        $today = strtotime('today');
        $yesterday = strtotime('yesterday');

        if ($timestamp >= $today) {
            // Agar aaj ka hai
            return '<span class="badge badge-success px-2 py-1" style="font-size:9px; background: rgba(10, 187, 135, 0.1); color: #0abb87; border: 1px solid #0abb87;">TODAY</span> <span class="small text-muted font-weight-bold ml-1">' . date('h:i A', $timestamp) . '</span>';
        } elseif ($timestamp >= $yesterday && $timestamp < $today) {
            // Agar kal ka hai
            return '<span class="badge badge-warning px-2 py-1" style="font-size:9px; background: rgba(249, 155, 45, 0.1); color: #f99b2d; border: 1px solid #f99b2d;">YESTERDAY</span> <span class="small text-muted font-weight-bold ml-1">' . date('h:i A', $timestamp) . '</span>';
        } else {
            // Agar aur purana hai
            return '<span class="small text-muted font-weight-bold">' . date('d M, Y', $timestamp) . '</span>';
        }
    }
}
