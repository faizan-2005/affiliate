#!/bin/bash

# Affiliate Marketing Platform - Deployment Script
# This script handles the complete deployment workflow

set -e

echo "🚀 Starting Deployment..."
echo "========================"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
REPO_PATH="/workspaces/affiliate"
DEPLOY_PATH="${DEPLOY_PATH:-.}"
BACKUP_DIR="${DEPLOY_PATH}/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Step 1: Pre-deployment checks
echo -e "${BLUE}[1/8] Running pre-deployment checks...${NC}"
if [ ! -f "$REPO_PATH/.env.example" ]; then
    echo -e "${RED}✗ .env.example not found!${NC}"
    exit 1
fi

if [ ! -f "$REPO_PATH/database/schema.sql" ]; then
    echo -e "${RED}✗ Database schema not found!${NC}"
    exit 1
fi

if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ PHP is not installed!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Pre-deployment checks passed${NC}"

# Step 2: Copy files to deployment directory
echo -e "${BLUE}[2/8] Copying files to deployment directory...${NC}"
if [ "$DEPLOY_PATH" != "$REPO_PATH" ]; then
    mkdir -p "$DEPLOY_PATH"
    rsync -av --delete \
        --exclude='.git' \
        --exclude='.env' \
        --exclude='backups' \
        --exclude='node_modules' \
        "$REPO_PATH/" "$DEPLOY_PATH/"
    echo -e "${GREEN}✓ Files copied successfully${NC}"
else
    echo -e "${GREEN}✓ Using repository as deployment directory${NC}"
fi

# Step 3: Create environment file
echo -e "${BLUE}[3/8] Setting up environment configuration...${NC}"
if [ ! -f "$DEPLOY_PATH/.env" ]; then
    cp "$DEPLOY_PATH/.env.example" "$DEPLOY_PATH/.env"
    echo -e "${YELLOW}⚠ .env file created from .env.example${NC}"
    echo -e "${YELLOW}⚠ Please edit $DEPLOY_PATH/.env with your configuration${NC}"
else
    echo -e "${GREEN}✓ .env file already exists${NC}"
fi

# Step 4: Create storage directories
echo -e "${BLUE}[4/8] Creating storage directories...${NC}"
mkdir -p "$DEPLOY_PATH/storage/logs"
mkdir -p "$DEPLOY_PATH/storage/cache"
mkdir -p "$DEPLOY_PATH/storage/sessions"
mkdir -p "$DEPLOY_PATH/backups"
chmod -R 755 "$DEPLOY_PATH/storage"
echo -e "${GREEN}✓ Storage directories created${NC}"

# Step 5: Check PHP version
echo -e "${BLUE}[5/8] Verifying PHP version...${NC}"
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "PHP Version: $PHP_VERSION"

if ! php -v | grep -q "PHP 8"; then
    echo -e "${YELLOW}⚠ Warning: PHP 8+ is recommended (found: $PHP_VERSION)${NC}"
else
    echo -e "${GREEN}✓ PHP version compatible${NC}"
fi

# Step 6: Check required PHP extensions
echo -e "${BLUE}[6/8] Checking required PHP extensions...${NC}"
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "redis" "json" "curl")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -i "$ext" > /dev/null; then
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
    echo -e "${YELLOW}⚠ Missing PHP extensions: ${MISSING_EXTENSIONS[*]}${NC}"
    echo "Install with: apt-get install php8.x-${MISSING_EXTENSIONS[0]} (example)"
else
    echo -e "${GREEN}✓ All required PHP extensions installed${NC}"
fi

# Step 7: Validate configuration files
echo -e "${BLUE}[7/8] Validating configuration files...${NC}"
if php -l "$DEPLOY_PATH/config/app.php" > /dev/null && \
   php -l "$DEPLOY_PATH/config/database.php" > /dev/null && \
   php -l "$DEPLOY_PATH/config/redis.php" > /dev/null; then
    echo -e "${GREEN}✓ Configuration files are valid PHP${NC}"
else
    echo -e "${RED}✗ Configuration file syntax error${NC}"
    exit 1
fi

# Step 8: Database schema check
echo -e "${BLUE}[8/8] Validating database schema...${NC}"
if grep -q "CREATE TABLE" "$DEPLOY_PATH/database/schema.sql"; then
    TABLE_COUNT=$(grep -c "CREATE TABLE" "$DEPLOY_PATH/database/schema.sql")
    echo -e "${GREEN}✓ Database schema validated ($TABLE_COUNT tables)${NC}"
else
    echo -e "${RED}✗ Database schema is empty${NC}"
    exit 1
fi

# Deployment complete
echo ""
echo -e "${GREEN}════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ DEPLOYMENT COMPLETE!${NC}"
echo -e "${GREEN}════════════════════════════════════════════${NC}"
echo ""
echo "📋 Next Steps:"
echo "1. Configure your .env file:"
echo "   nano $DEPLOY_PATH/.env"
echo ""
echo "2. Set up the MySQL database:"
echo "   mysql -u root -p < $DEPLOY_PATH/database/schema.sql"
echo ""
echo "3. Configure your web server (Apache/Nginx)"
echo "   - Point document root to: $DEPLOY_PATH/public"
echo "   - See INSTALLATION.md for detailed configuration"
echo ""
echo "4. Start background workers (in separate terminals):"
echo "   php $DEPLOY_PATH/workers/fraud_worker.php"
echo "   php $DEPLOY_PATH/workers/stats_worker.php"
echo "   php $DEPLOY_PATH/workers/archive_worker.php"
echo ""
echo "5. Test the installation:"
echo "   curl http://localhost/index.php"
echo ""
echo "📚 Documentation:"
echo "   - README.md: Platform overview and API documentation"
echo "   - INSTALLATION.md: Detailed setup and configuration guide"
echo "   - COMPLETION.md: Project inventory and statistics"
echo ""
