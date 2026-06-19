# QuickFix Nearby - Detailed PHP Schema

This document outlines the current PHP structure and proposes a detailed PHP schema for the QuickFix Nearby project, incorporating planned database integrations and existing business logic. The project aims to connect customers with verified service professionals, with a focus on scalability and maintainability.

## 1. Current PHP Structure and Data Handling

The current QuickFix Nearby application primarily uses `index.php` for server-side rendering and form processing, complemented by `config.php` for environment-specific configurations.

### 1.1. `config.php`

This file defines constants and returns an associative array for application-wide settings. It includes placeholders for future database connectivity and email configurations.

**Key Configuration Parameters:**

| Parameter       | Description                                                                 | Default Value (if not set via environment) |
| :-------------- | :-------------------------------------------------------------------------- | :----------------------------------------- |
| `DB_HOST`       | Database host                                                               | `localhost`                                |
| `DB_USER`       | Database username                                                           | `root`                                     |\n| `DB_PASS`       | Database password                                                           | `''` (empty string)                        |
| `DB_NAME`       | Database name                                                               | `quickfix`                                 |
| `MAIL_FROM`     | Sender email address for system notifications                               | `noreply@quickfix.ng`                      |
| `MAIL_ADMIN`    | Administrator email address for form submissions and critical alerts        | `admin@quickfix.ng`                        |
| `API_BASE_URL`  | Base URL for external API integrations                                      | `https://api.quickfix.ng`                  |
| `SESSION_TIMEOUT` | User session timeout in seconds                                             | `3600` (1 hour)                            |
| `MAX_FORM_SIZE` | Maximum allowed size for form submissions in bytes                          | `10485760` (10MB)                          |
| `ENABLE_ANALYTICS` | Feature flag for analytics                                                  | `true`                                     |
| `ENABLE_NOTIFICATIONS` | Feature flag for notifications                                              | `false`                                    |

### 1.2. `index.php`

This is the main entry point for the application, handling HTTP requests, rendering the user interface, and processing contact form submissions. It incorporates security measures and basic input validation.

**Key Modules and Logic:**

*   **Environment Setup**: Sets `APP_ENV`, `APP_NAME`, `APP_VERSION`, and error reporting based on the environment variable `APP_ENV`.
*   **Security Headers**: Configures HTTP security headers (`Content-Type`, `X-UA-Compatible`, `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`).
*   **Session Management**: Initializes PHP sessions and generates a CSRF token to protect against cross-site request forgery attacks.
*   **Utility Functions**:
    *   `sanitize_input($data)`: Cleans user input by stripping tags, trimming whitespace, and converting special characters to HTML entities.
    *   `validate_email($email)`: Validates email addresses using `filter_var`.
    *   `log_activity($message, $level)`: Logs application activities, primarily to `error_log` in non-production environments.
*   **Form Processing**: Handles `POST` requests from the contact form. It performs:
    *   CSRF token verification.
    *   Input sanitization using `sanitize_input()`.
    *   Validation for `name` (min 2 chars), `email` (valid format), `interest_type` (not empty), and `message` (min 10 chars).
    *   If validation passes, it constructs an email body and subject, and is set up to send an email to `MAIL_ADMIN` (currently commented out for production).
    *   Clears form data on successful submission.
*   **Frontend Rendering**: Dynamically embeds PHP variables (e.g., `APP_NAME`, `APP_VERSION`, CSRF token) into the HTML structure, which is built with TailwindCSS for styling.

**Current Data Flow (Contact Form):**

User Input (HTML Form) -> `index.php` (POST Request) -> CSRF Check -> Input Sanitization -> Input Validation -> Email Construction -> (Planned: Email Sending to `MAIL_ADMIN`) -> Success/Error Message.

## 2. Proposed PHP Data Models

Based on the `DEPLOYMENT.md` file, the project anticipates integrating a database with three primary tables: `customers`, `service_requests`, and `professionals`. These can be conceptualized as PHP data models (or classes) for object-oriented interaction with the database.

