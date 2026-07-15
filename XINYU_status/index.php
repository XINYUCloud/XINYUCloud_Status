<?php
/**
 * Author XINYU
 * QQ 2556017764
 * Web api.fohok.xin
 **/
require_once __DIR__ . '/includes/functions.php';

$sites = get_sites();
$status_history = get_status_history();
$current_status = [];

foreach ($sites as $site) {
    $site_name = $site['name'];
    $today = date('Y-m-d');
    
    if (isset($status_history[$site_name][$today])) {
        $current_status[$site_name] = $status_history[$site_name][$today];
    } else {
        $recent_status = null;
        foreach ($status_history[$site_name] ?? [] as $date => $status) {
            if (!$recent_status || $date > $recent_status['date']) {
                $recent_status = $status;
                $recent_status['date'] = $date;
            }
        }
        
        $current_status[$site_name] = $recent_status ?? [
            'status' => 'unknown',
            'code' => 'N/A',
            'response_time' => 0,
            'message' => 'No data available',
            'timestamp' => 0
        ];
    }
}

$dates = [];
for ($i = HISTORY_DAYS - 1; $i >= 0; $i--) {
    $dates[] = date('Y-m-d', strtotime("-$i days"));
}

$summary_stats = [
'total_sites' => count($sites),
'online_sites' => count(array_filter($current_status, function($s) { 
    return $s['status'] === 'ok'; 
})),
    'avg_response' => 0
];

