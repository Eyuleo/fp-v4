<?php

/**
 * Web Routes
 *
 * Define all application routes here
 */

// Load middleware classes
require_once __DIR__ . "/../src/Middleware/MiddlewareInterface.php";
require_once __DIR__ . "/../src/Middleware/AuthMiddleware.php";
require_once __DIR__ . "/../src/Middleware/CsrfMiddleware.php";
require_once __DIR__ . "/../src/Middleware/RateLimitMiddleware.php";
require_once __DIR__ . "/../src/Middleware/RoleMiddleware.php";
require_once __DIR__ . "/../src/Middleware/ServiceEditMiddleware.php";

// Home page
$router->get("/", "HomeController@home");

// Authentication routes
$router->get("/auth/register", "AuthController@showRegister");
$router->post("/auth/register", "AuthController@register", [
    new CsrfMiddleware(),
]);
$router->get("/auth/verify-email", "AuthController@verifyEmail");
$router->get("/auth/login", "AuthController@showLogin");
$router->post("/auth/login", "AuthController@login", [new CsrfMiddleware()]);
$router->get("/auth/logout", "AuthController@logout");
$router->get("/auth/request-reset", "AuthController@showRequestReset");
$router->post("/auth/request-reset", "AuthController@requestReset", [
    new CsrfMiddleware(),
]);
$router->get("/auth/reset-password", "AuthController@showResetPassword");
$router->post("/auth/reset-password", "AuthController@resetPassword", [
    new CsrfMiddleware(),
]);

// Legacy routes for backward compatibility
$router->get("/login", function () {
    redirect("/auth/login");
});
$router->get("/register", function () {
    redirect("/auth/register");
});

// Dashboard routes
$router->get(
    "/student/dashboard",
    function () {
        $db = require __DIR__ . "/../config/database.php";
        require_once __DIR__ . "/../src/Repositories/StudentProfileRepository.php";
        require_once __DIR__ . "/../src/Repositories/OrderRepository.php";

        $profileRepository = new StudentProfileRepository($db);
        $orderRepository   = new OrderRepository($db);

        // Get student profile for balance info
        $profile = $profileRepository->findByUserId(user_id());
        
        // Calculate balances - use available_balance column
        $availableBalance = $profile['available_balance'] ?? 0;
        
        // Calculate pending balance from active orders
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(price), 0) as pending_balance
            FROM orders
            WHERE student_id = :student_id
            AND status IN ('in_progress', 'delivered', 'revision_requested')
        ");
        $stmt->execute(['student_id' => user_id()]);
        $pendingBalance = $stmt->fetch()['pending_balance'];
        
        // Calculate total earned (completed orders)
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(price), 0) as total_earned
            FROM orders
            WHERE student_id = :student_id
            AND status = 'completed'
        ");
        $stmt->execute(['student_id' => user_id()]);
        $totalEarned = $stmt->fetch()['total_earned'];
        
        // Get recent orders (last 3)
        $recentOrders = $orderRepository->findByStudentId(user_id(), null, 3);

        view("student/dashboard", [
            'availableBalance' => $availableBalance,
            'pendingBalance'   => $pendingBalance,
            'totalEarned'      => $totalEarned,
            'recentOrders'     => $recentOrders,
        ], "dashboard");
    },
    [new AuthMiddleware(), new RoleMiddleware("student")],
);

$router->get(
    "/client/dashboard",
    function () {
        $db = require __DIR__ . "/../config/database.php";
        require_once __DIR__ . "/../src/Repositories/UserRepository.php";
        require_once __DIR__ . "/../src/Repositories/OrderRepository.php";

        $userRepository  = new UserRepository($db);
        $orderRepository = new OrderRepository($db);

        $user         = $userRepository->findById(user_id());
        $recentOrders = $orderRepository->findByClientId(user_id(), null, 3);

        view(
            "client/dashboard",
            [
                "title"        => "Client Dashboard - Student Skills Marketplace",
                "user"         => $user,
                "recentOrders" => $recentOrders,
            ],
            "dashboard",
        );
    },
    [new AuthMiddleware(), new RoleMiddleware("client")],
);

