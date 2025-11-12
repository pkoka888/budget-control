# Budget Control API Documentation

## Overview

The Budget Control API provides programmatic access to all features of the budget management system. This RESTful API uses JSON for request and response bodies.

**Base URL**: `https://your-domain.com/api/v1`

**Authentication**: All API requests require authentication using an API key passed in the `X-API-Key` header.

## Authentication

### Obtaining an API Key

1. Log in to your Budget Control account
2. Navigate to Settings → API Keys
3. Click "Generate New API Key"
4. Save your API key securely (it will only be shown once)

### Using Your API Key

Include your API key in the header of all requests:

```bash
curl -H "X-API-Key: your_api_key_here" \
     https://your-domain.com/api/v1/transactions
```

## Rate Limiting

- **Default**: 1,000 requests per hour
- **Headers**: Rate limit information is returned in response headers:
  - `X-RateLimit-Limit`: Total requests allowed
  - `X-RateLimit-Remaining`: Requests remaining
  - `X-RateLimit-Reset`: Timestamp when limit resets

## Endpoints

### Transactions

#### List Transactions

```http
GET /api/v1/transactions
```

**Query Parameters**:
- `start_date` (optional): Filter transactions from this date (YYYY-MM-DD)
- `end_date` (optional): Filter transactions until this date (YYYY-MM-DD)
- `type` (optional): Filter by type (`income`, `expense`, `transfer`)
- `category` (optional): Filter by category name
- `limit` (optional): Number of results (default: 100, max: 1000)
- `offset` (optional): Pagination offset (default: 0)

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "account_id": 1,
      "type": "expense",
      "amount": 1250.50,
      "currency": "CZK",
      "description": "Grocery shopping",
      "category": "Food",
      "date": "2025-11-12",
      "created_at": "2025-11-12T10:30:00Z"
    }
  ],
  "meta": {
    "total": 156,
    "limit": 100,
    "offset": 0
  }
}
```

#### Get Single Transaction

```http
GET /api/v1/transactions/{id}
```

**Response**:
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "account_id": 1,
    "type": "expense",
    "amount": 1250.50,
    "currency": "CZK",
    "description": "Grocery shopping",
    "category": "Food",
    "date": "2025-11-12",
    "receipt_scan_id": null,
    "notes": null,
    "created_at": "2025-11-12T10:30:00Z",
    "updated_at": "2025-11-12T10:30:00Z"
  }
}
```

#### Create Transaction

```http
POST /api/v1/transactions
```

**Request Body**:
```json
{
  "account_id": 1,
  "type": "expense",
  "amount": 1250.50,
  "currency": "CZK",
  "description": "Grocery shopping",
  "category": "Food",
  "date": "2025-11-12",
  "notes": "Weekly shopping"
}
```

**Response**: `201 Created`
```json
{
  "data": {
    "id": 157,
    "user_id": 1,
    "account_id": 1,
    "type": "expense",
    "amount": 1250.50,
    "currency": "CZK",
    "description": "Grocery shopping",
    "category": "Food",
    "date": "2025-11-12",
    "created_at": "2025-11-12T14:30:00Z"
  }
}
```

### Budgets

#### List Budgets

```http
GET /api/v1/budgets
```

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "category": "Food",
      "amount": 15000,
      "currency": "CZK",
      "period": "monthly",
      "spent": 8750.50,
      "remaining": 6249.50,
      "percentage": 58.34
    }
  ]
}
```

### Goals

#### List Goals

```http
GET /api/v1/goals
```

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Emergency Fund",
      "target_amount": 100000,
      "current_amount": 45000,
      "currency": "CZK",
      "deadline": "2026-12-31",
      "status": "in_progress",
      "progress_percentage": 45.0
    }
  ]
}
```

### Investments

#### Get Portfolio Summary

```http
GET /api/v1/investments
```

**Response**:
```json
{
  "data": {
    "total_value": 250000.00,
    "total_cost": 200000.00,
    "total_gain_loss": 50000.00,
    "total_gain_loss_pct": 25.0,
    "accounts": [
      {
        "id": 1,
        "name": "Main Portfolio",
        "type": "stock",
        "balance": 250000.00,
        "holdings": [
          {
            "symbol": "AAPL",
            "quantity": 50,
            "average_buy_price": 150.00,
            "current_price": 180.00,
            "current_value": 9000.00,
            "gain_loss": 1500.00
          }
        ]
      }
    ]
  }
}
```

