# PHP Deployment Configuration for QuickFix Nearby

## Environment Variables

Create a `.env` file in the project root with the following variables:

```env
# Application Environment
APP_ENV=production

# Database
DB_HOST=localhost
DB_USER=quickfix_user
DB_PASS=your_secure_password
DB_NAME=quickfix_db

# Email Configuration
MAIL_FROM=noreply@quickfix.ng
MAIL_ADMIN=admin@quickfix.ng

# API Configuration
API_BASE_URL=https://api.quickfix.ng

# Feature Flags
ENABLE_ANALYTICS=true
ENABLE_NOTIFICATIONS=false
```

## Deployment Checklist

### Pre-Deployment

- [ ] Test form submission locally
- [ ] Verify all links are correct
- [ ] Check responsive design on mobile devices
- [ ] Test accessibility with screen reader
- [ ] Run security scan (OWASP)
- [ ] Optimize images
- [ ] Minify CSS/JS (if applicable)

### Server Setup

- [ ] PHP 7.4+ installed and configured
- [ ] HTTPS certificate installed
- [ ] Sessions directory created and writable
- [ ] Error logging configured
- [ ] Cron jobs setup (if needed)
- [ ] Backup strategy in place

### Post-Deployment

- [ ] Verify form submission works
- [ ] Test Service Worker registration
- [ ] Monitor error logs
- [ ] Setup monitoring/alerts
- [ ] Configure auto-scaling (if using cloud)
- [ ] Setup CDN (optional)

## Server Configuration Examples

### Apache (.htaccess)

```apache
# Enable mod_rewrite
RewriteEngine On
RewriteBase /

# Redirect HTTP to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"

# Cache control
<FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$">
    Header set Cache-Control "max-age=31536000, public"
</FilesMatch>

# Compress text files
AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/json application/javascript
```

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name quickfix.ng www.quickfix.ng;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Root directory
    root /var/www/quickfix;
    index index.php index.html;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name quickfix.ng www.quickfix.ng;
    return 301 https://$server_name$request_uri;
}
```

## Docker Deployment

### Dockerfile

```dockerfile
FROM php:8.0-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite
RUN a2enmod rewrite ssl headers

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Set working directory
WORKDIR /var/www/html

EXPOSE 80 443

CMD ["apache2-foreground"]
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  quickfix:
    build: .
    ports:
      - "80:80"
      - "443:443"
    environment:
      APP_ENV: production
      DB_HOST: database
    volumes:
      - ./:/var/www/html
    depends_on:
      - database
    networks:
      - quickfix-network

  database:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: quickfix
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - quickfix-network

networks:
  quickfix-network:

volumes:
  db-data:
```

## Database Schema

QuickFix Nearby Version 2.0 ships with a dedicated database bootstrap file: `db.sql`. Import it into MySQL/MariaDB instead of copying schema snippets from this deployment guide.

```bash
mysql -u quickfix_user -p quickfix_db < db.sql
```

The schema includes:

- `users` for email/password, roles, and Google OAuth account data
- `customers` for requester profiles
- `service_categories` seeded with the launch service catalog
- `professionals` for provider profiles, verification, availability, ratings, and coverage
- `service_requests` for customer jobs and assignment status
- `contact_submissions` for landing-page leads
- `remember_tokens` for persistent login support

## Monitoring & Logging

### Log Files

- **PHP Errors**: `/var/log/php-errors.log`
- **Application**: `/var/www/quickfix/logs/app.log`
- **Access**: `/var/log/apache2/access.log` or `/var/log/nginx/access.log`

### Key Metrics

- Response time (target: < 200ms)
- Error rate (target: < 0.1%)
- Availability (target: 99.9%)
- Form submission success rate

## Performance Optimization

1. **Caching**
   - Enable browser caching (1 year for static assets)
   - Consider PHP opcode caching (OPcache)
   - Implement object caching if using database

2. **Compression**
   - Enable gzip compression
   - Minify CSS and JavaScript
   - Optimize images (WebP format)

3. **CDN**
   - Serve static assets from CDN
   - Cache images at edge locations

4. **Monitoring**
   - Setup performance monitoring
   - Monitor database queries
   - Track user behavior

## Support & Troubleshooting

### Common Issues

**502 Bad Gateway**
- Check PHP-FPM is running
- Verify socket/port configuration

**White screen**
- Enable error display temporarily: `display_errors = 1` in php.ini
- Check error logs

**Form not submitting**
- Verify CSRF token is present
- Check mail configuration
- Review error logs

**Performance issues**
- Check PHP slow log
- Monitor database queries
- Review server resources

## References

- [PHP Security](https://www.php.net/manual/en/security.php)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [Apache Documentation](https://httpd.apache.org/docs/)