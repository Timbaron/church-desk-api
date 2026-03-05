# ChurchDesk API Documentation

This API follow a standardized response pattern for all endpoints.

### Standard Response Format
```json
{
    "status": boolean,
    "message": "A descriptive message",
    "data": object|array|null
}
```

---

## 1. Authentication

### Login
- **Endpoint**: `POST /api/login`
- **Auth**: None
- **Payload**:
```json
{
    "email": "user@church.com",
    "password": "password"
}
```
- **Success Response (200)**:
```json
{
    "status": true,
    "message": "Login successful.",
    "data": {
        "user": { "id": "...", "name": "...", "role": "..." },
        "token": "sanctum_plain_text_token"
    }
}
```

### Register (New Church)
- **Endpoint**: `POST /api/register`
- **Auth**: None (Creates church + Admin user)
- **Payload**:
```json
{
    "churchName": "Grace Cathedral",
    "adminName": "John Doe",
    "adminEmail": "admin@grace.com",
    "adminPassword": "secure_password"
}
```
- **Success Response (201)**:
```json
{
    "status": true,
    "message": "Church registered successfully.",
    "data": {
        "user": { ... },
        "token": "..."
    }
}
```

### Logout
- **Endpoint**: `POST /api/logout`
- **Auth**: `Sanctum Token`
- **Success Response (200)**:
```json
{
    "status": true,
    "message": "Successfully logged out.",
    "data": null
}
```

---

## 2. Requisitions

### List Requisitions
- **Endpoint**: `GET /api/requisitions`
- **Auth**: `Sanctum Token`
- **Filters**: Auto-filtered by user's role and visibility.
- **Success Response (200)**:
```json
{
    "status": true,
    "message": "Requisitions retrieved successfully.",
    "data": [
        { "id": "...", "title": "...", "status": "Pending", ... }
    ]
}
```

### Create Requisition
- **Endpoint**: `POST /api/requisitions`
- **Auth**: `Sanctum Token`
- **Payload**:
```json
{
    "title": "Repair Generator",
    "amount_requested": 50000,
    "category": "Maintenance",
    "purpose": "Routine generator service",
    "date_needed": "2026-03-20",
    "department_id": "uuid"
}
```

### Process Workflow Action
- **Endpoint**: `POST /api/requisitions/{requisition}/action`
- **Auth**: `Sanctum Token` (Role: Dept Head, Section President, Finance, Auditor)
- **Payload**:
```json
{
    "action": "APPROVE|REJECT|REQUEST_CHANGES",
    "comments": "Optional feedback"
}
```

### Disburse Payment
- **Endpoint**: `POST /api/requisitions/{requisition}/disburse`
- **Auth**: `Sanctum Token` (Role: Finance)
- **Payload**:
```json
{
    "paymentDetails": {
        "amount_paid": 50000,
        "payment_method": "Bank Transfer",
        "payment_date": "2026-03-05",
        "reference_number": "REF123"
    }
}
```

### Upload Receipt
- **Endpoint**: `POST /api/requisitions/{requisition}/upload-receipt`
- **Auth**: `Sanctum Token` (Owner)
- **Payload**:
```json
{
    "receiptFileName": "receipt_image.jpg"
}
```

### Verify Receipt
- **Endpoint**: `POST /api/requisitions/{requisition}/verify-receipt`
- **Auth**: `Sanctum Token` (Role: Auditor, Finance)
- **Payload**:
```json
{
    "action": "VERIFY|REJECT",
    "comments": "Feedback"
}
```

---

## 3. Church & User Management

### Create User
- **Endpoint**: `POST /api/users`
- **Auth**: `Sanctum Token` (Role: Super Admin, App Owner)
- **Payload**:
```json
{
    "name": "Jane Doe",
    "email": "jane@church.com",
    "password": "password",
    "role": "Member|Department Head|Section President|Finance|Auditor",
    "section_id": "uuid_optional",
    "department_id": "uuid_optional"
}
```

### Extend Subscription
- **Endpoint**: `POST /api/churches/{church}/extend-subscription`
- **Auth**: `Sanctum Token` (Role: Super Admin, App Owner)
- **Payload**: `{ "months": 12 }`

---

## 4. Audit & Reports

### Global Audit Logs
- **Endpoint**: `GET /api/audit-logs`
- **Auth**: `Sanctum Token` (Role: Auditor, Super Admin, App Owner)

### Financial Summary
- **Endpoint**: `GET /api/financial-summary/{section_id}`
- **Auth**: `Sanctum Token`
- **Response**: Summary of requested vs disbursed funds for a section.

### Platform Data
- **Endpoint**: `GET /api/platform-data`
- **Auth**: `Sanctum Token` (Role: App Owner, Super Admin)
- **Response**: Aggregated platform-wide metrics.
