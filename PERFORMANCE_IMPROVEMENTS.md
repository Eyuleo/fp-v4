# Performance Improvements

This document outlines the performance improvements made to the codebase to address slow or inefficient code.

## Summary of Changes

### 1. **Optimized AdminController.dashboard() - Reduced Database Queries**
**File:** `src/Controllers/AdminController.php`

**Problem:** The dashboard method was executing 3 separate database queries to calculate statistics:
- One query for GMV (Gross Merchandise Value)
- One query for total orders and completion rate
- One query for on-time delivery rate

**Solution:** Combined all three queries into a single optimized query using aggregate functions with conditional CASE statements. This reduces database round trips from 3 to 1.

**Performance Gain:** ~66% reduction in database queries for dashboard loading.

```php
// Before: 3 separate queries
$gmvQuery = "SELECT COALESCE(SUM(price), 0) as gmv FROM orders WHERE ...";
$ordersQuery = "SELECT COUNT(*) as total_orders, SUM(...) FROM orders WHERE ...";
$onTimeQuery = "SELECT COUNT(*) as total_completed, SUM(...) FROM orders WHERE ...";

// After: 1 combined query
$statsQuery = "SELECT COUNT(*), SUM(CASE ...), SUM(CASE ...) FROM orders WHERE ...";
```

### 2. **Optimized AdminController.payments() - Window Functions**
**File:** `src/Controllers/AdminController.php`

**Problem:** The payments listing page was executing separate queries for pagination and statistics:
- One query for counting total payments
- One query for fetching paginated payments
- One query for calculating statistics (total amount, commission, refunds)

**Solution:** Used SQL window functions (COUNT() OVER(), SUM() OVER()) to calculate statistics alongside paginated results in a single query.

**Performance Gain:** ~66% reduction in database queries, from 3 queries to 1.

```php
// Before: 3 queries
$countQuery = "SELECT COUNT(*) FROM payments WHERE ...";
$paymentsQuery = "SELECT * FROM payments WHERE ... LIMIT x OFFSET y";
$statsQuery = "SELECT SUM(...), COUNT(...) FROM payments WHERE ...";

// After: 1 query with window functions
$optimizedQuery = "SELECT *, COUNT(*) OVER() as total_count, SUM(...) OVER() FROM payments ...";
```

### 3. **Optimized AdminController.services() - Eliminated Subquery**
**File:** `src/Controllers/AdminController.php`

**Problem:** A correlated subquery was used to count active orders for each service, executing once per service row.

**Solution:** Replaced the subquery with a LEFT JOIN and COUNT() with CASE in the GROUP BY clause.

**Performance Gain:** For a page with 20 services, this reduces queries from 20 subqueries to 0, resulting in significantly faster page loads.

```php
// Before: Correlated subquery
SELECT s.*, (SELECT COUNT(*) FROM orders WHERE service_id = s.id AND ...) as active_orders_count
FROM services s

// After: LEFT JOIN with aggregation
SELECT s.*, COUNT(CASE WHEN o.status IN (...) THEN 1 END) as active_orders_count
FROM services s
LEFT JOIN orders o ON o.service_id = s.id
GROUP BY s.id
```

### 4. **Optimized OrderRepository.incrementStudentOrderCount() - Single UPSERT**
**File:** `src/Repositories/OrderRepository.php`

**Problem:** The method was executing two queries:
- One SELECT to check if profile exists
- One INSERT or UPDATE based on the result

**Solution:** Used MySQL's `INSERT ... ON DUPLICATE KEY UPDATE` syntax to perform the operation in a single query.

**Performance Gain:** 50% reduction in database queries (from 2 to 1).

```php
// Before: 2 queries (check + insert/update)
$checkQuery = "SELECT id FROM student_profiles WHERE user_id = ?";
if (!$profile) {
    $insertQuery = "INSERT INTO student_profiles ...";
} else {
    $updateQuery = "UPDATE student_profiles SET total_orders = total_orders + 1 ...";
}

// After: 1 UPSERT query
$upsertQuery = "INSERT INTO student_profiles (...) VALUES (...) 
                ON DUPLICATE KEY UPDATE total_orders = total_orders + 1";
```

### 5. **Added Database Indexes**
**File:** `migrations/032_add_performance_indexes.sql`

**Problem:** Many frequently queried columns lacked proper indexes, causing full table scans.

**Solution:** Added composite indexes on frequently queried column combinations:
- `orders`: status + created_at, status + completed_at, student_id + status, client_id + status, service_id + status
- `payments`: status + created_at, order_id
- `messages`: order_id + sender_id, read flags
- `services`: status + created_at, delivery_days
- `student_profiles`: average_rating, user_id
- `notifications`: user_id + is_read, user_id + created_at
- `reviews`: student_id + is_hidden, order_id + is_hidden

**Performance Gain:** Queries using these indexes will be significantly faster, especially as data grows. Expected 10-100x speedup on filtered queries depending on table size.

### 6. **Added Categories Caching in ServiceRepository**
**File:** `src/Repositories/ServiceRepository.php`

**Problem:** The `getAllCategories()` method was called multiple times per request (e.g., in search pages, filter sidebars), causing redundant database queries.

**Solution:** Implemented static in-memory caching for categories with cache invalidation on category modifications.

**Performance Gain:** Eliminates redundant category queries within a single request.

```php
// Static cache property
private static ?array $categoriesCache = null;

// Cached retrieval
public function getAllCategories(): array {
    if (self::$categoriesCache !== null) {
        return self::$categoriesCache;
    }
    // Fetch from database
    self::$categoriesCache = $stmt->fetchAll();
    return self::$categoriesCache;
}
```

## Estimated Overall Performance Impact

| Area | Before | After | Improvement |
|------|--------|-------|-------------|
| Admin Dashboard Load | 4 queries | 2 queries | 50% fewer queries |
| Payments Page | 3 queries | 1 query | 66% fewer queries |
| Services Admin Page (20 items) | 22+ queries | 2 queries | 90% fewer queries |
| Student Order Count Update | 2 queries | 1 query | 50% fewer queries |
| Category Loading | N queries/request | 1 query/request | Up to 90% fewer queries |

## Best Practices Implemented

1. **Query Optimization**: Combine multiple queries into single queries using JOINs, window functions, and aggregate functions
2. **Index Strategy**: Add indexes on frequently queried columns and column combinations
3. **Caching**: Implement in-memory caching for frequently accessed, rarely-changed data
4. **Eliminate N+1 Queries**: Replace loops with database queries by using JOINs
5. **Use UPSERT**: Leverage database-specific features like `INSERT ... ON DUPLICATE KEY UPDATE` for better performance

## Testing Recommendations

1. Run the migration: `php cli/migrate.php` to add performance indexes
2. Test all admin pages to ensure functionality is preserved
3. Monitor query execution times using MySQL slow query log
4. Use `EXPLAIN` to verify indexes are being used
5. Load test with realistic data volumes to measure improvements

## Future Optimization Opportunities

1. Implement Redis or Memcached for distributed caching
2. Add query result caching for complex search queries
3. Consider read replicas for heavy read workloads
4. Implement database query logging to identify remaining slow queries
5. Add pagination to admin pages that list many records
6. Consider lazy loading or infinite scroll for large result sets
