# Pressify Poster

A clean, modern Print-On-Demand order management system built with PHP and MySQL.

## Features

- 🔐 Secure authentication system with session management
- 📦 Order search and management
- 🏷️ Shipping label printing with cross-origin proxy
- 🎨 Clean POD-style UI with responsive design
- 🔧 Environment-based configuration (.env)
- 🚀 Optimized for XAMPP on macOS

## Installation

### Prerequisites

- XAMPP installed on macOS
- MySQL database access
- PHP 7.4 or higher

### Setup Steps

1. **Clone or place files in XAMPP htdocs:**
```bash
/Applications/XAMPP/xamppfiles/htdocs/posterpressify/
```

2. **Configure environment variables:**
```bash
cp .env.example .env
```
Edit `.env` file with your database credentials:
```
DB_HOST=45.79.0.186
DB_DATABASE=pressify
DB_USERNAME=duytan
DB_PASSWORD=tandb
```

3. **Set up custom domain (optional):**
```bash
# Add to /etc/hosts
echo "127.0.0.1    posterpressify.local" | sudo tee -a /etc/hosts

# Restart Apache
sudo /Applications/XAMPP/xamppfiles/bin/httpd -k restart
```

4. **Test database connection:**
```bash
php test-db-connection.php
```

5. **Create admin user:**
```bash
php create-test-user.php
```

6. **Access the application:**
- With domain: `http://posterpressify.local`
- Without domain: `http://localhost/posterpressify/public/`

## Project Structure

```
posterpressify/
├── public/                 # Web-accessible files
│   ├── assets/            # CSS, JS, images
│   ├── index.php          # Entry point
│   ├── login.php          # Authentication
│   ├── orders.php         # Order management
│   ├── order-details.php  # Order details view
│   ├── print-label.php    # Label printing
│   └── proxy-label.php    # Label image proxy
├── src/                   # Application logic
│   ├── config/           # Configuration files
│   │   ├── database.php  # Database connection
│   │   ├── env.php       # Environment loader
│   │   └── app.php       # App configuration
│   └── controllers/      # Business logic
│       ├── AuthController.php
│       └── OrderController.php
├── cache/                # Cache directory
├── .env                  # Environment variables
├── .env.example          # Example environment file
└── .gitignore           # Git ignore rules
```

## Configuration

### Environment Variables

The application uses `.env` file for configuration. Key variables:

- `DB_*` - Database connection settings
- `APP_*` - Application settings (name, URL, debug mode)
- `SESSION_*` - Session configuration
- `LABEL_*` - Label proxy settings

### Security Features

- Password hashing with bcrypt
- Prepared statements for SQL queries
- Session-based authentication
- Environment variable protection
- Label proxy domain whitelist
- XSS protection

## Usage

### Login
Navigate to the login page and enter your credentials.

### Search Orders
- Search by Order ID (numeric)
- Search by Order Number (text/partial match)
- View recent orders

### Print Labels
- Click "Print Label" on any order with a shipping label
- Label images are proxied through your server for CORS compliance
- Automatic print dialog opens

## Development

### Database Schema
See `database_schema.md` for complete database structure.

### Adding New Features
1. Create controllers in `src/controllers/`
2. Add views in `public/`
3. Update CSS in `public/assets/css/style.css`

### Testing
```bash
# Test database connection
php test-db-connection.php

# Create test users
php create-test-user.php
```

## Troubleshooting

### Database Connection Issues
1. Verify credentials in `.env`
2. Check MySQL server accessibility
3. Ensure database exists
4. Check firewall settings

### Apache/Domain Issues
1. Verify Apache is running in XAMPP
2. Check virtual host configuration
3. Ensure hosts file is updated
4. Clear browser cache

## License

This project is proprietary software for FulfillSupover/PosterPressify.

## Support

For issues or questions, contact the development team.