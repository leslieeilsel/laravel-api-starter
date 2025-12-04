# 生产环境部署 (Nginx)

## 关键注意事项

### 1. 环境配置

```ini
# .env 生产环境必须配置
APP_ENV=production
APP_DEBUG=false

# 使用 Redis
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 2. Laravel 优化（部署后必做）

```bash
# 缓存配置、路由、视图
php artisan optimize

# 优化自动加载
composer dump-autoload --optimize
```

> ⚠️ 修改 `.env` 或配置文件后，必须重新运行 `php artisan optimize`

### 3. 目录权限

```bash
# storage 和 bootstrap/cache 需要写权限
chmod -R 775 storage bootstrap/cache
chown -R deployer:www-data storage bootstrap/cache
```

### 4. Nginx 配置

```nginx
server {
    listen 443 ssl http2;
    server_name api.example.com;
    root /var/www/api.example.com/public;
    index index.php;

    # 最大上传大小
    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 禁止访问敏感文件
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # WebSocket 代理（如使用 Reverb）
    location /app {
        proxy_pass http://127.0.0.1:8080\;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

### 5. Supervisor 进程管理

队列 Worker：

```ini
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
command=php /var/www/api.example.com/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=deployer
numprocs=4
stdout_logfile=/var/www/api.example.com/storage/logs/worker.log
```

Reverb WebSocket：

```ini
# /etc/supervisor/conf.d/laravel-reverb.conf
[program:laravel-reverb]
command=php /var/www/api.example.com/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=deployer
stdout_logfile=/var/www/api.example.com/storage/logs/reverb.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
```

### 6. 部署脚本

```bash
#!/bin/bash
set -e
cd /var/www/api.example.com

php artisan down --retry=60
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

### 7. SSL 证书 (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.example.com
```

## 部署检查清单

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] 运行 `php artisan optimize`
- [ ] 目录权限正确
- [ ] SSL 证书已配置
- [ ] Supervisor 已启动 Worker 和 Reverb
