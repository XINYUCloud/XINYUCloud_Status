部署流程
①下载压缩包并将压缩包上传至服务器站点目录后解压。

②环境要求
1.夜晚1.26及以上
2.php7.4及以上

3在数据文件夹修改sites.json文件内容
示例内容:
[
    {
"name"："星宇云API"，
"url"："https://api.fohok.xin"，
"check_path"：""，
"expected_status"：200，
"check_ssl"：true，
"check_dns"：true
    },
    {
"name"："云智API"，
"url"："https://api.jkyai.top"，
"check_path"：""，
"expected_status"：200，
"check_ssl"：true，
"check_dns"：true
    }
]
③-1.参数说明:
姓名--站点标题
url--站点链接
check_path--检查路径(无需可空)
预期状态--预期状态码
check_ssl--站点证书检查
check_dns--站点DNS IP解析

④配置config.php中的自定义密钥
在config.php文件中找到：
"定义('API_KEY'，'自定义密钥')；//安全密钥"
可修改为示例：adc123456
管理IP默认"127.0.0.1",因计划任务仅限内网访问无需修改

5  📋创建计划任务
宝塔面板用户在计划任务页中『创建任务』
"访问URL-get“”
时间设定为"N小时 | 0分钟"每小时的0分钟访问一次
开启进程锁.
URL地址：
HTTP://域名/check.php？key=你设置的自定义密钥

编辑完成后，点击确定.
在计划任务列表中找到你刚刚新增的那一个任务.
点击'执行'查看效果，执行后可在文件目录"data"中的
status_history.json文件中查看状态数据.

⑥访问你的网站查看效果
演示站: https://status.xinyui.icu/
