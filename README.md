# ChurchDesk Backend Documentation

## 1. Introduction

Welcome to the backend documentation for ChurchDesk. This document provides a comprehensive overview of the server-side application, its architecture, API endpoints, and data structures.

The backend is built using the **Laravel** framework (simulated in this environment) and is designed to be a robust, scalable, and secure foundation for the ChurchDesk platform. It handles all business logic, data persistence, and authentication.

**Technology Stack:**
-   **Framework:** Laravel (PHP)
-   **Architecture:** RESTful API
-   **Data Store:** In-memory `DataService` (simulating a database like MySQL or PostgreSQL for this demonstration).

---

## 2. Authentication

The backend uses a simulated, header-based authentication mechanism for development. All protected API endpoints require an `X-User-ID` header to be sent with the request.

-   **Header Name:** `X-User-ID`
-   **Value:** The integer `id` of the currently logged-in user.

The `AuthenticateMock` middleware intercepts incoming requests, validates the presence of this header, and attaches the corresponding user object to the request, making it available within the controllers.

**Example Request Header:**
```
GET /api/requisitions
Accept: application/json
X-User-ID: 101
```

If the `X-User-ID` header is missing or invalid for a protected route, the API will respond with a `401 Unauthorized` error.

---

## 3. Database Schema

The following tables describe the structure of the data models managed by the `DataService`.

### `users`
Stores user account information.

| Field          | Type         | Description                                        |
| -------------- | ------------ | -------------------------------------------------- |
| `id`           | `integer`    | Primary Key.                                       |
| `name`         | `string`     | User's full name.                                  |
| `email`        | `string`     | User's unique email address.                       |
| `password`     | `string`     | Hashed password.                                   |
| `role`         | `enum`       | User's role (`Role` enum).                         |
| `departmentId` | `integer`    | Foreign key to `departments`. Nullable.            |
| `sectionId`    | `integer`    | Foreign key to `sections`. Nullable.               |
| `church_id`     | `integer`    | Foreign key to `churches`.                         |

### `churches`
Stores information about each registered church.

| Field                | Type         | Description                                  |
| -------------------- | ------------ | -------------------------------------------- |
| `id`                 | `integer`    | Primary Key.                                 |
| `name`               | `string`     | Name of the church.                          |
| `subscriptionStatus` | `enum`       | `Trial`, `Active`, or `Expired`.             |
| `subscriptionEndsAt` | `string`     | ISO 8601 date string of subscription expiry. |

### `sections`
Stores the major arms or sections within a church (e.g., Youth Church, Main Church).

| Field     | Type      | Description                  |
| --------- | --------- | ---------------------------- |
| `id`      | `integer` | Primary Key.                 |
| `name`    | `string`  | Name of the section.         |
| `church_id`| `integer` | Foreign key to `churches`.   |

### `departments`
Stores departments within a section (e.g., Choir, Welfare).

| Field     | Type      | Description                |
| --------- | --------- | -------------------------- |
| `id`      | `integer` | Primary Key.               |
| `name`    | `string`  | Name of the department.    |
| `sectionId`| `integer` | Foreign key to `sections`. |

### `requisitions`
The core table for all payment requests.

| Field           | Type      | Description                                          |
| --------------- | --------- | ---------------------------------------------------- |
| `id`            | `string`  | Primary Key. Auto-generated unique ID.               |
| `title`         | `string`  | Title of the requisition.                            |
| `requestedById` | `integer` | Foreign key to `users`.                              |
| `departmentId`  | `integer` | Foreign key to `departments`.                        |
| `sectionId`     | `integer` | Foreign key to `sections`.                           |
| `church_id`      | `integer` | Foreign key to `churches`.                           |
| `amountRequested` | `float`   | The amount of money requested.                       |
| `category`      | `string`  | A user-defined category for the expense.             |
| `purpose`       | `string`  | Detailed description of the purpose.                 |
| `dateNeeded`    | `string`  | `YYYY-MM-DD` format.                                 |
| `createdAt`     | `string`  | ISO 8601 timestamp of creation.                      |
| `status`        | `enum`    | Current status from `RequisitionStatus` enum.        |
| `approvals`     | `array`   | Array of `Approval` objects.                         |
| `activityLog`   | `array`   | Array of `ActivityLog` objects.                      |
| `attachments`   | `array`   | Array of attachment file objects.                    |
| `payment`       | `object`  | `Payment` object. Nullable.                          |
| `finalReceipt`  | `object`  | `FinalReceipt` object. Nullable.                     |

