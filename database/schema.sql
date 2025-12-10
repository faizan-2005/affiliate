-- Affiliate Tracking Platform Database Schema
-- MySQL 8.0+

CREATE DATABASE IF NOT EXISTS affiliate_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE affiliate_db;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'affiliate', 'advertiser') NOT NULL DEFAULT 'affiliate',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliates Table
CREATE TABLE affiliates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255),
    website VARCHAR(255),
    payout_method ENUM('bank_transfer', 'paypal', 'wire', 'check') DEFAULT 'bank_transfer',
    payout_email VARCHAR(255),
    bank_details JSON,
    paypal_email VARCHAR(255),
    api_key VARCHAR(255) UNIQUE,
    api_secret VARCHAR(255),
    total_earned DECIMAL(15, 2) DEFAULT 0,
    total_clicks BIGINT DEFAULT 0,
    total_conversions BIGINT DEFAULT 0,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_approval_status (approval_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Advertisers Table
CREATE TABLE advertisers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    website VARCHAR(255),
    contact_person VARCHAR(255),
    postback_url VARCHAR(500),
    postback_method ENUM('get', 'post') DEFAULT 'post',
    api_key VARCHAR(255) UNIQUE,
    api_secret VARCHAR(255),
    total_conversions BIGINT DEFAULT 0,
    total_payout DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offers Table
CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advertiser_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    landing_page_url VARCHAR(500) NOT NULL,
    payout_type ENUM('fixed', 'percent') NOT NULL,
    payout_value DECIMAL(10, 2) NOT NULL,
    revenue_type ENUM('fixed', 'percent', 'dynamic') NOT NULL,
    revenue_value DECIMAL(10, 2),
    offer_category VARCHAR(100),
    status ENUM('active', 'inactive', 'paused', 'archived') DEFAULT 'active',
    preview_url VARCHAR(500),
    tracking_domain VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    INDEX idx_advertiser_id (advertiser_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offer Targeting Table
CREATE TABLE offer_targeting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    geo_countries JSON,
    blocked_countries JSON,
    device_types JSON,
    os_types JSON,
    os_versions JSON,
    browsers JSON,
    carriers JSON,
    connection_types JSON,
    min_os_version VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    INDEX idx_offer_id (offer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offer Caps Table
CREATE TABLE offer_caps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    cap_type ENUM('daily', 'monthly', 'total', 'affiliate_daily') NOT NULL,
    cap_value INT NOT NULL,
    cap_date DATE,
    current_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    INDEX idx_offer_id (offer_id),
    INDEX idx_cap_type (cap_type),
    INDEX idx_cap_date (cap_date),
    UNIQUE KEY unique_cap (offer_id, cap_type, cap_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smartlinks Table
CREATE TABLE smartlinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advertiser_id INT,
    affiliate_id INT,
    name VARCHAR(255) NOT NULL,
    url_slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    redirect_mode ENUM('weighted', 'geo', 'device', 'custom') DEFAULT 'weighted',
    status ENUM('active', 'inactive') DEFAULT 'active',
    total_clicks BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE SET NULL,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    INDEX idx_affiliate_id (affiliate_id),
    INDEX idx_url_slug (url_slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Smartlink Rules Table
CREATE TABLE smartlink_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smartlink_id INT NOT NULL,
    offer_id INT NOT NULL,
    rule_order INT DEFAULT 0,
    matching_condition JSON,
    weight DECIMAL(5, 2) DEFAULT 100,
    geo_match VARCHAR(5),
    device_match VARCHAR(50),
    os_match VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (smartlink_id) REFERENCES smartlinks(id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    INDEX idx_smartlink_id (smartlink_id),
    INDEX idx_offer_id (offer_id),
    INDEX idx_rule_order (rule_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clicks Table (Hot Storage - Last 30 days)
CREATE TABLE clicks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    affiliate_id INT NOT NULL,
    smartlink_id INT,
    click_id VARCHAR(64) NOT NULL UNIQUE,
    session_id VARCHAR(64),
    ip VARCHAR(45) NOT NULL,
    device VARCHAR(100),
    os VARCHAR(100),
    os_version VARCHAR(20),
    browser VARCHAR(100),
    browser_version VARCHAR(20),
    country VARCHAR(5),
    referrer LONGTEXT,
    ua_hash CHAR(64),
    user_agent LONGTEXT,
    converted TINYINT DEFAULT 0,
    conversion_id BIGINT,
    sub1 VARCHAR(255),
    sub2 VARCHAR(255),
    sub3 VARCHAR(255),
    sub4 VARCHAR(255),
    sub5 VARCHAR(255),
    source VARCHAR(255),
    domain VARCHAR(255),
    channel VARCHAR(255),
    placement VARCHAR(255),
    creative_id VARCHAR(100),
    campaign_id VARCHAR(100),
    deeplink LONGTEXT,
    rule_id INT,
    force_geo VARCHAR(5),
    force_device VARCHAR(50),
    force_os VARCHAR(50),
    sig VARCHAR(255),
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    FOREIGN KEY (smartlink_id) REFERENCES smartlinks(id) ON DELETE SET NULL,
    INDEX idx_offer_id (offer_id),
    INDEX idx_affiliate_id (affiliate_id),
    INDEX idx_click_id (click_id),
    INDEX idx_country (country),
    INDEX idx_created_at (created_at),
    INDEX idx_converted (converted),
    INDEX idx_session_id (session_id),
    INDEX idx_ip (ip),
    INDEX idx_device (device),
    INDEX idx_composite (offer_id, affiliate_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027)
);

-- Clicks Archive Table (Cold Storage)
CREATE TABLE clicks_archive (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT,
    affiliate_id INT,
    smartlink_id INT,
    click_id VARCHAR(64),
    session_id VARCHAR(64),
    ip VARCHAR(45),
    device VARCHAR(100),
    os VARCHAR(100),
    os_version VARCHAR(20),
    browser VARCHAR(100),
    country VARCHAR(5),
    referrer LONGTEXT,
    ua_hash CHAR(64),
    converted TINYINT DEFAULT 0,
    conversion_id BIGINT,
    sub1 VARCHAR(255),
    sub2 VARCHAR(255),
    sub3 VARCHAR(255),
    sub4 VARCHAR(255),
    sub5 VARCHAR(255),
    created_at DATETIME NOT NULL,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_click_id (click_id),
    INDEX idx_created_at (created_at),
    INDEX idx_affiliate_id (affiliate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversions Table
CREATE TABLE conversions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    click_id VARCHAR(64) NOT NULL,
    offer_id INT NOT NULL,
    affiliate_id INT NOT NULL,
    advertiser_id INT NOT NULL,
    advertiser_ref_id VARCHAR(255),
    transaction_id VARCHAR(255),
    payout DECIMAL(10, 2) NOT NULL,
    revenue DECIMAL(10, 2) NOT NULL,
    advertiser_payload JSON,
    status ENUM('pending', 'confirmed', 'rejected', 'duplicate') DEFAULT 'pending',
    source ENUM('postback', 'manual', 'api') DEFAULT 'postback',
    duplicate_detected TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    INDEX idx_click_id (click_id),
    INDEX idx_offer_id (offer_id),
    INDEX idx_affiliate_id (affiliate_id),
    INDEX idx_advertiser_id (advertiser_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_transaction_id (transaction_id),
    UNIQUE KEY unique_transaction (advertiser_id, transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attribution Paths Table
CREATE TABLE attribution_paths (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    conversion_id BIGINT NOT NULL,
    user_fingerprint VARCHAR(255),
    click_ids JSON,
    weights JSON,
    last_touch_click_id VARCHAR(64),
    attribution_model ENUM('last_click', 'first_click', 'weighted') DEFAULT 'last_click',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversion_id) REFERENCES conversions(id) ON DELETE CASCADE,
    INDEX idx_conversion_id (conversion_id),
    INDEX idx_user_fingerprint (user_fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Postback Logs Table
CREATE TABLE postback_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    conversion_id BIGINT,
    advertiser_id INT NOT NULL,
    click_id VARCHAR(64),
    transaction_id VARCHAR(255),
    postback_url VARCHAR(500),
    request_params JSON,
    response_status INT,
    response_body LONGTEXT,
    ip_verified TINYINT DEFAULT 0,
    ip_address VARCHAR(45),
    status ENUM('pending', 'sent', 'success', 'failed', 'rejected') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    error_message LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    INDEX idx_advertiser_id (advertiser_id),
    INDEX idx_click_id (click_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Advertiser IP Whitelist Table
CREATE TABLE advertiser_ip_whitelist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advertiser_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    ip_range_start VARCHAR(45),
    ip_range_end VARCHAR(45),
    description VARCHAR(255),
    active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    INDEX idx_advertiser_id (advertiser_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_active (active),
    UNIQUE KEY unique_ip (advertiser_id, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fraud Logs Table
CREATE TABLE fraud_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    click_id VARCHAR(64),
    offer_id INT,
    affiliate_id INT,
    fraud_type VARCHAR(100),
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT,
    data JSON,
    ip VARCHAR(45),
    user_agent_hash CHAR(64),
    blacklisted TINYINT DEFAULT 0,
    action_taken VARCHAR(255),
    reviewed TINYINT DEFAULT 0,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_click_id (click_id),
    INDEX idx_fraud_type (fraud_type),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    INDEX idx_reviewed (reviewed),
    INDEX idx_ip (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IP Blacklist Table
CREATE TABLE ip_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    ip_range_start VARCHAR(45),
    ip_range_end VARCHAR(45),
    reason VARCHAR(255),
    blacklist_type ENUM('manual', 'fraud_detection', 'abuse') DEFAULT 'manual',
    permanent TINYINT DEFAULT 1,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ip_address (ip_address),
    INDEX idx_blacklist_type (blacklist_type),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payouts Table
CREATE TABLE payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    affiliate_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    method ENUM('bank_transfer', 'paypal', 'wire', 'check') NOT NULL,
    status ENUM('pending', 'approved', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
    reference_number VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    INDEX idx_affiliate_id (affiliate_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily Stats Table (for reporting and analytics)
CREATE TABLE daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL,
    offer_id INT,
    affiliate_id INT,
    advertiser_id INT,
    country VARCHAR(5),
    device VARCHAR(100),
    clicks BIGINT DEFAULT 0,
    conversions BIGINT DEFAULT 0,
    revenue DECIMAL(15, 2) DEFAULT 0,
    payout DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE SET NULL,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE SET NULL,
    INDEX idx_stat_date (stat_date),
    INDEX idx_offer_id (offer_id),
    INDEX idx_affiliate_id (affiliate_id),
    INDEX idx_advertiser_id (advertiser_id),
    INDEX idx_country (country),
    INDEX idx_device (device),
    UNIQUE KEY unique_stats (stat_date, offer_id, affiliate_id, advertiser_id, country, device)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Logs Table
CREATE TABLE system_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    entity_type VARCHAR(100),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Logs Table
CREATE TABLE api_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    endpoint VARCHAR(500),
    method VARCHAR(10),
    request_body LONGTEXT,
    response_status INT,
    response_time_ms INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Reset Tokens Table
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
ALTER TABLE clicks ADD FULLTEXT INDEX ft_referrer (referrer);
ALTER TABLE conversions ADD INDEX idx_created_date (DATE(created_at));
ALTER TABLE clicks ADD INDEX idx_created_date (DATE(created_at));

-- Views for common queries
CREATE VIEW view_affiliate_stats AS
SELECT 
    a.id as affiliate_id,
    a.user_id,
    u.name,
    u.email,
    COUNT(DISTINCT c.id) as total_clicks,
    COUNT(DISTINCT conv.id) as total_conversions,
    SUM(conv.payout) as total_payout,
    COUNT(DISTINCT c.created_at) as active_days,
    MAX(c.created_at) as last_click_date
FROM affiliates a
JOIN users u ON a.user_id = u.id
LEFT JOIN clicks c ON a.id = c.affiliate_id AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
LEFT JOIN conversions conv ON c.click_id = conv.click_id
GROUP BY a.id;

CREATE VIEW view_offer_performance AS
SELECT 
    o.id,
    o.name,
    o.advertiser_id,
    COUNT(DISTINCT c.id) as clicks,
    COUNT(DISTINCT conv.id) as conversions,
    ROUND(COUNT(DISTINCT conv.id) / COUNT(DISTINCT c.id) * 100, 2) as conversion_rate,
    SUM(conv.payout) as total_payout,
    SUM(conv.revenue) as total_revenue
FROM offers o
LEFT JOIN clicks c ON o.id = c.offer_id AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
LEFT JOIN conversions conv ON c.click_id = conv.click_id AND conv.status IN ('pending', 'confirmed')
GROUP BY o.id;
