# Budget Control API Documentation

## Overview

The Budget Control API provides RESTful endpoints for managing personal finance data including transactions, accounts, budgets, and financial reports. The API uses JSON for request/response formats and requires authentication via API keys.

## Base URL

```
https://your-domain.com/api/v1/
```

## Authentication

All API requests require authentication using an API key. Include the API key in one of the following ways:

### Header Authentication
```
X-API-Key: your-api-key-here
```
or
```
Authorization: Bearer your-api-key-here
```

### API Key Management

API keys are generated per user and include rate limiting information. Each key has:
- Read permissions (default)
- Write permissions (for create/update/delete operations)

## Rate Limiting

- **Limit**: 1000 requests per hour per API key
- **Headers**: Response includes rate limit information
  - `X-Rate-Limit-Remaining`: Requests remaining in current hour
  - `X-Rate-Limit-Limit`: Total requests allowed per hour
  - `X-Rate-Limit-Reset`: Unix timestamp when limit resets

## Response Format

### Success Response
```json
{
  "status": "success",
  "data": {
    // Response data
  }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description",
  "code": "ERROR_CODE"
}
```

## Error Codes

| Code | Description |
|------|-------------|
| `400` | Bad Request - Invalid input data |
| `401` | Unauthorized - Authentication required |
| `403` | Forbidden - Insufficient permissions |
| `404` | Not Found - Resource doesn't exist |
| `429` | Too Many Requests - Rate limit exceeded |
| `500` | Internal Server Error |

---

## Endpoints

### Transactions

#### GET /api/v1/transactions

List transactions with optional filtering.

**Parameters:**
- `limit` (optional): Number of transactions to return (default: 50, max: 100)
- `offset` (optional): Number of transactions to skip (default: 0)
- `account_id` (optional): Filter by account ID
- `start_date` (optional): Filter transactions from this date (YYYY-MM-DD)
- `end_date` (optional): Filter transactions until this date (YYYY-MM-DD)

**Example Request:**
```bash
GET /api/v1/transactions?limit=10&start_date=2024-01-01&end_date=2024-01-31
X-API-Key: your-api-key
```

**Example Response:**
```json
{
  "transactions": [
    {
      "id": 1,
      "user_id": 1,
      "account_id": 1,
      "category_id": 2,
      "type": "expense",
      "description": "Grocery shopping",
      "amount": 150.50,
      "currency": "CZK",
      "date": "2024-01-15",
      "notes": null,
      "account_name": "Main Account",
      "category_name": "Food"
    }
  ]
}
```

#### GET /api/v1/transactions/{id}

Get details of a specific transaction.

**Example Request:**
```bash
GET /api/v1/transactions/123
X-API-Key: your-api-key
```

**Example Response:**
```json
{
  "transaction": {
    "id": 123,
    "user_id": 1,
    "account_id": 1,
    "category_id": 2,
    "type": "expense",
    "description": "Coffee",
    "amount": 45.00,
    "currency": "CZK",
    "date": "2024-01-15",
    "notes": "Morning coffee",
    "account_name": "Main Account",
    "category_name": "Food"
  }
}
```

#### POST /api/v1/transactions

Create a new transaction.

**Required Fields:**
- `account_id`: Account ID
- `type`: "income" or "expense"
- `description`: Transaction description
- `amount`: Transaction amount
- `date`: Transaction date (YYYY-MM-DD)

**Optional Fields:**
- `category_id`: Category ID
- `currency`: Currency code (default: "CZK")
- `notes`: Additional notes

**Example Request:**
```bash
POST /api/v1/transactions
Content-Type: application/json
X-API-Key: your-api-key

{
  "account_id": 1,
  "type": "expense",
  "description": "Lunch",
  "amount": 120.00,
  "date": "2024-01-15",
  "category_id": 2,
  "notes": "Business lunch"
}
```

**Example Response:**
```json
{
  "transaction_id": 124,
  "message": "Transaction created"
}
```

#### PUT /api/v1/transactions/{id}

Update an existing transaction.

**Example Request:**
```bash
PUT /api/v1/transactions/124
Content-Type: application/json
X-API-Key: your-api-key

{
  "amount": 150.00,
  "description": "Updated lunch expense"
}
```

**Example Response:**
```json
{
  "message": "Transaction updated"
}
```

#### DELETE /api/v1/transactions/{id}

Delete a transaction.

**Example Request:**
```bash
DELETE /api/v1/transactions/124
X-API-Key: your-api-key
```

**Example Response:**
```json
{
  "message": "Transaction deleted"
}
```

### Accounts

#### GET /api/v1/accounts

List user accounts.

**Parameters:**
- `type` (optional): Filter by account type ("checking", "savings", "credit")

**Example Request:**
```bash
GET /api/v1/accounts?type=checking
X-API-Key: your-api-key
```

**Example Response:**
```json
{
  "accounts": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Main Checking",
      "type": "checking",
      "currency": "CZK",
      "balance": 5000.00,
      "initial_balance": 0.00,
      "opening_date": "2024-01-01",
      "description": "Primary account"
    }
  ]
}
```

#### GET /api/v1/accounts/{id}

Get account details.

**Example Request:**
```bash
GET /api/v1/accounts/1
X-API-Key: your-api-key
```

#### POST /api/v1/accounts

Create a new account.

**Required Fields:**
- `name`: Account name
- `type`: Account type

**Optional Fields:**
- `currency`: Currency code (default: "CZK")
- `balance`: Current balance
- `initial_balance`: Initial balance
- `opening_date`: Opening date
- `description`: Account description

