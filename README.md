# Affiliate Tracking Platform

A commercial-grade, scalable affiliate marketing tracking platform built with PHP 8+, MySQL 8+, and Tailwind CSS. Supports millions of clicks per day with advanced fraud detection, multi-touch attribution, and comprehensive reporting.

## Features

### Core Features
- ✅ **Three-role system**: Admin, Affiliate, Advertiser
- ✅ **Click tracking**: Ultra-fast click recording with sub-parameters (sub1-sub5)
- ✅ **Conversion tracking**: Postback-based conversion recording
- ✅ **Smartlinks**: Weighted traffic distribution across offers
- ✅ **Multi-touch attribution**: Track user journey across multiple clicks
- ✅ **Fraud detection**: Bot detection, duplicate clicks, IP blacklisting, fast click detection
- ✅ **IP whitelisting**: Advertiser-specific IP restrictions for postbacks
- ✅ **Revenue share**: Flexible payout models (fixed/percent)
- ✅ **Payout management**: Multiple payout methods and status tracking
- ✅ **Real-time dashboards**: Performance metrics and analytics
- ✅ **Daily stats rollup**: Automated aggregation for reporting
- ✅ **Hot/cold storage**: Automatic archival of old clicks

### Advanced Features
- **API signatures**: HMAC-SHA256 signing for clicks and postbacks
- **Deep linking**: Support for app deep links
- **Offer targeting**: GEO, device, OS, browser, carrier, connection type
- **Offer caps**: Daily, monthly, total, and affiliate-specific limits
- **Fraud logging**: Detailed fraud detection with severity levels
- **Background workers**: Async processing for fraud checks and stats
- **Session tracking**: User fingerprinting for accurate attribution
- **Postback logging**: Complete audit trail of all conversions
- **System logging**: Admin action tracking and API logs

## Quick Start

```bash
# 1. Create environment file
cp .env.example .env

# 2. Configure database credentials
nano .env

# 3. Create database
mysql -u root -p < database/schema.sql

# 4. Set permissions
chmod 777 storage/logs storage/cache storage/sessions

# 5. Start server (PHP 8.0+)
php -S localhost:8000 -t public/

# 6. Visit http://localhost:8000 and login
```

## Database Setup

The complete MySQL schema is in `database/schema.sql`. It includes:
- Users, Affiliates, Advertisers tables
- Offers with targeting and caps
- Clicks table (with partitioning)
- Conversions and Attribution
- Fraud detection and logging
- Daily stats aggregation
- API and system logging

## API Endpoints

### Click Tracking
```
GET|POST /click?offer_id=1&aff_id=2&click_id=xxx&sub1=value&sig=xxx
```
Tracks clicks and redirects to offer landing page.

### Postback/Conversion
```
POST /postback?click_id=xxx&transaction_id=yyy&payout=10&revenue=50&sig=xxx
```
Records conversions from advertiser postbacks.

### Dashboard Access
- Admin: `/admin/dashboard`
- Affiliate: `/affiliate/dashboard`
- Advertiser: `/advertiser/dashboard`

## Architecture

**Frontend**: Tailwind CSS + Vanilla JS
**Backend**: PHP 8+ with custom micro-framework
**Database**: MySQL 8+ with optimization for scale
**Cache**: Redis (optional)
**Queue**: Redis-based queue for background jobs

## Key Components

- **Core Framework**: Router, Database, Cache, Queue, Session, Request/Response
- **Models**: ORM-like models for all entities
- **Controllers**: Clean request handling with auth/validation
- **Services**: Business logic for clicks, conversions, fraud, reports
- **Background Workers**: Async processing for fraud checks, stats, archival

## Security

- ✅ HMAC-SHA256 signatures for API calls
- ✅ IP whitelisting for postbacks
- ✅ Password hashing with bcrypt
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (HTML escaping)
- ✅ Session security

## Performance

- Clicks table partitioned by year
- Optimized indexes for common queries
- Redis caching support
- Background workers for heavy processing
- Auto-archival of old clicks
- Bulk insert capabilities

## Project Structure

```
affiliate/
├── config/              # Application configuration
├── public/              # Web root (index.php, click.php, postback.php)
├── src/
│   ├── Core/           # Framework classes
│   ├── Models/         # Database models
│   ├── Controllers/    # Request handlers
│   ├── Services/       # Business logic
│   ├── Middleware/     # Request middleware
│   ├── Jobs/           # Background jobs
│   └── Helpers/        # Utility functions
├── views/              # Tailwind HTML templates
├── database/
│   └── schema.sql      # Complete MySQL schema
├── storage/            # Logs, cache, sessions
├── workers/            # Background worker scripts
└── .env               # Environment configuration
```

## Database Schema Highlights

- **20+ optimized tables** with proper indexes
- **Click partitioning** by year for scalability
- **Views** for common queries (affiliate stats, offer performance)
- **Foreign keys** for data integrity
- **JSON columns** for flexible data storage
- Supports **millions of clicks per day**

## Background Workers

Three worker scripts for async processing:
1. `fraud_worker.php` - Fraud detection
2. `stats_worker.php` - Daily stats aggregation
3. `archive_worker.php` - Old click archival

## Installation

See detailed setup instructions in `database/schema.sql` and `.env.example` for configuration.

## Support

For detailed documentation, API examples, and advanced configurations, see the full README documentation in the project root.