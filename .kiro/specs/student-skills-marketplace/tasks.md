# Implementation Plan

This plan breaks down the Student Skills Marketplace implementation into discrete, testable coding tasks. Each task builds incrementally on previous work and references specific requirements from the requirements document.

## Current Status

**Completed:**

- ✅ Project foundation (routing, middleware, layouts)
- ✅ Database schema (all migrations created)
- ✅ Authentication system (registration, login, email verification, password reset)
- ✅ Role-based access control (policies and middleware)
- ✅ Basic dashboard views for all roles
- ✅ Error page templates

**In Progress:**

- 🔄 Student profile management (needs implementation)
- 🔄 Service listing management (needs implementation)
- 🔄 Order system (needs full implementation)

**Next Priority Tasks:**

1. Student profile management (Task 5)
2. Service listing CRUD operations (Task 6)
3. Service discovery and search (Task 7)
4. Order placement with Stripe integration (Task 8)

## Task List

- [x] 1. Project setup and foundation

- [x] 1.1 Create project directory structure and core configuration files

  - Set up directory structure: `/public`, `/src`, `/storage`, `/logs`, `/config`, `/views`, `/tests`
  - Create `.env.example` with all required configuration keys
  - Write `config/database.php` for PDO connection with error handling
  - Write `config/app.php` for application settings
  - Create `public/index.php` as front controller with error handling
  - _Requirements: 28.1-28.5_

- [x] 1.2 Implement routing system and middleware pipeline

  - Write `src/Router.php` with route registration and matching
  - Implement middleware interface and pipeline execution
  - Create CSRF middleware with token generation and validation
  - Create authentication middleware with session checking
  - Create rate limiting middleware with database-backed counters
  - _Requirements: 21.1-21.5, 26.1-26.5_

- [x] 1.3 Set up Tailwind CSS and create base layout templates

  - Add Tailwind CSS CDN link to base layout
  - Create `views/layouts/base.php` with HTML structure, meta tags, and Tailwind
  - Create `views/layouts/dashboard.php` extending base with navigation and sidebar
  - Create `views/layouts/admin.php` extending base with admin navigation
  - Write `views/partials/navigation.php` with responsive menu
  - Write `views/partials/alert.php` for flash messages
  - _Requirements: 30.1-30.5_

- [ ]\* 1.4 Write unit tests for router and middleware components

  - Test route matching with various patterns
  - Test middleware pipeline execution order
  - Test CSRF token validation logic
  - Test rate limiting counter increments
  - _Requirements: 21.1-21.5, 26.1-26.5_

- [x] 2. Database schema and migrations

- [x] 2.1 Create database migration system

  - Write `cli/migrate.php` script to run SQL migrations sequentially
  - Create `migrations` table to track applied migrations
  - Implement rollback functionality for development
  - _Requirements: 28.1-28.5_

- [x] 2.2 Write migrations for user and profile tables

  - Create `001_create_users_table.sql` with all columns and indexes
  - Create `002_create_student_profiles_table.sql` with foreign keys
  - Create `003_create_categories_table.sql`
  - _Requirements: 1.1-1.5, 3.1-3.5_

- [x] 2.3 Write migrations for service and order tables

  - Create `004_create_services_table.sql` with fulltext index
  - Create `005_create_orders_table.sql` with status enum and indexes
  - Create `006_create_payments_table.sql` with Stripe fields
  - _Requirements: 4.1-4.5, 6.1-6.5, 9.1-9.5_

- [x] 2.4 Write migrations for messaging, reviews, and admin tables

  - Create `007_create_messages_table.sql` with order relationship
  - Create `008_create_reviews_table.sql` with unique order constraint
  - Create `009_create_disputes_table.sql`
  - Create `010_create_notifications_table.sql`
  - Create `011_create_audit_logs_table.sql`
  - Create `012_create_webhook_events_table.sql`
  - Create `013_create_platform_settings_table.sql`
  - _Requirements: 11.1-11.5, 12.1-12.5, 18.1-18.5, 27.1-27.5_

- [x] 2.5 Create database seeder for initial data

  - Write `cli/seed.php` to populate categories and platform settings
  - Seed default categories (Web Development, Graphic Design, Writing, etc.)
  - Seed platform settings (commission_rate: 15%, max_revisions: 3)
  - Create test users (admin, sample student, sample client) for development
  - _Requirements: 19.1-19.5_

- [x] 3. Authentication system

- [x] 3.1 Implement user registration with email verification

  - Create `src/Controllers/AuthController.php` with register method
  - Write `src/Services/AuthService.php` with user creation and token generation
  - Create `src/Repositories/UserRepository.php` with prepared statements
  - Write `views/auth/register.php` form with role selection and CSRF token
  - Implement password hashing with bcrypt cost 12
  - Generate 32-byte verification token and send email
  - _Requirements: 1.1-1.5_

- [x] 3.2 Implement email verification flow

  - Add `verifyEmail` method to AuthController
  - Check token validity and expiration (24 hours)
  - Update user status to 'active' and set email_verified_at
  - Create `views/auth/verify-email.php` success page
  - _Requirements: 1.2_

