<?php

/**
 * Web Routes
 *
 * Define all application routes here
 */

// Load middleware classes
require_once __DIR__ . '/../src/Middleware/MiddlewareInterface.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../src/Middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../src/Middleware/RoleMiddleware.php';

// Home page
$router->get('/', 'HomeController@home');

// Test route with parameters
$router->get('/test/{id}', function ($id) {
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Test Route</title></head><body>";
    echo "<h1>Test Route</h1>";
    echo "<p>ID parameter: " . htmlspecialchars($id) . "</p>";
    echo "<p><a href='/'>Back to home</a></p>";
    echo "</body></html>";
});

// Authentication routes
$router->get('/auth/register', 'AuthController@showRegister');
$router->post('/auth/register', 'AuthController@register', [new CsrfMiddleware()]);
$router->get('/auth/verify-email', 'AuthController@verifyEmail');
$router->get('/auth/login', 'AuthController@showLogin');
$router->post('/auth/login', 'AuthController@login', [new CsrfMiddleware()]);
$router->get('/auth/logout', 'AuthController@logout');
$router->get('/auth/request-reset', 'AuthController@showRequestReset');
$router->post('/auth/request-reset', 'AuthController@requestReset', [new CsrfMiddleware()]);
$router->get('/auth/reset-password', 'AuthController@showResetPassword');
$router->post('/auth/reset-password', 'AuthController@resetPassword', [new CsrfMiddleware()]);

// Legacy routes for backward compatibility
$router->get('/login', function () {
    redirect('/auth/login');
});

$router->get('/register', function () {
    redirect('/auth/register');
});

// Dashboard routes (protected - require authentication and specific roles)
$router->get('/student/dashboard', function () {
    view('student/dashboard', [], 'dashboard');
}, [new AuthMiddleware(), new RoleMiddleware('student')]);

$router->get('/client/dashboard', function () {
    view('client/dashboard', [
        'title' => 'Client Dashboard - Student Skills Marketplace',
    ], 'dashboard');
}, [new AuthMiddleware(), new RoleMiddleware('client')]);