$router->get("/admin/dashboard", "AdminController@dashboard", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

// Admin payment history
$router->get("/admin/payments", "AdminController@payments", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

// Admin payments export to PDF
$router->get("/admin/payments/export-pdf", "AdminController@exportPaymentsPDF", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

// Admin observability dashboard
$router->get("/admin/observability", "AdminController@observability", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

// Admin order management
$router->get("/admin/orders", "AdminController@orders", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

// Admin user management
$router->get("/admin/users", "AdminController@users", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

$router->get("/admin/users/{id}", "AdminController@showUser", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

$router->post("/admin/users/{id}/suspend", "AdminController@suspendUser", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);

$router->post(
    "/admin/users/{id}/reactivate",
    "AdminController@reactivateUser",
    [new AuthMiddleware(), new RoleMiddleware("admin"), new CsrfMiddleware()],
);

// Admin service moderation
$router->get("/admin/services", "AdminController@services", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

$router->get("/admin/services/{id}", "AdminController@showService", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

$router->post(
    "/admin/services/{id}/deactivate",
    "AdminController@deactivateService",
    [new AuthMiddleware(), new RoleMiddleware("admin"), new CsrfMiddleware()],
);

$router->post(
    "/admin/services/{id}/activate",
    "AdminController@activateService",
    [new AuthMiddleware(), new RoleMiddleware("admin"), new CsrfMiddleware()],
);

$router->post("/admin/services/{id}/delete", "AdminController@deleteService", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);

$router->post("/admin/services/{id}/reject", "AdminController@rejectService", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);

$router->post("/admin/services/{id}/approve", "AdminController@approveService", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);

$router->get("/admin/services/{id}/moderation-history", "AdminController@getModerationHistory", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

// Order routes
$router->get("/orders", "OrderController@index", [new AuthMiddleware()]);
$router->get("/orders/create", "OrderController@create", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
]);
$router->post("/orders/store", "OrderController@store", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
    new CsrfMiddleware(),
]);
$router->get("/orders/payment-success", "OrderController@paymentSuccess", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
]);
$router->get("/orders/payment-cancelled", "OrderController@paymentCancelled", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
]);
$router->post("/orders/retry-payment", "OrderController@retryPayment", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
    new CsrfMiddleware(),
]);
$router->get("/orders/{id}", "OrderController@show", [new AuthMiddleware()]);
$router->get("/orders/{id}/deliver", "OrderController@showDeliveryPage", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
]);
$router->post("/orders/{id}/accept", "OrderController@accept", [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);
$router->post("/orders/{id}/deliver", "OrderController@deliver", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);
$router->get("/orders/{id}/request-revision", "OrderController@showRevisionRequestPage", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
]);
$router->post(
    "/orders/{id}/request-revision",
    "OrderController@requestRevision",
    [new AuthMiddleware(), new RoleMiddleware("client"), new CsrfMiddleware()],
);
$router->post("/orders/{id}/complete", "OrderController@complete", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
    new CsrfMiddleware(),
]);
$router->post("/orders/{id}/cancel", "OrderController@cancel", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);

// PATCH (restored): Admin force-complete overdue delivered order
$router->post("/admin/orders/{id}/force-complete", "OrderController@forceComplete", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);

// Service routes (student only)
$router->get("/student/services", "ServiceController@index", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
]);
$router->get("/student/services/create", "ServiceController@create", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
]);
$router->post("/student/services/store", "ServiceController@store", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);
$router->get("/student/services/{id}", "ServiceController@show", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
]);
$router->get("/student/services/{id}/edit", "ServiceController@edit", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new ServiceEditMiddleware(),
]);
$router->post("/student/services/{id}/update", "ServiceController@update", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new ServiceEditMiddleware(),
    new CsrfMiddleware(),
]);
$router->post("/student/services/{id}/delete", "ServiceController@delete", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);
$router->post("/student/services/{id}/deactivate", "ServiceController@deactivate", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);
$router->post("/student/services/{id}/activate", "ServiceController@activate", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);

// Message routes
$router->get("/messages", "MessageController@index", [new AuthMiddleware()]);
$router->get("/messages/thread/{orderId}", "MessageController@thread", [
    new AuthMiddleware(),
    new RoleMiddleware(["client", "student", "admin"]),
]);
$router->post("/messages/send", "MessageController@send", [
    new AuthMiddleware(),
    new RoleMiddleware(["client", "student"]),
    new CsrfMiddleware(),
]);
$router->get("/messages/poll", "MessageController@poll", [
    new AuthMiddleware(),
    new RoleMiddleware(["client", "student"]),
]);
$router->get("/messages/unread-count", "MessageController@unreadCount", [
    new AuthMiddleware(),
]);