- [x] 3.3 Implement login with session management

  - Add `login` method to AuthController
  - Verify email verification status before allowing login
  - Use `password_verify` for credential checking
  - Create secure session with httponly, secure, samesite flags
  - Regenerate session ID after successful login
  - Create `views/auth/login.php` form
  - _Requirements: 1.3-1.4_

- [x] 3.4 Implement password reset flow

  - Add `requestReset` and `resetPassword` methods to AuthController
  - Generate 32-byte reset token with 1-hour expiration
  - Send reset email with token link
  - Validate token and update password
  - Create `views/auth/request-reset.php` and `views/auth/reset-password.php` forms
  - _Requirements: 1.5_

- [ ]\* 3.5 Write feature tests for authentication flows

  - Test registration creates unverified user
  - Test email verification activates account
  - Test login rejects unverified accounts
  - Test password reset token expiration
  - _Requirements: 1.1-1.5_

- [x] 4. Role-based access control (RBAC)

- [x] 4.1 Create policy classes for authorization

  - Write `src/Policies/Policy.php` base interface
  - Write `src/Policies/OrderPolicy.php` with canView, canAccept, canDeliver, canComplete, canCancel methods
  - Write `src/Policies/ServicePolicy.php` with canEdit, canDelete, canActivate methods
  - Write `src/Policies/MessagePolicy.php` with canSend, canView methods
  - Implement role checks and ownership validation in each policy
  - _Requirements: 2.1-2.5_

- [x] 4.2 Create authorization middleware and helpers

  - Write `src/Middleware/RoleMiddleware.php` to check user role
  - Write `src/Auth.php` helper with authorize() method that calls policies
  - Integrate policy checks into controllers before actions
  - Return 403 Forbidden for unauthorized access
  - _Requirements: 2.1-2.5_

- [x]\* 4.3 Write unit tests for policy authorization rules

  - Test OrderPolicy canView for client, student, and admin
  - Test OrderPolicy canDeliver only for student in correct state
  - Test ServicePolicy canEdit only for owner
  - Test role-based access denials
  - _Requirements: 2.1-2.5_

- [x] 5. Student profile management

- [x] 5.1 Implement student profile creation and editing

  - Create `src/Controllers/ProfileController.php` with edit and update methods
  - Write `src/Services/ProfileService.php` for profile operations
  - Create `src/Repositories/StudentProfileRepository.php`
  - Write `views/student/profile.php` form with bio, skills (JSON array), portfolio upload
  - Implement file upload validation (10MB limit, allowed types)
  - Store portfolio files in `/storage/uploads/profiles/{user_id}/`
  - _Requirements: 3.1-3.5_

- [x] 5.2 Implement Stripe Connect onboarding for students

  - Add `connectStripe` method to ProfileController
  - Use Stripe API to create Connect account and onboarding link
  - Store `stripe_connect_account_id` in student_profiles
  - Handle return from Stripe and update `stripe_onboarding_complete` flag
  - _Requirements: 9.2_

- [x] 5.3 Create public student profile view

  - Add `show` method to ProfileController
  - Display bio, skills, portfolio samples, average rating, recent reviews
  - Create `views/student/show.php` with Tailwind styling
  - _Requirements: 3.3_

- [ ]\* 5.4 Write feature tests for profile management

  - Test profile creation with valid data
  - Test portfolio file upload validation
  - Test profile visibility to public users
  - _Requirements: 3.1-3.5_

- [-] 6. Service listing management

- [x] 6.1 Implement service listing creation

  - Create `src/Controllers/ServiceController.php` with create and store methods
  - Write `src/Services/ServiceService.php` for business logic
  - Create `src/Repositories/ServiceRepository.php`
  - Write `src/Validators/ServiceValidator.php` for input validation
  - Create `views/student/services/create.php` form with category dropdown, tags, price, delivery days
  - Validate price > 0, delivery_days > 0, title and description required
  - Store sample files in `/storage/uploads/services/{service_id}/`
  - Create service in 'inactive' status
  - _Requirements: 4.1_

- [x] 6.2 Implement service listing editing and deletion

  - Add `edit`, `update`, and `delete` methods to ServiceController
  - Check ownership via ServicePolicy before allowing edits
  - Prevent deletion if active orders exist
  - Create `views/student/services/edit.php` form
  - _Requirements: 4.4-4.5_

- [x] 6.3 Implement service activation and pausing

  - Add `activate` and `pause` methods to ServiceController
  - Update service status to 'active' or 'paused'
  - Only active services appear in discovery
  - _Requirements: 4.2-4.3_

- [x] 6.4 Create student service listing dashboard

  - Add `index` method to ServiceController for student view
  - Display all services owned by student with status badges
  - Show action buttons (Edit, Activate, Pause, Delete) based on status
  - Create `views/student/services/index.php` with service cards
  - _Requirements: 4.1-4.5_

- [ ]\* 6.5 Write feature tests for service CRUD operations

  - Test service creation with valid data
  - Test service deletion prevention with active orders
  - Test activation changes status to active
  - Test file upload validation
  - _Requirements: 4.1-4.5_

- [x] 7. Service discovery and search

