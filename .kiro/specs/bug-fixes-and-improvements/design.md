# Design Document

## Overview

This design document outlines the technical approach for fixing critical bugs and implementing missing features across the student skills marketplace platform. The fixes address authentication, file handling, messaging, notifications, UI/UX improvements, and admin functionality.

## Architecture

The platform follows an MVC-like architecture with:

- **Controllers**: Handle HTTP requests and responses
- **Services**: Contain business logic
- **Repositories**: Handle database operations
- **Views**: Render HTML templates
- **Middleware**: Handle cross-cutting concerns (auth, CSRF, roles)

All fixes will maintain this architecture and follow existing patterns.

## Components and Interfaces

### 1. Stripe Connect Integration Fix

**Problem**: Stripe Connect integration is failing during account connection.

**Root Cause Analysis**:

- Missing or incorrect Stripe Connect configuration
- Token storage issues in database
- Callback URL handling problems

**Solution**:

- Review and fix Stripe Connect OAuth flow
- Ensure proper token storage in `stripe_connect_tokens` table
- Add error logging for debugging
- Validate environment variables (STRIPE_CLIENT_ID, STRIPE_SECRET_KEY)

**Files to Modify**:

- `src/Controllers/StripeConnectController.php` (if exists, otherwise create)
- `src/Services/PaymentService.php`
- `src/Repositories/StripeConnectRepository.php` (if exists, otherwise create)

### 2. Password Visibility Toggle

**Problem**: Password fields lack visibility toggle functionality.

**Solution**:

- Add eye icon button next to all password input fields
- Implement JavaScript to toggle input type between "password" and "text"
- Apply consistently across all forms (login, register, password reset, password change)

**Implementation**:

- Create reusable password input component in views
- Add CSS for eye icon styling
- Add JavaScript event handlers for toggle functionality

**Files to Modify**:

- `views/auth/login.php`
- `views/auth/register.php`
- `views/auth/reset-password.php`
- `views/profile/change-password.php`
- `public/css/style.css`
- `public/js/password-toggle.js ` (new file)

### 3. Remember Me Functionality

**Problem**: No persistent login option available.

**Solution**:

- Add "Remember Me" checkbox to login form
- Generate secure remember token on login when checkbox is checked
- Store token in `remember_tokens` table with user_id, token hash, and expiration (30 days)
- Check for remember token cookie on subsequent visits
- Auto-authenticate user if valid token exists

**Database Schema**:

```sql
CREATE TABLE remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id)
);
```

**Files to Modify**:

- `src/Controllers/AuthController.php`
- `src/Services/AuthService.php`
- `src/Repositories/RememberTokenRepository.php` (new)
- `src/Middleware/AuthMiddleware.php`
- `views/auth/login.php`
- Database migration file

### 4. Form State Persistence

**Problem**: Form data is lost when validation errors occur.

**Solution**:

- Store submitted form data in session flash data
- Repopulate form fields with old values on validation error
- Clear old values after successful submission
- Apply to all forms: service creation/editing, order creation, review submission

**Implementation Pattern**:

```php
// In controller after validation fails
$_SESSION['old'] = $_POST;
$_SESSION['errors'] = $errors;

// In view
$oldValue = $_SESSION['old']['field_name'] ?? '';
unset($_SESSION['old']); // Clear after use
```

**Files to Modify**:

- `src/Controllers/ServiceController.php`
- `src/Controllers/OrderController.php`
- `src/Controllers/ReviewController.php`
- All relevant view files
- `src/Helpers/FormHelper.php` (create helper for old() function)

### 5. Order Cancellation and Refunds

**Problem**: Order cancellation fails with "Failed to process refund" error.

**Root Cause Analysis**:

- Stripe refund API call failing
- Missing error handling and logging
- Incorrect payment intent ID or charge ID

**Solution**:

- Add comprehensive error logging in refund process
- Validate Stripe payment intent exists before refund attempt
- Handle partial refunds and refund failures gracefully
- Store refund transaction details in database

**Files to Modify**:

- `src/Services/PaymentService.php`
- `src/Controllers/OrderController.php`
- `src/Repositories/OrderRepository.php`
- Add logging to `logs/stripe_errors.log`

