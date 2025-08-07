# 🔐 AuthHub - Laravel Token Authentication Service

A comprehensive **Laravel 10.48** application built with **PHP 8.3** that provides secure token authentication services for third-party applications. This project demonstrates advanced Laravel concepts, clean code practices, and production-ready architecture.

## 🎯 Project Goals

This application serves as a **case study** demonstrating:
- How to build a secure token authentication service
- Laravel best practices and clean architecture
- Advanced Eloquent relationships and database design
- Professional API development with proper validation
- Custom Artisan commands for system management
- Security, performance, and scalability considerations

## 🚀 What This Project Demonstrates

### Core Laravel Concepts Covered:
- ✅ **Eloquent Relationships** - Complex model relationships with advanced queries
- ✅ **CRUD Operations** - Complete MVC architecture with controllers, routes, and views
- ✅ **Custom Validation Rules** - Advanced validation with business logic
- ✅ **Artisan Commands** - Professional CLI tools with progress bars and options
- ✅ **Authentication & Authorization** - Policies, middleware, and security practices
- ✅ **Database Design** - Migrations, indexes, and foreign key constraints
- ✅ **API Development** - RESTful APIs with proper HTTP responses
- ✅ **Custom Middleware** - Token authentication for third-party applications
- ✅ **Form Requests** - Professional validation with custom request classes
- ✅ **Mail Integration** - Email functionality for join requests

## 🏗️ Project Features

### 🔑 Application Management (✅ Fully Implemented)
- ✅ Create and manage third-party applications via API
- ✅ Generate secure client credentials (UUID + secret)
- ✅ Configure callback URLs and allowed scopes
- ✅ Toggle application status (active/inactive)
- ✅ Regenerate client secrets securely
- ✅ Authorization policies for resource access
- ❌ Rate limiting per application (ready for implementation)

### 🎫 Token Management (✅ Fully Implemented)
- ✅ Issue API tokens for applications
- ✅ Set token expiration dates
- ✅ Scope-based permissions (read, write, delete, admin)
- ✅ Track token usage and last activity
- ✅ Revoke tokens individually or in bulk
- ✅ OAuth2-compliant token flow
- ✅ Custom middleware for token authentication

### 🛡️ Security Features (✅ Implemented)
- ✅ Secure token hashing (SHA-256) and storage
- ✅ Authorization policies for resource access
- ✅ Custom validation rules with security checks
- ✅ Scope-based permissions system
- ✅ Protection against common vulnerabilities
- ✅ Input sanitization and validation
- ✅ IP and User-Agent tracking for tokens

### ⚡ Management Tools (✅ Implemented)
- ✅ Clean up expired tokens automatically (Artisan command)
- ✅ Generate detailed usage statistics
- ✅ Export analytics to JSON/CSV
- ✅ Interactive application creation wizard
- ✅ Token statistics and analytics commands
- ✅ Comprehensive database design with proper indexes

### 🌐 Web Interface (🔧 Partial)
- ✅ Home page with join request functionality
- ✅ Email integration for join requests
- ❌ Web-based authentication (Laravel UI not installed)
- ❌ Dashboard for managing applications and tokens
- ❌ User registration/login interface

## 📁 Project Structure

```
app/
├── Console/Commands/           # Custom Artisan commands
│   ├── GenerateApplicationCredentials.php
│   ├── PruneExpiredTokens.php
│   └── TokenStatistics.php
├── Http/
│   ├── Controllers/           # API and web controllers
│   │   ├── ApplicationController.php
│   │   ├── ApiTokenController.php
│   │   └── AuthController.php
│   └── Requests/             # Form request validation
│       ├── StoreApplicationRequest.php
│       └── UpdateApplicationRequest.php
├── Models/                   # Eloquent models with relationships
│   ├── User.php
│   ├── Application.php
│   └── ApiToken.php
├── Policies/                 # Authorization policies
│   └── ApplicationPolicy.php
└── Rules/                    # Custom validation rules
    ├── ValidCallbackUrl.php
    ├── ValidTokenScope.php
    └── UniqueClientId.php

database/migrations/          # Database schema
routes/                      # Web and API routes
```

