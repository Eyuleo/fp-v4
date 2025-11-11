# Requirements Document

## Introduction

The Student Skills Marketplace is a three-tier, server-rendered PHP platform that enables Ethiopian university students to monetize their skills through a trusted marketplace. The system provides discovery, secure transactions, escrow-like payments, messaging, reviews, and administrative oversight to replace fragmented informal workflows with a cohesive, safe platform.

## Glossary

- **System**: The Student Skills Marketplace platform
- **Student**: A registered user with the provider role who offers services
- **Client**: A registered user with the buyer role who purchases services
- **Admin**: A registered user with administrative privileges
- **Service Listing**: A published offering by a student including description, price, and delivery terms
- **Order**: A transaction instance between a client and student with defined requirements and lifecycle states
- **Escrow Payment**: A payment mechanism where client funds are held until order completion
- **Revision**: A bounded request by the client for changes to delivered work
- **Dispute**: A formal escalation requiring admin intervention for fund resolution
- **Commission**: Platform fee deducted from student earnings on order completion
- **Webhook**: Asynchronous notification from Stripe about payment events

## Requirements

### Requirement 1: User Registration and Authentication

**User Story:** As a new user, I want to register with email verification, so that I can access the platform securely with a verified identity.

#### Acceptance Criteria

1. WHEN a user submits valid registration data with email, password, and role selection, THE System SHALL create an unverified account and send a verification email.
2. WHEN a user clicks the verification link within 24 hours, THE System SHALL activate the account and redirect to the login page.
3. WHEN a user attempts to login with an unverified account, THE System SHALL deny access and display a verification reminder message.
4. WHEN a user submits valid login credentials for a verified account, THE System SHALL create an authenticated session and redirect to the role-appropriate dashboard.
5. WHEN a user requests password reset with a registered email, THE System SHALL send a time-limited reset link valid for 1 hour.

### Requirement 2: Role-Based Access Control

**User Story:** As the system, I want to enforce role-based permissions on all actions, so that users can only perform operations appropriate to their role.

#### Acceptance Criteria

1. WHEN a Student attempts to access client-only features, THE System SHALL deny access and return a 403 Forbidden response.
2. WHEN a Client attempts to access student-only features, THE System SHALL deny access and return a 403 Forbidden response.
3. WHEN a non-Admin user attempts to access admin console features, THE System SHALL deny access and return a 403 Forbidden response.
4. WHEN a user attempts to modify a resource they do not own, THE System SHALL deny access and return a 403 Forbidden response.
5. THE System SHALL validate role and ownership permissions before executing any mutating operation.

### Requirement 3: Student Profile Management

**User Story:** As a student, I want to create and maintain a detailed profile with bio, skills, and portfolio samples, so that clients can discover and evaluate my offerings.

#### Acceptance Criteria

1. WHEN a Student submits profile data including bio, skills tags, and portfolio samples, THE System SHALL validate and save the profile information.
2. WHEN a Student uploads portfolio files exceeding 10MB per file, THE System SHALL reject the upload and display a size limit error.
3. WHEN a Student profile is viewed by any user, THE System SHALL display the bio, skills, portfolio samples, average rating, and recent reviews.
4. WHEN a Student updates their profile, THE System SHALL preserve the existing average rating and review history.
5. THE System SHALL calculate and display the Student average rating from all completed order reviews.

### Requirement 4: Service Listing Creation and Management

**User Story:** As a student, I want to create, edit, activate, pause, and delete service listings, so that I can control my marketplace offerings.

#### Acceptance Criteria

1. WHEN a Student submits a new service listing with title, description, category, tags, price, delivery days, and sample work, THE System SHALL validate and create the listing in inactive state.
2. WHEN a Student activates a service listing, THE System SHALL make the listing visible in discovery search results.
3. WHEN a Student pauses an active service listing, THE System SHALL hide the listing from discovery while preserving existing active orders.
4. WHEN a Student attempts to delete a service listing with active orders, THE System SHALL deny the deletion and display an error message.
5. WHEN a Student deletes a service listing with no active orders, THE System SHALL permanently remove the listing and associated files.

### Requirement 5: Service Discovery and Search

**User Story:** As a client, I want to search and filter service listings by category, price, delivery time, and rating, so that I can find services matching my needs.

#### Acceptance Criteria