### 6. Email Notifications for Activities

**Problem**: Missing email notifications for various platform activities.

**Solution**:

- Implement email notification service using existing email infrastructure
- Create email templates for each notification type
- Trigger notifications on key events (order created, status changed, message received, review submitted, dispute events)

**Notification Events**:

1. Order created → notify client and student
2. Order status changed → notify relevant party
3. Message received → notify recipient
4. Review submitted → notify student
5. Dispute created/updated → notify all parties

**Files to Modify**:

- `src/Services/NotificationService.php`
- `src/Services/EmailService.php`
- `src/Controllers/OrderController.php`
- `src/Controllers/MessageController.php`
- `src/Controllers/ReviewController.php`
- `src/Controllers/DisputeController.php`
- Email template files in `views/emails/`

### 7. Order Detail File Access

**Problem**: Requirement files don't appear on order detail page, delivered files throw 404 errors.

**Root Cause Analysis**:

- File paths not being retrieved or displayed correctly
- File serving endpoint missing or broken
- Permission checks failing

**Solution**:

- Fix file path retrieval in OrderRepository
- Ensure FileService correctly resolves file paths
- Add file download endpoint with proper authentication
- Display both requirement files and delivered files on order detail page

**Files to Modify**:

- `src/Controllers/OrderController.php`
- `src/Controllers/FileController.php` (create if doesn't exist)
- `src/Services/FileService.php`
- `src/Repositories/OrderRepository.php`
- `views/orders/detail.php`

### 8. Message System Fixes

**Problem**: Multiple issues with messaging system.

**Issues**:

1. SQL error: "Incorrect integer value: '' for column 'is_flagged'"
2. /messages route returns 404
3. Message icon links to /orders instead of /messages
4. /messages/unread-count returns 500 error

**Solutions**:

1. Set default value for is_flagged to 0 in MessageRepository
2. Add /messages route to Router
3. Fix navbar message icon link
4. Implement /messages/unread-count endpoint properly

**Files to Modify**:

- `src/Repositories/MessageRepository.php`
- `src/Router.php`
- `src/Controllers/MessageController.php`
- `views/layouts/navbar.php`

### 9. Review Display Improvements

**Problem**: Review stars not yellow, reviewer shows as "anonymous", missing profile picture.

**Solution**:

- Update CSS for star ratings to use yellow color
- Fetch and display reviewer name from users table
- Generate initials from reviewer name for profile picture
- Update review display logic in views

**Files to Modify**:

- `public/css/style.css`
- `src/Repositories/ReviewRepository.php`
- `views/students/profile.php`
- `views/services/detail.php`

### 10. Notification Polling

**Problem**: Notification polling returns 500 errors.

**Root Cause Analysis**:

- /notifications/unread-count endpoint not implemented or broken
- Missing NotificationController or method

**Solution**:

- Implement NotificationController with unread-count method
- Add route for /notifications/unread-count
- Return JSON response with unread count
- Ensure proper authentication check

**Files to Modify**:

- `src/Controllers/NotificationController.php`
- `src/Repositories/NotificationRepository.php`
- `src/Router.php`
- `public/js/notifications.js`

### 11. Category Management

**Problem**: No way to edit existing categories.

**Solution**:

- Add edit button to category list in admin panel
- Create edit category form
- Implement update method in CategoryController
- Add validation for category updates

**Files to Modify**:

- `src/Controllers/Admin/CategoryController.php`
- `views/admin/categories/index.php`
- `views/admin/categories/edit.php` (new)
- `src/Router.php`

### 12. Admin Service Filtering

**Problem**: "All" status filter doesn't include active services, inactive services render multiple times.

**Root Cause Analysis**:

- SQL query filtering logic incorrect
- Possible JOIN issue causing duplicate rows

**Solution**:

- Fix SQL query to include all statuses when "all" is selected
- Add DISTINCT or GROUP BY to prevent duplicates
- Review JOIN conditions in ServiceRepository

**Files to Modify**:

- `src/Repositories/ServiceRepository.php`
- `src/Controllers/Admin/ServiceController.php`

### 13. Service Activation

**Problem**: No way to activate inactive services.

**Solution**:

- Add "Activate" button to inactive service view in admin panel
- Implement activate method in ServiceController
- Update service status to "active" in database
- Send notification to student
- Log activation in audit logs

**Files to Modify**:

- `src/Controllers/Admin/ServiceController.php`
- `views/admin/services/detail.php`
- `src/Services/NotificationService.php`
- `src/Router.php`

### 14. Service Sample Works Display

**Problem**: Sample works not displaying on service detail page after FileService implementation.

**Root Cause Analysis**:

- FileService changes broke sample work file path retrieval
- Sample work files not being fetched from database
- File path resolution incorrect

**Solution**:

- Review FileService implementation for sample works
- Ensure ServiceRepository fetches sample work files
- Fix file path resolution in views
- Test file display and download functionality

**Files to Modify**:

- `src/Services/FileService.php`
- `src/Repositories/ServiceRepository.php`
- `src/Controllers/ServiceController.php`
- `views/services/detail.php`

### 15. Form Button Loading States

**Problem**: No visual feedback when forms are being submitted, allowing duplicate submissions.

**Solution**:

Create a modular JavaScript solution that can be applied to any form button:

1. **JavaScript Module** (`public/js/form-loading.js`):

   - Add event listener to form submissions
   - On submit, add loading class to button
   - Disable button to prevent duplicate clicks
   - Show loading spinner or text change
   - Re-enable button on error or completion

2. **CSS Styling**:

   - `.btn-loading` class with spinner animation
   - Disabled button styling
   - Loading spinner using CSS or Font Awesome icon

3. **Implementation Pattern**:

```javascript
// Auto-apply to all forms with data-loading attribute
document.querySelectorAll("form[data-loading]").forEach((form) => {
  form.addEventListener("submit", function (e) {
    const submitBtn = form.querySelector('button[type="submit"]')
    submitBtn.disabled = true
    submitBtn.classList.add("btn-loading")
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...'
  })
})
```

4. **HTML Usage**:

```html
<form method="POST" data-loading>
  <button
    type="submit"
    class="btn btn-primary"
    data-loading-text="Processing..."
  >
    Submit
  </button>
</form>
```

**Files to Create/Modify**:

- `public/js/form-loading.js` (new)
- `public/css/style.css` (add loading styles)
- `views/layouts/base.php` (include script)
- Update all form views to add `data-loading` attribute

### 16. Number Format Null Handling

**Problem**: PHP 8.1+ deprecation warnings when null values are passed to number_format().

**Root Cause Analysis**:

- number_format() expects float/int but receives null values
- Occurs when database fields are nullable (balance, price, amount, etc.)
- PHP 8.1+ made this a deprecation warning

**Solution**:

1. **Create Helper Function**:

```php
function safe_number_format($number, $decimals = 2, $decimal_separator = '.', $thousands_separator = ',') {
    return number_format($number ?? 0, $decimals, $decimal_separator, $thousands_separator);
}
```

2. **Find and Replace Pattern**:

   - Search for all `number_format(` calls
   - Replace with `safe_number_format(` or add null coalescing operator
   - Alternative: `number_format($value ?? 0, 2)`

3. **Common Locations**:
   - View files displaying prices, balances, earnings
   - Controllers preparing data for views
   - Service classes calculating totals
   - Repository classes with aggregate queries

**Files to Modify**:

- `src/Helpers.php` (add safe_number_format function)
- Search and replace in all view files
- `src/Controllers/StudentController.php`
- `src/Controllers/OrderController.php`
- `src/Controllers/WithdrawalController.php`
- `src/Services/PaymentService.php`
- Any other files using number_format
- `views/` folder specially dashboards

### 17. Dashboard Quick Actions Modernization

**Problem**: Dashboard quick actions show "coming soon" notices and lack modern styling with icons.

**Solution**:

1. **Remove Coming Soon Notices**:

   - Remove or comment out placeholder quick action cards
   - Implement functional quick actions only

2. **Add Font Awesome Icons**:

   - Ensure Font Awesome is loaded in layout
   - Add appropriate icons for each action:
     - Services: `fa-briefcase`
     - Create Service: `fa-plus-circle`
     - Orders: `fa-shopping-cart`
     - Earnings: `fa-dollar-sign`
     - Messages: `fa-envelope`
     - Browse: `fa-search`
     - Users: `fa-users`
     - Reports: `fa-chart-bar`

3. **Modern Card Design**:

use tailwind for styling not custom css

```html
<div class="quick-action-card">
  <div class="quick-action-icon">
    <i class="fas fa-briefcase"></i>
  </div>
  <div class="quick-action-content">
    <h3>My Services</h3>
    <p>Manage your service listings</p>
  </div>
  <a href="/student/services" class="quick-action-link">
    View <i class="fas fa-arrow-right"></i>
  </a>
</div>
```

4. **CSS Styling**:

   - Card-based layout with hover effects
   - Icon circles with gradient backgrounds
   - Smooth transitions
   - Responsive grid layout
   - Modern color scheme

5. **Quick Actions by Role**:

**Student Dashboard**:

- View My Services → `/student/services`
- Create New Service → `/student/services/create`
- View Orders → `/student/orders`
- View Earnings → `/student/withdrawals`
- Messages → `/messages`

**Client Dashboard**:

- Browse Services → `/services`
- My Orders → `/client/orders`
- Messages → `/messages`
- My Profile → `/client/profile`

**Admin Dashboard**:

- Manage Users → `/admin/users`
- Manage Services → `/admin/services`
- View Orders → `/admin/orders`
- View Payments → `/admin/payments`
- Review Moderation → `/admin/reviews/moderation`

**Files to Modify**:

- `views/student/dashboard.php`
- `views/client/dashboard.php`
- `views/admin/dashboard.php`
- `public/css/style.css` (add quick action styles)
- `views/layouts/main.php` (ensure Font Awesome is loaded)

## Data Models

### Remember Token Model

```php
class RememberToken {
    public int $id;
    public int $user_id;
    public string $token_hash;
    public DateTime $expires_at;
    public DateTime $created_at;
}
```

### Updated Message Model

```php
class Message {
    public int $id;
    public int $sender_id;
    public int $receiver_id;
    public string $content;
    public int $is_flagged = 0; // Default value
    public DateTime $created_at;
}
```

## Error Handling

### General Approach

- Log all errors to appropriate log files
- Display user-friendly error messages
- Maintain detailed error logs for debugging
- Use try-catch blocks for external API calls (Stripe)

### Specific Error Scenarios

1. **Stripe API Failures**: Log full error response, display generic message to user
2. **File Not Found**: Return 404 with clear message, log file path attempted
3. **Database Errors**: Log query and error, display generic error to user
4. **Validation Errors**: Display field-specific errors, preserve form state

## Testing Strategy

### Manual Testing Checklist

1. Test Stripe Connect flow end-to-end
2. Verify password toggle on all forms
3. Test remember me functionality across browser sessions
4. Submit forms with validation errors and verify data persistence
5. Test order cancellation and refund process
6. Verify email notifications are sent for all events
7. Test file uploads and downloads for orders
8. Send messages and verify no SQL errors
9. Check message routing and unread count polling
10. Verify review display with correct styling and reviewer info
11. Test notification polling
12. Edit categories in admin panel
13. Filter services by all statuses in admin panel
14. Activate inactive services
15. View service sample works on detail page
16. Submit forms and verify loading states appear and buttons are disabled
17. Test rapid clicking on submit buttons to ensure no duplicate submissions
18. Verify all number_format calls handle null values without warnings
19. Check dashboard quick actions display correctly with Font Awesome icons
20. Verify all quick action links navigate to correct pages
21. Test quick actions on student, client, and admin dashboards

### Database Testing

- Verify remember_tokens table creation
- Check default values for is_flagged column
- Test foreign key constraints
- Verify indexes are created

### Integration Testing

- Test Stripe API integration with test keys
- Verify email sending with test SMTP server
- Test file upload/download with various file types
- Test notification system end-to-end

## Security Considerations

1. **Remember Tokens**: Use cryptographically secure random tokens, hash before storage
2. **File Access**: Validate user permissions before serving files
3. **SQL Injection**: Use prepared statements for all queries
4. **CSRF Protection**: Maintain existing CSRF middleware
5. **Password Visibility**: Ensure toggle doesn't compromise security
6. **Stripe Keys**: Keep secret keys in environment variables, never expose in client-side code
