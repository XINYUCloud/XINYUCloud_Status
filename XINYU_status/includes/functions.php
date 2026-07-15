<?php
/**
 * Author XINYU
 * QQ 2556017764
 * Web api.fohok.xin
 **/
require_once __DIR__ . '/../config.php';

/**
 * 获取监控站点配置
 */
function get_sites() {
    if (!file_exists(SITES_FILE)) {
        return [];
    }
    
    $content = file_get_contents(SITES_FILE);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid JSON in sites file: " . json_last_error_msg());
        return [];
    }
    
    return $data;
}

/**
 * 保存站点配置
 */
function save_sites($sites) {
    if (!is_writable(DATA_DIR)) {
        throw new Exception("Data directory is not writable");
    }
    
    $result = file_put_contents(SITES_FILE, json_encode($sites, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        throw new Exception("Failed to save sites configuration");
    }
    
    return true;
}

/**
 * 获取状态历史记录
 */
function get_status_history() {
    if (!file_exists(STATUS_FILE)) {
        return [];
    }
    
    $content = file_get_contents(STATUS_FILE);
    $data = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid JSON in status file: " . json_last_error_msg());
        return [];
    }
    
    return $data;
}

/**
 * 保存状态历史记录
 */
function save_status_history($data) {
    if (file_exists(STATUS_FILE) && filesize(STATUS_FILE) > MAX_LOG_SIZE) {
        $backup_file = DATA_DIR . '/status_history_' . date('Ymd_His') . '.json';
        rename(STATUS_FILE, $backup_file);
    }
    
    $result = file_put_contents(STATUS_FILE, json_encode($data, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        throw new Exception("Failed to save status history");
    }
    
    return true;
}

/**
 * 检查网站状态
 */
function check_site_status($site_config) {
    $start_time = microtime(true);
    $url = rtrim($site_config['url'], '/') . ($site_config['check_path'] ?? '/200.php');
    $secret_key = "5201314";
    $current_minute = date('YmdHi');
    $access_token = md5($current_minute . $secret_key);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url . '?token=' . urlencode($access_token),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => TIMEOUT,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_USERAGENT => 'ServerStatusMonitor/2.0',
        CURLOPT_ENCODING => ''
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $total_time = microtime(true) - $start_time;
    $error = curl_error($ch);
    curl_close($ch);
    
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    $json_response = json_decode($body, true);
    
    $status = 'error';
    $message = $error ?: 'OK';
    
    if ($http_code === ($site_config['expected_status'] ?? 200)) {
        if (isset($site_config['expected_response'])) {
            $diff = array_diff_assoc($site_config['expected_response'], $json_response);
            if (empty($diff)) {
                $status = 'ok';
            } else {
                $message = 'Response mismatch: ' . json_encode($diff);
            }
        } else {
            $status = 'ok';
        }
    } else {
        $message = "Expected status {$site_config['expected_status']}, got $http_code";
    }
    
    $ssl_info = check_ssl_certificate($site_config['url']);
    
    $domain = parse_url($site_config['url'], PHP_URL_HOST);
    $dns_info = check_dns_resolution($domain);
    
    return [
        'status' => $status,
        'code' => $http_code,
        'response_time' => round($total_time * 1000),
        'ssl' => $ssl_info,
        'dns' => $dns_info,
        'message' => $message,
        'timestamp' => time(),
        'raw_response' => $error ? null : $body,
        'headers' => $error ? null : parse_headers($headers)
    ];
}

/**
 * 检查SSL证书
 */
function check_ssl_certificate($url) {
    $host = parse_url($url, PHP_URL_HOST);
    $port = parse_url($url, PHP_URL_PORT) ?: 443;
    
    $context = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $client = @stream_socket_client(
        "ssl://{$host}:{$port}",
        $errno,
        $errstr,
        3,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if (!$client) {
        return [
            'valid' => false,
            'error' => $errstr,
            'code' => $errno
        ];
    }
    
    $cert = stream_context_get_params($client)['options']['ssl']['peer_certificate'];
    $cert_info = openssl_x509_parse($cert);
    fclose($client);
    
    $valid_to = $cert_info['validTo_time_t'];
    $valid_from = $cert_info['validFrom_time_t'];
    
    return [
        'valid' => $valid_to > time(),
        'issuer' => $cert_info['issuer']['O'] ?? 'Unknown',
        'subject' => $cert_info['subject']['CN'] ?? 'Unknown',
        'expires' => date('Y-m-d H:i:s', $valid_to),
        'issued' => date('Y-m-d H:i:s', $valid_from),
        'days_remaining' => floor(($valid_to - time()) / 86400),
        'serial' => $cert_info['serialNumberHex'] ?? null,
        'signature_algo' => $cert_info['signatureTypeSN'] ?? null
    ];
}

/**
 * 检查DNS解析
 */
function check_dns_resolution($domain) {
    $start = microtime(true);
    
    $a_records = @dns_get_record($domain, DNS_A);
    $time = microtime(true) - $start;
    
    $mx_records = @dns_get_record($domain, DNS_MX);
    
    $txt_records = @dns_get_record($domain, DNS_TXT);
    
    return [
        'resolved' => !empty($a_records),
        'a_records' => array_column($a_records, 'ip'),
        'mx_records' => array_map(function($mx) {
            return [
                'host' => $mx['target'],
                'priority' => $mx['pri']
            ];
        }, $mx_records),
        'txt_records' => array_column($txt_records, 'txt'),
        'resolution_time' => round($time * 1000),
        'timestamp' => time()
    ];
}

/**
 * 解析HTTP头
 */
function parse_headers($headers) {
    $headers = explode("\r\n", trim($headers));
    $parsed = [];
    
    foreach ($headers as $header) {
        if (strpos($header, ':') !== false) {
            list($name, $value) = explode(':', $header, 2);
            $parsed[trim($name)] = trim($value);
        } else {
            $parsed[] = $header;
        }
    }
    
    return $parsed;
}

/**
 * 更新状态历史记录
 */
function update_status_history($site_name, $status_data) {
    $history = get_status_history();
    $today = date('Y-m-d');
    
    if (!isset($history[$site_name])) {
        $history[$site_name] = [];
    }
    
    $history[$site_name][$today] = $status_data;

    $cutoff_date = date('Y-m-d', strtotime('-' . HISTORY_DAYS . ' days'));
    foreach ($history[$site_name] as $date => $value) {
        if ($date < $cutoff_date) {
            unset($history[$site_name][$date]);
        }
    }
    
    save_status_history($history);
}

/**
 * 计算可用率统计
 */
function calculate_uptime_stats($site_name) {
    $history = get_status_history();
    if (!isset($history[$site_name])) {
        return [
            'daily' => 0,
            'weekly' => 0,
            'monthly' => 0,
            'all_time' => 0
        ];
    }
    
    $site_history = $history[$site_name];
    $today = date('Y-m-d');
    $week_start = date('Y-m-d', strtotime('-6 days'));
    $month_start = date('Y-m-d', strtotime('-29 days'));
    
    $stats = [
        'daily' => ['ok' => 0, 'total' => 0],
        'weekly' => ['ok' => 0, 'total' => 0],
        'monthly' => ['ok' => 0, 'total' => 0],
        'all_time' => ['ok' => 0, 'total' => 0]
    ];
    
    foreach ($site_history as $date => $status) {
        $is_ok = $status['status'] === 'ok';
        
        $stats['all_time']['total']++;
        if ($is_ok) $stats['all_time']['ok']++;

        if ($date >= $month_start) {
            $stats['monthly']['total']++;
            if ($is_ok) $stats['monthly']['ok']++;
        }
        
        if ($date >= $week_start) {
            $stats['weekly']['total']++;
            if ($is_ok) $stats['weekly']['ok']++;
        }
        
        if ($date === $today) {
            $stats['daily']['total']++;
            if ($is_ok) $stats['daily']['ok']++;
        }
    }
    
    return [
        'daily' => $stats['daily']['total'] ? round($stats['daily']['ok'] / $stats['daily']['total'] * 100, 2) : 0,
        'weekly' => $stats['weekly']['total'] ? round($stats['weekly']['ok'] / $stats['weekly']['total'] * 100, 2) : 0,
        'monthly' => $stats['monthly']['total'] ? round($stats['monthly']['ok'] / $stats['monthly']['total'] * 100, 2) : 0,
        'all_time' => $stats['all_time']['total'] ? round($stats['all_time']['ok'] / $stats['all_time']['total'] * 100, 2) : 0
    ];
}

/**
 * 获取响应时间趋势数据
 */
function get_response_time_trend($site_name, $days = 15) {
    $history = get_status_history();
    if (!isset($history[$site_name])) {
        return [];
    }
    
    $trend = [];
    $dates = [];
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $dates[] = date('Y-m-d', strtotime("-$i days"));
    }
    
    foreach ($dates as $date) {
        $trend[$date] = [
            'response_time' => $history[$site_name][$date]['response_time'] ?? null,
            'status' => $history[$site_name][$date]['status'] ?? 'unknown'
        ];
    }
    
    return $trend;
}

/**
 * 添加新监控站点
 */
function add_monitored_site($name, $url, $check_path = '/200.php', $expected_status = 200, $expected_response = null) {
    $sites = get_sites();
    
    foreach ($sites as $site) {
        if ($site['name'] === $name || $site['url'] === $url) {
            throw new Exception("Site already exists");
        }
    }
    
    $new_site = [
        'name' => $name,
        'url' => $url,
        'check_path' => $check_path,
        'expected_status' => $expected_status
    ];
    
    if ($expected_response) {
        $new_site['expected_response'] = $expected_response;
    }
    
    $sites[] = $new_site;
    save_sites($sites);
    
    return $new_site;
}

/**
 * 删除监控站点
 */
function remove_monitored_site($name) {
    $sites = get_sites();
    $new_sites = array_filter($sites, function($site) use ($name) {
        return $site['name'] !== $name;
    });
    
    if (count($sites) === count($new_sites)) {
        throw new Exception("Site not found");
    }
    
    save_sites(array_values($new_sites));
    
    $history = get_status_history();
    if (isset($history[$name])) {
        unset($history[$name]);
        save_status_history($history);
    }
    
    return true;
}
?>