<?php
/**
 * Author XINYU
 * QQ 2556017764
 * Web api.fohok.xin
 **/
define('API_KEY', '自定义密钥'); // 安全密钥
define('ADMIN_IP', '127.0.0.1'); // 管理ip

define('DATA_DIR', __DIR__ . '/data');
define('SITES_FILE', DATA_DIR . '/sites.json');
define('STATUS_FILE', DATA_DIR . '/status_history.json');
define('CONFIG_LOCK_FILE', DATA_DIR . '/config.lock');

define('HISTORY_DAYS', 30); // 保留30天历史记录
define('CHECK_INTERVAL', 300); // 5分钟检查一次(秒)
define('TIMEOUT', 10); // 请求超时时间(秒)
define('MAX_LOG_SIZE', 1048576); // 1MB最大日志大小

if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
    file_put_contents(CONFIG_LOCK_FILE, '');
}

if (!file_exists(SITES_FILE)) {
    $default_sites = [
        [
            "name" => "baidu",
            "url" => "https://www.baidu.com",
            "check_path" => "/200.php",
            "expected_status" => 200,
            "expected_response" => ['Status' => '200']
        ]
    ];
    file_put_contents(SITES_FILE, json_encode($default_sites, JSON_PRETTY_PRINT));
}

if (!file_exists(STATUS_FILE)) {
    file_put_contents(STATUS_FILE, json_encode([], JSON_PRETTY_PRINT));
}
?>