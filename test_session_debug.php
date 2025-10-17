#!/usr/bin/env php
<?php

// Test session configuration
echo "=== Session Configuration Test ===\n";
echo "SESSION_COOKIE_DOMAIN: " . (getenv('SESSION_COOKIE_DOMAIN') ?: 'EMPTY') . "\n";
echo "session.save_path: " . (ini_get('session.save_path') ?: 'default') . "\n";
echo "session.cookie_domain: " . (ini_get('session.cookie_domain') ?: 'empty') . "\n";
echo "session.cookie_samesite: " . (ini_get('session.cookie_samesite') ?: 'empty') . "\n";
echo "session.cookie_secure: " . (ini_get('session.cookie_secure') ? 'true' : 'false') . "\n";
echo "\n";

// Test if sessions directory is writable
$sessionPath = __DIR__ . '/var/sessions/dev';
echo "=== Session Directory Test ===\n";
echo "Path: $sessionPath\n";
echo "Exists: " . (is_dir($sessionPath) ? 'yes' : 'no') . "\n";
echo "Writable: " . (is_writable($sessionPath) ? 'yes' : 'no') . "\n";
echo "Files count: " . count(glob("$sessionPath/sess_*")) . "\n";
echo "\n";

// List recent session files
echo "=== Recent Session Files ===\n";
$files = glob("$sessionPath/sess_*");
usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
foreach (array_slice($files, 0, 5) as $file) {
    echo basename($file) . " - " . date('H:i:s', filemtime($file)) . "\n";
    $content = file_get_contents($file);
    if (strpos($content, 'csrf') !== false) {
        echo "  Has CSRF token\n";
    }
    if (strpos($content, '_active_organization') !== false) {
        echo "  Has organization context\n";
    }
}