- [x] 7.1 Implement search functionality with filters

  - Create `src/Controllers/DiscoveryController.php` with search method
  - Write `src/Services/SearchService.php` with query building
  - Implement fulltext search on title and description
  - Add filters for category, price range (min/max), delivery time, rating
  - Add sorting options (relevance, price, rating, delivery time)
  - Implement pagination (20 results per page)
  - _Requirements: 5.1-5.6_

- [x] 7.2 Create search results page with filters UI

  - Create `views/client/services/index.php` with search form
  - Display filter sidebar with category checkboxes, price inputs, rating select
  - Show service cards in grid layout with Tailwind
  - Add pagination component at bottom
  - Use Alpine.js for filter interactions
  - _Requirements: 5.1-5.6_

- [x] 7.3 Optimize search queries with database indexes

  - Verify fulltext index on services (title, description)
  - Verify composite index on (category_id, status)
  - Verify index on price for range queries
  - Test query performance with EXPLAIN
  - _Requirements: 28.1_

- [x] 7.4 Create service detail page for clients

  - Add `show` method to DiscoveryController
  - Display full service details, student profile summary, reviews
  - Show "Order Now" button for clients
  - Create `views/client/services/show.php`
  - _Requirements: 5.1-5.6_

- [ ]\* 7.5 Write feature tests for search and filtering

  - Test search returns matching services
  - Test category filter excludes other categories
  - Test price range filter
  - Test pagination correctness
  - _Requirements: 5.1-5.6_

- [x] 8. Order placement and payment integration

- [x] 8.1 Implement Stripe checkout session creation

  - Install stripe-php SDK via Composer
  - Create `src/Services/PaymentService.php` with createCheckoutSession method
  - Generate Stripe checkout session with order metadata
  - Set success_url and cancel_url
  - Store payment record with status 'pending'
  - _Requirements: 6.1-6.3, 9.1_

- [x] 8.2 Implement order creation flow

  - Create `src/Controllers/OrderController.php` with create and store methods
  - Write `src/Services/OrderService.php` with createOrder method
  - Create `src/Repositories/OrderRepository.php`
  - Write `src/Validators/OrderValidator.php`
  - Create `views/client/orders/create.php` form with requirements textarea and file upload
  - Validate requirements text (min 10 characters) and file size (25MB total)
  - Store requirement files in `/storage/uploads/orders/{order_id}/requirements/`
  - Calculate deadline: current_time + service.delivery_days
  - Create order in 'pending' status
  - Redirect to Stripe checkout
  - _Requirements: 6.1-6.5_

- [x] 8.3 Implement Stripe webhook endpoint

  - Create `src/Controllers/WebhookController.php` with stripe method
  - Verify webhook signature using Stripe SDK
  - Check webhook_events table for duplicate event_id (idempotency)
  - Insert webhook_event record
  - Handle checkout.session.completed: update payment and order to 'pending'
  - Handle charge.refunded: update payment status to 'refunded'
  - Handle transfer.created: update payment with transfer_id
  - Return 200 OK for all valid webhooks
  - _Requirements: 10.1-10.5_

- [ ]\* 8.4 Write webhook tests with fixtures

  - Create Stripe webhook payload fixtures
  - Test signature verification rejects invalid signatures
  - Test idempotency prevents duplicate processing
  - Test checkout.session.completed updates order status
  - Test charge.refunded updates payment status
  - _Requirements: 10.1-10.5_

- [x] 9. Order lifecycle state machine

- [x] 9.1 Implement order acceptance by student

  - Add `accept` method to OrderController
  - Check order status is 'pending' and user is the student
  - Update order status to 'in_progress' using database transaction
  - Send notification to client
  - _Requirements: 7.1_

- [x] 9.2 Implement order delivery by student

  - Add `deliver` method to OrderController
  - Check order status is 'in_progress' or 'revision_requested'
  - Validate delivery message and files are provided
  - Store delivery files in `/storage/uploads/orders/{order_id}/delivery/`
  - Update order status to 'delivered' with delivery_message and delivery_files
  - Send notification to client
  - Create `views/student/orders/deliver.php` form
  - _Requirements: 7.2_

- [x] 9.3 Implement order completion by client

  - Add `complete` method to OrderController
  - Check order status is 'delivered' and user is the client
  - Begin database transaction
  - Update order status to 'completed' with completed_at timestamp
  - Calculate student earnings: order_amount - (order_amount × commission_rate)
  - Call WithdrawalService.addToBalance to credit student's available_balance
  - Update student_profile total_orders counter
  - Commit transaction
  - Send notifications to both parties
  - _Requirements: 7.3, 9.2_
  - _Note: Uses balance system instead of direct Stripe transfer for easier showcase_

- [x] 9.4 Implement revision request by client

  - Add `requestRevision` method to OrderController
  - Check order status is 'delivered' and revision_count < max_revisions
  - Update order status to 'revision_requested' and increment revision_count
  - Send notification to student with revision reason
  - Create `views/client/orders/request-revision.php` form
  - _Requirements: 7.4-7.6_

- [x] 9.5 Implement order cancellation

  - Add `cancel` method to OrderController
  - Check order status is 'pending' (only allow cancellation before work starts)
  - Begin database transaction
  - Update order status to 'cancelled' with cancellation_reason
  - Call PaymentService.refundPayment for full refund
  - Commit transaction
  - Send notifications to both parties
  - _Requirements: 8.1-8.5_

