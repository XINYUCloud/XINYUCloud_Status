```markdown
### **部署流程**

#### **① 下载并上传**
1. 下载压缩包，上传至服务器站点目录后解压。

#### **② 环境要求**
-**Nginx**：1.26及以上
-**PHP**：7.4及以上

####**③配置‘sites.json‘**
在'data'文件夹中修改'sites.json'文件，示例内容如下：
"'json
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
```

##### **参数说明**
|参数|说明|
|------------------|-----------------------------|
| `姓名`           |站点标题|
| `URL`            |站点链接|
| `check_path`     |检查路径（无需可留空）|
| `预期状态(_S)`|预期HTTP状态码(如200)|
| `check_ssl`      |是否检查SSL证书(true/false)|
| `check_dns`      |是否检查DNS解析(true/false)|

#### **④ 配置`config.php`**
修改`config.php`中的以下内容：
```PHP
定义('API_KEY'，'自定义密钥')；//安全密钥
```
- **示例密钥**: `adc123456`  
- **管理IP**: 默认`127.0.0.1`（计划任务仅限内网访问，无需修改）

#### **⑤ 创建计划任务（宝塔面板）**
1.进入宝塔面板 →**计划任务**→**创建任务**：  
   - **任务类型**: `访问URL`(GET)
   - **执行周期**: `N小时|0分钟`（建议每小时一次）
   - **URL地址**:  
     ```
     HTTP://域名/check.php？key=你设置的自定义密钥
     ```
   - **其他选项**: 开启`进程锁`  

2.创建后，在任务列表中点击**执行**测试效果。
3.执行结果会保存在`data/status_history.json`中。

#### **⑥ 访问网站**
- **演示站**: [https://status.xinyui.icu/](https://status.xinyui.icu/)  
```