---

## 4. API Endpoints

All endpoints are prefixed with `/api`.

### Authentication

#### `POST /login`
Authenticates a user and returns their data.
-   **Request Body:**
    ```json
    {
        "email": "superadmin@churchone.com",
        "password": "password123"
    }
    ```
-   **Success Response (200 OK):**
    ```json
    {
        "id": 101,
        "name": "John Doe (Admin)",
        "email": "superadmin@churchone.com",
        "role": "Super Admin",
        "departmentId": null,
        "sectionId": null,
        "church_id": 1
    }
    ```
-   **Error Response (401 Unauthorized):**
    ```json
    { "message": "Invalid credentials." }
    ```

#### `POST /register`
Registers a new church and its first super admin user.
-   **Request Body:**
    ```json
    {
      "churchName": "New Hope Chapel",
      "adminName": "Jane Smith",
      "adminEmail": "jane.smith@newhope.com",
      "adminPassword": "password123"
    }
    ```
-   **Success Response (201 Created):**
    ```json
    {
        "id": 105,
        "name": "Jane Smith",
        "email": "jane.smith@newhope.com",
        "role": "Super Admin",
        // ... other user fields
    }
    ```
-   **Error Response (400 Bad Request):**
    ```json
    { "message": "Email is already in use." }
    ```

### Requisitions

#### `GET /requisitions`
Fetches a list of all requisitions visible to the authenticated user.
-   **Headers:** `X-User-ID: <userId>`
-   **Success Response (200 OK):**
    ```json
    [
        {
            "id": "REQ-YTH-2024-001",
            "title": "Youth Conference Banners",
            "amountRequested": 50000,
            "status": "Completed",
            // ... other requisition fields
        }
    ]
    ```

#### `POST /requisitions`
Creates a new requisition.
-   **Headers:** `X-User-ID: <userId>`
-   **Request Body:**
    ```json
    {
        "title": "New Projector for Sanctuary",
        "amountRequested": 150000,
        "category": "Equipment",
        "dateNeeded": "2024-12-01",
        "purpose": "To replace the failing main projector.",
        "departmentId": 1,
        "sectionId": 1,
        "church_id": 1,
        "requestedById": 102,
        "attachments": []
    }
    ```
-   **Success Response (201 Created):** Returns the newly created requisition object.

#### `GET /requisitions/{id}`
Fetches details for a single requisition.
-   **Headers:** `X-User-ID: <userId>`
-   **Success Response (200 OK):** Returns the full requisition object.

#### `PUT /requisitions/{id}`
Updates an existing requisition.
-   **Headers:** `X-User-ID: <userId>`
-   **Request Body:** Partial requisition object with fields to update.
-   **Success Response (200 OK):** Returns the updated requisition object.

#### `POST /requisitions/{id}/action`
Processes a workflow action (Approve, Reject, etc.).
-   **Headers:** `X-User-ID: <userId>` (ID of the approver)
-   **Request Body:**
    ```json
    {
        "action": "APPROVE",
        "comments": "Looks good to go."
    }
    ```
-   **Success Response (200 OK):** Returns the updated requisition object.

#### `POST /requisitions/{id}/disburse`
Records a payment for an approved requisition.
-   **Headers:** `X-User-ID: <userId>` (ID of the finance user)
-   **Request Body:**
    ```json
    {
        "paymentDetails": {
            "amountPaid": 150000,
            "paymentMethod": "Bank Transfer",
            "paymentDate": "2024-10-28",
            "referenceNumber": "TRF12345",
            "proofFile": null
        }
    }
    ```
