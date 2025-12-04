# Laravel API Starter

[中文文档](./README_zh_CN.md)

A Laravel 12 based API starter project with modular architecture, unified response format, permission management and more.

## Features

-   📦 **Modular Architecture** - Based on `internachi/modular`
-   🔒 **Unified Response Format** - Standardized API responses with i18n support
-   🛡️ **Permission Management** - Based on `spatie/laravel-permission`
-   📡 **Real-time Communication** - Based on `laravel/reverb`
-   🧪 **Test Driven** - Based on `pestphp/pest`

## Quick Start

```bash
git clone <repository-url>
cd laravel-api-starter
composer setup    # Install dependencies, initialize config, run migrations
composer dev      # Start development server
```

Visit http://localhost:8000 to verify installation.

## Documentation

📚 **[View Full Documentation](./docs/README.md)**

| Document                                              | Description                           |
| ----------------------------------------------------- | ------------------------------------- |
| [Architecture](./docs/01-architecture.md)             | Directory structure, modular design   |
| [Packages](./docs/02-packages.md)                     | Core dependencies                     |
| [Conventions](./docs/03-conventions.md)               | API response format, coding standards |
| [Examples](./docs/04-examples.md)                     | User management module implementation |
| [Deployment](./docs/05-deployment.md)                 | Nginx deployment configuration        |
| [Redis/Queue/Reverb](./docs/06-redis-queue-reverb.md) | Cache, queue, real-time communication |

## License

[MIT license](https://opensource.org/licenses/MIT)