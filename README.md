# Secure Smart Attendance System

## System Overview
The Secure Attendance System is a modern, web-based platform designed to precisely manage university class attendance. Built with a focus on strict data integrity, the system seamlessly connects Lecturers and Students through real-time synchronized timetables. It heavily replaces legacy roll-call systems with dynamic, time-sensitive cryptographic tokens. 

## Trust & Security Architecture
This system was built from the ground up to prevent spoofing, proxy-attendance, and data manipulation ensuring that both user roles can implicitly trust the underlying records.

### Why Lecturers Can Trust It
1. **Dynamic Cryptographic Tokens:** Attendance QR tokens are generated strictly server-side using secure randomness (`random_bytes`). Tokens inherently automatically expire after exactly 45 seconds, completely destroying the risk of students sharing codes over group chats.
2. **Time-Locked Validation:** The generation logic strictly enforces the lecturer's timetable constraints. A lecturer physically cannot open an active token session out of bounds of the actual allocated server-driven lecture time block.
3. **Immutable Audit Trails:** Any manual attendance overrides applied to a student's record are aggressively tracked inside a separate `audit_logs` table (timestamped, linked to the explicit lecturer, capturing the exact state change).
4. **Enforced 2FA (Two-Factor Authentication):** To prevent malicious login access, logging into the administrative UI mandates TOTP-bound Google Authenticator checks. 

### Why Students Can Trust It
1. **Strict Context Boundaries:** Students securely interact only via their personalized portals. Database parameters are heavily typed so a student can never spoof relationships to alter another peer's attendance status.
2. **Transparent Immutability:** Once an attendance transaction securely posts against a valid session, the record solidifies. The history view updates instantaneously allowing the user to precisely track their own historical status.
3. **Synchronized Accuracy:** Thanks to a synchronized clock payload, student interfaces are universally aligned with the backend server validation time ensuring absolute fairness regardless of a user's local device clock deviations.

## Production Explanation
- **Deployment**: Configured to run cleanly on Railway via Docker containers.
- **Database**: Relies defensively on secure environment parameters via `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD` to obscure credentials from source code exposure.
- **Global Demo Time**: The system provides an administration override to mock the server day/time across all users simultaneously. This global time override is stored in the database's `demo_time_control` table and features a 1-minute cooldown to prevent conflicting concurrent updates by admins/lecturers. When enabled, student timetables and generated QR tokens instantly sync with this global override without modifying real server timezone logic.

## Technical Structure
- `index.php` - Unified dashboard interface rendering modern, secure user layouts using vanilla technologies.
- `api.php` - The powerhouse backend heavily applying strictly bound PDO variables (Anti-SQLi) and discrete endpoint role-restrictions.
- `import_db.php & setup.sql` - Establishes the `secure_attendance` schema mapping dense multi-relational structures securely.