## ⚙️ Installation & Setup

### Prerequisites
- PHP 8.3+
- MySQL 5.7+
- Composer

### Step 1: Clone and Install
```bash
git clone <repository-url> authhub
cd authhub
composer install
```

### Step 2: Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=authhub
DB_USERNAME=root
DB_PASSWORD=root
```

### Step 3: Database Setup
```bash
# Run migrations
php artisan migrate

# (Optional) Seed sample data
php artisan db:seed
```

### Step 4: Start Development Server
```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 🔧 Usage Examples

### Create an Application via Command Line
```bash
# Interactive creation
php artisan app:create "My Third-Party App"

# With parameters
php artisan app:create "Mobile App" \
  --user=developer@example.com \
  --scopes=read,write \
  --rate-limit=5000 \
  --description="Mobile application API access"
```

### API Authentication Flow
```bash
# 1. Get access token
curl -X POST http://localhost:8000/api/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "your-client-id",
    "client_secret": "your-client-secret",
    "grant_type": "client_credentials",
    "scope": "read write"
  }'

# 2. Use token in requests
curl -X GET http://localhost:8000/api/protected/user \
  -H "Authorization: Bearer your-access-token"
```

### Management Commands
```bash
# View token statistics
php artisan tokens:stats --detailed

# Clean expired tokens
php artisan tokens:prune --dry-run --days=30

# Export statistics
php artisan tokens:stats --export=json --user=user@example.com
```

## 🗄️ Database Schema

### Applications Table
- **id** - Primary key
- **name** - Application name
- **description** - Optional description
- **client_id** - Unique UUID for identification
- **client_secret** - Secure secret for authentication
- **callback_urls** - JSON array of allowed callback URLs
- **allowed_scopes** - JSON array of permitted scopes
- **is_active** - Boolean status flag
- **user_id** - Foreign key to users table
- **rate_limit** - Requests per hour limit
- **timestamps** - Created/updated timestamps

### API Tokens Table
- **id** - Primary key
- **name** - Token identifier name
- **token** - Hashed token value
- **abilities** - JSON array of token scopes
- **application_id** - Foreign key to applications
- **user_id** - Foreign key to users
- **expires_at** - Optional expiration timestamp
- **last_used_at** - Last usage timestamp
- **is_active** - Boolean status flag
- **created_from_ip** - IP address where token was created
- **user_agent** - User agent string
- **timestamps** - Created/updated timestamps

## 🛣️ API Endpoints

> **Legend:** ✅ = Implemented | ❌ = Not Implemented | 🔧 = Partial Implementation

### 🔓 Public Authentication
- ✅ `POST /api/auth/login` - User login with email/password
- ✅ `POST /api/auth/register` - User registration
- ✅ `POST /api/auth/token/validate` - Validate Sanctum token
- ✅ `POST /api/auth/token/refresh` - Refresh Sanctum token

### 🔐 OAuth2 Authentication (Third-party Apps)
- ✅ `POST /api/oauth/token` - Issue access token for applications
- ✅ `POST /api/oauth/verify` - Verify token validity and get user info
- ✅ `POST /api/oauth/revoke` - Revoke OAuth token

### 🛡️ Protected Routes (Requires Sanctum Authentication)

#### User Management
- ✅ `GET /api/user` - Get authenticated user info
- ✅ `POST /api/auth/logout` - Logout (revoke current token)
- ✅ `PUT /api/auth/profile` - Update user profile
- ✅ `DELETE /api/auth/account` - Delete user account