### AI Insights

#### Get Insights

```http
GET /api/v1/insights
```

**Query Parameters**:
- `type` (optional): Filter by insight type
- `unread` (optional): Show only unread insights (boolean)

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "insight_type": "spending_pattern",
      "title": "Spending Increasing in Transportation",
      "description": "Your spending in Transportation has increased by 35% compared to your average.",
      "severity": "warning",
      "confidence_score": 0.85,
      "is_read": false,
      "created_at": "2025-11-12T09:00:00Z"
    }
  ]
}
```

### Bills

#### List Bills

```http
GET /api/v1/bills
```

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Internet",
      "category": "utilities",
      "amount": 599.00,
      "currency": "CZK",
      "frequency": "monthly",
      "next_due_date": "2025-11-20",
      "auto_pay_enabled": true
    }
  ]
}
```

## Webhooks

### Setting Up Webhooks

Webhooks allow you to receive real-time notifications when events occur in your account.

1. Navigate to Settings → Webhooks
2. Click "Create Webhook"
3. Enter your endpoint URL
4. Select events to subscribe to
5. Save and receive a webhook secret

### Webhook Events

- `transaction.created`
- `transaction.updated`
- `budget.exceeded`
- `goal.achieved`
- `bill.due`
- `insight.generated`
- `investment.price_alert`

### Webhook Payload Example

```json
{
  "event": "transaction.created",
  "timestamp": "2025-11-12T14:30:00Z",
  "data": {
    "id": 157,
    "type": "expense",
    "amount": 1250.50,
    "description": "Grocery shopping"
  }
}
```

### Verifying Webhooks

All webhook requests include a `X-Webhook-Signature` header containing an HMAC SHA256 signature. Verify it using your webhook secret:

```php
$signature = hash_hmac('sha256', $payload, $webhookSecret);
if (hash_equals($signature, $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'])) {
    // Valid webhook
}
```

## Data Export

### Create Export Job

```http
POST /api/v1/export
```

**Request Body**:
```json
{
  "type": "transactions",
  "format": "csv",
  "start_date": "2025-01-01",
  "end_date": "2025-12-31"
}
```

**Response**: `202 Accepted`
```json
{
  "job_id": 123,
  "status": "processing",
  "download_url": null
}
```

### Check Export Status

```http
GET /api/v1/export/{job_id}
```

**Response**:
```json
{
  "job_id": 123,
  "status": "completed",
  "download_url": "https://your-domain.com/export/download/123",
  "expires_at": "2025-11-19T14:30:00Z"
}
```

## Error Responses

All errors follow this format:

```json
{
  "error": {
    "code": "invalid_request",
    "message": "The request is missing required parameter: amount",
    "field": "amount"
  }
}
```

### Common Error Codes

- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Missing or invalid API key
- `403 Forbidden`: API key lacks required permissions
- `404 Not Found`: Resource not found
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

## SDKs and Libraries

### PHP

```php
use BudgetControl\ApiClient;

$client = new ApiClient('your_api_key');
$transactions = $client->transactions()->list([
    'start_date' => '2025-01-01',
    'limit' => 50
]);
```

### Python

```python
from budget_control import BudgetControlAPI

client = BudgetControlAPI(api_key='your_api_key')
transactions = client.transactions.list(
    start_date='2025-01-01',
    limit=50
)
```

### JavaScript/Node.js

```javascript
const BudgetControl = require('budget-control-api');

const client = new BudgetControl({ apiKey: 'your_api_key' });
const transactions = await client.transactions.list({
    startDate: '2025-01-01',
    limit: 50
});
```

## Support

- **Documentation**: https://docs.budgetcontrol.com
- **API Status**: https://status.budgetcontrol.com
- **Support Email**: api-support@budgetcontrol.com
- **Community Forum**: https://forum.budgetcontrol.com

## Changelog

### v1.0.0 (2025-11-12)
- Initial API release
- Support for transactions, budgets, goals, accounts
- Investment portfolio endpoints
- AI insights integration
- Bill management
- Data export capabilities
- Webhook support
