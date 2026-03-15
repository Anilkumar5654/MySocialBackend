<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to make reading short.
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'rbac'          => \App\Filters\AdminRoleFilter::class,
        'authFilter'    => \App\Filters\AuthFilter::class, // Added AuthFilter for Mobile API
    ];

    /**
     * List of special required filters.
     */
    public array $required = [
        'before' => [
            'forcehttps', 
            'pagecache',
        ],
        'after' => [
            'pagecache', 
            'performance', 
            'toolbar',
        ],
    ];

    /**
     * List of filter aliases that are always applied to every request.
     */
    public array $globals = [
        'before' => [
            // 'csrf',
        ],
        'after' => [],
    ];

    /**
     * List of filter aliases that are applied to HTTP methods (GET, POST, etc.).
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on specific HTTP uri patterns.
     */
    public array $filters = [];
}
