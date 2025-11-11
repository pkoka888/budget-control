#!/bin/bash

echo "🎯 Async Bank Import API Test"
echo ""

# Create a test user
TEST_EMAIL="async-test-$(date +%s)@example.com"
TEST_PASSWORD="test123"
TEST_NAME="Async Test User"

echo "📝 Step 1: Register user..."
curl -s -X POST http://localhost:8080/register \
  -d "email=$TEST_EMAIL&password=$TEST_PASSWORD&name=$TEST_NAME" \
  -c cookies.txt > /dev/null

echo "✅ Registered"
echo ""

# Login
echo "🔐 Step 2: Login..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8080/login \
  -d "email=$TEST_EMAIL&password=$TEST_PASSWORD" \
  -b cookies.txt \
  -c cookies.txt)

echo "✅ Logged in"
echo ""

# Trigger import
echo "🔄 Step 3: Trigger async bank import..."
IMPORT_RESPONSE=$(curl -s -X POST http://localhost:8080/bank-import/auto-import \
  -b cookies.txt \
  -H "Content-Type: application/x-www-form-urlencoded")

echo "📊 API Response:"
echo "$IMPORT_RESPONSE" | jq . 2>/dev/null || echo "$IMPORT_RESPONSE"
echo ""

# Extract job ID
JOB_ID=$(echo "$IMPORT_RESPONSE" | jq -r '.job_id' 2>/dev/null)

if [ -z "$JOB_ID" ] || [ "$JOB_ID" == "null" ]; then
  echo "❌ No job_id in response"
  exit 1
fi

echo "📦 Job ID: $JOB_ID"
echo ""

# Poll job status
echo "🔍 Step 4: Check job status (polling)..."
for i in {1..20}; do
  STATUS_RESPONSE=$(curl -s "http://localhost:8080/bank-import/job-status?job_id=$JOB_ID" \
    -b cookies.txt)

  STATUS=$(echo "$STATUS_RESPONSE" | jq -r '.status' 2>/dev/null)
  PROCESSED=$(echo "$STATUS_RESPONSE" | jq -r '.progress.processed_files' 2>/dev/null)
  TOTAL=$(echo "$STATUS_RESPONSE" | jq -r '.progress.total_files' 2>/dev/null)
  IMPORTED=$(echo "$STATUS_RESPONSE" | jq -r '.progress.imported_count' 2>/dev/null)

  echo "Attempt $i: Status=$STATUS, Progress=$PROCESSED/$TOTAL, Imported=$IMPORTED"

  if [ "$STATUS" == "completed" ] || [ "$STATUS" == "failed" ]; then
    echo ""
    echo "✅ Job finished with status: $STATUS"
    echo ""
    echo "📊 Final Results:"
    echo "$STATUS_RESPONSE" | jq . 2>/dev/null || echo "$STATUS_RESPONSE"

    if [ "$STATUS" == "completed" ]; then
      echo ""
      echo "✅ Async import test completed successfully!"
      rm -f cookies.txt
      exit 0
    else
      rm -f cookies.txt
      exit 1
    fi
  fi

  if [ $i -lt 20 ]; then
    sleep 1
  fi
done

echo ""
echo "⚠️ Job did not complete within timeout"
rm -f cookies.txt
exit 1
