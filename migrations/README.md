# Database Migrations

This directory contains SQL migration files for the Student Skills Marketplace database schema.

## Migration System

The migration system tracks which migrations have been applied and runs them sequentially.

### Commands

**Run pending migrations:**

```bash
php cli/migrate.php up
# or
php cli/migrate.php migrate
```

**Rollback last batch:**

```bash
php cli/migrate.php down
# or
php cli/migrate.php rollback
```

**Check migration status:**

```bash
php cli/migrate.php status
```

### Seed Database

After running migrations, seed the database with initial data:

```bash
php cli/seed.php
```

This will populate:

- Default categories (Web Development, Graphic Design, etc.)
- Platform settings (commission_rate: 15%, max_revisions: 3)
- Test users for development (admin, student, client)

### Test User Credentials

After seeding, you can login with:

- **Admin:** admin@marketplace.local / password123
- **Student:** student@marketplace.local / password123
- **Client:** client@marketplace.local / password123

## Migration Files

Migrations are numbered sequentially and run in order:

1. `001_create_users_table.sql` - User accounts with authentication
2. `002_create_student_profiles_table.sql` - Student profile information
3. `003_create_categories_table.sql` - Service categories
4. `004_create_services_table.sql` - Service listings
5. `005_create_orders_table.sql` - Order transactions
6. `006_create_payments_table.sql` - Payment records with Stripe integration
7. `007_create_messages_table.sql` - Order messaging system
8. `008_create_reviews_table.sql` - Review and rating system
9. `009_create_disputes_table.sql` - Dispute resolution
10. `010_create_notifications_table.sql` - In-app notifications
11. `011_create_audit_logs_table.sql` - Security and audit logging
12. `012_create_webhook_events_table.sql` - Stripe webhook tracking
13. `013_create_platform_settings_table.sql` - Platform configuration

## Database Requirements

- MySQL 8.0+
- InnoDB storage engine
- UTF-8 (utf8mb4) character set

## Environment Configuration

Ensure your `.env` file has the correct database credentials:

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=marketplace
DB_USER=root
DB_PASS=your_password
```
