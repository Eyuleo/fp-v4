# Requirements Document

## Introduction

This document outlines the requirements for fixing critical bugs and implementing missing features in the student skills marketplace platform. The issues span authentication, file handling, messaging, notifications, UI/UX improvements, and admin functionality.

## Glossary

- **Platform**: The student skills marketplace web application
- **User**: Any authenticated person using the Platform (student, client, or admin)
- **Student**: A User who provides services on the Platform
- **Client**: A User who purchases services on the Platform
- **Admin**: A User with administrative privileges on the Platform
- **Service**: A skill-based offering created by a Student
- **Order**: A transaction where a Client purchases a Service
- **Message**: Communication between Users within the Platform
- **Notification**: System-generated alert for User activities
- **Stripe Connect**: Payment processing integration for the Platform
- **Form State**: User input data that persists across page reloads or validation errors

## Requirements

### Requirement 1: Stripe Connect Integration

**User Story:** As a Student, I want the Stripe Connect integration to work properly, so that I can receive payments for my services

#### Acceptance Criteria

1. WHEN a Student attempts to connect their Stripe account, THE Platform SHALL complete the connection process without errors
2. WHEN a Student views their payment settings, THE Platform SHALL display their current Stripe Connect status accurately
3. IF the Stripe Connect process fails, THEN THE Platform SHALL display a specific error message indicating the failure reason
4. THE Platform SHALL store Stripe Connect tokens securely in the database

additional note: the issue is that after successfully submitting data to stripe during redirect the app just throws you to the login page, the session is expired because of that the account id is not being stored

### Requirement 2: Password Input Visibility Toggle

**User Story:** As a User, I want to toggle password visibility on all password input fields, so that I can verify my password entry

#### Acceptance Criteria

1. WHEN a User views any password input field, THE Platform SHALL display a visibility toggle icon adjacent to the field
2. WHEN a User clicks the visibility toggle icon, THE Platform SHALL switch between masked and visible password text
3. THE Platform SHALL apply the password visibility toggle to all password fields including login, registration, password change, and password reset forms
4. THE Platform SHALL maintain password security by defaulting to masked state

### Requirement 3: Remember Me Functionality

**User Story:** As a User, I want a "Remember Me" option during login, so that I can stay logged in across browser sessions

#### Acceptance Criteria

1. WHEN a User views the login form, THE Platform SHALL display a "Remember Me" checkbox
2. WHEN a User checks "Remember Me" and logs in successfully, THE Platform SHALL create a persistent authentication token with expiration of 30 days
3. WHEN a User returns to the Platform with a valid remember token, THE Platform SHALL authenticate the User automatically
4. WHEN a User logs out, THE Platform SHALL invalidate the remember token

### Requirement 4: Form State Persistence

**User Story:** As a User, I want my form inputs to persist when validation errors occur, so that I don't have to re-enter all information

#### Acceptance Criteria

1. WHEN a User submits a form with validation errors, THE Platform SHALL repopulate all form fields with the previously entered values
2. THE Platform SHALL apply form state persistence to service creation, service editing, order creation, and review submission forms
3. WHEN a User corrects validation errors and resubmits, THE Platform SHALL process the form with the updated values
4. THE Platform SHALL clear persisted form state after successful form submission

### Requirement 5: Order Cancellation and Refunds

**User Story:** As a Client, I want to cancel orders and receive refunds, so that I can recover payment for services I no longer need

#### Acceptance Criteria

1. WHEN a Client cancels an eligible order, THE Platform SHALL process the refund through Stripe without errors
2. WHEN a refund fails, THE Platform SHALL log the specific error details for debugging
3. IF a refund cannot be processed automatically, THEN THE Platform SHALL display a clear error message with next steps
4. THE Platform SHALL update the order status to "cancelled" and record the refund transaction

### Requirement 6: Email Notifications for Activities

**User Story:** As a User, I want to receive email notifications for important activities, so that I stay informed about platform events

#### Acceptance Criteria

1. WHEN an order is created, THE Platform SHALL send email notifications to both the Client and the Student
2. WHEN an order status changes, THE Platform SHALL send an email notification to the relevant User
3. WHEN a message is received, THE Platform SHALL send an email notification to the recipient
4. WHEN a review is submitted, THE Platform SHALL send an email notification to the Student
5. WHEN a dispute is created or updated, THE Platform SHALL send email notifications to all involved parties
6. THE Platform SHALL use consistent email templates for all notification types

### Requirement 7: Order Detail File Access