1. WHEN a Client submits a search query, THE System SHALL return paginated results of active service listings matching the query text in title or description.
2. WHEN a Client applies category filters, THE System SHALL return only listings within the selected categories.
3. WHEN a Client applies price range filters, THE System SHALL return only listings with prices within the specified minimum and maximum values.
4. WHEN a Client applies rating filters, THE System SHALL return only listings from students with average ratings meeting the minimum threshold.
5. WHEN a Client selects a sort option, THE System SHALL order results by the selected criterion (price, rating, delivery time, or relevance).
6. THE System SHALL display search results with pagination showing 20 listings per page.

### Requirement 6: Order Placement and Requirements Intake

**User Story:** As a client, I want to place an order with detailed requirements and attachments, so that the student understands my needs clearly.

#### Acceptance Criteria

1. WHEN a Client submits an order with requirements text and optional attachments, THE System SHALL create the order in pending state and charge the client via Stripe.
2. WHEN the Stripe payment succeeds, THE System SHALL transition the order to pending state and notify the Student via email.
3. WHEN the Stripe payment fails, THE System SHALL cancel the order creation and display a payment error message to the Client.
4. WHEN a Client uploads requirement attachments exceeding 25MB total, THE System SHALL reject the upload and display a size limit error.
5. THE System SHALL calculate the order deadline by adding the service delivery days to the order placement timestamp.

### Requirement 7: Order Lifecycle State Transitions

**User Story:** As a student, I want to accept, work on, deliver, and complete orders through defined states, so that the workflow is clear and trackable.

#### Acceptance Criteria

1. WHEN a Student accepts a pending order, THE System SHALL transition the order to in_progress state and notify the Client.
2. WHEN a Student submits delivery with work files and message, THE System SHALL transition the order to delivered state and notify the Client.
3. WHEN a Client accepts delivered work, THE System SHALL transition the order to completed state, release escrowed funds to the Student, deduct commission, and notify both parties.
4. WHEN a Client requests revision on delivered work within the revision limit, THE System SHALL transition the order to revision_requested state and notify the Student.
5. WHEN a Student redelivers after revision request, THE System SHALL transition the order back to delivered state.
6. WHEN revision requests exceed the configured maximum limit (3 revisions), THE System SHALL prevent further revision requests and require dispute escalation.

### Requirement 8: Order Cancellation

**User Story:** As a client or student, I want to cancel orders under appropriate conditions, so that I can exit transactions that cannot proceed.

#### Acceptance Criteria

1. WHEN a Client cancels a pending order before Student acceptance, THE System SHALL refund the full payment amount to the Client and transition the order to cancelled state.
2. WHEN a Student cancels a pending order, THE System SHALL refund the full payment amount to the Client and transition the order to cancelled state.
3. WHEN a Client or Student attempts to cancel an order in in_progress or delivered state, THE System SHALL deny the cancellation and require dispute escalation.
4. WHEN an order is cancelled, THE System SHALL notify both parties via email with the cancellation reason.
5. THE System SHALL process Stripe refunds idempotently to prevent duplicate refund attempts.

### Requirement 9: Escrow Payment Processing

**User Story:** As a client, I want my payment held securely until order completion, so that I am protected from non-delivery or poor quality work.

#### Acceptance Criteria

1. WHEN a Client places an order, THE System SHALL charge the full service price via Stripe and hold the funds in escrow.
2. WHEN an order transitions to completed state, THE System SHALL transfer funds to the Student Stripe Connect account minus the platform commission.
3. WHEN an order is cancelled before completion, THE System SHALL refund the escrowed funds to the Client payment method.
4. WHEN a Stripe webhook delivers a payment event, THE System SHALL process the webhook idempotently using the event ID as idempotency key.
5. THE System SHALL record all payment state changes in an audit log with timestamp, amount, and event type.

### Requirement 10: Payment Webhook Handling

**User Story:** As the system, I want to handle Stripe webhooks reliably and idempotently, so that payment state remains consistent despite network failures or retries.

#### Acceptance Criteria

1. WHEN a Stripe webhook is received, THE System SHALL verify the webhook signature before processing.
2. WHEN a webhook with a previously processed event ID is received, THE System SHALL acknowledge the webhook without reprocessing.
3. WHEN a checkout.session.completed webhook is received, THE System SHALL confirm the order payment and transition to pending state.
4. WHEN a charge.refunded webhook is received, THE System SHALL update the order payment status to refunded.
5. WHEN webhook processing fails, THE System SHALL log the error and return a 500 status to trigger Stripe retry.

### Requirement 11: Messaging Between Parties

**User Story:** As a client or student, I want to exchange messages with file attachments within order threads, so that I can communicate requirements and updates.

#### Acceptance Criteria

