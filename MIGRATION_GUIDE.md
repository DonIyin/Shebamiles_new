# Database Migration Guide

This guide explains how to use the database migration system introduced in this release.

## Overview

The migration system provides a structured way to evolve the database schema over time while tracking which changes have been applied.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Database credentials configured in environment or config.php

## Running Migrations

### First Time Setup

If you're setting up the database for the first time:

```bash
# Create database and initial tables
php backend/setup_database.php

# Run migrations to add missing tables and indexes
php backend/run_migrations.php
```

### Check Migration Status

To see which migrations have been applied:

```bash
php backend/run_migrations.php --status
```

This will show:
- Total available migrations
- Executed migrations (with ✓)
- Pending migrations (with ○)

### Running Pending Migrations

To run all pending migrations:

```bash
php backend/run_migrations.php
```

Or simply:

```bash
php backend/run_migrations.php run
```

### Rolling Back Last Migration

To rollback the most recently applied migration:

```bash
php backend/run_migrations.php --rollback
```

**Warning**: Rollback will drop tables and indexes. Use with caution in production!

## Migration Files

Migrations are stored in `backend/migrations/` and follow the naming convention:

```
001_add_missing_tables.php
002_add_performance_indexes.php
```

### Available Migrations

#### 001_add_missing_tables.php
Creates two required tables:

1. **rate_limits** - Stores rate limiting data
   - `id` (INT, PRIMARY KEY)
   - `identifier` (VARCHAR, e.g., IP address)
   - `bucket` (VARCHAR, e.g., 'login', 'api')
   - `timestamp` (INT, Unix timestamp)
   - Indexes on (identifier, bucket) and timestamp

2. **logs** - Stores application logs
   - `id` (INT, PRIMARY KEY)
   - `level` (VARCHAR, e.g., 'INFO', 'ERROR')
   - `message` (TEXT)
   - `context` (JSON)
   - `user_id` (INT, nullable)
   - `ip_address` (VARCHAR)
   - `created_at` (TIMESTAMP)
   - Indexes on level, created_at, and user_id

#### 002_add_performance_indexes.php
Adds performance indexes to existing tables:

- `user_activity.timestamp`
- `user_activity.(user_id, timestamp)` - composite index
- `login_attempts.attempt_time`
- `users.last_login`
- `employees.(department, status)` - composite index

These indexes significantly improve query performance for:
- Activity reports
- Login monitoring
- User analytics
- Employee filtering

## Creating New Migrations

To create a new migration:

1. Create a new file in `backend/migrations/` with the next number:
   ```
   003_your_migration_name.php
   ```

2. Use this template:

```php
<?php
/**
 * Migration: Your Migration Name
 * Description of what this migration does
 */

return [
    'description' => 'Short description',
    
    'up' => function($conn) {
        $queries = [];
        
        // Add your SQL queries here
        $queries[] = "
            CREATE TABLE IF NOT EXISTS your_table (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $results = [];
        foreach ($queries as $query) {
            $result = $conn->query($query);
            if ($result === false) {
                throw new Exception("Migration failed: " . $conn->error);
            }
            $results[] = $result;
        }
        
        return $results;
    },
    
    'down' => function($conn) {
        // Rollback logic
        $query = "DROP TABLE IF EXISTS your_table";
        $result = $conn->query($query);
        
        if ($result === false) {
            throw new Exception("Rollback failed: " . $conn->error);
        }
        
        return [$result];
    }
];
?>
```

## Troubleshooting

### Migration Fails Halfway

If a migration fails partway through:

1. Check the error message
2. Fix the issue in the database or migration file
3. Remove the migration record from the `migrations` table:
   ```sql
   DELETE FROM migrations WHERE migration = '001_add_missing_tables';
   ```
4. Run the migration again

### Table Already Exists

The migrations use `CREATE TABLE IF NOT EXISTS` to avoid errors if tables already exist. This is safe to run multiple times.

### Permission Denied

Ensure your database user has permissions to:
- CREATE TABLE
- ALTER TABLE
- CREATE INDEX
- DROP INDEX (for rollbacks)

## Best Practices

1. **Test migrations** in development before production
2. **Backup database** before running migrations in production
3. **Review migration** code before applying
4. **Never edit** already-applied migrations
5. **Use transactions** when possible (InnoDB tables)
6. **Document changes** in migration description

## Production Deployment

For production deployments:

1. Backup the database
2. Check migration status
3. Review pending migrations
4. Run migrations during maintenance window
5. Verify application functionality
6. Monitor logs for issues

## Health Check

After running migrations, verify system health:

```bash
curl http://your-domain/backend/health_check.php
```

Or visit in browser. Should show:
- Database: connected ✓
- All required tables: exists ✓
- Logs directory: writable ✓

## Support

If you encounter issues:
1. Check application logs in `backend/logs/`
2. Check migration tracking: `SELECT * FROM migrations;`
3. Review this guide
4. Contact system administrator
