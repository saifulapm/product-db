# Deployment Instructions

## To deploy changes to live server (design.ethos-merch.com)

### Option 1: SSH into the server and pull changes

1. SSH into your live server:
   ```bash
   ssh user@design.ethos-merch.com
   # or whatever your SSH connection string is
   ```

2. Navigate to your application directory:
   ```bash
   cd /path/to/your/application
   ```

3. Pull the latest changes:
   ```bash
   git pull origin main
   ```

4. **Install new dependencies** (CRITICAL - includes PDF parser):
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

5. **Run new migrations** (CRITICAL - creates new tables):
   ```bash
   php artisan migrate --force
   ```

6. Clear Laravel caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   php artisan route:cache
   php artisan config:cache
   ```

7. If needed, rebuild assets:
   ```bash
   npm run build
   ```

### Option 2: If using a deployment script

Run your deployment script on the server, ensuring it includes:
- `composer install --optimize-autoloader --no-dev`
- `php artisan migrate --force`
- Cache clearing commands

### Option 3: If using CI/CD

The changes should automatically deploy if you have CI/CD set up (GitHub Actions, etc.).

---

## Latest Changes (Nov 26, 2025)

**New Features:**
- Incoming Shipments resource with packing list import (CSV, Excel, PDF)
- Orders resource for splitting shipments into client orders
- OrderItems for tracking allocations
- Carton-level stock tracking

**Database Changes:**
- New tables: `incoming_shipments`, `orders`, `order_items`
- Database default changed from SQLite to MySQL

**Dependencies:**
- Added `smalot/pdfparser` for PDF import support

**IMPORTANT:** After pulling, you MUST:
1. Run `composer install` to install the new PDF parser dependency
2. Run `php artisan migrate --force` to create the new tables
3. Clear all caches