-   **Success Response (200 OK):** Returns the updated requisition object.

#### `POST /requisitions/{id}/upload-receipt`
Uploads the final expense receipt for a disbursed requisition.
-   **Headers:** `X-User-ID: <userId>` (ID of the original requester)
-   **Request Body:**
    ```json
    {
        "receiptFileName": "projector_receipt.pdf"
    }
    ```
-   **Success Response (200 OK):** Returns the updated requisition object.

#### `POST /requisitions/{id}/verify-receipt`
Verifies or requests correction for a final receipt.
-   **Headers:** `X-User-ID: <userId>` (ID of the finance user)
-   **Request Body:**
    ```json
    {
        "action": "VERIFY",
        "comments": "Receipt is clear and matches the amount."
    }
    ```
-   **Success Response (200 OK):** Returns the updated requisition object.

### Church & User Management

#### `GET /churches/{church_id}`
Fetches details for a specific church, including its sections and departments.
-   **Headers:** `X-User-ID: <userId>`
-   **Success Response (200 OK):** Returns the full church object.

#### `GET /churches/{church_id}/users`
Fetches a list of all users belonging to a church.
-   **Headers:** `X-User-ID: <userId>`
-   **Success Response (200 OK):** Returns an array of user objects.

#### `POST /users`
Creates a new user within the authenticated user's church.
-   **Headers:** `X-User-ID: <userId>` (Must be a Super Admin)
-   **Request Body:** User object (without `id`).
-   **Success Response (201 Created):** Returns the newly created user object.

#### `POST /churches/{church_id}/sections`
Creates a new section (e.g., Youth Arm) within a church.
-   **Headers:** `X-User-ID: <userId>` (Must be a Super Admin)
-   **Request Body:**
    ```json
    {
        "name": "Children's Section"
    }
    ```
-   **Success Response (201 Created):** Returns the newly created section object.

#### `POST /churches/{church_id}/extend-subscription`
Extends the subscription for a church. (App Owner only).
-   **Headers:** `X-User-ID: <userId>` (Must be an App Owner)
-   **Request Body:**
    ```json
    {
        "months": 12
    }
    ```
-   **Success Response (200 OK):** Returns the updated church object.

### Dashboards & Reports

#### `GET /financial-summary/{sectionId}`
Fetches the financial summary for a specific section.
-   **Headers:** `X-User-ID: <userId>`
-   **Success Response (200 OK):**
    ```json
    {
        "balance": 2500000,
        "totalInflow": 5000000,
        "totalOutflow": 2500000
    }
    ```

#### `GET /finance-overview/{sectionId}`
Fetches data for the Finance Hub dashboard.
-   **Headers:** `X-User-ID: <userId>`
-   **Success Response (200 OK):** Returns a `FinanceOverview` object.

#### `GET /churches/{church_id}/audit-logs`
Fetches all audit trail logs for a church.
-   **Headers:** `X-User-ID: <userId>`
-   **Success Response (200 OK):** Returns an array of `AuditLog` objects.

#### `GET /platform-data`
Fetches platform-wide data for the App Owner dashboard.
-   **Headers:** `X-User-ID: <userId>` (Must be an App Owner)
-   **Success Response (200 OK):** Returns a `PlatformData` object.
```

## 5. Core Services

### `DataService`
This service acts as the application's in-memory database. It holds all the data in static arrays and contains the business logic for creating, retrieving, updating, and deleting records. It mimics the behavior of an Eloquent model and repository layer in a full Laravel application.

### `EmailService`
A mock service to handle notifications. Instead of sending real emails, it logs the intended recipient, subject, and body to the Laravel log file (`storage/logs/laravel.log`). This allows for easy inspection of notification triggers during development.
