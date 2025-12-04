# 项目架构

## 目录结构

```
laravel-api-starter/
├── app/
│   ├── Enums/                 # 枚举类
│   │   └── ResponseCode.php   # API 响应码
│   ├── Http/
│   │   ├── Controllers/       # 控制器
│   │   └── Traits/
│   │       └── ApiResponse.php # API 响应 Trait
│   ├── Models/                # Eloquent 模型
│   └── Providers/             # 服务提供者
├── app-modules/               # 业务模块目录（模块化开发）
├── bootstrap/
│   └── app.php                # 应用配置（中间件、异常处理）
├── config/                    # 配置文件
├── database/
│   ├── factories/             # 模型工厂
│   ├── migrations/            # 数据库迁移
│   └── seeders/               # 数据填充
├── lang/                      # 多语言文件
│   ├── en/                    # 英文
│   └── zh_CN/                 # 中文
├── routes/
│   ├── api.php                # API 路由
│   ├── web.php                # Web 路由
│   └── channels.php           # 广播频道
└── tests/
    ├── Feature/               # 功能测试
    └── Unit/                  # 单元测试
```

## 模块化架构

项目使用 `internachi/modular` 实现模块化，新功能应该以模块形式开发。

### 创建新模块

```bash
php artisan make:module UserManagement
```

生成的模块结构：

```
app-modules/user-management/
├── src/
│   ├── Actions/               # 业务逻辑（Action 类）
│   ├── Data/                  # DTO 数据对象
│   ├── Http/
│   │   ├── Controllers/       # 控制器
│   │   ├── Requests/          # 表单请求验证
│   │   └── Resources/         # API 资源
│   ├── Models/                # 模型
│   ├── Jobs/                  # 队列任务
│   └── Providers/
│       └── UserManagementServiceProvider.php
├── routes/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
└── tests/
```

### 模块命名规范

| 类型       | 命名规则                | 示例                            |
| ---------- | ----------------------- | ------------------------------- |
| 模块目录   | kebab-case              | `user-management`               |
| 命名空间   | PascalCase              | `Modules\UserManagement`        |
| 服务提供者 | {Module}ServiceProvider | `UserManagementServiceProvider` |

## 数据流

```
Request → Controller → Action → Model → Database
                ↓
            Response ← Resource ← Data
```

1. **Controller** - 接收请求，调用 Action，返回响应
2. **Action** - 执行业务逻辑，操作 Model
3. **Data (DTO)** - 数据传输对象，类型安全
4. **Resource** - 格式化输出数据

## 多语言支持

项目内置中英文支持，语言文件位于 `lang/` 目录：

```
lang/
├── en/
│   ├── auth.php
│   ├── pagination.php
│   ├── passwords.php
│   ├── response.php      # API 响应消息
│   └── validation.php
└── zh_CN/
    ├── auth.php
    ├── pagination.php
    ├── passwords.php
    ├── response.php      # API 响应消息
    └── validation.php
```

### 切换语言

```php
// 在中间件中根据请求头设置
app()->setLocale($request->header('Accept-Language', 'en'));

// 或在 config/app.php 中设置默认语言
'locale' => 'zh_CN',
```

### 添加新语言

1. 创建语言目录：`lang/ja/`
2. 复制 `lang/en/` 下的所有文件
3. 翻译各文件中的内容
