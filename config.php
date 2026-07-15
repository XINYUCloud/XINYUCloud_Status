<?php
error_reporting(E_ALL); ini_set('display_errors', '1'); ini_set('log_errors', '1');
date_default_timezone_set('Asia/Shanghai');
define('BASE_PATH', __DIR__);
function loadEnv($path) {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key).'='.trim(trim($value), '"\''));
    }
}
loadEnv(__DIR__ . '/.env');
function env($key, $default = null) { $v = getenv($key); return $v === false ? $default : $v; }
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'xinyu_status'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');
define('REDIS_HOST', env('REDIS_HOST', '127.0.0.1'));
define('REDIS_PORT', env('REDIS_PORT', '6379'));
define('REDIS_PASS', env('REDIS_PASS', ''));
define('REDIS_DB', env('REDIS_DB', '0'));
define('REDIS_PREFIX', 'xinyu_status:');
define('SESSION_LIFETIME', 86400);
define('SESSION_NAME', 'xinyu_sid');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('BCRYPT_COST', 12);
define('RATE_LIMIT_MAX', 100);
define('RATE_LIMIT_WINDOW', 60);
define('DEFAULT_CHECK_INTERVAL', 300);
define('DEFAULT_TIMEOUT', 10);
define('DEFAULT_HISTORY_DAYS', 90);
define('APP_VERSION', '3.0.0');
define('APP_NAME', 'XINYU Status Monitor');
require_once __DIR__ . '/includes/functions.php';