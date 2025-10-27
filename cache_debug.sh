#!/bin/bash

echo "=== DEBUGGING CACHE TAGS ==="

# Get token
LOGIN_RESPONSE=$(curl -s -X POST "http://nginx-lb/api/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"teacher@example.com","password":"password"}')

TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

echo "Got token: ${TOKEN:0:20}..."

# Create course to populate cache
echo ""
echo "Creating a course to populate cache..."
CREATE_RESPONSE=$(curl -s -X POST "http://nginx-lb/api/courses" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Cache Debug Course",
    "slug": "cache-debug-'$(date +%s)'",
    "description": "Testing cache",
    "isOpen": true,
    "total_hours": 1
  }')

NEW_COURSE_ID=$(echo "$CREATE_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo "Created course ID: $NEW_COURSE_ID"

# Test 1: Get courses to populate cache
echo ""
echo "=== GETTING COURSES TO POPULATE CACHE ==="
curl -s -X GET "http://nginx-lb/api/courses/all" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" > /dev/null

echo "Cache should now be populated"

# Test 2: Create another course 
echo ""
echo "=== CREATING ANOTHER COURSE ==="
CREATE_RESPONSE2=$(curl -s -X POST "http://nginx-lb/api/courses" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Cache Debug Course 2",
    "slug": "cache-debug-2-'$(date +%s)'",
    "description": "Testing cache 2",
    "isOpen": true,
    "total_hours": 1
  }')

NEW_COURSE_ID2=$(echo "$CREATE_RESPONSE2" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo "Created second course ID: $NEW_COURSE_ID2"

# Test 3: Check if second course appears
echo ""
echo "=== CHECKING IF SECOND COURSE APPEARS ==="
FINAL_RESPONSE=$(curl -s -X GET "http://nginx-lb/api/courses/all" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Full response:"
echo "$FINAL_RESPONSE"

HAS_FIRST=$(echo "$FINAL_RESPONSE" | grep "\"id\":$NEW_COURSE_ID" | wc -l)
HAS_SECOND=$(echo "$FINAL_RESPONSE" | grep "\"id\":$NEW_COURSE_ID2" | wc -l)

echo ""
echo "First course ($NEW_COURSE_ID) in list: $HAS_FIRST"
echo "Second course ($NEW_COURSE_ID2) in list: $HAS_SECOND"

# Cleanup
curl -s -X DELETE "http://nginx-lb/api/courses/$NEW_COURSE_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" > /dev/null

curl -s -X DELETE "http://nginx-lb/api/courses/$NEW_COURSE_ID2" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" > /dev/null

echo "Cleanup done."