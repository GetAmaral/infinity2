<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // Set PHP timezone from environment variable
    $timezone = $context['APP_TIMEZONE'] ?? 'UTC';
    date_default_timezone_set($timezone);

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
