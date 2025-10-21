#!/bin/bash

# Quiz API HTTP Testing Script
# This script tests the Quiz API endpoints using curl

BASE_URL="http://localhost/api"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="password"

echo "🧪 Starting HTTP Quiz API Tests..."
echo "Using base URL: $BASE_URL"
echo ""

# Function to make HTTP requests with error handling
make_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    local headers=$4
    local description=$5
    
    echo "Testing: $description"
    echo "Method: $method $endpoint"
    
    if [ -z "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" $headers)
    else
        response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" \
            -H "Content-Type: application/json" \
            $headers \
            -d "$data")
    fi
    
    # Split response body and status code
    status_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ $status_code -ge 200 ] && [ $status_code -lt 300 ]; then
        echo "✅ Success ($status_code)"
        echo "Response: $(echo $body | jq . 2>/dev/null || echo $body)"
    else
        echo "❌ Failed ($status_code)"
        echo "Response: $(echo $body | jq . 2>/dev/null || echo $body)"
    fi
    echo ""
    
    # Return response for further processing
    echo "$body"
}

# Test 1: Login to get authentication token
echo "1. Testing Authentication..."
login_data='{
    "email": "'$ADMIN_EMAIL'",
    "password": "'$ADMIN_PASSWORD'"
}'

auth_response=$(make_request "POST" "/login" "$login_data" "" "Admin Login")
auth_token=$(echo "$auth_response" | jq -r '.access_token // empty' 2>/dev/null)

if [ -z "$auth_token" ]; then
    echo "❌ Failed to get authentication token. Please check:"
    echo "   - Laravel server is running on port 80"
    echo "   - Admin user exists with email: $ADMIN_EMAIL"
    echo "   - Password is correct: $ADMIN_PASSWORD"
    exit 1
fi

echo "✅ Got authentication token: ${auth_token:0:20}..."
AUTH_HEADER="-H \"Authorization: Bearer $auth_token\""

# Test 2: Get current user
echo "2. Testing User Authentication..."
eval make_request "GET" "/user" "" "$AUTH_HEADER" "Get Current User"

# Test 3: Create a quiz
echo "3. Testing Quiz Creation..."
quiz_data='{
    "title": "HTTP Test Quiz",
    "slug": "http-test-quiz-'$(date +%s)'",
    "type": "assessment",
    "summary": "Test quiz created via HTTP API",
    "content": "This quiz tests the HTTP API functionality"
}'

quiz_response=$(eval make_request "POST" "/quizzes" "'$quiz_data'" "$AUTH_HEADER" "Create Quiz")
quiz_id=$(echo "$quiz_response" | jq -r '.data.id // .id // empty' 2>/dev/null)

if [ -z "$quiz_id" ]; then
    echo "❌ Failed to get quiz ID from response"
    exit 1
fi

echo "✅ Created quiz with ID: $quiz_id"

# Test 4: Get all quizzes
echo "4. Testing Quiz List..."
eval make_request "GET" "/quizzes" "" "$AUTH_HEADER" "Get All Quizzes"

# Test 5: Get specific quiz
echo "5. Testing Quiz Retrieval..."
eval make_request "GET" "/quizzes/$quiz_id" "" "$AUTH_HEADER" "Get Quiz by ID"

# Test 6: Create a question with answers
echo "6. Testing Question Creation..."
question_data='{
    "question_text": "What is the correct way to declare a variable in JavaScript?",
    "question_type": "multiple_choice",
    "quiz_answers": [
        {
            "answer": "var myVar = 5;",
            "is_correct": true
        },
        {
            "answer": "variable myVar = 5;",
            "is_correct": false
        },
        {
            "answer": "let myVar = 5;",
            "is_correct": true
        },
        {
            "answer": "const myVar = 5;",
            "is_correct": false
        }
    ]
}'

question_response=$(eval make_request "POST" "/quizzes/$quiz_id/questionswithanswers" "'$question_data'" "$AUTH_HEADER" "Create Question with Answers")
question_id=$(echo "$question_response" | jq -r '.data.id // .id // empty' 2>/dev/null)

if [ -z "$question_id" ]; then
    echo "❌ Failed to get question ID from response"
else
    echo "✅ Created question with ID: $question_id"
fi

# Test 7: Get quiz questions
echo "7. Testing Quiz Questions Retrieval..."
eval make_request "GET" "/quizzes/$quiz_id/questions" "" "$AUTH_HEADER" "Get Quiz Questions"

# Test 8: Update quiz
echo "8. Testing Quiz Update..."
update_data='{
    "title": "Updated HTTP Test Quiz",
    "slug": "updated-http-test-quiz-'$(date +%s)'",
    "type": "assessment",
    "summary": "Updated test quiz via HTTP API",
    "content": "This quiz has been updated via HTTP API",
    "published_at": "'$(date -u +%Y-%m-%dT%H:%M:%SZ)'"
}'

eval make_request "PUT" "/quizzes/$quiz_id" "'$update_data'" "$AUTH_HEADER" "Update Quiz"

# Test 9: Test error handling - unauthorized access
echo "9. Testing Error Handling (Unauthorized)..."
make_request "GET" "/quizzes" "" "" "Access without token"

# Test 10: Test error handling - invalid quiz ID
echo "10. Testing Error Handling (Invalid Quiz ID)..."
eval make_request "GET" "/quizzes/99999" "" "$AUTH_HEADER" "Get Non-existent Quiz"

# Test 11: Delete quiz (cleanup)
echo "11. Testing Quiz Deletion..."
eval make_request "DELETE" "/quizzes/$quiz_id" "" "$AUTH_HEADER" "Delete Quiz"

echo ""
echo "🎉 HTTP Quiz API Testing Completed!"
echo "If all tests passed, your Quiz API is working correctly."
