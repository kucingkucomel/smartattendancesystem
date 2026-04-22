# Smart Attendance System

Files:
- index.php
- api.php
- import_db.php
- setup.sql

This project is a simple PHP-based smart attendance system.

## Production Explanation
- **Deployment**: Configured to run on Railway via Docker.
- **Database**: Connects to the database using `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` environment variables.
- **Global Demo Time**: The system provides an administration override to mock the server day/time across all users simultaneously. This global time override is stored in the database's `demo_time_control` table and features a 1-minute cooldown to prevent conflicting concurrent updates by admins/lecturers. When enabled, student timetables and generated QR tokens instantly sync with this global override without modifying real server timezone logic.
