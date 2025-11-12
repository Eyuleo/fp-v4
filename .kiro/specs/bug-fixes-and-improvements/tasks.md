# Implementation Plan

- [x] 1. Fix Stripe Connect Integration

  - Fix OAuth callback handling to prevent session expiration
  - Ensure Stripe account ID is stored in database after successful connection
  - Add error logging for debugging connection failures
  - Validate environment variables and configuration
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 2. Implement Password Visibility Toggle

  - [x] 2.1 Create reusable password toggle JavaScript component

    - Write JavaScript function to toggle password visibility
    - Add eye icon SVG or font icon
    - _Requirements: 2.1, 2.2_

  - [x] 2.2 Add password toggle to all password input fields

    - Update login form view
    - Update registration form view
    - Update password reset form view
    - Update password change form view
    - Add CSS styling for toggle button
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 3. Implement Remember Me Functionality

  - [x] 3.1 Create database migration for remember_tokens table

    - Write migration SQL with proper indexes and foreign keys
    - _Requirements: 3.2, 3.3_

  - [x] 3.2 Create RememberTokenRepository

    - Implement create, find, delete methods for remember tokens
    - Add token validation logic
    - _Requirements: 3.2, 3.3_

  - [x] 3.3 Update AuthService and AuthController

    - Add remember me checkbox to login form
    - Generate and store remember token on login when checked
    - Implement token-based auto-authentication
    - Handle token invalidation on logout
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 3.4 Update AuthMiddleware to check remember tokens

    - Check for remember token cookie if session is not authenticated
    - Validate and authenticate user from remember token
    - _Requirements: 3.3_

- [x] 4. Implement Form State Persistence

  - [x] 4.1 Create FormHelper utility

    - Write old() helper function to retrieve old form values
    - Write flash() helper function for session flash data
    - _Requirements: 4.1, 4.3_

  - [x] 4.2 Update service creation and editing forms

    - Store form data in session on validation error
    - Repopulate form fields with old values
    - Clear old values after successful submission
    - _Requirements: 4.1, 4.2, 4.4_

  - [x] 4.3 Update order creation form

    - Store form data in session on validation error
    - Repopulate form fields with old values
    - _Requirements: 4.1, 4.2, 4.4_

  - [x] 4.4 Update review submission form

    - Store form data in session on validation error
    - Repopulate form fields with old values
    - _Requirements: 4.1, 4.2, 4.4_

- [ ] 5. Fix Order Cancellation and Refunds

  - [ ] 5.1 Add comprehensive error logging to PaymentService

    - Log Stripe API errors with full details
    - Add error context (order ID, amount, payment intent ID)
    - _Requirements: 5.2_

  - [ ] 5.2 Fix refund processing logic
    - Validate payment intent exists before refund
    - Handle Stripe API errors gracefully
    - Update order status and record refund transaction
    - Display specific error messages to users
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 6. Implement Email Notifications for Activities

  - [x] 6.1 Create email templates

    - Create order created email template
    - Create order status changed email template
    - Create message received email template
    - Create review submitted email template
    - Create dispute email templates
    - _Requirements: 6.6_

  - [x] 6.2 Update OrderController to send email notifications

    - Send emails on order creation
    - Send emails on order status changes
    - _Requirements: 6.1, 6.2_

  - [x] 6.3 Update MessageController to send email notifications

    - Send email when message is received
    - _Requirements: 6.3_

  - [x] 6.4 Update ReviewController to send email notifications

    - Send email to student when review is submitted
    - _Requirements: 6.4_

  - [x] 6.5 Update DisputeController to send email notifications

    - Send emails on dispute creation and updates
    - _Requirements: 6.5_

- [-] 7. Fix Order Detail File Access

  - [ ] 7.1 Fix requirement files display

    - Update OrderRepository to fetch requirement files
    - Display requirement files on order detail page
    - Ensure files are accessible to both client and student
    - _Requirements: 7.1, 7.5_

  - [ ] 7.2 Fix delivered files access
    - Update FileService to correctly resolve delivered file paths
    - Fix file download endpoint to prevent 404 errors
    - Add proper authentication and permission checks
    - _Requirements: 7.2, 7.3, 7.4, 7.5_

- [ ] 8. Fix Message System Issues

  - [ ] 8.1 Fix is_flagged SQL error

    - Set default value of 0 for is_flagged in MessageRepository
    - Update message creation logic
    - _Requirements: 8.2_

  - [ ] 8.2 Add /messages route and fix navbar link

    - Add /messages route to Router
    - Create messages index view
    - Fix navbar message icon to link to /messages
    - _Requirements: 8.3, 8.4_

  - [ ] 8.3 Implement /messages/unread-count endpoint
    - Create unread count method in MessageController
    - Add route for /messages/unread-count
    - Return JSON response with count
    - _Requirements: 8.5_

- [ ] 9. Improve Review Display

  - [ ] 9.1 Update review star styling

    - Add CSS to make star ratings yellow
    - _Requirements: 9.1_

  - [ ] 9.2 Display reviewer information
    - Update ReviewRepository to fetch reviewer name
    - Display reviewer name instead of "anonymous"
    - Generate and display reviewer initials as profile picture
    - _Requirements: 9.2, 9.3, 9.4_

- [ ] 10. Fix Notification Polling

  - [ ] 10.1 Implement NotificationController unread count endpoint

    - Create or update NotificationController
    - Implement unread-count method
    - Add route for /notifications/unread-count
    - _Requirements: 10.2, 10.3_

  - [ ] 10.2 Update notification polling JavaScript
    - Fix polling to call correct endpoint
    - Handle errors gracefully
    - Update notification badge on success
    - _Requirements: 10.1, 10.4_

- [ ] 11. Add Category Editing

  - [ ] 11.1 Create category edit form and controller method
    - Add edit button to category list view
    - Create edit category view with pre-populated form
    - Implement update method in CategoryController
    - Add validation for category updates
    - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [ ] 12. Fix Admin Service Filtering

  - [ ] 12.1 Fix "all" status filter to include active services

    - Update SQL query in ServiceRepository
    - Ensure all statuses are included when "all" is selected
    - _Requirements: 12.1, 12.3_

  - [ ] 12.2 Fix duplicate inactive service rendering
    - Add DISTINCT or GROUP BY to query
    - Review JOIN conditions
    - _Requirements: 12.2, 12.4_

- [ ] 13. Implement Service Activation

  - [ ] 13.1 Add service activation functionality
    - Add "Activate" button to inactive service view in admin
    - Implement activate method in ServiceController
    - Update service status to "active"
    - Send notification to student
    - Log activation in audit logs
    - _Requirements: 13.1, 13.2, 13.3, 13.4_

- [ ] 14. Fix Service Sample Works Display
  - [ ] 14.1 Fix sample works file retrieval and display
    - Review and fix FileService implementation for sample works
    - Ensure ServiceRepository fetches sample work files correctly
    - Fix file path resolution in service detail view
    - Test file display and download
    - _Requirements: 14.1, 14.2, 14.3, 14.4_
