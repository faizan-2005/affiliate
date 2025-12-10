# Quick Start Guide - Docker Deployment

This guide will help you get the Affiliate Marketing Platform running in Docker in minutes.

## Prerequisites

- Docker (version 20.10+)
- Docker Compose (version 1.29+)
- 2GB+ available RAM
- 5GB+ available disk space

## Quick Start (3 Steps)

### 1. Clone and Setup

```bash
# Clone the repository
git clone https://github.com/faizan-2005/affiliate.git
cd affiliate

# Copy environment file
cp .env.example .env

# Edit configuration (optional)
nano .env
```

### 2. Start Services

```bash
# Build and start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Check services status
docker-compose ps
```

### 3. Access the Platform

Once all services are healthy:

```
Web Application:  http://localhost:8080
MySQL Database:   localhost:3306
Redis Cache:      localhost:6379
```

## Default Credentials

**Admin Login:**
- Email: admin@example.com
- Password: admin123

**Affiliate Account:**
- Email: affiliate@example.com
- Password: affiliate123

**Advertiser Account:**
- Email: advertiser@example.com
- Password: advertiser123

## Common Docker Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f [service_name]

# Execute command in container
docker-compose exec app php script.php

# Restart a specific service
docker-compose restart app

# View resource usage
docker stats

# Clean up volumes (WARNING: deletes data)
docker-compose down -v
```

## Environment Configuration

Key variables in `.env`:

```env
# Application
APP_NAME=Affiliate Platform
APP_ENV=production
APP_DEBUG=false

# Database
DB_NAME=affiliate_db
DB_USER=affiliate_user
DB_PASSWORD=affiliate_pass
DB_ROOT_PASSWORD=rootpassword

# Redis
REDIS_PORT=6379

# Application Port
APP_PORT=8080
```

## Database Management

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u affiliate_user -p affiliate_db

# Backup database
docker-compose exec mysql mysqldump -u affiliate_user -p affiliate_db > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u affiliate_user -p affiliate_db < backup.sql
```

## Monitoring & Troubleshooting

### View Application Logs
```bash
docker-compose logs -f app
```

### View Worker Logs
```bash
docker-compose logs -f fraud_worker
docker-compose logs -f stats_worker
docker-compose logs -f archive_worker
```

### Check Database Connection
```bash
docker-compose exec app php -r "
    include 'config/database.php';
    try {
        \$pdo = new PDO('mysql:host=mysql;dbname=' . getenv('DB_NAME'), 
                        getenv('DB_USER'), getenv('DB_PASSWORD'));
        echo 'Database connected successfully';
    } catch (\Exception \$e) {
        echo 'Connection error: ' . \$e->getMessage();
    }
"
```

### Restart All Services
```bash
docker-compose restart
```

### Full Cleanup and Reinstall
```bash
# Stop and remove all containers/volumes
docker-compose down -v

# Remove old images
docker-compose rm -f

# Rebuild and start fresh
docker-compose up -d --build
```

## Production Deployment

For production, modify these settings:

1. **Update .env:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   DB_PASSWORD=strong_random_password
   REDIS_PASSWORD=strong_random_password
   ```

2. **Configure SSL/TLS:**
   - Use nginx reverse proxy with SSL certificates
   - Or use AWS ALB with SSL termination

3. **Scale Workers:**
   ```bash
   docker-compose up -d --scale fraud_worker=3 --scale stats_worker=2
   ```

4. **Set up Monitoring:**
   - Configure health checks
   - Set up log aggregation
   - Monitor container resources

## Performance Tuning

### MySQL Optimization
```bash
docker-compose exec mysql mysql-tuner
```

### PHP Performance
```bash
# Increase memory limit in Dockerfile
# RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini
```

### Redis Optimization
```bash
# Increase max connections in docker-compose.yml
# command: redis-server --maxclients 10000
```

## Support & Documentation

- **README.md** - Platform overview and API documentation
- **INSTALLATION.md** - Detailed setup guide
- **COMPLETION.md** - Project inventory and statistics
- **GitHub Issues** - Report bugs or feature requests

## Next Steps

1. Log in to the admin dashboard: `http://localhost:8080`
2. Create your first offer
3. Add an affiliate account
4. Test click tracking with sample clicks
5. Monitor conversions and payouts

---

**Happy tracking! 🚀**