#### Application Management
- ✅ `GET /api/applications` - List user's applications (paginated)
- ✅ `POST /api/applications` - Create new application
- ✅ `GET /api/applications/{id}` - Get application details
- ✅ `PUT /api/applications/{id}` - Update application
- ✅ `DELETE /api/applications/{id}` - Delete application
- ✅ `POST /api/applications/{application}/regenerate-secret` - Regenerate client secret
- ✅ `PATCH /api/applications/{application}/toggle-status` - Toggle active status

#### API Token Management
- ✅ `GET /api/api-tokens` - List all user's API tokens
- ✅ `POST /api/api-tokens` - Create new API token
- ✅ `GET /api/api-tokens/{id}` - Get token details
- ✅ `DELETE /api/api-tokens/{id}` - Revoke API token
- ✅ `GET /api/applications/{application}/tokens` - List tokens for specific application
- ✅ `POST /api/applications/{application}/tokens` - Create token for specific application

### 🔒 Token-Authenticated Routes (Custom Middleware)
- ✅ `GET /api/protected/user` - Get authenticated user via API token
- ✅ `GET /api/protected/profile` - Get user profile via API token

### 🌐 Web Routes
- ✅ `GET /` - Home page with join request form
- ✅ `POST /join-request` - Submit join request (sends email)
- ✅ `GET /welcome` - Welcome page
- ❌ Full web-based authentication (Laravel UI not installed)
- ❌ Web-based application management interface
- ❌ Web-based token management interface

### 📊 Additional API Endpoints (Available but not documented)
- 🔧 Token statistics and analytics
- 🔧 Bulk token operations

## 🎨 Laravel Concepts Demonstrated

### 1. Eloquent Relationships
```php
// User has many applications
public function applications(): HasMany
{
    return $this->hasMany(Application::class);
}

// Application belongs to user and has many tokens
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function apiTokens(): HasMany
{
    return $this->hasMany(ApiToken::class);
}
```

### 2. Custom Validation Rules
```php
// ValidCallbackUrl.php - Validates URLs with security checks
public function validate(string $attribute, mixed $value, Closure $fail): void
{
    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        $fail('The :attribute must be a valid URL.');
    }
    
    // Additional security checks...
}
```

### 3. Authorization Policies
```php
// ApplicationPolicy.php - Control access to resources
public function view(User $user, Application $application): bool
{
    return $user->id === $application->user_id;
}
```

### 4. Artisan Commands with Options
```php
// Command signature with options
protected $signature = 'tokens:prune 
                        {--dry-run : Show what would be deleted}
                        {--days=30 : Delete tokens expired more than X days ago}';
```

## 🧪 Testing the Features

### 1. Create a Test User and Application
```bash
# Create application interactively
php artisan app:create "Test App"
```

### 2. Test API Authentication Flow

#### Register a User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### Login User
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

#### Test OAuth2 Flow
```bash
# Use the generated credentials to get a token
curl -X POST http://localhost:8000/api/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "your-uuid",
    "client_secret": "your-secret",
    "scope": "read write"
  }'

# Verify the token
curl -X POST http://localhost:8000/api/oauth/verify \
  -H "Authorization: Bearer your-access-token"
```

#### Test Protected Endpoints
```bash
# Get user applications (requires Sanctum token)
curl -X GET http://localhost:8000/api/applications \
  -H "Authorization: Bearer your-sanctum-token"

# Access via API token (custom middleware)
curl -X GET http://localhost:8000/api/protected/user \
  -H "Authorization: Bearer your-api-token"
```

### 3. View Statistics
```bash
# See comprehensive token statistics
php artisan tokens:stats --detailed
```

### 4. Test Join Request Feature
Visit `http://localhost:8000` and fill out the join request form to test the email functionality.

## 🔒 Security Features

- **Token Hashing**: Tokens are hashed before storage
- **Scope Validation**: Granular permission control
- **Rate Limiting**: Configurable per application
- **URL Validation**: Callback URLs validated for security
- **Authorization Policies**: Resource-level access control
- **Input Sanitization**: All inputs properly validated