$router->get('/admin/dashboard', 'AdminController@dashboard', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

// Admin payment history
$router->get('/admin/payments', 'AdminController@payments', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

// Admin order management
$router->get('/admin/orders', 'AdminController@orders', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

// Admin user management
$router->get('/admin/users', 'AdminController@users', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->get('/admin/users/{id}', 'AdminController@showUser', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->post('/admin/users/{id}/suspend', 'AdminController@suspendUser', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

$router->post('/admin/users/{id}/reactivate', 'AdminController@reactivateUser', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

// Admin service moderation
$router->get('/admin/services', 'AdminController@services', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->get('/admin/services/{id}', 'AdminController@showService', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->post('/admin/services/{id}/deactivate', 'AdminController@deactivateService', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

$router->post('/admin/services/{id}/activate', 'AdminController@activateService', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

$router->post('/admin/services/{id}/delete', 'AdminController@deleteService', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

// Example POST route with CSRF protection
$router->post('/test/submit', function () {
    echo json_encode([
        'success' => true,
        'message' => 'Form submitted successfully',
        'data'    => $_POST,
    ]);
    header('Content-Type: application/json');
}, [new CsrfMiddleware()]);

// Order routes

// List orders
$router->get('/orders', 'OrderController@index', [
    new AuthMiddleware(),
]);

// Create order form (client only)
$router->get('/orders/create', 'OrderController@create', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
]);

// Store new order (client only)
$router->post('/orders/store', 'OrderController@store', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
    new CsrfMiddleware(),
]);

// Payment success callback
$router->get('/orders/payment-success', 'OrderController@paymentSuccess', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
]);

// View order (accessible by client, student, or admin)
$router->get('/orders/{id}', 'OrderController@show', [
    new AuthMiddleware(),
]);

// Accept order (student only)
$router->post('/orders/{id}/accept', 'OrderController@accept', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Deliver order (student only)
$router->post('/orders/{id}/deliver', 'OrderController@deliver', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Request revision (client only)
$router->post('/orders/{id}/request-revision', 'OrderController@requestRevision', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
    new CsrfMiddleware(),
]);

// Complete order (client only)
$router->post('/orders/{id}/complete', 'OrderController@complete', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
    new CsrfMiddleware(),
]);

// Cancel order (client or student)
$router->post('/orders/{id}/cancel', 'OrderController@cancel', [
    new AuthMiddleware(),
    new RoleMiddleware(['client', 'student']),
    new CsrfMiddleware(),
]);

// Service routes (student only)

// List all services for student
$router->get('/student/services', 'ServiceController@index', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
]);

// Create service form
$router->get('/student/services/create', 'ServiceController@create', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
]);

// Store new service
$router->post('/student/services/store', 'ServiceController@store', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Show service detail
$router->get('/student/services/{id}', 'ServiceController@show', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
]);

// Edit service form
$router->get('/student/services/{id}/edit', 'ServiceController@edit', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
]);

// Update service
$router->post('/student/services/{id}/update', 'ServiceController@update', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Delete service
$router->post('/student/services/{id}/delete', 'ServiceController@delete', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Message routes

// List all message conversations
$router->get('/messages', 'MessageController@index', [
    new AuthMiddleware(),
]);

// View message thread (client or student, ownership checked in controller)
$router->get('/messages/thread/{orderId}', 'MessageController@thread', [
    new AuthMiddleware(),
    new RoleMiddleware(['client', 'student']),
]);

// Send message (client or student, ownership checked in controller)
$router->post('/messages/send', 'MessageController@send', [
    new AuthMiddleware(),
    new RoleMiddleware(['client', 'student']),
    new CsrfMiddleware(),
]);

// Poll for new messages (AJAX endpoint)
$router->get('/messages/poll', 'MessageController@poll', [
    new AuthMiddleware(),
    new RoleMiddleware(['client', 'student']),
]);

// Get unread message count (AJAX endpoint)
$router->get('/messages/unread-count', 'MessageController@unreadCount', [
    new AuthMiddleware(),
]);

// Notification routes

// Notification center
$router->get('/notifications', 'NotificationController@index', [
    new AuthMiddleware(),
]);

// Get unread notification count (AJAX endpoint)
$router->get('/notifications/unread-count', 'NotificationController@getUnreadCount', [
    new AuthMiddleware(),
]);

// Mark notification as read (AJAX endpoint)
$router->post('/notifications/mark-as-read', 'NotificationController@markAsRead', [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);

// Mark all notifications as read (AJAX endpoint)
$router->post('/notifications/mark-all-as-read', 'NotificationController@markAllAsRead', [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);

// Student Profile routes

// Edit profile (student only)
$router->get('/student/profile/edit', 'ProfileController@edit', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
]);

// Update profile (student only)
$router->post('/student/profile/update', 'ProfileController@update', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Delete portfolio file (student only)
$router->post('/student/profile/delete-file', 'ProfileController@deletePortfolioFile', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Delete profile picture (student only)
$router->post('/student/profile/delete-picture', 'ProfileController@deleteProfilePicture', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Connect Stripe (student only)
$router->get('/student/profile/connect-stripe', 'ProfileController@connectStripe', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
]);

// Stripe Connect return (no auth middleware - handles session internally)
$router->get('/student/profile/connect-stripe/return', 'ProfileController@connectStripeReturn');

// Public student profile (accessible to all)
$router->get('/student/profile', 'ProfileController@show');

// Service Discovery routes (public - accessible to all)
$router->get('/services/search', 'DiscoveryController@search');
$router->get('/services/{id}', 'DiscoveryController@show');

// File serving routes
$router->get('/storage/file', 'FileController@serve');
$router->get('/files/download', 'FileController@download'); // Secure download with signed URLs

// Webhook routes (no authentication - verified by signature)
$router->post('/webhooks/stripe', 'WebhookController@stripe');

// Withdrawal routes (student only)
$router->get('/student/withdrawals', 'WithdrawalController@index', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
]);

$router->post('/student/withdrawals/request', 'WithdrawalController@request', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

$router->get('/student/withdrawals/stripe-dashboard', 'WithdrawalController@stripeDashboard', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
]);

// Review routes

// Create review form (client only)
$router->get('/reviews/create', 'ReviewController@create', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
]);

// Store new review (client only)
$router->post('/reviews/store', 'ReviewController@store', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
    new CsrfMiddleware(),
]);

// Edit review form (client only)
$router->get('/reviews/{id}/edit', 'ReviewController@edit', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
]);

// Update review (client only)
$router->post('/reviews/{id}/update', 'ReviewController@update', [
    new AuthMiddleware(),
    new RoleMiddleware('client'),
    new CsrfMiddleware(),
]);

// Add student reply to review (student only)
$router->post('/reviews/{id}/reply', 'ReviewController@reply', [
    new AuthMiddleware(),
    new RoleMiddleware('student'),
    new CsrfMiddleware(),
]);

// Settings routes (authenticated users)
$router->get('/settings/account', 'SettingsController@account', [
    new AuthMiddleware(),
]);

$router->post('/settings/account/update', 'SettingsController@updateAccount', [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);

$router->post('/settings/password/update', 'SettingsController@updatePassword', [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);

// Dispute routes

// Create dispute form (client or student)
$router->get('/disputes/create', 'DisputeController@create', [
    new AuthMiddleware(),
    new RoleMiddleware(['client', 'student']),
]);

// Store new dispute (client or student)
$router->post('/disputes/store', 'DisputeController@store', [
    new AuthMiddleware(),
    new RoleMiddleware(['client', 'student']),
    new CsrfMiddleware(),
]);

// Admin dispute management
$router->get('/admin/disputes', 'AdminController@disputes', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->get('/admin/disputes/{id}', 'AdminController@showDispute', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->post('/admin/disputes/{id}/resolve', 'AdminController@resolveDispute', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

// Admin platform settings
$router->get('/admin/settings', 'AdminController@settings', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->post('/admin/settings/update', 'AdminController@updateSettings', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

// Admin category management
$router->get('/admin/categories', 'AdminController@categories', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->post('/admin/categories/create', 'AdminController@createCategory', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

$router->get('/admin/categories/{id}/edit', 'AdminController@editCategory', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
]);

$router->post('/admin/categories/{id}/update', 'AdminController@updateCategory', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);

$router->post('/admin/categories/{id}/delete', 'AdminController@deleteCategory', [
    new AuthMiddleware(),
    new RoleMiddleware('admin'),
    new CsrfMiddleware(),
]);