**User Story:** As a Client, I want to view requirement files (this are the ones client upload during order creation currently the student can't view or access them) and delivered files on the order detail page(the view exist but when you click on the link 404 is thrown), so that I can access all order-related documents

#### Acceptance Criteria

1. WHEN a Client/student views an order detail page, THE Platform SHALL display all requirement files uploaded during order creation
2. WHEN a Client/student clicks on a requirement file link, THE Platform SHALL serve the file for download without 404 errors
3. WHEN a Student delivers files for an order, THE Platform SHALL display the delivered files on the order detail page
4. WHEN a Client clicks on a delivered file link, THE Platform SHALL serve the file for download without 404 errors
5. THE Platform SHALL validate file paths and permissions before serving files

### Requirement 8: Message System Fixes

**User Story:** As a User, I want to send and receive messages without errors, so that I can communicate with other Users

#### Acceptance Criteria

1. WHEN a User sends a message, THE Platform SHALL store the message with valid integer values for all required fields
2. THE Platform SHALL set default value of 0 for the is_flagged column when creating messages
3. WHEN a User accesses the messages page at /messages, THE Platform SHALL display the messages interface without 404 errors
4. WHEN a User clicks the message icon in the navbar, THE Platform SHALL navigate to /messages instead of /orders
5. THE Platform SHALL implement the /messages/unread-count endpoint without 500 errors

### Requirement 9: Review Display Improvements

**User Story:** As a User, I want to see properly styled reviews with reviewer information, so that I can evaluate service quality

#### Acceptance Criteria

1. WHEN a User views reviews, THE Platform SHALL display star ratings in yellow color
2. WHEN a User views a review on a Student profile, THE Platform SHALL display the reviewer's name instead of "anonymous"
3. WHEN a User views a review on a Student profile, THE Platform SHALL display the reviewer's initials as a profile picture
4. THE Platform SHALL maintain consistent review styling across all pages

### Requirement 10: Notification Polling

**User Story:** As a User, I want real-time notification updates, so that I am immediately aware of new activities

#### Acceptance Criteria

1. WHEN a User is logged in, THE Platform SHALL poll for new notifications at regular intervals of 30 seconds
2. THE Platform SHALL implement the /notifications/unread-count endpoint without errors
3. WHEN new notifications are available, THE Platform SHALL update the notification badge count in the navbar
4. THE Platform SHALL handle polling errors gracefully without breaking the user interface

### Requirement 11: Category Management

**User Story:** As an Admin, I want to edit existing categories, so that I can maintain accurate category information

#### Acceptance Criteria

1. WHEN an Admin views the categories list, THE Platform SHALL display an edit button for each category
2. WHEN an Admin clicks the edit button, THE Platform SHALL display a form pre-populated with the category's current data
3. WHEN an Admin submits category edits, THE Platform SHALL update the category in the database
4. THE Platform SHALL validate category data before saving edits

### Requirement 12: Admin Service Filtering

**User Story:** As an Admin, I want accurate service filtering by status, so that I can manage services effectively

#### Acceptance Criteria

1. WHEN an Admin filters services by "all" status, THE Platform SHALL include services with all status in the results
2. THE Platform SHALL display each service only once in the filtered results
3. WHEN an Admin filters services by a specific status, THE Platform SHALL display only services matching that status
4. THE Platform SHALL fix duplicate rendering of inactive services

### Requirement 13: Service Activation

**User Story:** As an Admin, I want to activate inactive services, so that I can restore services after review

#### Acceptance Criteria

1. WHEN an Admin views an inactive service, THE Platform SHALL display an "Activate" button
2. WHEN an Admin clicks the "Activate" button, THE Platform SHALL change the service status to "active"
3. THE Platform SHALL log the service activation in the audit logs
4. WHEN a service is activated, THE Platform SHALL send a notification to the Student

### Requirement 14: Service Sample Works Display

**User Story:** As a User, I want to view service sample works on the service detail page, so that I can evaluate the Student's work quality

#### Acceptance Criteria

1. WHEN a User views a service detail page, THE Platform SHALL display all sample work files associated with the service
2. THE Platform SHALL use the FileService to retrieve sample work file paths correctly
3. WHEN a User clicks on a sample work file, THE Platform SHALL display or download the file without errors
4. THE Platform SHALL maintain sample work display functionality after FileService implementation changes

### Requirement 15: Form Button Loading States

**User Story:** As a User, I want visual feedback when submitting forms, so that I know my action is being processed and avoid duplicate submissions

#### Acceptance Criteria

1. WHEN a User clicks a form submit button, THE Platform SHALL display a loading indicator on the button
2. WHEN a form is being submitted, THE Platform SHALL disable the submit button to prevent duplicate submissions
3. THE Platform SHALL apply loading and disabled states to all form buttons across the application
4. THE Platform SHALL use a modular approach that can be easily applied to any form button
5. WHEN form submission completes or fails, THE Platform SHALL restore the button to its original state

### Requirement 16: Number Format Null Handling

**User Story:** As a User, I want the Platform to handle null numeric values gracefully, so that I don't encounter deprecation warnings or errors

#### Acceptance Criteria

1. WHEN the Platform calls number_format with a null value, THE Platform SHALL handle the null value before formatting
2. THE Platform SHALL apply null handling to all instances of number_format throughout the codebase
3. THE Platform SHALL display appropriate default values (such as 0 or empty string) when numeric values are null
4. THE Platform SHALL eliminate all "passing null to parameter of type float is deprecated" warnings

### Requirement 17: Dashboard Quick Actions Modernization

**User Story:** As a User, I want modern and functional quick action buttons on my dashboard, so that I can efficiently navigate to key features

#### Acceptance Criteria

1. WHEN a User views their dashboard, THE Platform SHALL display quick action buttons without "coming soon" notices
2. THE Platform SHALL display Font Awesome icons on all quick action buttons
3. THE Platform SHALL apply modern styling to quick action buttons with consistent design
4. THE Platform SHALL implement quick actions for Student dashboards including view services, create service, view orders, and view earnings
5. THE Platform SHALL implement quick actions for Client dashboards including browse services, view orders, and view messages
6. THE Platform SHALL implement quick actions for Admin dashboards including manage users, manage services, and view reports
