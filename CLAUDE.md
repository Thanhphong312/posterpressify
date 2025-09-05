# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a POD (Print-On-Demand) order management system designed for FulfillSupover/PosterPressify. The system manages orders, products, shipping labels, and customer data with MySQL database backend.

## Database Connection

```
Host: 45.79.0.186
User: duytan
Password: tandb
Database: [needs to be specified]
```

## Key Development Commands

### XAMPP Environment (macOS)
```bash
# Start XAMPP services
sudo /Applications/XAMPP/xamppfiles/xampp start

# Stop XAMPP services  
sudo /Applications/XAMPP/xamppfiles/xampp stop

# Check XAMPP status
sudo /Applications/XAMPP/xamppfiles/xampp status

# Access MySQL CLI
/Applications/XAMPP/xamppfiles/bin/mysql -u duytan -p -h 45.79.0.186
```

### PHP Development
```bash
# Run local PHP server (if not using XAMPP Apache)
php -S localhost:8000 -t public/

# Check PHP configuration
/Applications/XAMPP/xamppfiles/bin/php -i

# Test database connection
/Applications/XAMPP/xamppfiles/bin/php test-db-connection.php
```

## System Architecture

### Core Components

1. **Authentication System**
   - Users table with role-based permissions
   - Session-based authentication
   - Password hashing with bcrypt

2. **Order Management**
   - Regular orders (`orders`, `order_items`) 
   - Dropship orders (`order_dropships`, `order_item_dropships`)
   - Support for tracking, labels, and fulfillment status

3. **Key Features to Implement**
   - Login/logout with session management
   - Order search by ID or order number
   - Label printing with proxy for cross-origin images
   - Product variant management

### Directory Structure (Recommended)
```
public/           # Web root - Apache/Nginx points here
├── index.php     # Entry point
├── login.php     # Authentication
├── orders.php    # Order management
├── proxy_label.php # Label image proxy
└── assets/       # CSS, JS, images

src/              # Application code
├── config/       # Database config
├── models/       # Data models
├── controllers/  # Business logic
└── views/        # Templates

vendor/           # Composer dependencies (if using)
```

## Implementation Guidelines

### Database Queries
- Use PDO with prepared statements for all queries
- Reference `database_schema.md` for complete table structures
- Key tables: `users`, `orders`, `order_items`, `order_dropships`

### Security Requirements
- All user inputs must be validated and escaped
- Use prepared statements for SQL queries
- Implement CSRF protection for forms
- Store passwords as bcrypt hashes
- Validate and whitelist domains for label proxy

### Label Printing Implementation
- Labels stored as URLs in `orders.shipping_label` field
- Proxy required due to CORS restrictions
- Proxy endpoint should validate URL domain, cache images, and set appropriate headers

### Order Search Logic
- Numeric input: Search by `orders.id` first
- String input: Search by `orders.ref_id` using LIKE
- Display order details with related items from `order_items`

## Testing Approach

### Database Connection Test
Create a test script to verify MySQL connectivity before implementing features.

### Order Search Test
1. Insert test orders via MySQL CLI
2. Verify search returns correct results
3. Test both ID and order number search

### Label Proxy Test
1. Test with valid label URLs
2. Verify CORS headers are properly set
3. Check caching mechanism works

## Common SQL Patterns

```sql
-- Find order by ID or ref_id
SELECT * FROM orders WHERE id = ? OR ref_id LIKE ?

-- Get order with items
SELECT o.*, oi.* FROM orders o 
LEFT JOIN order_items oi ON o.id = oi.order_id 
WHERE o.id = ?

-- Authenticate user
SELECT id, username, password FROM users WHERE username = ?
```

## Development Notes

- The system uses XAMPP on macOS for local development
- Database is hosted remotely at 45.79.0.186
- Focus on POD-style clean UI with minimal design
- Prioritize functionality over aesthetics initially