- [x] 9.6 Create order detail view for both parties

  - Add `show` method to OrderController
  - Check authorization via OrderPolicy.canView
  - Display order details, requirements, delivery, messages, status timeline
  - Show action buttons based on status and user role (Accept, Deliver, Complete, Request Revision, Cancel)
  - Create `views/orders/show.php` with status badge and action buttons
  - _Requirements: 7.1-7.6_

- [ ]\* 9.7 Write feature tests for order lifecycle

  - Test complete order flow: pending → in_progress → delivered → completed
  - Test revision loop: delivered → revision_requested → delivered
  - Test cancellation from pending state
  - Test state transition guards prevent invalid transitions
  - _Requirements: 7.1-7.6_

- [x] 10. Payment processing and escrow

- [x] 10.1 Implement payment release to student (Balance System)

  - Add `releasePayment` method to PaymentService
  - Get payment record and verify status is 'succeeded'
  - Calculate commission: order_amount × commission_rate (default 15%)
  - Calculate student amount: order_amount - commission
  - Call WithdrawalService.addToBalance to credit student's available_balance
  - Update payment record with release timestamp
  - Insert audit_log entry
  - _Requirements: 9.2_
  - _Note: Funds added to student balance; actual Stripe transfer happens on withdrawal_

- [x] 10.2 Implement payment refund to client

  - Add `refundPayment` method to PaymentService
  - Get payment record and stripe_payment_intent_id
  - Create Stripe refund (full or partial based on amount parameter)
  - Update payment status to 'refunded' or 'partially_refunded'
  - Update refund_amount field
  - Insert audit_log entry
  - Handle Stripe API errors with retry logic
  - _Requirements: 8.1-8.5, 9.5_

- [x] 10.3 Implement idempotency for payment operations

  - Use order_id + operation type as idempotency key
  - Check if operation already completed before calling Stripe
  - Store idempotency keys in payment metadata
  - _Requirements: 9.5, 10.2_

- [x] 10.4 Create payment history view for admin

  - Add `payments` method to AdminController
  - Display all payments with order details, amounts, status, Stripe IDs
  - Add filters for status and date range
  - Create `views/admin/payments/index.php`
  - _Requirements: 9.1-9.5_

- [ ]\* 10.5 Write unit tests for payment operations

  - Test commission calculation
  - Test transfer creation with correct amounts
  - Test refund processing
  - Test idempotency prevents duplicate operations
  - _Requirements: 9.1-9.5_

- [x] 11. Messaging system

- [x] 11.1 Implement message sending with attachments

  - Create `src/Controllers/MessageController.php` with send method
  - Write `src/Services/MessageService.php` with sendMessage method
  - Create `src/Repositories/MessageRepository.php`
  - Validate user is client or student of the order
  - Store message attachments in `/storage/uploads/messages/{order_id}/`
  - Check message content for off-platform patterns (email, phone, payment keywords)
  - Set is_flagged=true if suspicious content detected
  - Send notification to recipient
  - _Requirements: 11.1-11.3_

- [x] 11.2 Create message thread view

  - Add `thread` method to MessageController
  - Get all messages for order ordered by created_at
  - Mark messages as read for current user (read_by_client or read_by_student)
  - Display messages in chat-style layout
  - Create `views/messages/thread.php` with message bubbles and file attachments
  - Add message form at bottom with file upload
  - _Requirements: 11.1-11.5_

- [x] 11.3 Implement unread message counter

  - Add `getUnreadCount` method to MessageService
  - Count messages where user is recipient and not marked as read
  - Display counter in navigation header
  - Update counter via AJAX after marking messages as read
  - _Requirements: 11.4_

- [x] 11.4 Implement message polling for real-time updates

  - Add `poll` method to MessageController returning JSON
  - Accept `after` parameter with last message ID
  - Return new messages since that ID
  - Write JavaScript polling function (10-second interval)
  - Append new messages to thread without page reload
  - _Requirements: 11.1_

- [ ]\* 11.5 Write feature tests for messaging

  - Test message sending creates record
  - Test message with suspicious content is flagged
  - Test unread counter updates correctly
  - Test marking messages as read
  - _Requirements: 11.1-11.5_

- [-] 12. Review and rating system

- [x] 12.1 Implement review submission by client

  - Create `src/Controllers/ReviewController.php` with create and store methods
  - Write `src/Services/ReviewService.php` with createReview method
  - Create `src/Repositories/ReviewRepository.php`
  - Check order is completed and belongs to client
  - Check no existing review for this order (unique constraint)
  - Validate rating is 1-5 stars
  - Set can_edit_until to 24 hours from now
  - Insert review record
  - Update student average_rating and total_reviews in transaction
  - Send notification to student
  - Create `views/reviews/create.php` form with star rating and comment
  - _Requirements: 12.1-12.5_

- [x] 12.2 Implement review editing within 24-hour window

  - Add `edit` and `update` methods to ReviewController
  - Check can_edit_until > current time
  - Update review rating and comment
  - Recalculate student average_rating
  - _Requirements: 12.3_

- [x] 12.3 Implement student reply to reviews

  - Add `reply` method to ReviewController
  - Check user is the student being reviewed
  - Update review with student_reply and student_replied_at
  - Send notification to client
  - _Requirements: 13.1-13.5_