if ($summary_stats['total_sites'] > 0) {
    $response_times = array_column($current_status, 'response_time');
    $summary_stats['avg_response'] = round(array_sum($response_times) / count($response_times));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 服务状态监控面板</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="assets/css/i.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <h1>📋 服务状态监控面板</h1>
                <div class="header-info">
                    <div class="last-updated">
                        <span class="material-icons">update</span>
                        <span>最后更新: <?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                    <div class="next-check">
                        <span class="material-icons">schedule</span>
                        <span id="next-check-text">下次检查: 计算中...</span>
                    </div>
                </div>
            </div>
            
            <div class="system-status">
                <span class="status-indicator active"></span>
                <span>系统运行中</span>
                <span class="divider">|</span>
                <span class="material-icons">settings</span>
                <span>版本 2.0</span>
            </div>
        </header>
        
        <div class="summary-stats">
            <div class="summary-card total-sites">
                <div class="card-icon">
                    <span class="material-icons">dns</span>
                </div>
                <div class="card-content">
                    <span class="summary-value"><?php echo $summary_stats['total_sites']; ?></span>
                    <span class="summary-label">监控站点</span>
                </div>
            </div>
            
            <div class="summary-card online-sites">
                <div class="card-icon">
                    <span class="material-icons">check_circle</span>
                </div>
                <div class="card-content">
                    <span class="summary-value"><?php echo $summary_stats['online_sites']; ?></span>
                    <span class="summary-label">在线站点</span>
                </div>
            </div>
            
            <div class="summary-card avg-response">
                <div class="card-icon">
                    <span class="material-icons">speed</span>
                </div>
                <div class="card-content">
                    <span class="summary-value"><?php echo $summary_stats['avg_response']; ?> ms</span>
                    <span class="summary-label">平均响应</span>
                </div>
            </div>
            
            <div class="summary-card last-checked">
                <div class="card-icon">
                    <span class="material-icons">history</span>
                </div>
                <div class="card-content">
                    <span class="summary-value">
                        <?php 
                            $last_checked = filemtime(STATUS_FILE);
                            echo $last_checked ? date('H:i:s', $last_checked) : 'N/A';
                        ?>
                    </span>
                    <span class="summary-label">最后检查</span>
                </div>
            </div>
        </div>
        
        <div class="status-grid">
            <?php foreach ($sites as $site): 
                $site_name = $site['name'];
                $status = $current_status[$site_name] ?? [
                    'status' => 'unknown',
                    'code' => 'N/A',
                    'response_time' => 0,
                    'message' => 'No data available',
                    'timestamp' => 0
                ];
                
                $uptime_stats = calculate_uptime_stats($site_name);
                $response_trend = get_response_time_trend($site_name);
                $chart_id = 'chart-' . preg_replace('/[^a-z0-9]/i', '-', $site_name);
            ?>
            <div class="status-card <?php echo $status['status']; ?>">
                <div class="status-header">
                    <h3><?php echo htmlspecialchars($site_name); ?></h3>
                    <div class="status-meta">
                        <span class="status-badge"><?php 
                            echo $status['status'] === 'ok' ? '在线' : 
                                 ($status['status'] === 'error' ? '离线' : '未知');
                        ?></span>
                        <span class="response-time"><?php echo $status['response_time']; ?> ms</span>
                    </div>
                </div>
                
                <div class="status-details">
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons">http</span>
                            <span>状态码:</span>
                        </span>
                        <span class="detail-value"><?php echo htmlspecialchars($status['code']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons">timer</span>
                            <span>响应时间:</span>
                        </span>
                        <span class="detail-value"><?php echo $status['response_time']; ?> ms</span>
                    </div>
                    
                    <?php if (isset($status['ssl'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons">lock</span>
                            <span>SSL证书:</span>
                        </span>
                        <span class="detail-value ssl-<?php echo $status['ssl']['valid'] ? 'valid' : 'invalid'; ?>">
                            <?php if ($status['ssl']['valid']): ?>
                                剩余<?php echo $status['ssl']['days_remaining']; ?>天
                            <?php else: ?>
                                无效/过期
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($status['dns'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons">dns</span>
                            <span>DNS解析:</span>
                        </span>
                        <span class="detail-value dns-<?php echo $status['dns']['resolved'] ? 'resolved' : 'failed'; ?>">
                            <?php if ($status['dns']['resolved']): ?>
                                成功 (<?php echo implode(', ', $status['dns']['a_records']); ?>)
                            <?php else: ?>
                                失败
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons">message</span>
                            <span>状态消息:</span>
                        </span>
                        <span class="detail-value"><?php echo htmlspecialchars($status['message']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">
                            <span class="material-icons">access_time</span>
                            <span>最后检测:</span>
                        </span>
                        <span class="detail-value">
                            <?php echo $status['timestamp'] ? date('Y-m-d H:i:s', $status['timestamp']) : '从未检测'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="uptime-stats">
                    <h4>可用率统计</h4>
                    <div class="stat-row">
                        <span class="stat-label">今日:</span>
                        <div class="stat-bar">
                            <div class="stat-bar-fill" style="width: <?php echo $uptime_stats['daily']; ?>%"></div>
                            <span class="stat-value"><?php echo $uptime_stats['daily']; ?>%</span>
                        </div>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">本周:</span>
                        <div class="stat-bar">
                            <div class="stat-bar-fill" style="width: <?php echo $uptime_stats['weekly']; ?>%"></div>
                            <span class="stat-value"><?php echo $uptime_stats['weekly']; ?>%</span>
                        </div>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">本月:</span>
                        <div class="stat-bar">
                            <div class="stat-bar-fill" style="width: <?php echo $uptime_stats['monthly']; ?>%"></div>
                            <span class="stat-value"><?php echo $uptime_stats['monthly']; ?>%</span>
                        </div>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">历史:</span>
                        <div class="stat-bar">
                            <div class="stat-bar-fill" style="width: <?php echo $uptime_stats['all_time']; ?>%"></div>
                            <span class="stat-value"><?php echo $uptime_stats['all_time']; ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="history-graph">
                    <h4>最近<?php echo HISTORY_DAYS; ?>天状态</h4>
                    <div class="graph-bars">
                        <?php foreach ($dates as $date): 
                            $day_status = $status_history[$site_name][$date] ?? null;
                            $is_ok = $day_status && $day_status['status'] === 'ok';
                        ?>
                        <div class="graph-bar" title="<?php echo $date; ?>: <?php 
                            echo $is_ok ? '在线' : ($day_status ? '离线' : '无数据');
                        ?>">
                            <div class="bar-fill <?php echo $is_ok ? 'ok' : ($day_status ? 'error' : 'unknown'); ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="response-time-chart">
                    <canvas id="<?php echo $chart_id; ?>"></canvas>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <footer>
            <p>系统每隔1小时自动检测一次 &bull; 数据保留最近<?php echo HISTORY_DAYS; ?>天记录</p>
            <p class="tech-info">
                <span>系统版本: 1.2.0</span>
                <span class="divider">|</span>
                <span>最后数据更新: <?php echo date('Y-m-d H:i:s', filemtime(STATUS_FILE)); ?></span>
                <span class="divider">|</span>
                <span>内存使用: <?php echo round(memory_get_usage(true)/1024/1024, 2); ?> MB</span>
            </p>
        </footer>
    </div>
    
    <script src="https://ajax.aspnetcdn.com/ajax/jquery/jquery-3.5.1.min.js"></script>
    <script>
    $(document).ready(function() {
        function updateNextCheckTime() {
            const now = new Date();
            const nextCheck = new Date(now.getTime() + <?php echo CHECK_INTERVAL; ?> * 1000);
            $('#next-check-text').text('下次检查: ' + nextCheck.toLocaleTimeString());
        }
        
        updateNextCheckTime();
        setInterval(updateNextCheckTime, 1000);
        
        $('.status-card').each(function() {
            const card = $(this);
            const siteName = card.find('h3').text().trim();
            const canvas = card.find('canvas')[0];
            const ctx = canvas.getContext('2d');
            
            const responseData = {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: '响应时间 (ms)',
                    data: <?php 
                        $trend_values = array_map(function($date) use ($response_trend) {
                            return $response_trend[$date]['response_time'] ?? null;
                        }, $dates);
                        echo json_encode($trend_values);
                    ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointRadius: 3,
                    pointBackgroundColor: function(context) {
                        const index = context.dataIndex;
                        const date = <?php echo json_encode($dates); ?>[index];
                        const status = <?php echo json_encode($response_trend); ?>[date]?.status;
                        
                        return status === 'ok' ? 'rgba(75, 192, 192, 1)' : 
                               status === 'error' ? 'rgba(255, 99, 132, 1)' : 'rgba(201, 203, 207, 1)';
                    }
                }]
            };
            
            new Chart(ctx, {
                type: 'line',
                data: responseData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: siteName + ' 响应时间趋势',
                            font: {
                                size: 14
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw + ' ms';
                                },
                                afterLabel: function(context) {
                                    const date = <?php echo json_encode($dates); ?>[context.dataIndex];
                                    const status = <?php echo json_encode($response_trend); ?>[date]?.status;
                                    return '状态: ' + (status === 'ok' ? '在线' : status === 'error' ? '离线' : '未知');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '毫秒 (ms)'
                            }
                        }
                    }
                }
            });
        });
        
        setTimeout(function() {
            location.reload();
        }, <?php echo CHECK_INTERVAL; ?> * 1000);
    });
    </script>
</body>
</html>