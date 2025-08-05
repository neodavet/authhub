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

## 🏗️ Project Features

### 🔑 Application Management
- Create and manage third-party applications
- Generate secure client credentials (UUID + secret)
- Configure callback URLs and allowed scopes
- Set rate limiting per application
- Toggle application status (active/inactive)

### 🎫 Token Management
- Issue API tokens for applications
- Set token expiration dates
- Scope-based permissions (read, write, delete, admin)
- Track token usage and last activity
- Revoke tokens when needed

### 🛡️ Security Features
- Secure token hashing and storage
- Authorization policies for resource access
- Custom validation rules with security checks
- Rate limiting and scope-based permissions
- Protection against common vulnerabilities

### ⚡ Management Tools
- Clean up expired tokens automatically
- Generate detailed usage statistics
- Export analytics to JSON/CSV
- Interactive application creation wizard

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

### Public Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/oauth/token` - Issue access token
- `POST /api/oauth/verify` - Verify token validity

### Protected Resources (Requires Authentication)
- `GET /api/applications` - List user's applications
- `POST /api/applications` - Create new application
- `GET /api/applications/{id}` - Get application details
- `PUT /api/applications/{id}` - Update application
- `DELETE /api/applications/{id}` - Delete application
- `POST /api/applications/{id}/regenerate-secret` - Regenerate secret

### Token Management
- `GET /api/api-tokens` - List tokens
- `POST /api/applications/{id}/tokens` - Create token for application
- `DELETE /api/api-tokens/{id}` - Revoke token

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

### 2. Test API Authentication
```bash
# Use the generated credentials to get a token
curl -X POST http://localhost:8000/api/oauth/token \
  -H "Content-Type: application/json" \
  -d '{"client_id":"your-uuid","client_secret":"your-secret","grant_type":"client_credentials"}'
```

### 3. View Statistics
```bash
# See comprehensive token statistics
php artisan tokens:stats --detailed
```

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

## 📝 Notes

- This is a **case study project** demonstrating Laravel concepts
- Built with Laravel 10.48 and PHP 8.3 for modern practices
- Follows PSR coding standards and Laravel conventions
- Production-ready architecture with proper error handling
- Comprehensive documentation for educational purposes

## 🤝 Contributing

This is an educational project. Feel free to:
- Fork and experiment with the code
- Add new features to practice Laravel skills
- Improve documentation and examples
- Share your learning experiences

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).