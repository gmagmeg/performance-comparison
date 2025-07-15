# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a web performance testing project that implements identical Laravel blog applications across four different server configurations to compare performance characteristics:

- **new-apache** - Traditional Apache + PHP setup (port 9100)
- **new-frankenphp** - FrankenPHP server with Laravel Octane (ports 9400/9401/443)
- **new-nginx** - Nginx + PHP-FPM configuration (port 9200)
- **new-swoole-php** - Swoole-based high-performance setup (port 9300)

Each configuration runs the same Laravel codebase with OpenTelemetry tracing integration for performance monitoring.

## Common Development Commands

### Docker Operations
```bash
# Start a specific server configuration
cd new-nginx && docker compose up -d
cd new-apache && docker compose up -d
cd new-frankenphp && docker compose up -d
cd new-swoole-php && docker compose up -d

# Stop services
docker compose down

# Rebuild containers
docker compose up --build -d
```

### Laravel Development
```bash
# Development server (runs concurrently: server, queue, logs, vite)
composer dev

# Run tests
composer test
# or
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Database operations
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Frontend build commands
npm run build
npm run dev
```

### Performance Testing
```bash
# Access performance testing endpoints (adjust port for each server)
curl "http://localhost:9200/api/read-weight?trace=1"
curl "http://localhost:9200/api/post-weight?trace=1"
curl "http://localhost:9200/api/csv-weight?trace=1"

# Port mapping for different servers:
# Apache: 9100, Nginx: 9200, Swoole: 9300, FrankenPHP: 9400
```

## Architecture Overview

### Core Data Model
The application implements a comprehensive blog system with performance testing capabilities:

**Primary Entities:**
- `posts` (custom primary key `post_id`) - Main content
- `users` - Authentication system
- `categories` - Hierarchical with parent-child relationships
- `tags` - Simple tagging system
- `comments` - Nested comments with user/guest support
- `post_views` - View tracking with IP/user agent
- `likes` - Polymorphic like system

**Key Relationships:**
- Many-to-many: posts-tags, posts-categories via pivot tables
- Self-referencing: nested categories and comments
- Polymorphic: likes system supports multiple entity types

### Performance Testing Controllers
- `ReadWeighController` - Heavy read operations with complex eager loading
- `PostWeighController` - CPU-intensive data processing without DB persistence  
- `LightweightController` - Simple pagination testing
- `CsvWeighController` - File processing performance testing

### API Endpoints
- `GET /api/read-weight` - Complex query with eager loading
- `POST /api/post-weight` - Data processing benchmark
- `GET /api/post-weight/{id}` - Single record retrieval
- `POST /api/csv-weight` - File processing benchmark

### OpenTelemetry Integration
- Custom `Tracer` class for manual span management
- Jaeger integration for trace visualization (port 16686-16689)
- Different instrumentation strategies per server type
- Conditional tracing via `?trace=1` parameter

### Database Seeding Strategy
Designed for consistent performance testing with reproducible data:
- 100 users with fixed seed (12345)
- 1,000 posts (10 per user)
- 5,000 comments (5 per post) - mix of authenticated users and guest comments
- 5,000 post views with IP tracking and user agent data
- 5,000 likes with polymorphic relationships
- 30 categories (20 parent + 10 child categories with hierarchical relationships)
- 100 tags with color coding
- Many-to-many relationships: 5 tags and 5 categories per post
- Batch processing for efficient insertion with chunked operations

## Server Configuration Differences

### Apache (`new-apache`)
- Traditional PHP 8.3 + Apache
- Basic OpenTelemetry instrumentation
- MySQL 8.0 on port 3307

### FrankenPHP (`new-frankenphp`)
- Modern PHP application server with HTTP/2/3
- Laravel Octane integration with persistent state
- Worker mode for enhanced performance
- MySQL 8.0 on port 3308

### Nginx (`new-nginx`)
- Nginx + PHP-FPM separation
- Custom Docker configuration with performance optimizations
- PHP-FPM status monitoring
- MySQL 8.0 on port 3310

### Swoole (`new-swoole-php`)
- High-performance async PHP server
- Laravel Octane with Swoole driver
- Memory-resident application
- Advanced worker management and caching tables
- Custom Octane configuration with optimized listeners
- MySQL 8.0 on port 3309

## Key Development Patterns

### Performance Monitoring
- Use the custom `Tracer` class (`app/Tracer.php`) for manual span creation
- Enable tracing with `?trace=1` parameter on API endpoints
- Monitor traces via Jaeger UI at respective ports (16686-16689)
- Tracer automatically manages root span lifecycle and supports nested span operations
- Spans are automatically named and cleaned up via destructor pattern

### Data Access
- Comprehensive eager loading to prevent N+1 queries (see `ReadWeighController::fetchPosts()`)
- Custom primary keys (`post_id`, `category_id`, `tag_id`, `comment_id`, etc.)
- Batch operations for efficient bulk insertions (chunked processing in seeders)
- Polymorphic relationships for flexible like system (`likeable_type`/`likeable_id`)
- Explicit foreign key definitions in pivot tables with timestamps
- Self-referencing relationships for nested categories and comments (`parent_id`)

### Testing
- In-memory SQLite for fast test execution
- Environment isolation with separate test configuration
- Run tests with `composer test` or `php artisan test`
- Run specific test files: `php artisan test tests/Feature/ExampleTest.php`
- PHPUnit configured with custom test environment variables
- Test suites: Unit tests in `tests/Unit/`, Feature tests in `tests/Feature/`
- `composer test` includes config cache clearing for clean test environment

## Environment Setup

Each server configuration uses Docker with:
- Isolated networks to prevent interference
- Separate MySQL ports to avoid conflicts
- Identical Laravel codebase for consistent testing
- OpenTelemetry tracing configured per server type

When working with this codebase, ensure Docker is running and use the appropriate port numbers for each server configuration when testing performance differences.