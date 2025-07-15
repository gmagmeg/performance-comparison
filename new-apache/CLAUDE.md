# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Laravel 12.0+ blog application** designed for **web performance testing and benchmarking**. It includes comprehensive blog functionality with OpenTelemetry integration for distributed tracing and observability.

## Development Environment

**Docker Setup:**
- Multi-container architecture with PHP/Apache, MySQL, and Jaeger
- Use `docker-compose up` to start all services
- Web app: http://localhost:9100
- Jaeger UI: http://localhost:16687
- MySQL: localhost:3309

**Development Commands:**
```bash
# Start development environment (concurrent processes)
composer dev

# Build assets
npm run build

# Development with hot reload
npm run dev

# Install dependencies
composer install
npm install

# Run tests
composer test
php artisan test

# Database operations
php artisan migrate
php artisan db:seed --class=BlogSeeder

# Generate application key
php artisan key:generate
```

## Architecture

### Core Models and Relationships

**Posts** (primary entity with `post_id` as primary key):
- Belongs to User
- Has many Comments, Views (PostView)
- Many-to-many with Tags and Categories via pivot tables
- Polymorphic many-to-many with Likes

**User Authentication:**
- Standard Laravel authentication
- Users can author posts, comments, and likes

**Blog Content Structure:**
- Posts can have multiple tags and categories
- Comments support nested replies via `parent_id`
- Post views track user engagement with IP and user agent
- Likes are polymorphic (can be applied to various models)

### API Controllers

**Performance Testing Controllers:**
- `ReadWeighController`: Heavy read operations with full relationship loading
- `PostWeighController`: Validation and data processing without DB persistence
- `LightweightController`: Minimal paginated queries for basic performance testing

**Key API Endpoints:**
- `GET /api/read-weight`: Complex queries with all relationships
- `POST /api/post-weight`: Data validation and processing
- `GET /api/post-weight/{id}`: Single post retrieval
- `POST /api/csv-weight`: CSV processing endpoint

### OpenTelemetry Integration

**Tracer Class (`app/Tracer.php`):**
- Custom tracing wrapper for performance monitoring
- Supports span creation with `startSpan()` and `endSpan()`
- Configurable tracing enable/disable
- Automatic root span management

**Tracing Usage:**
```php
$tracer = new Tracer($isTrace);
$tracer->startSpan('operation.name');
// ... perform operations
$tracer->endSpan('operation.name');
```

### Database Architecture

**Custom Primary Keys:**
- Posts use `post_id` instead of `id`
- Tags use `tag_id`, Categories use `category_id`
- Comments use `comment_id`, etc.

**Pivot Tables:**
- `post_tags`: Links posts to tags
- `post_categories`: Links posts to categories
- Both include `created_at` timestamps

**Data Seeding:**
- `BlogSeeder` creates 100 users, 1,000 posts, 5,000+ related records
- Designed for performance testing with realistic data volumes
- Uses batched inserts for efficiency

## Testing

**PHPUnit Configuration:**
- Uses SQLite in-memory database for testing
- Separate test suites for Feature and Unit tests
- Tests located in `tests/Feature/` and `tests/Unit/`

**Environment Variables for Testing:**
- `APP_ENV=testing`
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`

## Performance Considerations

This application is specifically designed for **web performance benchmarking**:

1. **Heavy Query Testing**: `ReadWeighController` loads all relationships for stress testing
2. **Validation Testing**: `PostWeighController` performs extensive validation without DB writes
3. **Lightweight Testing**: `LightweightController` provides minimal query baseline
4. **Tracing Integration**: OpenTelemetry spans measure performance bottlenecks
5. **Large Dataset**: Seeder creates substantial data for realistic performance testing

## Development Notes

- Uses PHP 8.3+ with Laravel 12.0+
- Vite for asset compilation with Tailwind CSS
- OpenTelemetry auto-instrumentation enabled
- Custom primary key naming convention throughout models
- Batch processing patterns in seeders for performance
- Comprehensive factory definitions for all models