- [x] 12.4 Display reviews on student profile

  - Update student profile view to show recent reviews
  - Display rating stars, comment, client name, date
  - Show student reply if exists
  - Order by created_at descending
  - Implement pagination for reviews
  - _Requirements: 12.5, 13.4_

- [x] 12.5 Calculate and update student average rating

  - Add `calculateAverageRating` method to ReviewService
  - Query all reviews for student and calculate AVG(rating)
  - Update student_profile.average_rating and total_reviews
  - Call this method after review create, update, or delete
  - _Requirements: 3.5, 12.1_

- [ ]\* 12.6 Write feature tests for review system

  - Test review submission updates student rating
  - Test duplicate review prevention
  - Test edit window expiration
  - Test student reply functionality
  - _Requirements: 12.1-12.5, 13.1-13.5_

- [x] 13. Notification system

- [x] 13.1 Set up PHPMailer with Gmail SMTP

  - Install PHPMailer via Composer
  - Create `src/Services/MailService.php` with send method
  - Configure SMTP settings from .env (host, port, username, app password)
  - Set from address and name
  - Implement error handling and logging for failed sends
  - _Requirements: 14.7_

- [x] 13.2 Create email templates for notifications

  - Create `views/emails/order-placed.php` template
  - Create `views/emails/order-delivered.php` template
  - Create `views/emails/revision-requested.php` template
  - Create `views/emails/order-completed.php` template
  - Create `views/emails/review-submitted.php` template
  - Create `views/emails/message-received.php` template
  - Create `views/emails/verification.php` template
  - Create `views/emails/password-reset.php` template
  - Use simple HTML with inline styles for email client compatibility
  - _Requirements: 14.1-14.6_

- [x] 13.3 Implement email notification sending

  - Add `sendEmail` method to NotificationService
  - Accept recipient, subject, template name, and data array
  - Render template with data
  - Call MailService.send
  - Log email send attempts and failures
  - Implement retry logic (2 attempts with 5s delay)
  - _Requirements: 14.1-14.7_

- [x] 13.4 Implement in-app notification creation

  - Add `createInAppNotification` method to NotificationService
  - Insert notification record with user_id, type, title, message, link
  - Set is_read to false
  - _Requirements: 15.1_

- [x] 13.5 Create notification center view

  - Add `index` method to NotificationController
  - Get all notifications for user ordered by created_at desc
  - Display notifications with icons based on type
  - Show unread badge on unread notifications
  - Create `views/notifications/index.php` with notification list
  - _Requirements: 15.2_

- [x] 13.6 Implement mark as read functionality

  - Add `markAsRead` method to NotificationController
  - Update notification is_read to true
  - Return JSON response for AJAX calls
  - Update unread counter in navigation
  - _Requirements: 15.3-15.4_

- [x] 13.7 Integrate notifications into order lifecycle events

  - Call NotificationService after order placement (email + in-app to student)
  - Call NotificationService after order delivery (email + in-app to client)
  - Call NotificationService after revision request (email + in-app to student)
  - Call NotificationService after order completion (email + in-app to both)
  - Call NotificationService after review submission (email + in-app to student)
  - Call NotificationService after message received (email + in-app to recipient)
  - _Requirements: 14.1-14.6, 15.1_

- [ ]\* 13.8 Write feature tests for notification delivery

  - Test email is sent after order placement
  - Test in-app notification is created
  - Test notification marked as read
  - Test unread counter accuracy
  - _Requirements: 14.1-14.6, 15.1-15.5_

- [x] 14. Admin user management

- [x] 14.1 Create admin dashboard with analytics

  - Create `src/Controllers/AdminController.php` with dashboard method
  - Calculate GMV (sum of completed order prices)
  - Calculate total orders and completion rate
  - Calculate on-time delivery rate (completed before deadline)
  - Calculate dispute rate
  - Support date range filtering (last 7, 30, 90 days, or custom)
  - Create `views/admin/dashboard.php`(use the admin layout ) with metric cards and charts
  - _Requirements: 20.1-20.5_

- [x] 14.2 Implement user management interface

  - Add `users` method to AdminController
  - Display paginated list of all users with role, status, registration date
  - Add search by email
  - Add filters for role and status
  - Create `views/admin/users/index.php` with user table
  - _Requirements: 16.1_

- [x] 14.3 Implement user suspension and reactivation

  - Add `suspend` and `reactivate` methods to AdminController
  - Update user status to 'suspended' or 'active'
  - Insert audit_log entry with admin_id and action
  - Suspended users cannot login (check in authentication)
  - _Requirements: 16.2-16.3, 16.5_

- [x] 14.4 Create user detail view for admin

  - Add `showUser` method to AdminController
  - Display complete user details, order history, review history
  - Show action buttons (Suspend, Reactivate)
  - Create `views/admin/users/show.php`
  - _Requirements: 16.4_

- [ ]\* 14.5 Write feature tests for admin user management

  - Test user suspension prevents login
  - Test reactivation restores access
  - Test audit log records admin actions
  - _Requirements: 16.1-16.5_

- [-] 15. Admin service moderation

