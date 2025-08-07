# Scripts Directory

This directory contains utility scripts for the AuthHub project, organized by functionality.

## Directory Structure

```
scripts/
├── README.md          # This documentation file
└── testing/          # API integration testing scripts
    ├── test_registration.sh    # User registration testing
    └── user_crud_tests.sh     # Complete CRUD operations testing
```

## Testing Scripts

### Prerequisites

Before running any testing scripts:

1. **Start your development server** (MAMP, Laravel Sail, or `php artisan serve`)
2. **Ensure the database is set up** and migrations are run
3. **Update the BASE_URL** in scripts if your server runs on a different port
4. **Install Python 3** (for JSON formatting in scripts)

### Available Scripts

#### 🔧 test_registration.sh
**Purpose**: Tests user registration functionality with various scenarios.

**Features**:
- Successful user registration
- Token generation verification
- Email duplication validation
- Field validation testing

**Usage**:
```bash
cd scripts/testing
./test_registration.sh
```

#### 🔧 user_crud_tests.sh
**Purpose**: Comprehensive testing of all user CRUD operations.

**Features**:
- **CREATE**: User registration with validation
- **READ**: User profile information retrieval
- **UPDATE**: Profile updates (name, email, password)
- **DELETE**: Account deletion with security checks
- **ERROR HANDLING**: Unauthorized access, invalid tokens
- **SECURITY**: Password verification, confirmation requirements

**Usage**:
```bash
cd scripts/testing
./user_crud_tests.sh
```

### Script Configuration

Both scripts use these default settings:
- **Base URL**: `http://localhost:8888/authhub/public/api`
- **Test Data**: Generates temporary test users
- **Output**: Colored terminal output with JSON formatting

To modify the base URL, edit the `BASE_URL` variable at the top of each script:
```bash
BASE_URL="http://your-domain:port/path/api"
```

### Understanding the Output

The scripts provide colored output:
- 🟢 **Green**: Successful operations
- 🔴 **Red**: Failed operations or errors
- 🟡 **Yellow**: Warnings or potential issues
- 🔵 **Blue**: Section headers and information

### Testing Flow

#### Registration Test Flow:
1. Create new user account
2. Validate token generation
3. Test duplicate email prevention
4. Test field validation errors

#### CRUD Test Flow:
1. **CREATE**: Register test user
2. **READ**: Retrieve user information
3. **UPDATE**: Modify user profile data
4. **DELETE**: Remove user account
5. **VERIFY**: Confirm operations completed

### API Endpoints Tested

| Operation | Endpoint | Method | Authentication |
|-----------|----------|---------|----------------|
| Register | `/auth/register` | POST | No |
| Login | `/auth/login` | POST | No |
| Get User | `/user` | GET | Required |
| Get Profile | `/protected/profile` | GET | Required |
| Update Profile | `/auth/profile` | PUT | Required |
| Delete Account | `/auth/account` | DELETE | Required |
| Validate Token | `/auth/token/validate` | POST | Required |
| Logout | `/auth/logout` | POST | Required |

### Security Features Tested

- Password hashing and verification
- Token-based authentication
- Email uniqueness validation
- Input validation and sanitization
- Account deletion confirmation
- Unauthorized access prevention

### Troubleshooting

#### Common Issues:

1. **Connection refused errors**:
   - Check if your server is running
   - Verify the BASE_URL in the script
   - Ensure the correct port is being used

2. **Permission denied**:
   ```bash
   chmod +x scripts/testing/*.sh
   ```

3. **JSON formatting errors**:
   - Install Python 3: `brew install python3` (macOS)
   - Or remove `| python3 -m json.tool` from scripts

4. **Database errors**:
   - Run migrations: `php artisan migrate`
   - Seed database if needed: `php artisan db:seed`

### Contributing

When adding new testing scripts:

1. Place them in the appropriate subdirectory
2. Follow the naming convention: `test_[feature].sh`
3. Include proper error handling and colored output
4. Update this README with script documentation
5. Ensure scripts are executable: `chmod +x script_name.sh`

### Related Documentation

- [Main Project README](../../README.md)
- [API Documentation](../../README.md#api-endpoints)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)