### 2.1. `Customer` Model

Represents a user who requests services.

| Property   | Type      | Description                               | Constraints/Notes                                |
| :--------- | :-------- | :---------------------------------------- | :----------------------------------------------- |
| `id`       | `int`     | Unique identifier for the customer        | Primary Key, Auto-Increment                      |
| `name`     | `string`  | Full name of the customer                 | Not Null, Max 255 chars                          |
| `email`    | `string`  | Email address of the customer             | Not Null, Unique, Max 255 chars                  |
| `phone`    | `string`  | Phone number of the customer              | Optional, Max 20 chars                           |
| `location` | `string`  | General location of the customer          | Optional, Max 255 chars                          |
| `created_at` | `DateTime` | Timestamp of customer creation            | Default: Current Timestamp                       |
| `updated_at` | `DateTime` | Timestamp of last update to customer record | Default: Current Timestamp, On Update: Current Timestamp |

### 2.2. `ServiceRequest` Model

Represents a request made by a customer for a specific service.

| Property      | Type        | Description                               | Constraints/Notes                                                               |
| :------------ | :---------- | :---------------------------------------- | :------------------------------------------------------------------------------ |
| `id`          | `int`       | Unique identifier for the service request | Primary Key, Auto-Increment                                                     |
| `customer_id` | `int`       | Foreign key referencing the customer      | Not Null, Foreign Key to `Customer.id`                                          |
| `service_type` | `string`    | Type of service requested                 | Not Null, e.g., 'Electrician', 'Plumber', 'Mechanic'                            |
| `description` | `string`    | Detailed description of the request       | Optional, Text type                                                             |
| `location`    | `string`    | Location where service is needed          | Optional, Max 255 chars                                                         |
| `urgency`     | `enum`      | Urgency level of the request              | Default: 'medium', Values: 'low', 'medium', 'high'                              |
| `status`      | `enum`      | Current status of the service request     | Default: 'pending', Values: 'pending', 'assigned', 'in_progress', 'completed', 'cancelled' |
| `created_at`  | `DateTime`  | Timestamp of request creation             | Default: Current Timestamp                                                      |

### 2.3. `Professional` Model

Represents a verified service provider.

| Property    | Type        | Description                               | Constraints/Notes                                |
| :---------- | :---------- | :---------------------------------------- | :----------------------------------------------- |
| `id`        | `int`       | Unique identifier for the professional    | Primary Key, Auto-Increment                      |
| `name`      | `string`    | Full name of the professional             | Not Null, Max 255 chars                          |
| `email`     | `string`    | Email address of the professional         | Not Null, Unique, Max 255 chars                  |
| `phone`     | `string`    | Phone number of the professional          | Not Null, Max 20 chars                           |
| `service_type` | `string`    | Primary service offered by professional   | Not Null, Max 100 chars                          |
| `verified`  | `boolean`   | Indicates if professional is verified     | Default: `FALSE`                                 |
| `rating`    | `decimal`   | Average rating of the professional        | Default: `0.00`, Precision: 3, Scale: 2          |
| `total_jobs` | `int`       | Total number of jobs completed            | Default: `0`                                     |
| `created_at` | `DateTime`  | Timestamp of professional registration    | Default: Current Timestamp                       |

## 3. Relationships Between Models

The primary relationship identified is between `Customer` and `ServiceRequest`:

*   **One-to-Many**: One `Customer` can make multiple `ServiceRequest`s. Each `ServiceRequest` belongs to exactly one `Customer`.
    *   This is enforced by the `customer_id` foreign key in the `service_requests` table, referencing the `id` in the `customers` table.

Future relationships could include:

*   **Many-to-Many**: Between `Professional` and `ServiceRequest` (a professional can be assigned to many requests, and a request might be handled by multiple professionals or a team). This would likely require an intermediary `assignment` or `job` table.
*   **One-to-Many**: Between `Professional` and `ServiceCategory` (if `service_type` becomes a separate entity).

