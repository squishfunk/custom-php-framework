# Client Management System

A simple CRUD system for managing clients and their transactions.

## Requirements

- PHP 8.2 or higher
- Composer
- Docker 

## Setup

1. Clone the project and install dependencies:

```bash
composer install
```

2. Copy the environment file:

```bash
cp .env.example .env
```

3. Edit `.env` file with your database settings

## Environments

### Development (dev)

Set `APP_ENV=dev` in your `.env` file. This enables:
- Twig debug mode
- Verbose error messages visible on screen (filp/whoops package)

### Production (prod)

Set `APP_ENV=prod` in your `.env` file for live server.

## Running the Project

### Option 1: Using Docker (recommended)

Start the database:

```bash
docker-compose up -d
```

The database will start on port 3306 with the schema auto-loaded from `database/schema.sql`.

Run PHP built-in server:

```bash
php -S localhost:8000 -t public/
```

Open browser: `http://localhost:8000`

### Option 2: Using your own MySQL

1. Create a MySQL database called `thinkhuge`
2. Run the SQL file: `database/schema.sql`
3. Update `.env` with your database credentials
4. Start PHP server: `php -S localhost:8000 -t public/`

## Running Tests

Tests use a separate test database running on Docker.

1. Start the test database:

```bash
docker-compose -f docker-compose.test.yml up -d
```

2. Run all tests:

```bash
./vendor/bin/phpunit
```

3. Stop the test database:

```bash
docker-compose -f docker-compose.test.yml down
```

## Framework Architecture

This is a custom lightweight PHP framework using MVC pattern:

### Directory Structure

```
app/
├── Controller/     # Handles HTTP requests
├── Service/        # Business logic
├── Repository/     # Database access
├── Entity/         # Data models
├── Dto/            # Data transfer objects
├── Core/           # Framework core (Router, Database, etc.)
├── Middleware/     # Request filtering (Auth, CSRF)
└── Exception/      # Custom exceptions

templates/          # Twig view templates
public/             # Web root (index.php)
routes/             # Route definitions
database/           # SQL schema
tests/              # PHPUnit tests
```

### Core Components

**Router** (`app/Core/Router.php`)
- Maps URLs to Controller methods
- Supports route parameters like `/clients/{id}`
- Global and per-route middleware support

**Request** (`app/Core/Request.php`)
- Handles GET/POST data
- Query parameters

**Response** (`app/Core/Response.php`)
- HTTP responses with status codes
- HTML responses

**Database** (`app/Core/Database.php`)
- Singleton PDO connection
- Transaction support
- Separate test database configuration

**View** (`app/Core/View.php`)
- Twig template engine
- Global variables (user, app_name)
- CSRF token helpers

**Validator** (`app/Core/Validator.php`)
- Input validation rules: required, email, min, max, numeric, date

**CsrfToken** (`app/Core/CsrfToken.php`)
- Generates secure tokens
- 1-hour token lifetime

### Request Flow

1. Browser request → `public/index.php`
2. Load config and create Request object
3. Router matches URL to Controller
4. Middleware runs (Session, CSRF, Auth)
5. Controller calls Service layer
6. Service uses Repository for database
7. Repository returns Entity objects
8. Controller renders View with data

### Security Features

- CSRF protection on all forms
- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- Session regeneration on login
- Input validation
- Authentication middleware

## First Login

After setup, register a new admin account at `/register`.

Then login at `/login` to access the system.
