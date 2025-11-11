#!/bin/bash

TEST_EMAIL="status-test-$(date +%s)@example.com"
TEST_PASSWORD="test123"
TEST_NAME="Status Test User"

echo "🔐 Registering user..."
curl -s -X POST http://localhost:8080/register \
  -d "email=$TEST_EMAIL&password=$TEST_PASSWORD&name=$TEST_NAME" \
  -c cookies.txt > /dev/null

echo "✅ Registered"

echo "🔐 Logging in..."
curl -s -X POST http://localhost:8080/login \
  -d "email=$TEST_EMAIL&password=$TEST_PASSWORD" \
  -b cookies.txt \
  -c cookies.txt > /dev/null

echo "✅ Logged in"

echo ""
echo "📊 Testing API response (HTTP status + body):"
RESPONSE=$(curl -i -s -X POST http://localhost:8080/bank-import/auto-import \
  -b cookies.txt \
  -H "Content-Type: application/x-www-form-urlencoded")

echo "$RESPONSE"

rm -f cookies.txt