// Notification routes
$router->get("/notifications", "NotificationController@index", [
    new AuthMiddleware(),
]);
$router->get("/notifications/unread-count", "NotificationController@getUnreadCount", [
    new AuthMiddleware(),
]);
$router->post("/notifications/mark-as-read", "NotificationController@markAsRead", [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);
$router->post("/notifications/mark-all-as-read", "NotificationController@markAllAsRead", [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);

// Student Profile routes
$router->get("/student/profile/edit", "ProfileController@edit", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
]);
$router->post("/student/profile/update", "ProfileController@update", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);
$router->post("/student/profile/delete-file", "ProfileController@deletePortfolioFile", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);
$router->post("/student/profile/delete-picture", "ProfileController@deleteProfilePicture", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);

// Client Profile routes
$router->get("/client/profile/edit", "ClientProfileController@edit", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
]);
$router->post("/client/profile/update", "ClientProfileController@update", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
    new CsrfMiddleware(),
]);
$router->post("/client/profile/delete-picture", "ClientProfileController@deleteProfilePicture", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
    new CsrfMiddleware(),
]);

// Connect Stripe (student only)
$router->get("/student/profile/connect-stripe", "ProfileController@connectStripe", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
]);
$router->get("/student/profile/connect-stripe/return", "ProfileController@connectStripeReturn");

// Public student profile
$router->get("/student/profile", "ProfileController@show");

// Service Discovery routes
$router->get("/services/search", "DiscoveryController@search");
$router->get("/services/{id}", "DiscoveryController@show");

// File serving routes
$router->get("/storage/file", "FileController@serve");
$router->get("/files/download", "FileController@download");

// Webhook routes
$router->post("/webhooks/stripe", "WebhookController@stripe");

// Withdrawal routes
$router->get("/student/withdrawals", "WithdrawalController@index", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
]);
$router->post("/student/withdrawals/request", "WithdrawalController@request", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);
$router->get("/student/withdrawals/stripe-dashboard", "WithdrawalController@stripeDashboard", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
]);

// Review routes
$router->get("/reviews/create", "ReviewController@create", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
]);
$router->post("/reviews/store", "ReviewController@store", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
    new CsrfMiddleware(),
]);
$router->get("/reviews/{id}/edit", "ReviewController@edit", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
]);
$router->post("/reviews/{id}/update", "ReviewController@update", [
    new AuthMiddleware(),
    new RoleMiddleware("client"),
    new CsrfMiddleware(),
]);
$router->post("/reviews/{id}/reply", "ReviewController@reply", [
    new AuthMiddleware(),
    new RoleMiddleware("student"),
    new CsrfMiddleware(),
]);

// Admin review moderation routes
$router->get("/admin/reviews/moderation", "AdminController@reviewModeration", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);
$router->post("/admin/reviews/{id}/flag", "AdminController@flagReview", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);
$router->post("/admin/reviews/{id}/unflag", "AdminController@unflagReview", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);

// Settings routes
$router->get("/settings/account", "SettingsController@account", [
    new AuthMiddleware(),
]);
$router->post("/settings/account/update", "SettingsController@updateAccount", [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);
$router->post("/settings/password/update", "SettingsController@updatePassword", [
    new AuthMiddleware(),
    new CsrfMiddleware(),
]);

// Admin category management
$router->get("/admin/categories", "AdminController@categories", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);
$router->post("/admin/categories/create", "AdminController@createCategory", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);
$router->get("/admin/categories/{id}/edit", "AdminController@editCategory", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);
$router->post("/admin/categories/{id}/update", "AdminController@updateCategory", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);
$router->post("/admin/categories/{id}/delete", "AdminController@deleteCategory", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);

// Admin moderation routes
$router->get("/admin/moderation/messages", "ModerationController@messagesDashboard", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);
$router->get("/admin/moderation/violations/confirm", "ModerationController@showConfirmViolationForm", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);
$router->post("/admin/moderation/violations/confirm", "ModerationController@confirmViolation", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);
$router->post("/admin/moderation/violations/dismiss", "ModerationController@dismissFlag", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);
$router->get("/admin/users/{id}/violations", "ModerationController@viewUserViolations", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);

// Dispute routes
$router->get("/disputes/create", "DisputeController@showCreateForm", [
    new AuthMiddleware(),
    new RoleMiddleware(["client", "student"]),
]);
$router->post("/disputes/create", "DisputeController@create", [
    new AuthMiddleware(),
    new RoleMiddleware(["client", "student"]),
    new CsrfMiddleware(),
]);
$router->get("/admin/disputes", "DisputeController@index", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);
$router->get("/admin/disputes/{id}", "DisputeController@show", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
]);
$router->post("/admin/disputes/{id}/resolve", "DisputeController@resolve", [
    new AuthMiddleware(),
    new RoleMiddleware("admin"),
    new CsrfMiddleware(),
]);
