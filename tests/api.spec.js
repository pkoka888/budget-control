/**
 * API Testing Suite
 * Tests REST API endpoints and functionality
 */

const { test, expect } = require('@playwright/test');

test.describe('Budget Control - API Tests', () => {
  test('should return proper API documentation', async ({ request }) => {
    const response = await request.get('/api/v1/docs');
    expect(response.ok()).toBeTruthy();
    expect(response.headers()['content-type']).toContain('text/html');
  });

  test('should handle unauthenticated API requests properly', async ({ request }) => {
    // Test transactions endpoint
    const transactionsResponse = await request.get('/api/v1/transactions');
    expect(transactionsResponse.status()).toBe(401);

    // Test accounts endpoint
    const accountsResponse = await request.get('/api/v1/accounts');
    expect(accountsResponse.status()).toBe(401);

    // Test budgets endpoint
    const budgetsResponse = await request.get('/api/v1/budgets');
    expect(budgetsResponse.status()).toBe(401);
  });

  test('should return proper error responses for invalid endpoints', async ({ request }) => {
    const response = await request.get('/api/v1/nonexistent');
    expect(response.status()).toBe(404);

    const responseBody = await response.json();
    expect(responseBody).toHaveProperty('error');
  });

  test('should handle CORS headers properly', async ({ request }) => {
    const response = await request.get('/api/v1/docs', {
      headers: {
        'Origin': 'http://localhost:3000'
      }
    });

    // Check CORS headers
    expect(response.headers()['access-control-allow-origin']).toBeTruthy();
    expect(response.headers()['access-control-allow-methods']).toContain('GET');
  });

  test('should return JSON content type for API endpoints', async ({ request }) => {
    const response = await request.get('/api/v1/transactions');
    expect(response.headers()['content-type']).toContain('application/json');
  });

  test('should handle different HTTP methods appropriately', async ({ request }) => {
    // Test OPTIONS method (CORS preflight)
    const optionsResponse = await request.fetch('/api/v1/transactions', {
      method: 'OPTIONS'
    });
    expect(optionsResponse.status()).toBe(200);

    // Test POST method on read-only endpoint
    const postResponse = await request.post('/api/v1/transactions', {
      data: {}
    });
    expect(postResponse.status()).toBe(401); // Should fail auth, not method not allowed
  });

  test('should validate API response structure', async ({ request }) => {
    const response = await request.get('/api/v1/transactions');
    const responseBody = await response.json();

    // Should have error structure for unauthenticated requests
    expect(responseBody).toHaveProperty('error');
    expect(typeof responseBody.error).toBe('string');
  });

  test('should handle query parameters correctly', async ({ request }) => {
    const response = await request.get('/api/v1/transactions?page=1&limit=10');
    expect(response.status()).toBe(401); // Auth required, but should parse params

    // Check if the endpoint accepts query parameters by examining the URL
    // This is more of a documentation test
    expect(response.url()).toContain('page=1');
    expect(response.url()).toContain('limit=10');
  });

  test('should have reasonable response times', async ({ request }) => {
    const startTime = Date.now();
    const response = await request.get('/api/v1/transactions');
    const endTime = Date.now();

    const responseTime = endTime - startTime;
    expect(responseTime).toBeLessThan(5000); // Should respond within 5 seconds
  });

  test('should handle malformed JSON gracefully', async ({ request }) => {
    const response = await request.post('/api/v1/transactions', {
      data: '{invalid json',
      headers: {
        'Content-Type': 'application/json'
      }
    });

    // Should return a proper error response
    expect(response.status()).toBeGreaterThanOrEqual(400);
  });
});
