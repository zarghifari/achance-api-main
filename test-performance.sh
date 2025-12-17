#!/bin/bash
# Performance Test Script for Docker Laravel API (Bash version)
# Tests all critical endpoints and generates a performance report

BASE_URL="${1:-http://localhost/api}"
ITERATIONS="${2:-3}"

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║          Docker Laravel API Performance Test              ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Test login
echo -e "${YELLOW}Testing Login Endpoint...${NC}"
LOGIN_TIMES=()
for i in $(seq 1 $ITERATIONS); do
    START=$(date +%s%3N)
    RESPONSE=$(curl -s -w "%{http_code}" -X POST "$BASE_URL/login" \
        -H "Content-Type: application/json" \
        -d '{"email":"student@example.com","password":"password"}')
    END=$(date +%s%3N)
    TIME=$((END - START))
    LOGIN_TIMES+=($TIME)
    HTTP_CODE="${RESPONSE: -3}"
    BODY="${RESPONSE:0:${#RESPONSE}-3}"
done

# Calculate average
LOGIN_AVG=0
for time in "${LOGIN_TIMES[@]}"; do
    LOGIN_AVG=$((LOGIN_AVG + time))
done
LOGIN_AVG=$((LOGIN_AVG / ${#LOGIN_TIMES[@]}))

if [ "$HTTP_CODE" = "200" ]; then
    TOKEN=$(echo "$BODY" | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)
    echo -e "  ${GREEN}✓ Login: ${LOGIN_AVG}ms (avg)${NC}"
else
    echo -e "  ${RED}✗ Login: FAILED${NC}"
    exit 1
fi

# Test courses endpoint
echo ""
echo -e "${YELLOW}Testing Courses Endpoint...${NC}"
COURSES_TIMES=()
for i in $(seq 1 $ITERATIONS); do
    START=$(date +%s%3N)
    RESPONSE=$(curl -s -w "%{http_code}" "$BASE_URL/courses/all" \
        -H "Authorization: Bearer $TOKEN")
    END=$(date +%s%3N)
    TIME=$((END - START))
    COURSES_TIMES+=($TIME)
done

COURSES_AVG=0
for time in "${COURSES_TIMES[@]}"; do
    COURSES_AVG=$((COURSES_AVG + time))
done
COURSES_AVG=$((COURSES_AVG / ${#COURSES_TIMES[@]}))

echo -e "  ${GREEN}✓ Courses: ${COURSES_AVG}ms (avg)${NC}"

# Test quizzes endpoint
echo ""
echo -e "${YELLOW}Testing Quizzes Endpoint...${NC}"
QUIZZES_TIMES=()
for i in $(seq 1 $ITERATIONS); do
    START=$(date +%s%3N)
    RESPONSE=$(curl -s -w "%{http_code}" "$BASE_URL/quizzes" \
        -H "Authorization: Bearer $TOKEN")
    END=$(date +%s%3N)
    TIME=$((END - START))
    QUIZZES_TIMES+=($TIME)
done

QUIZZES_AVG=0
for time in "${QUIZZES_TIMES[@]}"; do
    QUIZZES_AVG=$((QUIZZES_AVG + time))
done
QUIZZES_AVG=$((QUIZZES_AVG / ${#QUIZZES_TIMES[@]}))

echo -e "  ${GREEN}✓ Quizzes: ${QUIZZES_AVG}ms (avg)${NC}"

# Summary
echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                    Test Results                            ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
printf "%-20s %-15s %-10s\n" "Endpoint" "Avg Time" "Target"
echo "────────────────────────────────────────────────────────────"
printf "%-20s %-15s %-10s\n" "POST /login" "${LOGIN_AVG}ms" "< 1000ms"
printf "%-20s %-15s %-10s\n" "GET /courses/all" "${COURSES_AVG}ms" "< 500ms"
printf "%-20s %-15s %-10s\n" "GET /quizzes" "${QUIZZES_AVG}ms" "< 500ms"
echo ""

# Overall performance
TOTAL_AVG=$(((LOGIN_AVG + COURSES_AVG + QUIZZES_AVG) / 3))

if [ $TOTAL_AVG -lt 300 ]; then
    GRADE="A+ (Excellent)"
    COLOR=$GREEN
elif [ $TOTAL_AVG -lt 500 ]; then
    GRADE="A (Very Good)"
    COLOR=$GREEN
elif [ $TOTAL_AVG -lt 1000 ]; then
    GRADE="B (Good)"
    COLOR=$YELLOW
else
    GRADE="C (Needs Improvement)"
    COLOR=$YELLOW
fi

echo -e "${COLOR}Overall Performance Grade: $GRADE${NC}"
echo -e "${COLOR}Average Response Time: ${TOTAL_AVG}ms${NC}"
echo ""