- [ ] 15.1 Create service moderation interface

  - Add `services` method to AdminController
  - Display all services with status, student, creation date
  - Add filters for status and category
  - Show flagged services at top
  - Create `views/admin/services/index.php` with service table
  - _Requirements: 17.1_

- [ ] 15.2 Implement service deactivation and deletion

  - Add `deactivateService` and `deleteService` methods to AdminController
  - Update service status to 'inactive' for deactivation
  - Delete service record and files for deletion
  - Send notification to student with reason
  - Insert audit_log entry
  - _Requirements: 17.2-17.3, 17.5_

- [ ] 15.3 Create service detail view for admin

  - Add `showService` method to AdminController
  - Display complete service details and associated orders
  - Show action buttons (Deactivate, Delete)
  - Create `views/admin/services/show.php`
  - _Requirements: 17.4_

- [ ]\* 15.4 Write feature tests for service moderation

  - Test service deactivation hides from discovery
  - Test service deletion removes record
  - Test notification sent to student
  - _Requirements: 17.1-17.5_

- [ ] 16. Admin dispute resolution
- [ ] 16.1 Implement dispute creation by users

  - Create `src/Controllers/DisputeController.php` with create and store methods
  - Write `src/Services/DisputeService.php`
  - Create `src/Repositories/DisputeRepository.php`
  - Check order status allows dispute (in_progress, delivered, revision_requested)
  - Insert dispute record with status 'open'
  - Send notification to admin
  - Create `views/disputes/create.php` form
  - _Requirements: 18.1_

- [ ] 16.2 Create dispute management interface for admin

  - Add `disputes` method to AdminController
  - Display all disputes with order details, status, opened date
  - Filter by status (open, resolved)
  - Create `views/admin/disputes/index.php` with dispute table
  - _Requirements: 18.1_

- [ ] 16.3 Create dispute detail view with resolution actions

  - Add `showDispute` method to AdminController
  - Display order details, messages, delivery files, both party statements
  - Show resolution form with options: release to student, refund to client, partial refund
  - Create `views/admin/disputes/show.php`
  - _Requirements: 18.1_

- [ ] 16.4 Implement dispute resolution actions

  - Add `resolve` method to AdminController
  - For "release to student": call OrderService.completeOrder
  - For "refund to client": call OrderService.cancelOrder with full refund
  - For "partial refund": call PaymentService.refundPayment with amount, then PaymentService.releasePayment with remainder
  - Update dispute status to 'resolved' with resolution type and notes
  - Send notifications to both parties with resolution decision
  - Insert audit_log entry
  - _Requirements: 18.2-18.5_

- [ ]\* 16.5 Write feature tests for dispute resolution

  - Test dispute creation
  - Test release to student completes order
  - Test refund to client cancels order
  - Test partial refund splits payment correctly
  - _Requirements: 18.1-18.5_

- [ ] 17. Admin platform settings
- [ ] 17.1 Create platform settings interface

  - Add `settings` method to AdminController
  - Display current settings: commission_rate, max_revisions
  - Create form to update settings
  - Create `views/admin/settings.php`
  - _Requirements: 19.1-19.2_

- [ ] 17.2 Implement settings update

  - Add `updateSettings` method to AdminController
  - Validate commission_rate (0-100) and max_revisions (> 0)
  - Update platform_settings records
  - Insert audit_log entry with old and new values
  - _Requirements: 19.1-19.2, 19.5_

- [ ] 17.3 Implement category management

  - Add `categories` method to AdminController
  - Display all categories with service count
  - Add create and delete category actions
  - Prevent deletion if category has services
  - Create `views/admin/categories.php`
  - _Requirements: 19.3-19.4_

- [ ]\* 17.4 Write feature tests for settings management

  - Test commission rate update applies to new orders
  - Test max revisions update applies to new orders
  - Test category deletion prevention with existing services
  - _Requirements: 19.1-19.5_

- [ ] 18. File storage and access control
- [ ] 18.1 Implement file upload handler with validation

  - Create `src/Services/FileService.php` with upload method
  - Validate file extension against allowlist (jpg, jpeg, png, gif, pdf, doc, docx, zip)
  - Validate MIME type matches extension
  - Validate file size against limits (10MB for images, 25MB for documents)
  - For images, verify with getimagesize()
  - Generate random filename with UUID
  - Store in appropriate directory based on context
  - Return file metadata (path, original_name, size, mime_type)
  - _Requirements: 24.1-24.5_

- [ ] 18.2 Implement signed URL generation for downloads

  - Add `generateSignedUrl` method to FileService
  - Create HMAC signature with file_id, expires timestamp, and secret key
  - Return URL: `/files/download/{file_id}?signature={sig}&expires={exp}`
  - Set expiration to 5 minutes from generation
  - _Requirements: 25.2_

- [ ] 18.3 Implement file download controller with authorization

  - Create `src/Controllers/FileController.php` with download method
  - Verify signature and expiration
  - Get file metadata from database
  - Check user authorization based on file context (OrderPolicy, ServicePolicy)
  - Set appropriate headers (Content-Type, Content-Disposition, X-Content-Type-Options)
  - Stream file contents
  - Return 403 for unauthorized or invalid signature
  - _Requirements: 25.1-25.5_

