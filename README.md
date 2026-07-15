# XINYU Status Monitor

一个基于 PHP + MySQL + Redis 构建的功能全面的网站监控系统。支持实时监控 HTTP/HTTPS 站点、SSL 证书和 DNS 解析，配备现代化管理后台和公开状态页。

## 功能特性

- **实时监控** — HTTP 状态码、响应时间、SSL 证书过期检测、DNS 解析检测
- **管理后台** — 站点管理、事件追踪、用户管理、通知设置、系统设置
- **公开状态页** — 简洁响应式设计，自动刷新，Chart.js 响应时间趋势图
- **多渠道通知** — Webhook、邮件、Slack、钉钉、企业微信
- **安装向导** — 4 步引导安装，环境检查、数据库配置、管理员设置一步到位
- **REST API** — 公开状态 API + 需认证的管理 API，方便外部集成
- **定时检测** — CLI 脚本配合 Cron 实现自动化检测，异常自动创建故障事件
- **暗色模式** — 公开状态页内置暗色主题支持

## 环境要求

| 组件 | 最低版本 |
|------|----------|
| PHP  | 8.0+    |
| MySQL| 5.7+    |
| Redis| 6.0+（可选，推荐）|
| Nginx / Apache | — |

**PHP 扩展：** `pdo`、`pdo_mysql`、`curl`、`openssl`、`json`、`redis`（可选）

## 快速开始

### 1. 下载并上传

从 [Releases 发布页](https://github.com/XINYUCloud/XINYUCloud_Status/releases) 下载最新版本，解压后上传至服务器网站目录。

### 2. 运行安装向导

访问 `http(s)://你的域名/install/`，按 4 步向导完成安装：

1. **环境检查** — 验证 PHP 版本和所需扩展
2. **数据库配置** — 配置 MySQL 和 Redis 连接，自动初始化数据表
3. **管理员设置** — 创建管理员账户
4. **完成** — 提供状态页和管理后台入口链接

### 3. 配置定时任务

添加 Cron 定时任务以实现自动检测（建议每分钟执行）：

```
* * * * * php /path/to/XINYU_status/cron/check.php
```

### 4. 访问系统

- **公开状态页：** `http(s)://你的域名/`
- **管理后台：** `http(s)://你的域名/admin/`

## 项目结构

```
XINYU_status/
├── admin/                  # 管理后台
│   ├── api/index.php       # 管理 API 端点
│   ├── index.php           # 仪表盘
│   ├── sites.php           # 站点管理（增删改查）
│   ├── incidents.php       # 事件管理
│   ├── users.php           # 用户管理
│   ├── notifications.php   # 通知设置
│   ├── settings.php        # 系统设置与修改密码
│   ├── login.php           # 管理员登录
│   └── sidebar.php         # 侧边栏组件
├── assets/
│   └── css/
│       ├── admin.css       # 管理后台样式
│       └── public.css      # 公开状态页样式
├── cron/
│   └── check.php           # CLI 定时检测脚本
├── includes/
│   ├── Auth.php            # 认证系统（bcrypt、CSRF、速率限制）
│   ├── Database.php        # PDO MySQL 单例封装
│   ├── functions.php       # 辅助函数
│   ├── Monitor.php         # 核心监控引擎
│   ├── RedisClient.php     # Redis 单例客户端
│   └── Session.php         # Redis 会话管理
├── install/
│   └── index.php           # 4 步安装向导
├── public/
│   ├── api/status.php      # 公开状态 JSON API
│   └── index.php           # 公开状态页
├── .env.example            # 环境配置模板
├── .htaccess               # Apache 安全规则
├── nginx.conf.example      # Nginx 配置示例
├── config.php              # 系统配置加载
├── schema.sql              # MySQL 数据库结构（7 张表）
└── index.php               # 入口重定向
```

## 数据库设计

系统使用 7 张 MySQL 数据表：

| 表名               | 说明                              |
|--------------------|-----------------------------------|
| `users`            | 管理员账户（bcrypt 哈希存储）       |
| `sites`            | 监控站点配置                       |
| `checks`           | 历史检测记录（含响应时间等指标）     |
| `incidents`        | 故障事件记录                       |
| `incident_updates` | 事件时间线更新                     |
| `notifications`    | 通知渠道配置                       |
| `settings`         | 系统键值对设置                     |

## 安全措施

- **密码哈希** — bcrypt，cost 因子 12
- **SQL 注入防护** — 全部使用 PDO 预处理语句
- **CSRF 防护** — 所有管理表单均带 per-session 令牌
- **XSS 防护** — 输出统一使用 `htmlspecialchars()` 转义
- **会话管理** — Redis 存储会话，自动轮换 Session ID
- **速率限制** — 登录接口防暴力破解
- **输入验证** — 所有用户输入均经过验证和过滤

## 配置说明

系统配置通过 `.env` 文件管理，安装向导会自动生成。模板见 `.env.example`：

```ini
# 数据库
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=xinyu_status
DB_USER=root
DB_PASS=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASS=
REDIS_DB=0
```

## API 文档

### 公开 API

**`GET /public/api/status.php`**

返回所有监控站点的当前状态。

```json
{
  "success": true,
  "summary": {
    "total_sites": 2,
    "online_sites": 2,
    "offline_sites": 0,
    "avg_response": 235
  },
  "sites": [
    {
      "id": 1,
      "name": "示例站点",
      "url": "https://example.com",
      "status": "ok",
      "http_code": 200,
      "response_time": 230,
      "uptime": {
        "daily": 100,
        "weekly": 99.95,
        "monthly": 99.88,
        "all_time": 99.91
      }
    }
  ],
  "timestamp": "2026-07-15 16:00:00",
  "version": "3.0.0"
}
```

### 管理 API

所有管理端点需登录认证和 CSRF 令牌。

| 端点               | 方法 | 说明             |
|--------------------|------|------------------|
| `?action=summary`  | GET  | 获取仪表盘摘要   |
| `?action=check`    | POST | 检测单个站点     |
| `?action=check_all`| POST | 检测所有站点     |
| `?action=uptime`   | GET  | 获取可用率统计   |
| `?action=trend`    | GET  | 获取响应时间趋势 |
| `?action=history`  | GET  | 获取检测历史记录 |

## 开源协议

GNU General Public License v3.0 — 详见 [LICENSE](https://github.com/XINYUCloud/XINYUCloud_Status/blob/main/LICENSE)