## 📈 Performance Considerations

- **Database Indexes**: Optimized queries with proper indexing
- **Eager Loading**: Prevents N+1 query problems
- **Chunked Operations**: Large datasets processed in batches
- **Pagination**: API responses properly paginated
- **Caching Ready**: Structure supports Redis/Memcached integration

## 🎓 Learning Outcomes

After studying this project, you'll understand:
- How to structure a professional Laravel application
- Advanced Eloquent relationships and query optimization
- Building secure APIs with proper authentication
- Creating reusable validation rules and policies
- Developing custom Artisan commands
- Database design for multi-tenant applications
- Security best practices in Laravel

## 🎯 Implementation Status Summary

### ✅ Fully Implemented Features
- **User Authentication** - Complete registration, login, logout, profile management
- **OAuth2 Flow** - Token issuing, verification, and revocation for third-party apps
- **Application Management** - Full CRUD operations via API
- **API Token Management** - Complete token lifecycle management
- **Custom Middleware** - Token-based authentication for external applications
- **Database Schema** - Complete with proper relationships and indexes
- **Custom Artisan Commands** - Token management and statistics tools
- **Email Integration** - Join request functionality with SMTP support
- **Authorization Policies** - Resource-level access control
- **Custom Validation Rules** - Business logic validation
- **Form Requests** - Professional API validation

### ❌ Missing Features (Great for Contributors!)
- **Web-Based Dashboard** - User interface for managing applications and tokens
- **Laravel UI Integration** - Traditional web authentication flows
- **Advanced Analytics Dashboard** - Visual statistics and usage metrics
- **Rate Limiting Implementation** - Per-application request limits
- **Webhook Support** - Event notifications for third-party applications
- **Multi-Factor Authentication** - Enhanced security features
- **API Documentation** - Interactive Swagger/OpenAPI documentation
- **Docker Configuration** - Containerized deployment setup
- **Comprehensive Test Suite** - Unit and feature tests expansion
- **Caching Layer** - Redis/Memcached integration for performance

## 📝 Notes

- This is a **case study project** demonstrating Laravel concepts
- Built with Laravel 10.48 and PHP 8.3 for modern practices
- Follows PSR coding standards and Laravel conventions
- Production-ready architecture with proper error handling
- Comprehensive documentation for educational purposes [[memory:5237819]]

## 🤝 Contributing

This project welcomes contributors! Here's how you can help:

### 🚀 For Beginners
- **Add Web Interface**: Create Blade templates for application and token management
- **Improve Styling**: Enhance the existing home page with better CSS/JavaScript
- **Add Validation**: Implement additional custom validation rules
- **Expand Tests**: Write feature tests for existing endpoints

### 🔧 For Intermediate Developers
- **Implement Rate Limiting**: Add per-application request throttling
- **Build Analytics Dashboard**: Create visual statistics and reporting
- **Add Webhook System**: Implement event notifications for applications
- **Docker Setup**: Create containerized development environment

### 🏗️ For Advanced Developers
- **Multi-Factor Authentication**: Implement TOTP/SMS verification
- **API Documentation**: Generate interactive OpenAPI/Swagger docs
- **Performance Optimization**: Add caching, database optimization
- **Security Enhancements**: Implement additional security layers

### 📚 Getting Started as a Contributor
1. Fork the repository
2. Check the **❌ Missing Features** section above
3. Pick a feature that matches your skill level
4. Create a feature branch: `git checkout -b feature/your-feature-name`
5. Follow the existing code patterns and Laravel conventions
6. Test your implementation thoroughly
7. Submit a pull request with clear description

### 💡 Contribution Guidelines
- Follow PSR coding standards
- Maintain the educational focus - code should be clear and well-commented
- Add/update tests for new features
- Update documentation when adding new endpoints or features
- Keep the [[memory:5237819]] approach: methodical, explanatory, and beginner-friendly

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).