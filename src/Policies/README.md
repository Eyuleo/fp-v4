# Authorization System

This directory contains the policy classes for role-based access control (RBAC) in the Student Skills Marketplace.

## Overview

The authorization system uses **Policy classes** to define fine-grained permissions for different resources. Policies check both the user's role and their relationship to the resource (e.g., ownership).

## Components

### 1. Policy Interface (`Policy.php`)

Base interface that all policy classes implement.

### 2. Policy Classes

#### OrderPolicy (`OrderPolicy.php`)

Handles authorization for order-related actions:

- `canView()` - Check if user can view an order
- `canAccept()` - Check if student can accept an order
- `canDeliver()` - Check if student can deliver work
- `canRequestRevision()` - Check if client can request revisions
- `canComplete()` - Check if client can complete an order
- `canCancel()` - Check if user can cancel an order

#### ServicePolicy (`ServicePolicy.php`)

Handles authorization for service-related actions:

- `canCreate()` - Check if user can create services
- `canEdit()` - Check if user can edit a service
- `canDelete()` - Check if user can delete a service
- `canActivate()` - Check if user can activate/pause a service

#### MessagePolicy (`MessagePolicy.php`)

Handles authorization for messaging actions:

- `canSend()` - Check if user can send messages in an order
- `canView()` - Check if user can view messages in an order

### 3. Auth Helper (`src/Auth.php`)

Provides convenient methods for authorization checks:

```php
// Check if user can perform an action (returns boolean)
if (Auth::can('order', 'view', $order)) {
    // User can view the order
}

// Check if user cannot perform an action
if (Auth::cannot('service', 'edit', $service)) {
    // User cannot edit the service
}

// Authorize or throw 403 exception
Auth::authorizeOrFail('order', 'complete', $order);
```

### 4. RoleMiddleware (`src/Middleware/RoleMiddleware.php`)

Middleware that checks if user has required role(s) before accessing a route.

## Usage Examples

### In Controllers

```php
class OrderController
{
    public function show(int $orderId): void
    {
        $order = $this->getOrderById($orderId);

        // Check authorization - throws 403 if unauthorized
        Auth::authorizeOrFail('order', 'view', $order);

        // Continue with business logic...
        view('orders/show', ['order' => $order]);
    }

    public function complete(int $orderId): void
    {
        $order = $this->getOrderById($orderId);

        // Check authorization
        Auth::authorizeOrFail('order', 'complete', $order);

        // Process completion...
    }
}
```

### In Routes

```php
// Require authentication + specific role
$router->post('/orders/{id}/accept', 'OrderController@accept', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),  // Only students
    new CsrfMiddleware()
]);

// Allow multiple roles
$router->post('/orders/{id}/cancel', 'OrderController@cancel', [
    new AuthMiddleware(),
    new RoleMiddleware(['client', 'student']),  // Clients or students
    new CsrfMiddleware()
]);
```

### In Views

```php
<?php if (Auth::can('order', 'complete', $order)): ?>
    <form method="POST" action="/orders/<?= $order['id'] ?>/complete">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary">Complete Order</button>
    </form>
<?php endif; ?>

<?php if (Auth::can('service', 'edit', $service)): ?>
    <a href="/services/<?= $service['id'] ?>/edit">Edit Service</a>
<?php endif; ?>
```

## Authorization Flow

1. **Route Middleware** - `RoleMiddleware` checks if user has the required role
2. **Controller Authorization** - `Auth::authorizeOrFail()` checks fine-grained permissions
3. **Policy Evaluation** - Policy class checks role + ownership/state

### Example Flow

```
Request: POST /orders/123/complete
    ↓
AuthMiddleware: Is user logged in? ✓
    ↓
RoleMiddleware: Is user a 'client'? ✓
    ↓
OrderController::complete()
    ↓
Auth::authorizeOrFail('order', 'complete', $order)
    ↓
OrderPolicy::canComplete($user, $order)
    - Is user a client? ✓
    - Is user the order owner? ✓
    - Is order status 'delivered'? ✓
    ↓
Authorization successful - proceed with business logic
```

## Adding New Policies

1. Create a new policy class implementing `Policy` interface
2. Add authorization methods (e.g., `canView`, `canEdit`)
3. Register the policy in `Auth::getPolicy()` method
4. Use in controllers with `Auth::authorizeOrFail()`

Example:

```php
class ReviewPolicy implements Policy
{
    public function canEdit(array $user, array $review): bool
    {
        // Only client who wrote the review can edit
        if ($user['role'] !== 'client') {
            return false;
        }

        if ($review['client_id'] != $user['id']) {
            return false;
        }

        // Check 24-hour edit window
        $canEditUntil = new DateTime($review['can_edit_until']);
        $now = new DateTime();

        return $now < $canEditUntil;
    }
}
```

## Best Practices

1. **Use RoleMiddleware for coarse-grained checks** - Filter by role at the route level
2. **Use Policies for fine-grained checks** - Check ownership and state in controllers
3. **Always check authorization before business logic** - Fail fast with 403
4. **Use `authorizeOrFail()` in controllers** - Throws exception automatically
5. **Use `can()` in views** - Conditionally show/hide UI elements
6. **Keep policies simple** - One responsibility per method
7. **Return boolean** - Policies should return true/false, not throw exceptions

## Security Notes

- Policies receive user data from session - ensure session is secure
- Always validate resource exists before checking authorization
- Policies check permissions, not business rules (use services for that)
- Admin role typically bypasses most restrictions
- Never trust client-side authorization - always check server-side
