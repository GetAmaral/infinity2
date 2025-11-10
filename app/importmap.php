<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.20',
    ],
    'bootstrap' => [
        'version' => '5.3.8',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.8',
        'type' => 'css',
    ],
    'bootstrap-icons/font/bootstrap-icons.min.css' => [
        'version' => '1.13.1',
        'type' => 'css',
    ],
    'chart.js' => [
        'version' => '4.5.1',
    ],
    'chart.js/auto' => [
        'version' => '4.5.1',
    ],
    'plyr' => [
        'version' => '3.8.3',
    ],
    'plyr/dist/plyr.css' => [
        'version' => '3.8.3',
        'type' => 'css',
    ],
    'hls.js' => [
        'version' => '1.6.14',
    ],
    'tom-select' => [
        'version' => '2.4.3',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'sortablejs' => [
        'version' => '1.15.6',
    ],
    '@floating-ui/dom' => [
        'version' => '1.7.4',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
    'rangetouch' => [
        'version' => '2.0.1',
    ],
    'loadjs' => [
        'version' => '4.3.0',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    '@floating-ui/core' => [
        'version' => '1.7.3',
    ],
    '@floating-ui/utils' => [
        'version' => '0.2.10',
    ],
    '@floating-ui/utils/dom' => [
        'version' => '0.2.10',
    ],
    'plyr/dist/plyr.min.css' => [
        'version' => '3.8.3',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
];
