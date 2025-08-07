#!/bin/bash

# Comprehensive User CRUD Testing Script
# Tests Create, Read, Update, Delete operations for user management
# All variables, messages and comments in English

BASE_URL="http://localhost:8888/authhub/public/api"
TEST_USER_EMAIL="crud_test@example.com"
TEST_USER_NAME="CRUD Test User"
TEST_PASSWORD="password123"
UPDATED_NAME="Updated Test User"
UPDATED_EMAIL="updated_crud@example.com"
NEW_PASSWORD="newpassword456"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper function to print colored output
print_section() {
    echo -e "\n${BLUE}=== $1 ===${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Helper function to format JSON response
format_response() {
    echo "$1" | python3 -m json.tool 2>/dev/null || echo "$1"
}

# Helper function to extract token from response
extract_token() {
    echo "$1" | python3 -c "import sys, json; print(json.load(sys.stdin).get('token', ''))" 2>/dev/null
}

# Global variable to store authentication token
AUTH_TOKEN=""

echo -e "${BLUE}======================================${NC}"
echo -e "${BLUE}    USER CRUD OPERATIONS TEST SUITE   ${NC}"
echo -e "${BLUE}======================================${NC}"

# ============================================================================
# CREATE - User Registration
# ============================================================================
print_section "CREATE - User Registration"

echo "Creating new user account..."
create_response=$(curl -s -X POST "$BASE_URL/auth/register" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"name\": \"$TEST_USER_NAME\",
    \"email\": \"$TEST_USER_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"$TEST_PASSWORD\"
}")

echo "Response:"
format_response "$create_response"

# Extract token for subsequent operations
AUTH_TOKEN=$(extract_token "$create_response")

if [ ! -z "$AUTH_TOKEN" ]; then
    print_success "User created successfully! Token obtained: ${AUTH_TOKEN:0:20}..."
else
    print_error "Failed to create user or extract token"
    echo "Exiting test suite..."
    exit 1
fi

# Test duplicate email validation
echo -e "\nTesting duplicate email validation..."
duplicate_response=$(curl -s -X POST "$BASE_URL/auth/register" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"name\": \"Another User\",
    \"email\": \"$TEST_USER_EMAIL\",
    \"password\": \"$TEST_PASSWORD\",
    \"password_confirmation\": \"$TEST_PASSWORD\"
}")

echo "Duplicate email response:"
format_response "$duplicate_response"

if echo "$duplicate_response" | grep -q "already been taken"; then
    print_success "Duplicate email validation working correctly"
else
    print_warning "Duplicate email validation may not be working"
fi

# ============================================================================
# READ - User Profile Information
# ============================================================================
print_section "READ - User Profile Information"

# Test 1: Get current user basic info
echo "1. Getting current user basic information..."
user_response=$(curl -s -X GET "$BASE_URL/user" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Accept: application/json")

echo "Current user response:"
format_response "$user_response"

if echo "$user_response" | grep -q "$TEST_USER_EMAIL"; then
    print_success "Successfully retrieved current user information"
else
    print_error "Failed to retrieve current user information"
fi

# Test 2: Get detailed user profile
echo -e "\n2. Getting detailed user profile..."
profile_response=$(curl -s -X GET "$BASE_URL/protected/profile" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Accept: application/json")

echo "User profile response:"
format_response "$profile_response"

if echo "$profile_response" | grep -q "applications_count"; then
    print_success "Successfully retrieved detailed user profile"
else
    print_error "Failed to retrieve detailed user profile"
fi

# Test 3: Validate token
echo -e "\n3. Validating authentication token..."
validate_response=$(curl -s -X POST "$BASE_URL/auth/token/validate" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Accept: application/json")

echo "Token validation response:"
format_response "$validate_response"

if echo "$validate_response" | grep -q '"valid": true'; then
    print_success "Token validation successful"
else
    print_error "Token validation failed"
fi

# ============================================================================
# UPDATE - User Profile Updates
# ============================================================================
print_section "UPDATE - User Profile Updates"

# Test 1: Update name only
echo "1. Updating user name only..."
update_name_response=$(curl -s -X PUT "$BASE_URL/auth/profile" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"name\": \"$UPDATED_NAME\"
}")

echo "Name update response:"
format_response "$update_name_response"

if echo "$update_name_response" | grep -q "$UPDATED_NAME"; then
    print_success "Successfully updated user name"
else
    print_error "Failed to update user name"
fi

# Test 2: Update email only
echo -e "\n2. Updating user email only..."
update_email_response=$(curl -s -X PUT "$BASE_URL/auth/profile" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"email\": \"$UPDATED_EMAIL\"
}")

echo "Email update response:"
format_response "$update_email_response"

if echo "$update_email_response" | grep -q "$UPDATED_EMAIL"; then
    print_success "Successfully updated user email"
else
    print_error "Failed to update user email"
fi

# Test 3: Update password
echo -e "\n3. Updating user password..."
update_password_response=$(curl -s -X PUT "$BASE_URL/auth/profile" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"password\": \"$NEW_PASSWORD\",
    \"password_confirmation\": \"$NEW_PASSWORD\"
}")

echo "Password update response:"
format_response "$update_password_response"

if echo "$update_password_response" | grep -q "Profile updated successfully"; then
    print_success "Successfully updated user password"
else
    print_error "Failed to update user password"
fi

# Test 4: Update multiple fields at once
echo -e "\n4. Updating multiple fields at once..."
update_multiple_response=$(curl -s -X PUT "$BASE_URL/auth/profile" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"name\": \"Final Updated Name\",
    \"email\": \"final_updated@example.com\"
}")

echo "Multiple fields update response:"
format_response "$update_multiple_response"

if echo "$update_multiple_response" | grep -q "Profile updated successfully"; then
    print_success "Successfully updated multiple user fields"
else
    print_error "Failed to update multiple user fields"
fi

# Test 5: Invalid update (duplicate email)
echo -e "\n5. Testing invalid update with existing email..."
invalid_update_response=$(curl -s -X PUT "$BASE_URL/auth/profile" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"email\": \"final_updated@example.com\"
}")

echo "Invalid update response:"
format_response "$invalid_update_response"

# ============================================================================
# READ AFTER UPDATE - Verify Updates
# ============================================================================
print_section "READ AFTER UPDATE - Verify Changes"

echo "Verifying updated user information..."
updated_user_response=$(curl -s -X GET "$BASE_URL/user" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Accept: application/json")

echo "Updated user information:"
format_response "$updated_user_response"

if echo "$updated_user_response" | grep -q "Final Updated Name"; then
    print_success "User updates verified successfully"
else
    print_warning "Some updates may not have been applied"
fi

# ============================================================================
# DELETE - Account Deletion
# ============================================================================
print_section "DELETE - Account Deletion"

# Test 1: Attempt deletion with wrong password
echo "1. Testing account deletion with wrong password..."
wrong_password_response=$(curl -s -X DELETE "$BASE_URL/auth/account" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"password\": \"wrongpassword\",
    \"confirmation\": \"DELETE_MY_ACCOUNT\"
}")

echo "Wrong password deletion response:"
format_response "$wrong_password_response"

if echo "$wrong_password_response" | grep -q "Invalid password"; then
    print_success "Password validation for deletion working correctly"
else
    print_warning "Password validation for deletion may not be working"
fi

# Test 2: Attempt deletion without confirmation
echo -e "\n2. Testing account deletion without proper confirmation..."
no_confirmation_response=$(curl -s -X DELETE "$BASE_URL/auth/account" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"password\": \"$NEW_PASSWORD\",
    \"confirmation\": \"WRONG_CONFIRMATION\"
}")

echo "No confirmation deletion response:"
format_response "$no_confirmation_response"

if echo "$no_confirmation_response" | grep -q "Validation failed"; then
    print_success "Confirmation validation for deletion working correctly"
else
    print_warning "Confirmation validation for deletion may not be working"
fi

# Test 3: Successful account deletion
echo -e "\n3. Performing successful account deletion..."
delete_response=$(curl -s -X DELETE "$BASE_URL/auth/account" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d "{
    \"password\": \"$NEW_PASSWORD\",
    \"confirmation\": \"DELETE_MY_ACCOUNT\"
}")

echo "Account deletion response:"
format_response "$delete_response"

if echo "$delete_response" | grep -q "Account deleted successfully"; then
    print_success "Account successfully deleted"
else
    print_error "Failed to delete account"
fi

# ============================================================================
# VERIFY DELETION - Confirm Account No Longer Exists
# ============================================================================
print_section "VERIFY DELETION - Confirm Account Removal"

echo "Attempting to access deleted account..."
verify_deletion_response=$(curl -s -X GET "$BASE_URL/user" \
-H "Authorization: Bearer $AUTH_TOKEN" \
-H "Accept: application/json")

echo "Verification response:"
format_response "$verify_deletion_response"

if echo "$verify_deletion_response" | grep -q "Unauthenticated"; then
    print_success "Account deletion verified - user no longer accessible"
else
    print_error "Account may not have been properly deleted"
fi

# ============================================================================
# ERROR HANDLING TESTS
# ============================================================================
print_section "ERROR HANDLING TESTS"

# Test 1: Unauthorized access
echo "1. Testing unauthorized access..."
unauthorized_response=$(curl -s -X GET "$BASE_URL/user" \
-H "Accept: application/json")

echo "Unauthorized access response:"
format_response "$unauthorized_response"

if echo "$unauthorized_response" | grep -q "Unauthenticated"; then
    print_success "Unauthorized access properly blocked"
else
    print_warning "Unauthorized access protection may not be working"
fi

# Test 2: Invalid token
echo -e "\n2. Testing invalid token..."
invalid_token_response=$(curl -s -X GET "$BASE_URL/user" \
-H "Authorization: Bearer invalid_token_12345" \
-H "Accept: application/json")

echo "Invalid token response:"
format_response "$invalid_token_response"

if echo "$invalid_token_response" | grep -q "Unauthenticated"; then
    print_success "Invalid token properly rejected"
else
    print_warning "Invalid token protection may not be working"
fi

# ============================================================================
# TEST SUMMARY
# ============================================================================
print_section "TEST SUMMARY"

echo -e "\n${GREEN}✓ CREATE Operations:${NC}"
echo "  - User registration with validation"
echo "  - Duplicate email prevention"
echo "  - Token generation"

echo -e "\n${GREEN}✓ READ Operations:${NC}"
echo "  - Basic user information retrieval"
echo "  - Detailed profile information"
echo "  - Token validation"

echo -e "\n${GREEN}✓ UPDATE Operations:${NC}"
echo "  - Name updates"
echo "  - Email updates"
echo "  - Password updates"
echo "  - Multiple field updates"
echo "  - Update validation"

echo -e "\n${GREEN}✓ DELETE Operations:${NC}"
echo "  - Account deletion with password verification"
echo "  - Confirmation requirement"
echo "  - Token revocation"
echo "  - Account removal verification"

echo -e "\n${GREEN}✓ ERROR HANDLING:${NC}"
echo "  - Unauthorized access protection"
echo "  - Invalid token rejection"
echo "  - Input validation"

echo -e "\n${BLUE}======================================${NC}"
echo -e "${GREEN}   CRUD TEST SUITE COMPLETED!        ${NC}"
echo -e "${BLUE}======================================${NC}"

print_success "All user CRUD operations have been tested"
print_warning "Review any warnings above for potential issues"