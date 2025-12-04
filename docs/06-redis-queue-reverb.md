# Redis、队列与实时通信

本文档详细介绍 Redis 配置、队列系统和 Reverb WebSocket 的使用。

## 目录

1. [Redis 配置](#redis-配置)
2. [队列系统](#队列系统)
3. [Reverb 实时通信](#reverb-实时通信)
4. [最佳实践](#最佳实践)

---

## Redis 配置

Redis 在本项目中用于：

-   **缓存** - 提升数据读取性能
-   **会话** - 存储用户会话
-   **队列** - 异步任务处理
-   **广播** - 实时事件推送

### 安装 Redis

```bash
# Ubuntu/Debian
sudo apt install redis-server

# 启动并设置开机自启
sudo systemctl start redis-server
sudo systemctl enable redis-server

# 验证安装
redis-cli ping
# 返回 PONG 表示成功
```

### 安装 PHP Redis 扩展

```bash
# 安装 phpredis 扩展
sudo apt install php8.3-redis

# 重启 PHP-FPM
sudo systemctl restart php8.3-fpm

# 验证
php -m | grep redis
```

### 环境配置

编辑 `.env` 文件：

```ini
# Redis 连接
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 使用 Redis 作为缓存驱动
CACHE_STORE=redis

# 使用 Redis 作为会话驱动
SESSION_DRIVER=redis

# 使用 Redis 作为队列驱动
QUEUE_CONNECTION=redis
```

### Redis 配置文件

查看 `config/database.php` 中的 Redis 配置：

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],

    // 默认连接
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    // 缓存专用连接
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

### 基本使用

```php
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

// 直接操作 Redis
Redis::set('key', 'value');
$value = Redis::get('key');

// 使用 Cache Facade（推荐）
Cache::put('key', 'value', now()->addMinutes(10));
$value = Cache::get('key');
$value = Cache::remember('key', 60, fn() => User::find(1));

// 缓存标签（便于批量清除）
Cache::tags(['users', 'profiles'])->put('user:1', $user, 3600);
Cache::tags(['users'])->flush();  // 清除所有 users 标签的缓存
```

### Redis 安全配置

生产环境建议配置密码：

```bash
# 编辑 Redis 配置
sudo vim /etc/redis/redis.conf
```

```conf
# 设置密码
requirepass your_strong_password

# 禁用危险命令
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command DEBUG ""
rename-command CONFIG ""

# 只监听本地
bind 127.0.0.1 ::1
```

```bash
# 重启 Redis
sudo systemctl restart redis-server
```

更新 `.env`：

```ini
REDIS_PASSWORD=your_strong_password
```

---

## 队列系统

队列用于异步处理耗时任务，如发送邮件、处理图片、调用第三方 API 等。

### 为什么使用队列？

| 场景               | 同步处理             | 队列处理             |
| ------------------ | -------------------- | -------------------- |
| 用户注册后发送邮件 | 用户等待邮件发送完成 | 立即返回，后台发送   |
| 上传图片生成缩略图 | 请求阻塞直到处理完成 | 立即返回，后台处理   |
| 调用第三方 API     | 受第三方响应时间影响 | 异步调用，失败可重试 |

### 创建 Job

```bash
php artisan make:job SendWelcomeEmail
```

```php
<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大尝试次数
     */
    public int $tries = 3;

    /**
     * 超时时间（秒）
     */
    public int $timeout = 60;

    /**
     * 重试间隔（秒）
     */
    public int $backoff = 10;

    public function __construct(
        public User $user
    ) {}

    public function handle(): void
    {
        Mail::to($this->user)->send(new WelcomeMail($this->user));
    }

    /**
     * 任务失败处理
     */
    public function failed(\Throwable $exception): void
    {
        // 记录日志或通知管理员
        logger()->error('发送欢迎邮件失败', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### 分发 Job

```php
use App\Jobs\SendWelcomeEmail;

// 基本分发
SendWelcomeEmail::dispatch($user);

// 延迟执行
SendWelcomeEmail::dispatch($user)->delay(now()->addMinutes(5));

// 指定队列
SendWelcomeEmail::dispatch($user)->onQueue('emails');

// 指定连接
SendWelcomeEmail::dispatch($user)->onConnection('redis');

// 链式任务（按顺序执行）
use Illuminate\Support\Facades\Bus;

Bus::chain([
    new ProcessUploadedImage($image),
    new GenerateThumbnails($image),
    new NotifyUser($user),
])->dispatch();
```

### 启动队列 Worker

#### 开发环境

```bash
# 基本启动
php artisan queue:work

# 指定队列
php artisan queue:work --queue=emails,default

# 单次执行（调试用）
php artisan queue:work --once

# 监听模式（代码变更自动重启）
php artisan queue:listen
```

#### 生产环境 (Supervisor)

创建 Supervisor 配置：

```bash
sudo vim /etc/supervisor/conf.d/laravel-worker.conf
```

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/api.example.com/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deployer
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/api.example.com/storage/logs/worker.log
stopwaitsecs=3600
```

**参数说明：**

| 参数              | 说明                                         |
| ----------------- | -------------------------------------------- |
| `--sleep=3`       | 无任务时休眠 3 秒                            |
| `--tries=3`       | 最大重试 3 次                                |
| `--max-time=3600` | Worker 最大运行 1 小时后重启（防止内存泄漏） |
| `numprocs=4`      | 启动 4 个 Worker 进程                        |

```bash
# 更新并启动
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*

# 查看状态
sudo supervisorctl status
```

### 多队列配置

根据任务优先级使用不同队列：

```php
// 高优先级任务
SendPasswordResetEmail::dispatch($user)->onQueue('high');

// 普通任务
SendWelcomeEmail::dispatch($user)->onQueue('default');

// 低优先级任务
GenerateReport::dispatch($report)->onQueue('low');
```

Supervisor 配置多队列优先级：

```ini
[program:laravel-worker-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/api.example.com/artisan queue:work redis --queue=high,default,low --sleep=3 --tries=3
numprocs=2
# ... 其他配置同上

[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/api.example.com/artisan queue:work redis --queue=default,low --sleep=3 --tries=3
numprocs=2
# ... 其他配置同上
```

### 队列监控

#### 使用 Artisan 命令

```bash
# 查看失败任务
php artisan queue:failed

# 重试失败任务
php artisan queue:retry all
php artisan queue:retry 5  # 重试 ID 为 5 的任务

# 清空失败任务
php artisan queue:flush

# 重启所有 Worker（部署后执行）
php artisan queue:restart
```

#### 使用 Horizon（可选）

如果需要更强大的队列监控，可以安装 Laravel Horizon：

```bash
composer require laravel/horizon
php artisan horizon:install
```

---

## Reverb 实时通信

Laravel Reverb 是官方的 WebSocket 服务器，用于实时通信。

### 安装配置

```bash
# 安装 Reverb
php artisan install:broadcasting
```

安装过程会：

1. 安装 `laravel/reverb` 包
2. 生成 `config/reverb.php` 配置文件
3. 更新 `.env` 文件

### 环境配置

```ini
# 广播驱动
BROADCAST_CONNECTION=reverb

# Reverb 服务器配置
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# 前端连接配置
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 启动 Reverb 服务器

#### 开发环境

```bash
php artisan reverb:start

# 指定端口
php artisan reverb:start --port=8080

# 调试模式（显示所有事件）
php artisan reverb:start --debug
```

#### 生产环境 (Supervisor)

```bash
sudo vim /etc/supervisor/conf.d/laravel-reverb.conf
```

```ini
[program:laravel-reverb]
process_name=%(program_name)s
command=php /var/www/api.example.com/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deployer
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/api.example.com/storage/logs/reverb.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-reverb
```

### Nginx 反向代理 WebSocket

```nginx
# 在站点配置中添加
server {
    # ... 其他配置 ...

    # WebSocket 代理
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # 超时设置
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    location /apps {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

### 创建事件

```bash
php artisan make:event MessageSent
```

```php
<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    /**
     * 广播频道
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->chat_id),
        ];
    }

    /**
     * 广播事件名称
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * 广播数据
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
```

### 频道类型

| 类型              | 用途                         | 示例       |
| ----------------- | ---------------------------- | ---------- |
| `Channel`         | 公开频道，任何人可订阅       | 公告、新闻 |
| `PrivateChannel`  | 私有频道，需要认证           | 用户通知   |
| `PresenceChannel` | 存在频道，可获取在线用户列表 | 聊天室     |

### 频道授权

编辑 `routes/channels.php`：

```php
use Illuminate\Support\Facades\Broadcast;

// 私有频道授权
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return $user->chats()->where('id', $chatId)->exists();
});

// 存在频道授权（返回用户信息）
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    if ($user->canJoinRoom($roomId)) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar_url,
        ];
    }
    return false;
});
```

### 触发事件

```php
use App\Events\MessageSent;

// 方式一：使用 event()
event(new MessageSent($message));

// 方式二：使用 broadcast()（可链式调用）
broadcast(new MessageSent($message));

// 排除当前用户
broadcast(new MessageSent($message))->toOthers();
```

### 前端连接 (JavaScript)

```bash
npm install laravel-echo pusher-js
```

```javascript
// resources/js/echo.js
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});

// 订阅私有频道
Echo.private(`chat.${chatId}`).listen(".message.sent", (e) => {
    console.log("收到新消息:", e);
});

// 订阅存在频道
Echo.join(`room.${roomId}`)
    .here((users) => {
        console.log("当前在线用户:", users);
    })
    .joining((user) => {
        console.log("用户加入:", user);
    })
    .leaving((user) => {
        console.log("用户离开:", user);
    })
    .listen(".message.sent", (e) => {
        console.log("收到消息:", e);
    });
```

---

## 最佳实践

### Redis

1. **使用不同的数据库编号** - 缓存、会话、队列使用不同的 DB
2. **设置合理的过期时间** - 避免内存无限增长
3. **使用缓存标签** - 便于批量清除相关缓存
4. **生产环境设置密码** - 防止未授权访问

### 队列

1. **任务要幂等** - 同一任务多次执行结果相同
2. **设置合理的超时和重试** - 根据任务类型调整
3. **使用多队列** - 按优先级分离任务
4. **监控失败任务** - 及时处理失败任务
5. **部署后重启 Worker** - `php artisan queue:restart`

### Reverb

1. **使用 Nginx 反向代理** - 支持 SSL 和负载均衡
2. **合理设计频道** - 避免频道过多或过少
3. **控制广播数据量** - 只发送必要的数据
4. **使用 `toOthers()`** - 避免发送者收到自己的消息

### 开发调试

```bash
# 同时启动所有服务（开发环境）
# 在 composer.json 中已配置
composer dev

# 或手动启动
php artisan serve &
php artisan queue:listen &
php artisan reverb:start --debug &
```

### 生产环境检查清单

-   [ ] Redis 已设置密码
-   [ ] Redis 只监听本地
-   [ ] Supervisor 已配置队列 Worker
-   [ ] Supervisor 已配置 Reverb
-   [ ] Nginx 已配置 WebSocket 代理
-   [ ] 防火墙已开放 WebSocket 端口（如需外部访问）
-   [ ] 日志文件已配置轮转
