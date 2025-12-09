# 依赖库介绍

详细用法请参阅各库的官方文档。

## 核心依赖

| 包名                                                                            | 用途               | 文档                                                   |
| ------------------------------------------------------------------------------- | ------------------ | ------------------------------------------------------ |
| [internachi/modular](https://github.com/InterNACHI/modular)                     | 模块化开发         | [README](https://github.com/InterNACHI/modular#readme) |
| [spatie/laravel-data](https://github.com/spatie/laravel-data)                   | DTO 数据对象       | [文档](https://spatie.be/docs/laravel-data)            |
| [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder) | API 查询构建器     | [文档](https://spatie.be/docs/laravel-query-builder)   |
| [spatie/laravel-permission](https://github.com/spatie/laravel-permission)       | 角色权限管理       | [文档](https://spatie.be/docs/laravel-permission)      |
| [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)     | 操作日志记录       | [文档](https://spatie.be/docs/laravel-activitylog)     |
| [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)   | 文件/媒体管理      | [文档](https://spatie.be/docs/laravel-medialibrary)    |
| [laravel/sanctum](https://github.com/laravel/sanctum)                           | API Token 认证     | [文档](https://laravel.com/docs/sanctum)               |
| [laravel/reverb](https://github.com/laravel/reverb)                             | WebSocket 实时通信 | [文档](https://laravel.com/docs/reverb)                |

## 开发依赖

| 包名                                                            | 用途       | 文档                                                     |
| --------------------------------------------------------------- | ---------- | -------------------------------------------------------- |
| [larastan/larastan](https://github.com/larastan/larastan)       | 静态分析   | [README](https://github.com/larastan/larastan#readme)    |
| [pestphp/pest](https://github.com/pestphp/pest)                 | 测试框架   | [文档](https://pestphp.com/docs)                         |
| [laravel/pint](https://github.com/laravel/pint)                 | 代码格式化 | [文档](https://laravel.com/docs/pint)                    |
| [opcodesio/log-viewer](https://github.com/opcodesio/log-viewer) | 日志查看器 | [README](https://github.com/opcodesio/log-viewer#readme) |

## 可选推荐

| 包名                                                        | 用途         | 文档                                        |
| ----------------------------------------------------------- | ------------ | ------------------------------------------- |
| [knuckleswtf/scribe](https://github.com/knuckleswtf/scribe) | API 文档生成 | [文档](https://scribe.knuckles.wtf/laravel) |

### Scribe - API 文档生成

自动从路由、控制器和 FormRequest 生成 API 文档。

```bash
composer require --dev knuckleswtf/scribe
php artisan vendor:publish --tag=scribe-config
php artisan scribe:generate
```

通过注释增强文档：

```php
/**
 * 创建用户
 *
 * @bodyParam name string required 用户名. Example: John
 * @bodyParam email string required 邮箱. Example: john@example.com
 *
 * @response 200 {"code": 200, "message": "Success", "data": {"id": 1, "name": "John"}}
 */
public function store(StoreUserRequest $request)
{
    // ...
}
```

生成后访问 `/docs` 查看交互式 API 文档。
