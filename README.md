# Laravel API Starter

A Laravel-based API starter project.

## Requirements

-   PHP >= 8.2
-   Composer
-   MySQL
-   Node.js & npm

## Quick Start

### 1. Clone and Install

```bash
git clone <repository-url>
cd laravel-api-starter
composer setup
```

The `composer setup` command will:

-   Install PHP dependencies
-   Copy `.env.example` to `.env`
-   Generate application key
-   Run database migrations
-   Install npm dependencies
-   Build frontend assets

### 2. Configure Environment

Edit `.env` file to configure your database and other settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Run Development Server

```bash
composer dev
```

This starts:

-   **Laravel server** at http://localhost:8000
-   **Queue worker** for background jobs
-   **Pail** for real-time log viewing
-   **Vite** for frontend assets

### 4. Verify Installation

Visit http://localhost:8000 — you will be redirected to `/status` and see:

```json
{
    "status": "available",
    "message": "Server Available"
}
```

## Available Commands

| Command             | Description               |
| ------------------- | ------------------------- |
| `composer setup`    | Initial project setup     |
| `composer dev`      | Start development servers |
| `composer test`     | Run tests                 |
| `php artisan serve` | Start Laravel server only |

## API Documentation

All API routes are prefixed with `/api`. See `routes/api.php` for available endpoints.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