1. WHEN a Client or Student sends a message in an order thread, THE System SHALL save the message and notify the recipient via email.
2. WHEN a user uploads message attachments exceeding 10MB per file, THE System SHALL reject the upload and display a size limit error.
3. WHEN a message contains patterns suggesting off-platform contact or payment, THE System SHALL flag the message for admin review.
4. WHEN a user views an order thread, THE System SHALL mark all messages as read and update the unread counter.
5. THE System SHALL allow pre-order messaging between Client and Student before order placement.

### Requirement 12: Review Submission and Display

**User Story:** As a client, I want to submit a rating and review after order completion, so that I can share my experience and help other clients make informed decisions.

#### Acceptance Criteria

1. WHEN a Client submits a review with 1-5 star rating and optional text for a completed order, THE System SHALL save the review and update the Student average rating.
2. WHEN a Client attempts to submit multiple reviews for the same order, THE System SHALL reject the duplicate and display an error message.
3. WHEN a Client edits a review within 24 hours of submission, THE System SHALL update the review and recalculate the Student average rating.
4. WHEN a review is submitted, THE System SHALL notify the Student via email.
5. THE System SHALL display reviews on the Student profile ordered by submission date descending.

### Requirement 13: Student Review Response

**User Story:** As a student, I want to publicly reply to client reviews, so that I can provide context or thank clients for positive feedback.

#### Acceptance Criteria

1. WHEN a Student submits a reply to a review on their profile, THE System SHALL save and display the reply beneath the review.
2. WHEN a Student attempts to reply to a review multiple times, THE System SHALL replace the previous reply with the new reply.
3. WHEN a Student reply is submitted, THE System SHALL notify the Client via email.
4. THE System SHALL display the Student reply immediately below the corresponding review.
5. WHEN a review has no Student reply, THE System SHALL display a "Reply" action button to the Student.

### Requirement 14: Email Notifications

**User Story:** As a user, I want to receive email notifications for key events, so that I stay informed of important platform activities.

#### Acceptance Criteria

1. WHEN an order is placed, THE System SHALL send an email notification to the Student with order details.
2. WHEN an order is delivered, THE System SHALL send an email notification to the Client with delivery message and files.
3. WHEN a revision is requested, THE System SHALL send an email notification to the Student with revision details.
4. WHEN an order is completed, THE System SHALL send email notifications to both Client and Student with completion confirmation.
5. WHEN a review is submitted, THE System SHALL send an email notification to the Student with review content.
6. WHEN a message is received, THE System SHALL send an email notification to the recipient with message preview.
7. THE System SHALL send emails via PHPMailer using Gmail SMTP with App Password authentication.

### Requirement 15: In-App Notifications

**User Story:** As a user, I want to see in-app notifications for events, so that I can stay informed while using the platform.

#### Acceptance Criteria

1. WHEN a notification-triggering event occurs, THE System SHALL create an in-app notification record for the recipient.
2. WHEN a user views their notifications page, THE System SHALL display all notifications ordered by creation date descending.
3. WHEN a user views a notification, THE System SHALL mark the notification as read.
4. THE System SHALL display an unread notification counter in the navigation header.
5. WHEN a user clicks a notification, THE System SHALL navigate to the relevant resource (order, message, review).

### Requirement 16: Admin User Management

**User Story:** As an admin, I want to view, suspend, and manage user accounts, so that I can maintain platform integrity.

#### Acceptance Criteria

1. WHEN an Admin views the user management page, THE System SHALL display paginated lists of all users with role, status, and registration date.
2. WHEN an Admin suspends a user account, THE System SHALL prevent the user from logging in and display a suspension message.
3. WHEN an Admin reactivates a suspended account, THE System SHALL restore login access.
4. WHEN an Admin views a user profile, THE System SHALL display complete user details, order history, and review history.
5. THE System SHALL log all admin actions with timestamp, admin ID, and action details.

### Requirement 17: Admin Service Moderation

**User Story:** As an admin, I want to review and moderate service listings, so that I can ensure quality and policy compliance.

#### Acceptance Criteria

1. WHEN an Admin views the service moderation page, THE System SHALL display all service listings with status, student, and creation date.
2. WHEN an Admin deactivates a service listing, THE System SHALL hide the listing from discovery and notify the Student.
3. WHEN an Admin deletes a service listing, THE System SHALL permanently remove the listing and notify the Student with reason.
4. WHEN an Admin views a service listing, THE System SHALL display complete listing details and associated orders.
5. THE System SHALL flag service listings with reported content for admin review.

### Requirement 18: Admin Dispute Resolution

**User Story:** As an admin, I want to resolve order disputes by holding, releasing, or refunding payments, so that I can mediate conflicts fairly.

