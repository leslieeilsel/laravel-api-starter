# Laravel API Starter 开发文档

本文档帮助开发者快速了解项目结构和开发规范。

## 目录

1. [项目架构](./01-architecture.md) - 目录结构、模块化设计
2. [依赖库介绍](./02-packages.md) - 核心依赖库的作用和用法
3. [开发规范](./03-conventions.md) - 代码风格、开发模式
4. [完整示例](./04-examples.md) - 用户管理模块完整实现
5. [生产部署](./05-deployment.md) - Nginx 部署、安全配置
6. [Redis/队列/Reverb](./06-redis-queue-reverb.md) - 缓存、异步任务、实时通信

## 快速开始

```bash
# 1. 克隆项目
git clone <repository-url>
cd laravel-api-starter

# 2. 安装依赖并初始化
composer setup

# 3. 配置环境变量
cp .env.example .env
# 编辑 .env 配置数据库等

# 4. 启动开发服务器
composer dev
```

访问 http://localhost:8000 验证服务是否正常运行。

## 技术栈

| 组件    | 版本  | 用途     |
| ------- | ----- | -------- |
| PHP     | ^8.2  | 运行环境 |
| Laravel | ^12.0 | 框架     |
| MySQL   | -     | 数据库   |

## 核心特性

-   **模块化架构** - 使用 `internachi/modular` 实现业务隔离
-   **类型安全** - 使用 `spatie/laravel-data` 实现 DTO
-   **统一响应** - 标准化的 API 响应格式
-   **多语言支持** - 内置中英文语言包
-   **权限管理** - 使用 `spatie/laravel-permission`
-   **静态分析** - 使用 `larastan` 进行代码检查