- [ ] 18.4 Update all file upload forms to use FileService

  - Update profile portfolio upload to use FileService
  - Update service sample upload to use FileService
  - Update order requirements upload to use FileService
  - Update order delivery upload to use FileService
  - Update message attachments upload to use FileService
  - _Requirements: 24.1-24.5_

- [ ]\* 18.5 Write feature tests for file upload and download

  - Test file extension validation rejects disallowed types
  - Test file size validation rejects oversized files
  - Test signed URL expiration
  - Test authorization prevents unauthorized downloads
  - _Requirements: 24.1-24.5, 25.1-25.5_

- [ ] 19. Security hardening
- [x] 19.1 Implement output escaping helpers

  - Create `src/Helpers.php` with e() function for HTML escaping
  - Use htmlspecialchars with ENT_QUOTES and UTF-8
  - Create js() function for JavaScript context escaping
  - Create url() function for URL encoding
  - Update all templates to use e() for user-generated content
  - _Requirements: 22.1-22.5_

- [ ] 19.2 Implement SQL injection prevention

  - Audit all database queries to use prepared statements
  - Ensure no string concatenation of user input in SQL
  - Use parameter binding for all dynamic values
  - Validate table/column names if dynamic (use allowlist)
  - _Requirements: 23.1-23.5_

- [ ] 19.3 Implement rate limiting for critical endpoints

  - Create `src/Services/RateLimitService.php`
  - Store attempt counts in database with key format: `rate_limit:{action}:{identifier}:{window}`
  - Implement limits: login (5/15min), password reset (10/hour), search (100/min)
  - Return 429 Too Many Requests with Retry-After header
  - _Requirements: 26.1-26.5_

- [ ] 19.4 Implement audit logging for security events

  - Create `src/Services/AuditService.php` with log method
  - Log authentication events (login, logout, failed login)
  - Log authorization failures (403 responses)
  - Log order state changes
  - Log payment operations
  - Log admin actions
  - Store in audit_logs table with user_id, action, resource, IP, user agent
  - _Requirements: 27.1-27.5_

- [ ] 19.5 Add security headers to responses

  - Set X-Frame-Options: SAMEORIGIN
  - Set X-Content-Type-Options: nosniff
  - Set X-XSS-Protection: 1; mode=block
  - Set Referrer-Policy: strict-origin-when-cross-origin
  - Set Content-Security-Policy (basic policy)
  - Add headers in base controller or middleware
  - _Requirements: 21.1-21.5_

- [ ]\* 19.6 Write security tests

  - Test CSRF token validation rejects invalid tokens
  - Test XSS prevention escapes script tags
  - Test SQL injection prevention with malicious input
  - Test rate limiting blocks after threshold
  - Test file upload rejects executable files
  - _Requirements: 21.1-21.5, 22.1-22.5, 23.1-23.5, 24.1-24.5, 26.1-26.5_

- [ ] 20. Dashboard views for students and clients
- [ ] 20.1 Create student dashboard

  - Add `dashboard` method to student-specific controller
  - Display active orders with status and deadlines
  - Show pending orders requiring acceptance
  - Display recent messages and notifications
  - Show earnings summary (total, pending, completed)
  - Create `views/student/dashboard.php` with cards and lists
  - _Requirements: 7.1-7.6_

- [ ] 20.2 Create client dashboard

  - Add `dashboard` method to client-specific controller
  - Display active orders with status and deadlines
  - Show orders requiring action (delivered, needs review)
  - Display recent messages and notifications
  - Show spending summary
  - Create `views/client/dashboard.php` with cards and lists
  - _Requirements: 7.1-7.6_

- [ ] 20.3 Create order list views for both roles

  - Add `orders` method to OrderController
  - Filter orders by user role (client_id or student_id)
  - Add status filter tabs (all, pending, in_progress, delivered, completed, cancelled)
  - Display order cards with service title, other party, status, deadline
  - Create `views/student/orders/index.php` and `views/client/orders/index.php`
  - _Requirements: 7.1-7.6_

- [ ] 20.4 Implement order deadline tracking and late flags

  - Add `checkDeadlines` method to OrderService
  - Query orders where status = 'in_progress' and deadline < now
  - Add is_late flag to order display
  - Show late badge in red on order cards
  - _Requirements: 6.5_

- [ ]\* 20.5 Write feature tests for dashboard views

  - Test student dashboard shows their orders
  - Test client dashboard shows their orders
  - Test order filtering by status
  - Test late flag appears for overdue orders
  - _Requirements: 7.1-7.6_

- [ ] 21. Landing page and public views
- [ ] 21.1 Create landing page

  - Add `home` method to HomeController
  - Display hero section with value proposition
  - Show featured services or categories
  - Add call-to-action buttons (Browse Services, Sign Up)
  - Create `views/home.php` with Tailwind styling
  - _Requirements: 5.1-5.6_

- [ ] 21.2 Create public service browsing

  - Allow unauthenticated users to view service listings
  - Allow unauthenticated users to view service details
  - Require authentication for "Order Now" action
  - _Requirements: 5.1-5.6_

- [ ] 21.3 Create public student profiles

  - Allow unauthenticated users to view student profiles
  - Display bio, skills, portfolio, ratings, reviews
  - Show list of active services
  - _Requirements: 3.3_

