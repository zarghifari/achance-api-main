#!/bin/bash

echo "=== TESTING COURSE CACHE INVALIDATION ==="

# Get token
echo "Getting auth token..."
LOGIN_RESPONSE=$(curl -s -X POST "http://nginx-lb/api/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"teacher@example.com","password":"password"}')

TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "FAILED to get token"
  echo "Response: $LOGIN_RESPONSE"
  exit 1
fi

echo "SUCCESS: Got token"

# Test 1: Get courses before
echo ""
echo "=== STEP 1: Get all courses BEFORE creating new one ==="
BEFORE_RESPONSE=$(curl -s -X GET "http://nginx-lb/api/courses/all" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

BEFORE_COUNT=$(echo "$BEFORE_RESPONSE" | grep -o '"id":[0-9]*' | wc -l)
echo "Found $BEFORE_COUNT courses before creation"
echo "Response: $BEFORE_RESPONSE"

# Test 2: Create new course
echo ""
echo "=== STEP 2: Create new course ==="
TIMESTAMP=$(date +%s)
CREATE_RESPONSE=$(curl -s -X POST "http://nginx-lb/api/courses" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Cache Test Course",
    "slug": "cache-test-'$TIMESTAMP'",
    "description": "Testing cache invalidation",
    "isOpen": true,
    "total_hours": 5
  }')

NEW_COURSE_ID=$(echo "$CREATE_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$NEW_COURSE_ID" ]; then
  echo "FAILED to create course"
  echo "Response: $CREATE_RESPONSE"
  exit 1
fi

echo "SUCCESS: Created course with ID $NEW_COURSE_ID"
echo "Response: $CREATE_RESPONSE"

# Test 3: Get courses after (immediate check)
echo ""
echo "=== STEP 3: Get all courses AFTER creating new one ==="
AFTER_RESPONSE=$(curl -s -X GET "http://nginx-lb/api/courses/all" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

AFTER_COUNT=$(echo "$AFTER_RESPONSE" | grep -o '"id":[0-9]*' | wc -l)
echo "Found $AFTER_COUNT courses after creation"

# Check if new course is in the list
HAS_NEW_COURSE=$(echo "$AFTER_RESPONSE" | grep "\"id\":$NEW_COURSE_ID" | wc -l)

echo ""
echo "=== RESULTS ==="
echo "Courses before: $BEFORE_COUNT"
echo "Courses after:  $AFTER_COUNT"
echo "New course in list: $HAS_NEW_COURSE"

if [ "$HAS_NEW_COURSE" -eq 1 ]; then
  echo "✅ SUCCESS: Cache invalidation WORKING - new course found in list!"
else
  echo "❌ FAILED: Cache invalidation NOT working - new course missing!"
  echo "Full response: $AFTER_RESPONSE"
fi

# Clean up
echo ""
echo "=== CLEANUP ==="
curl -s -X DELETE "http://nginx-lb/api/courses/$NEW_COURSE_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" > /dev/null

echo "Cleanup done."