**Example Request:**
```bash
POST /api/v1/accounts
Content-Type: application/json
X-API-Key: your-api-key

{
  "name": "Savings Account",
  "type": "savings",
  "currency": "CZK",
  "balance": 10000.00,
  "description": "Emergency savings"
}
```

#### PUT /api/v1/accounts/{id}

Update an account.

**Example Request:**
```bash
PUT /api/v1/accounts/2
Content-Type: application/json
X-API-Key: your-api-key

{
  "balance": 12000.00,
  "description": "Updated savings account"
}
```

#### DELETE /api/v1/accounts/{id}

Delete an account (only if no transactions exist).

**Example Request:**
```bash
DELETE /api/v1/accounts/2
X-API-Key: your-api-key
```

### Budgets

#### GET /api/v1/budgets

List budgets for a specific month.

**Parameters:**
- `month` (optional): Month in YYYY-MM format (default: current month)

**Example Request:**
```bash
GET /api/v1/budgets?month=2024-01
X-API-Key: your-api-key
```

**Example Response:**
```json
{
  "budgets": [
    {
      "id": 1,
      "user_id": 1,
      "category_id": 2,
      "month": "2024-01",
      "amount": 2000.00,
      "notes": "Food budget",
      "category_name": "Food"
    }
  ]
}
```

#### GET /api/v1/budgets/{id}

Get budget details.

#### POST /api/v1/budgets

Create a new budget.

**Required Fields:**
- `category_id`: Category ID
- `month`: Month in YYYY-MM format
- `amount`: Budget amount

**Optional Fields:**
- `notes`: Budget notes

**Example Request:**
```bash
POST /api/v1/budgets
Content-Type: application/json
X-API-Key: your-api-key

{
  "category_id": 3,
  "month": "2024-02",
  "amount": 1500.00,
  "notes": "Transportation budget"
}
```

#### PUT /api/v1/budgets/{id}

Update a budget.

#### DELETE /api/v1/budgets/{id}

Delete a budget.

### Reports

#### GET /api/v1/reports/summary

Get financial summary for a month.

**Parameters:**
- `month` (optional): Month in YYYY-MM format (default: current month)

**Example Request:**
```bash
GET /api/v1/reports/summary?month=2024-01
X-API-Key: your-api-key
```

**Example Response:**
```json
{
  "summary": {
    "month": "2024-01",
    "income": 50000.00,
    "expenses": 35000.00,
    "net": 15000.00
  },
  "budget_vs_actual": [
    {
      "category": "Food",
      "budgeted": 2000.00,
      "spent": 1850.00
    }
  ]
}
```

#### GET /api/v1/reports/transactions

Get transaction reports grouped by category, account, or date.

**Parameters:**
- `start_date` (optional): Start date (default: first day of current month)
- `end_date` (optional): End date (default: last day of current month)
- `group_by` (optional): Group by "category", "account", or "date" (default: "category")

**Example Request:**
```bash
GET /api/v1/reports/transactions?start_date=2024-01-01&end_date=2024-01-31&group_by=category
X-API-Key: your-api-key
```

#### GET /api/v1/reports/budgets

Get budget performance report.

**Parameters:**
- `month` (optional): Month in YYYY-MM format (default: current month)

**Example Request:**
```bash
GET /api/v1/reports/budgets?month=2024-01
X-API-Key: your-api-key
```

### Analytics

#### GET /api/v1/analytics/{period}

Get financial analytics data.

**Parameters:**
- `period`: Time period ("30days", "90days", "1year")

**Example Request:**
```bash
GET /api/v1/analytics/30days
X-API-Key: your-api-key
```

**Example Response:**
```json
{
  "trend": [
    {
      "date": "2024-01-01",
      "expenses": 150.00,
      "income": 5000.00
    }
  ],
  "summary": {
    "total_income": 150000.00,
    "total_expenses": 105000.00,
    "net_amount": 45000.00
  },
  "anomalies": [],
  "healthScore": 75
}
```

### API Documentation

#### GET /api/v1/docs

Get API documentation (this endpoint).

**Parameters:**
- `version` (optional): API version (default: "v1")

---

## Data Types

### Transaction Types
- `income`: Money received
- `expense`: Money spent

### Account Types
- `checking`: Checking account
- `savings`: Savings account
- `credit`: Credit card account

### Currency Codes
- `CZK`: Czech Koruna (default)
- Other ISO currency codes supported

---

## SDKs and Libraries

Currently, no official SDKs are available. You can integrate with the API using any HTTP client library in your preferred programming language.

### Example: JavaScript (Node.js)

```javascript
const axios = require('axios');

const api = axios.create({
  baseURL: 'https://your-domain.com/api/v1/',
  headers: {
    'X-API-Key': 'your-api-key',
    'Content-Type': 'application/json'
  }
});

// Get transactions
const transactions = await api.get('/transactions');
console.log(transactions.data);
```

### Example: Python

```python
import requests

headers = {
    'X-API-Key': 'your-api-key',
    'Content-Type': 'application/json'
}

# Get transactions
response = requests.get('https://your-domain.com/api/v1/transactions', headers=headers)
transactions = response.json()
print(transactions)
```

---

## Changelog

### Version 1.0
- Initial release
- Basic CRUD operations for transactions, accounts, and budgets
- Financial reports and analytics
- API key authentication
- Rate limiting

---

## Support

For API support or questions:
- Check this documentation first
- Review error messages for specific guidance
- Contact the development team if issues persist