- [ ] 21.4 Add navigation and footer

  - Update navigation partial with logo, search, auth links
  - Create footer partial with links (About, Terms, Privacy, Contact)
  - Add responsive mobile menu with Alpine.js
  - _Requirements: 30.1-30.5_

- [ ] 22. Error handling and logging
- [x] 22.1 Create error page templates

  - Create `views/errors/403.php` for forbidden access
  - Create `views/errors/404.php` for not found
  - Create `views/errors/429.php` for rate limit exceeded
  - Create `views/errors/500.php` for server errors
  - Use consistent styling with helpful messages
  - _Requirements: 21.1-21.5_

- [ ] 22.2 Implement global exception handler

  - Set exception handler in bootstrap
  - Log exception with stack trace
  - Show appropriate error page based on exception type
  - Never expose sensitive information to users
  - Return correct HTTP status codes
  - _Requirements: 27.1-27.5_

- [ ] 22.3 Implement structured logging

  - Create `src/Services/LogService.php` with log levels
  - Write to separate log files: app.log, security.log, audit.log, payment.log, error.log
  - Use format: `[timestamp] [level] [context] message {json_data}`
  - Implement log rotation (daily, keep 30 days)
  - _Requirements: 27.1-27.5_

- [ ] 22.4 Add transaction management to critical operations

  - Wrap order state transitions in database transactions
  - Wrap payment operations in transactions
  - Wrap review submission and rating update in transactions
  - Implement rollback on errors
  - Use READ COMMITTED isolation level
  - _Requirements: 29.1-29.5_

- [ ]\* 22.5 Write tests for error handling

  - Test 404 page shown for invalid routes
  - Test 403 page shown for unauthorized access
  - Test exception handler logs errors
  - Test transaction rollback on failure
  - _Requirements: 27.1-27.5, 29.1-29.5_

- [ ] 23. Health check and deployment preparation
- [ ] 23.1 Create health check endpoint

  - Add `health` method to HealthController
  - Check database connection
  - Verify Stripe API key validity
  - Test email configuration
  - Verify storage directory writable
  - Return JSON with status and individual checks
  - Return 200 if healthy, 503 if any check fails
  - _Requirements: 28.1-28.5_

- [ ] 23.2 Create CLI command for first admin user

  - Write `cli/create-admin.php` script
  - Prompt for email and password
  - Create user with role 'admin' and status 'active'
  - Set email_verified_at to now
  - Output success message with credentials
  - _Requirements: 16.1-16.5_

- [ ] 23.3 Create deployment documentation

  - Document server requirements (PHP, MySQL, extensions)
  - Document environment configuration (.env setup)
  - Document database migration steps
  - Document Stripe setup (API keys, webhook endpoint)
  - Document Gmail SMTP setup (App Password)
  - Document file permissions for storage and logs
  - Create `DEPLOYMENT.md` file
  - _Requirements: All_

- [ ] 23.4 Create seed data for development

  - Enhance seed script with realistic test data
  - Create 5 sample students with profiles and services
  - Create 3 sample clients
  - Create sample orders in various states
  - Create sample messages and reviews
  - _Requirements: All_

- [ ]\* 23.5 Run full integration test suite
  - Execute all feature tests
  - Verify test coverage for critical paths
  - Fix any failing tests
  - Document test execution in README
  - _Requirements: All_

## Notes

- All tasks marked with `*` are optional testing tasks that can be skipped for faster MVP delivery
- Each task should be completed and tested before moving to the next
- All code must follow security best practices (prepared statements, output escaping, CSRF protection)
- All payment operations must be idempotent and audited
- All state transitions must be wrapped in database transactions
- All user-facing errors must be friendly and not expose technical details

## Implementation Progress Summary

**Foundation Complete (Tasks 1-4):**

- ✅ Project structure, routing, middleware pipeline
- ✅ Database migrations for all tables
- ✅ Authentication system with email verification
- ✅ RBAC with policies and middleware
- ✅ Basic layouts and error pages

**Ready to Implement (Recommended Order):**

1. **Task 5: Student Profile Management** - Enable students to create profiles and connect Stripe
2. **Task 6: Service Listing Management** - Allow students to create and manage service offerings
3. **Task 7: Service Discovery** - Build search and filtering for clients to find services
4. **Task 8: Order Placement** - Integrate Stripe checkout for order creation
5. **Task 9: Order Lifecycle** - Implement state machine for order progression
6. **Task 10: Payment Processing** - Handle escrow, releases, and refunds
7. **Task 11: Messaging System** - Enable communication between parties
8. **Task 12: Review System** - Allow clients to rate and review completed orders
9. **Task 13: Notifications** - Email and in-app notifications for events
10. **Tasks 14-17: Admin Features** - User management, moderation, disputes, settings
11. **Tasks 18-19: Security & Files** - File handling and security hardening
12. **Tasks 20-23: Polish** - Dashboards, landing page, error handling, deployment prep

**Critical Path for MVP:**
Tasks 5 → 6 → 7 → 8 → 9 → 10 form the core marketplace functionality. Tasks 11-13 add essential communication and feedback. Tasks 14-17 enable platform management. Tasks 18-23 are polish and production readiness.
