# Database Migration Guide - Roar POS Backend

## Overview
Panduan ini menjelaskan cara menjalankan migrasi database untuk mengupdate sistem POS dengan dukungan multi-user dan offline sync.

## Changes Summary

### 1. User Table
- ✅ **store_name** field sudah ada
- Digunakan untuk menyimpan nama toko/bisnis setiap user

### 2. Categories Table
- ➕ **user_id** - Foreign key ke users table
- ➕ **sync_status** - Status sync (synced, pending, conflict)  
- ➕ **last_synced** - Timestamp sync terakhir
- ➕ **client_version** - Versi client yang melakukan sync
- ➕ **version_id** - ID versi untuk conflict resolution

### 3. Products Table  
- ➕ **user_id** - Foreign key ke users table
- ➕ **sync_status** - Status sync (synced, pending, conflict)
- ➕ **last_synced** - Timestamp sync terakhir
- ➕ **client_version** - Versi client yang melakukan sync
- ➕ **version_id** - ID versi untuk conflict resolution

### 4. Orders Table
- 🔄 **kasir_id → user_id** - Rename untuk konsistensi
- ➕ **transaction_number** - Nomor transaksi unik
- ➕ **nominal_bayar** - Jumlah pembayaran
- ➕ **status** - Status order (completed, refund, etc.)
- ➕ **refund_method** - Metode refund
- ➕ **refund_nominal** - Nominal refund
- ➕ **sync_status** - Status sync (synced, pending, conflict)
- ➕ **last_synced** - Timestamp sync terakhir
- ➕ **client_version** - Versi client yang melakukan sync
- ➕ **version_id** - ID versi untuk conflict resolution

### 5. Additional Tables (if exists)
- **discounts** - Added user_id dan sync fields
- **additional_charges** - Added user_id dan sync fields
- **order_temporaries** - Added user_id dan sync fields

## Migration Files Created

1. `2025_08_27_000001_add_user_id_to_categories_table.php` - Add user_id to categories
2. `2025_08_27_000002_add_offline_sync_fields.php` - Add sync fields to all tables
3. `2025_08_27_000003_add_user_id_fields_comprehensive.php` - Add user_id to all tables
4. `2025_08_27_000004_adjust_orders_table_structure.php` - Fix orders table structure

## Running Migrations

### Step 1: Backup Database (IMPORTANT!)
```bash
# Backup your current database first!
mysqldump -u username -p database_name > backup_before_migration.sql
```

### Step 2: Run Migrations
```bash
# Navigate to backend directory
cd roar-pos-backend

# Run all pending migrations
php artisan migrate

# If you want to see what will run first
php artisan migrate:status
```

### Step 3: Seed Demo Data (Optional)
```bash
# Run the demo seeder for testing
php artisan db:seed --class=UpdatedDemoSeeder
```

## Rollback Plan (if needed)

If something goes wrong, you can rollback:

```bash
# Rollback the last batch of migrations
php artisan migrate:rollback

# Rollback specific number of batches
php artisan migrate:rollback --step=4

# Rollback all migrations (CAREFUL!)
php artisan migrate:reset
```

## Verification

After migration, verify the changes:

```bash
# Check migration status
php artisan migrate:status

# Check table structures
php artisan tinker
>>> Schema::hasColumn('categories', 'user_id')  // should return true
>>> Schema::hasColumn('products', 'sync_status')  // should return true
```

## Testing API Endpoints

After migration, test these endpoints:

1. **Ping Test**
   ```bash
   curl -X GET http://your-domain/api/ping
   ```

2. **Categories with User Filter**
   ```bash
   curl -X GET http://your-domain/api/categories \
   -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Sync Status**
   ```bash
   curl -X GET http://your-domain/api/sync/status \
   -H "Authorization: Bearer YOUR_TOKEN"
   ```

## New Features Available

1. **Multi-tenant Support** - Each user has their own data
2. **Offline Sync** - Batch sync capabilities
3. **Conflict Resolution** - Handle multiple device edits
4. **Connection Testing** - Ping endpoint for connection quality
5. **Version Control** - Track data changes with version IDs

## Demo Accounts

After running the seeder:
- **admin@roarpos.com** / password
- **demo@roarpos.com** / password

## Important Notes

- All existing data will be preserved
- New fields are nullable initially
- Foreign key constraints will prevent orphaned data
- Sync status defaults to 'synced' for existing records
- Version IDs start at 1 for existing records

## Troubleshooting

### Migration Errors
If you get foreign key constraint errors:
```bash
# Temporary disable foreign key checks
php artisan tinker
>>> DB::statement('SET FOREIGN_KEY_CHECKS=0;');
>>> Artisan::call('migrate');
>>> DB::statement('SET FOREIGN_KEY_CHECKS=1;');
```

### Column Already Exists
If you get "column already exists" errors, the migration will skip those columns automatically.

### Performance
For large databases, these migrations might take time. Consider:
- Running during low-traffic hours  
- Monitor database locks
- Consider adding indexes after migration

---

**⚠️ ALWAYS BACKUP YOUR DATABASE BEFORE RUNNING MIGRATIONS!**
