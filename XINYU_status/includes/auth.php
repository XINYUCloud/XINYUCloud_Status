<?php
/**
 * Author XINYU
 * QQ 2556017764
 * Web api.fohok.xin
 **/
require_once __DIR__ . '/../config.php';

function authenticate($require_admin = false) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ip = trim(explode(',', $ip)[0]);
    
    $provided_key = $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
    if ($provided_key !== API_KEY) {
        http_response_code(401);
        header('Content-Type: application/json');
        die(json_encode([
            'status' => 'error',
            'message' => 'Invalid API key',
            'timestamp' => time()
        ]));
    }
    
    if ($require_admin && $ip !== ADMIN_IP) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode([
            'status' => 'error',
            'message' => 'Admin access required',
            'your_ip' => $ip,
            'timestamp' => time()
        ]));
    }
    
    return true;
}

function is_admin() {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return trim(explode(',', $ip)[0]) === ADMIN_IP;
}
?>