#### Acceptance Criteria

1. WHEN an Admin views a dispute, THE System SHALL display order details, messages, delivery files, and both party statements.
2. WHEN an Admin releases payment to the Student, THE System SHALL transfer escrowed funds minus commission and transition the order to completed state.
3. WHEN an Admin refunds payment to the Client, THE System SHALL process a full Stripe refund and transition the order to cancelled state.
4. WHEN an Admin processes a partial refund, THE System SHALL refund the specified amount to the Client and transfer the remainder to the Student minus commission.
5. WHEN a dispute is resolved, THE System SHALL notify both parties via email with the resolution decision and reasoning.

### Requirement 19: Admin Platform Settings

**User Story:** As an admin, I want to configure platform settings including commission rate, revision limits, and categories, so that I can adapt the platform to business needs.

#### Acceptance Criteria

1. WHEN an Admin updates the commission percentage, THE System SHALL apply the new rate to all subsequent order completions.
2. WHEN an Admin updates the maximum revision limit, THE System SHALL apply the new limit to all new orders.
3. WHEN an Admin creates a new service category, THE System SHALL make the category available for service listing creation.
4. WHEN an Admin deletes a category with existing listings, THE System SHALL prevent deletion and display an error message.
5. THE System SHALL log all settings changes with timestamp, admin ID, old value, and new value.

### Requirement 20: Admin Analytics Dashboard

**User Story:** As an admin, I want to view platform analytics including GMV, order counts, on-time rates, and dispute rates, so that I can monitor platform health.

#### Acceptance Criteria

1. WHEN an Admin views the analytics dashboard, THE System SHALL display total Gross Merchandise Value for the selected time period.
2. WHEN an Admin views the analytics dashboard, THE System SHALL display total order count and completion rate for the selected time period.
3. WHEN an Admin views the analytics dashboard, THE System SHALL display on-time delivery rate calculated from orders completed before deadline.
4. WHEN an Admin views the analytics dashboard, THE System SHALL display dispute rate calculated from orders escalated to disputes.
5. THE System SHALL allow filtering analytics by date range (last 7 days, 30 days, 90 days, or custom range).

### Requirement 21: Security - CSRF Protection

**User Story:** As the system, I want to protect all mutating operations with CSRF tokens, so that cross-site request forgery attacks are prevented.

#### Acceptance Criteria

1. WHEN a user loads a form, THE System SHALL generate and embed a unique CSRF token in the form.
2. WHEN a user submits a mutating request without a valid CSRF token, THE System SHALL reject the request and return a 403 Forbidden response.
3. WHEN a user submits a mutating request with an expired CSRF token, THE System SHALL reject the request and display a session timeout message.
4. THE System SHALL validate CSRF tokens on all POST, PUT, PATCH, and DELETE requests.
5. THE System SHALL regenerate CSRF tokens after successful authentication.

### Requirement 22: Security - XSS Prevention

**User Story:** As the system, I want to escape all user-generated content in HTML output, so that cross-site scripting attacks are prevented.

#### Acceptance Criteria

1. WHEN user-generated content is rendered in HTML, THE System SHALL escape all HTML special characters.
2. WHEN user-generated content contains script tags, THE System SHALL render the tags as escaped text rather than executable code.
3. WHEN user-generated content is rendered in HTML attributes, THE System SHALL escape attribute-specific special characters.
4. THE System SHALL use context-aware output escaping for HTML, JavaScript, CSS, and URL contexts.
5. THE System SHALL sanitize rich text content using an allowlist of safe HTML tags and attributes.

### Requirement 23: Security - SQL Injection Prevention

**User Story:** As the system, I want to use prepared statements for all database queries, so that SQL injection attacks are prevented.

#### Acceptance Criteria

1. WHEN the System executes a database query with user input, THE System SHALL use prepared statements with parameter binding.
2. WHEN the System constructs dynamic queries, THE System SHALL validate and sanitize all identifiers (table names, column names).
3. THE System SHALL never concatenate user input directly into SQL query strings.
4. THE System SHALL use parameterized queries for all SELECT, INSERT, UPDATE, and DELETE operations.
5. THE System SHALL log and block queries that fail parameter binding validation.

### Requirement 24: Security - File Upload Validation

**User Story:** As the system, I want to validate all file uploads by type, size, and content, so that malicious files cannot be uploaded.

#### Acceptance Criteria

