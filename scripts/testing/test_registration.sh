#!/bin/bash

# User registration testing script
# Make sure to change the base URL if your server is on a different port

BASE_URL="http://localhost:8888/authhub/public/api"

echo "=== User Registration Test ==="
echo

# 1. Successful registration
echo "1. Testing successful registration..."
response=$(curl -s -X POST "$BASE_URL/auth/register" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}')

echo "Response:"
echo "$response" | python3 -m json.tool 2>/dev/null || echo "$response"
echo

# Extract token from response
token=$(echo "$response" | python3 -c "import sys, json; print(json.load(sys.stdin).get('token', ''))" 2>/dev/null)

if [ ! -z "$token" ]; then
    echo "Token obtained: $token"
    echo
    
    # 2. Validate the token
    echo "2. Validating the token..."
    curl -s -X POST "$BASE_URL/auth/token/validate" \
    -H "Authorization: Bearer $token" \
    -H "Accept: application/json" | python3 -m json.tool 2>/dev/null
    echo
fi

# 3. Test duplicate email error
echo "3. Testing duplicate email error..."
curl -s -X POST "$BASE_URL/auth/register" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
    "name": "Another User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}' | python3 -m json.tool 2>/dev/null
echo

# 4. Test validation errors
echo "4. Testing validation errors..."
curl -s -X POST "$BASE_URL/auth/register" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
    "name": "",
    "email": "invalid-email",
    "password": "123"
}' | python3 -m json.tool 2>/dev/null
echo

echo "=== End of tests ==="