## 4. Integration with Existing Business Logic

The current `index.php` handles contact form submissions. Once the database is integrated, this logic would need to be extended:

*   **Contact Form Submission**: Instead of (or in addition to) sending an email, the form data could be used to:
    1.  **Create a new `Customer` record**: If the email address is new, a new customer entry could be created. If existing, the existing customer record could be retrieved.
    2.  **Create a new `ServiceRequest` record**: The `interest_type` from the form could map to `service_type`, and the `message` to `description`. The `customer_id` would link to the newly created or retrieved customer.
*   **Input Validation**: The existing `sanitize_input` and `validate_email` functions would be crucial for preparing data before insertion into the database, preventing SQL injection and other vulnerabilities.
*   **Error Handling and Logging**: The `log_activity` function would be used to record database operations, errors, and successful transactions.

## 5. Future Enhancements and Considerations

The `README.md` and `DEPLOYMENT.md` files highlight several future enhancements that will build upon this schema:

*   **User Authentication System**: This would require additional models for `User` (if different from `Customer` or `Professional`), `Role`, and potentially `Session` or `Token` management.
*   **Payment Gateway Integration**: This would involve models for `Transaction`, `Invoice`, and `PaymentMethod`.
*   **Real-time Notifications**: Could leverage a `Notification` model linked to `Customer` and `Professional`.
*   **Admin Controls**: A separate set of models and interfaces for managing `Customer`, `Professional`, and `ServiceRequest` entities, potentially with `AdminUser` roles.
*   **Service Categories**: The `service_type` fields could evolve into a dedicated `ServiceCategory` model for better management and scalability.

This schema provides a foundational understanding for developing the QuickFix Nearby application with a robust and scalable backend.

## 6. User Authentication Schema

To support the registration and login functionality, a unified `users` table is proposed. This table will handle authentication for all user types (customers, professionals, and admins).

### 6.1. `users` Table

| Column      | Type        | Description                               | Constraints/Notes                                |
| :---------- | :---------- | :---------------------------------------- | :----------------------------------------------- |
| `id`        | `INT`       | Unique identifier for the user            | Primary Key, Auto-Increment                      |
| `name`      | `VARCHAR(255)` | Full name of the user                     | Not Null                                         |
| `email`     | `VARCHAR(255)` | Email address (used for login)            | Not Null, Unique                                 |
| `password`  | `VARCHAR(255)` | Hashed password                           | Not Null (use `password_hash()`)                 |
| `role`      | `ENUM`      | User role                                 | 'customer', 'professional', 'admin'              |
| `created_at` | `TIMESTAMP` | Record creation time                      | Default: CURRENT_TIMESTAMP                       |
| `updated_at` | `TIMESTAMP` | Record last update time                   | Default: CURRENT_TIMESTAMP ON UPDATE             |

### 6.2. Authentication Flow

1.  **Registration**:
    *   Collect name, email, password, and role.
    *   Validate email uniqueness.
    *   Hash password using `PASSWORD_DEFAULT`.
    *   Insert into `users` table.
2.  **Login**:
    *   Collect email and password.
    *   Retrieve user record by email.
    *   Verify password using `password_verify()`.
    *   Store user ID and role in `$_SESSION`.
3.  **Session Management**:
    *   Use `session_start()` on protected pages.
    *   Check `$_SESSION['user_id']` for authentication status.

## 7. Google OAuth Integration

To support Google Sign-In, the `users` table needs additional columns to store Google-specific identifiers.

### 7.1. Updated `users` Table (OAuth Columns)

| Column            | Type           | Description                               | Constraints/Notes                                |
| :---------------- | :------------- | :---------------------------------------- | :----------------------------------------------- |
| `google_id`       | `VARCHAR(255)` | Unique ID from Google                     | Unique, Nullable (for non-Google users)          |
| `profile_picture` | `VARCHAR(255)` | URL to Google profile picture             | Nullable                                         |

### 7.2. OAuth Configuration

New configuration constants in `config.php`:
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URL`