1. WHEN a user uploads a file, THE System SHALL validate the file extension against an allowlist of permitted types.
2. WHEN a user uploads a file, THE System SHALL validate the MIME type matches the file extension.
3. WHEN a user uploads a file exceeding the size limit, THE System SHALL reject the upload and display a size error message.
4. WHEN a user uploads an image file, THE System SHALL verify the file is a valid image by attempting to read image dimensions.
5. THE System SHALL store uploaded files outside the web root with randomized filenames to prevent direct access.

### Requirement 25: Security - Signed File Downloads

**User Story:** As the system, I want to protect file downloads with signed URLs and authorization checks, so that only authorized users can access files.

#### Acceptance Criteria

1. WHEN a user requests a file download, THE System SHALL verify the user has authorization to access the file.
2. WHEN a user requests a file download, THE System SHALL generate a time-limited signed URL valid for 5 minutes.
3. WHEN a user accesses a file with an expired signature, THE System SHALL deny access and return a 403 Forbidden response.
4. WHEN a user accesses a file with an invalid signature, THE System SHALL deny access and return a 403 Forbidden response.
5. THE System SHALL serve files through a download controller that enforces authorization rather than direct web server access.

### Requirement 26: Security - Rate Limiting

**User Story:** As the system, I want to rate limit authentication attempts and API endpoints, so that brute force and denial of service attacks are mitigated.

#### Acceptance Criteria

1. WHEN a user exceeds 5 failed login attempts within 15 minutes, THE System SHALL temporarily block the account for 30 minutes.
2. WHEN a user exceeds 10 password reset requests within 1 hour, THE System SHALL temporarily block password reset for that email for 1 hour.
3. WHEN a user exceeds 100 search requests within 1 minute, THE System SHALL temporarily throttle the user for 5 minutes.
4. WHEN a webhook endpoint receives more than 1000 requests within 1 minute, THE System SHALL temporarily throttle webhook processing.
5. THE System SHALL return a 429 Too Many Requests response when rate limits are exceeded.

### Requirement 27: Security - Audit Logging

**User Story:** As the system, I want to log all security-relevant events, so that suspicious activity can be detected and investigated.

#### Acceptance Criteria

1. WHEN a user authenticates successfully, THE System SHALL log the event with user ID, IP address, and timestamp.
2. WHEN a user authentication fails, THE System SHALL log the event with attempted email, IP address, and timestamp.
3. WHEN a payment state change occurs, THE System SHALL log the event with order ID, amount, previous state, new state, and timestamp.
4. WHEN an admin performs a moderation action, THE System SHALL log the event with admin ID, action type, target resource, and timestamp.
5. THE System SHALL retain audit logs for a minimum of 90 days.

### Requirement 28: Performance - Database Indexing

**User Story:** As the system, I want to maintain appropriate database indexes, so that query performance remains acceptable as data grows.

#### Acceptance Criteria

1. THE System SHALL maintain an index on the services table for (category, status, created_at) to optimize discovery queries.
2. THE System SHALL maintain an index on the orders table for (student_id, status) to optimize student dashboard queries.
3. THE System SHALL maintain an index on the orders table for (client_id, status) to optimize client dashboard queries.
4. THE System SHALL maintain an index on the messages table for (order_id, created_at) to optimize message thread queries.
5. THE System SHALL maintain an index on the reviews table for (student_id, created_at) to optimize profile review queries.

### Requirement 29: Reliability - Transaction Management

**User Story:** As the system, I want to wrap critical state changes in database transactions, so that data consistency is maintained during failures.

#### Acceptance Criteria

1. WHEN an order state transition occurs, THE System SHALL execute all related database updates within a single transaction.
2. WHEN a payment is processed, THE System SHALL execute order and payment record updates within a single transaction.
3. WHEN a transaction fails due to database error, THE System SHALL roll back all changes and log the error.
4. WHEN a review is submitted, THE System SHALL update the review record and student average rating within a single transaction.
5. THE System SHALL use transaction isolation level READ COMMITTED for all transactional operations.

### Requirement 30: Usability - Mobile Responsiveness

**User Story:** As a user on a mobile device, I want the interface to adapt to my screen size, so that I can use all features comfortably.

#### Acceptance Criteria

1. WHEN a user accesses the platform on a screen width below 768px, THE System SHALL display a mobile-optimized layout with stacked elements.
2. WHEN a user accesses forms on mobile, THE System SHALL display touch-friendly input controls with appropriate sizing.
3. WHEN a user accesses tables on mobile, THE System SHALL display data in a card-based layout or horizontal scroll container.
4. WHEN a user accesses navigation on mobile, THE System SHALL display a collapsible hamburger menu.
5. THE System SHALL ensure all interactive elements have a minimum touch target size of 44x44 pixels.
