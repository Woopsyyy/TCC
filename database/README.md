# Database Setup Instructions

## Quick Setup (Automatic)

**Easiest Method:**
1. The database will be **automatically created** when you first access the application
2. To create all tables, visit: `http://localhost/TCC/BackEnd/setup_database.php` in your browser
3. This will automatically set up all tables and create the admin account

## Manual Setup

1. **Import the clean schema:**
   - Open phpMyAdmin or MySQL command line
   - Import `schema.sql` to create a fresh database with all required tables
   - This will create the `accountmanager` database with all necessary tables

2. **Default Admin Account:**
   - Username: `admin`
   - Password: `admin123`
   - **Important:** Change this password after first login!

## Database Structure

The database includes the following tables:
- `users` - User accounts (admin, teacher, student)
- `announcements` - System announcements
- `projects` - Project information
- `buildings` - Building details
- `section_assignments` - Section to building/room assignments
- `user_assignments` - User academic and financial records
- `audit_log` - Admin action logs

## Migration from JSON

If you have existing JSON data files, you can use the migration script:
- Navigate to: `BackEnd/admin/migrate_json_to_db.php`
- Run it once to import JSON data into the database
- The script will automatically backup JSON files with `.bak` extension

## Notes

- All tables use UTF-8 encoding (utf8mb4)
- Foreign keys are properly set up for data integrity
- The `user_assignments` table links to `users` via `user_id` for proper user matching

