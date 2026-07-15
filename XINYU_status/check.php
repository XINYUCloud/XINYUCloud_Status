<?php
/**
 *  Author XINYU
 * QQ 2556017764
 * Web api.fohok.xin
 **/
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

authenticate();

$sites = get_sites();
$results = [];

foreach ($sites as $site) {
    try {
        $status = check_site_status($site);
        update_status_history($site['name'], $status);
        $results[$site['name']] = $status;
        
        if (count($sites) > 1) {
            sleep(1);
        }
    } catch (Exception $e) {
        $results[$site['name']] = [
            'status' => 'error',
            'code' => 500,
            'message' => $e->getMessage(),
            'timestamp' => time()
        ];
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'timestamp' => time(),
    'next_check' => time() + CHECK_INTERVAL,
    'checked_sites' => count($sites),
    'online_sites' => count(array_filter($results, function($r) { 
    return $r['status'] === 'ok'; 
})),
    'results' => $results
], JSON_PRETTY_PRINT);
?>