# Student Management System Setup Guide

This guide provides step-by-step instructions for setting up and configuring the Student Management System (SMS) on your server.

## Prerequisites

Before you begin, ensure you have the following:

- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Git (optional, for cloning the repository)

## Step 1: Obtain the Code

Choose one of the following methods:

### Option A: Clone from Git

```bash
git clone https://github.com/yourusername/student-management-system.git
cd student-management-system
```

### Option B: Download ZIP Archive

1. Download the ZIP archive from the repository
2. Extract the contents to your web server's document root or desired directory

## Step 2: Configure Web Server

### For Apache

Ensure the following modules are enabled:
- mod_rewrite
- mod_headers

Create or update your virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName sms.yourdomain.com
    DocumentRoot /path/to/sms
    
    <Directory /path/to/sms>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sms_error.log
    CustomLog ${APACHE_LOG_DIR}/sms_access.log combined
</VirtualHost>
```

Restart Apache:

```bash
sudo systemctl restart apache2
# or
sudo service apache2 restart
```

### For Nginx

Create a new server block configuration:

```nginx
server {
    listen 80;
    server_name sms.yourdomain.com;
    root /path/to/sms;
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

Restart Nginx:

```bash
sudo systemctl restart nginx
# or
sudo service nginx restart
```

## Step 3: Create the Database

1. Log in to MySQL:

```bash
mysql -u root -p
```

2. Create a database:

```sql
CREATE DATABASE college_management;
```

3. Create a user and grant privileges:

```sql
CREATE USER 'sms_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON college_management.* TO 'sms_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

4. Import the database schema:

```bash
mysql -u sms_user -p college_management < schema.sql
```

## Step 4: Configure the Application

1. Open `dbms.php` in your code editor
2. Update the database connection settings:

```php
private $host = "localhost";
private $username = "sms_user"; // Replace with your database username
private $password = "your_strong_password"; // Replace with your database password
private $database = "college_management";
```

## Step 5: Set Directory Permissions

Set appropriate permissions for file uploads and cache directories:

```bash
# Create directories if they don't exist
mkdir -p uploads/documents
mkdir -p uploads/profile_images
mkdir -p cache

# Set permissions
chmod -R 755 .
chmod -R 775 uploads
chmod -R 775 cache
```

## Step 6: Create Admin User (if needed)

The default admin account credentials are:

- Username: admin
- Password: admin123

**Important**: Change the default password immediately after first login.

To create a new admin user directly in the database:

```sql
INSERT INTO users (username, password, email, role, full_name, status)
VALUES ('newadmin', MD5('your_secure_password'), 'admin@example.com', 'admin', 'Administrator', 'active');
```

## Step 7: Testing the Installation

1. Open your web browser and navigate to your SMS installation (e.g., http://sms.yourdomain.com or http://localhost/sms)
2. You should see the login page
3. Log in using the admin credentials
4. Verify that you can access the dashboard and all functionality

## Troubleshooting

### Cannot Connect to Database

- Verify that MySQL is running: `sudo systemctl status mysql`
- Check database credentials in `dbms.php`
- Ensure the database user has proper permissions

### Blank Page or 500 Error

- Check PHP error logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- Enable error reporting in PHP (for development only):
  ```php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  ```

### Permission Issues

- Check directory permissions
- Ensure web server user (www-data, apache, nginx) has write access to uploads and cache directories

## Security Recommendations

1. Change default admin password immediately
2. Set strong passwords for all accounts
3. Install an SSL certificate and force HTTPS
4. Regularly update your PHP version and dependencies
5. Implement a proper backup strategy for the database
6. Configure server firewall and security groups
7. Regularly check server and application logs for suspicious activity

## Support

For additional support, please contact the system administrator or open an issue in the repository. 