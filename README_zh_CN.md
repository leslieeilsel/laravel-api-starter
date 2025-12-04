# Laravel API Starter

[English](./README.md)

基于 Laravel 12 的 API 起始项目，集成模块化架构、统一响应格式、权限管理等常用功能。

## 特性

-   📦 **模块化架构** - 基于 `internachi/modular`
-   🔒 **统一响应格式** - 标准化 API 响应，支持多语言
-   🛡️ **权限管理** - 基于 `spatie/laravel-permission`
-   📡 **实时通信** - 基于 `laravel/reverb`
-   🧪 **测试驱动** - 基于 `pestphp/pest`

## 快速开始

```bash
git clone <repository-url>
cd laravel-api-starter
composer setup    # 安装依赖、初始化配置、运行迁移
composer dev      # 启动开发服务器
```

访问 http://localhost:8000 验证安装。

## 文档

📚 **[查看完整文档](./docs/README.md)**

| 文档                                                 | 内容                   |
| ---------------------------------------------------- | ---------------------- |
| [项目架构](./docs/01-architecture.md)                | 目录结构、模块化设计   |
| [依赖库](./docs/02-packages.md)                      | 核心依赖库介绍         |
| [开发规范](./docs/03-conventions.md)                 | API 响应格式、代码规范 |
| [完整示例](./docs/04-examples.md)                    | 用户管理模块实现       |
| [生产部署](./docs/05-deployment.md)                  | Nginx 部署配置         |
| [Redis/队列/Reverb](./docs/06-redis-queue-reverb.md) | 缓存、队列、实时通信   |

## License

[MIT license](https://opensource.org/licenses/MIT)
