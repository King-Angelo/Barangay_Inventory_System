# Production MySQL setup guide (Option B)

**Stack:** XAMPP (local-first) · MySQL / MariaDB · PHP session + JWT

---

## 1. Start MySQL in XAMPP

1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **MySQL**. The port column should show `3306`.
3. If port 3306 is blocked by another service, change the port in `xampp/mysql/bin/my.ini` under `[mysqld]` → `port=3307`, and update `DB_PORT` in `.env.local`.

---

## 2. Create the database and app user

Open **phpMyAdmin** (`http://localhost/phpmyadmin`) or the XAMPP MySQL shell:

```sql
-- Create the database
CREATE DATABASE IF NOT EXISTS `mimds`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Create a dedicated app user (do NOT use root in production)
CREATE USER IF NOT EXISTS 'brgy_app'@'127.0.0.1' IDENTIFIED BY 'ChooseAStrongPassword!';
GRANT SELECT, INSERT, UPDATE, DELETE ON `mimds`.* TO 'brgy_app'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Update `.env.local`:
```
DB_USER=brgy_app
DB_PASS=ChooseAStrongPassword!
```

---

## 3. Import base schema + run migrations in order

**Error: `Table 'mimds.residents' doesn't exist` (or `permits`, etc.):** the app code is ahead of your database. Fix one of these ways:

1. **Fresh full schema (simplest):** back up if needed, drop database `mimds`, create it again, import the **root** `mimds.sql` from the repo (it already includes residents, permits, migrations applied).
2. **Upgrade an old database:** import **`migrations/apply_all_migrations.sql`** once (phpMyAdmin: select `mimds` → Import). Use this only if your DB still has the **legacy** `users` table (e.g. `UserName` as primary key) and none of the new tables. If you already have `users.id` and new tables, skip or run individual `00x_*.sql` files as needed.

Run each file in sequence. In phpMyAdmin: select `mimds` → **Import** tab → choose file.
Via command line (XAMPP shell or Windows CMD):

```bash
# Windows XAMPP default path
cd C:\xampp\mysql\bin

# 1. Base legacy schema
mysql -u root -p mimds < path\to\mimds.sql

# 2. Migrations (run in numbered order)
mysql -u root -p mimds < migrations\001_users_role_and_id.sql
mysql -u root -p mimds < migrations\002_residents.sql
mysql -u root -p mimds < migrations\003_permit_types_and_permits.sql
mysql -u root -p mimds < migrations\004_payments.sql
mysql -u root -p mimds < migrations\005_integration_events_and_notification_log.sql
mysql -u root -p mimds < migrations\006_patient_resident_link.sql
mysql -u root -p mimds < migrations\007_seeds.sql
```

**Linux / Mac (XAMPP or native MySQL):**
```bash
mysql -u root -p mimds < migrations/001_users_role_and_id.sql
# … repeat for 002–007
```

---

## 4. Set the admin password

The seed in `007_seeds.sql` inserts a placeholder bcrypt hash.
Replace it before any real use:

```php
<?php
// Run once from CLI: php set_admin_password.php
$hash = password_hash('YourRealAdminPassword', PASSWORD_BCRYPT);
echo $hash . PHP_EOL;
```

Then in phpMyAdmin or MySQL shell:
```sql
UPDATE `users`
SET `password_hash` = '$2y$12$...(your hash)...'
WHERE `UserName` = 'admin';
```

---

## 5. Configure .env.local

Copy `.env.example` → `inventoryProjBrgy/inventoryProjBrgy/.env.local` and fill in real values:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=brgy_app
DB_PASS=ChooseAStrongPassword!
DB_NAME=mimds
APP_ENV=local

# JWT API (required for POST .../api/v1/auth/login)
JWT_SECRET=your-random-secret-at-least-32-characters-long
JWT_ISS=barangay-inventory
JWT_TTL=3600
```

`.env.local` is in `.gitignore`. Verify with `git status` — it must **not** appear as a tracked file.

### API (Composer + JWT login)

1. In **`inventoryProjBrgy/inventoryProjBrgy`**, run **`composer install`** (install [Composer](https://getcomposer.org/) first if needed, or `php composer.phar install` after downloading `composer.phar`).
2. Ensure **`JWT_SECRET`** is set in `.env.local` (32+ characters). Without it, the API returns HTTP 500 with a clear message.
3. **Apache:** enable **`mod_rewrite`**. The API entry is **`api/index.php`**; pretty URLs use **`api/.htaccess`** (e.g. `.../api/v1/auth/login`).
4. **Health (no DB):** `GET .../api/v1/auth/health` returns JSON `{"status":"ok",...}`.
5. **Login:** `POST .../api/v1/auth/login` with body  
   `{"username":"your_user","password":"your_pass"}`  
   Response includes **`access_token`** (Bearer JWT) and **`user`** (`id`, `username`, `role`).

**Note:** `composer.json` sets `audit.block-insecure` to allow installing `firebase/php-jwt` on environments where Composer’s advisory check blocks the package; keep dependencies updated and review advisories for production.

### Outbox worker (SMTP)

Approving or rejecting a permit via **`PATCH /v1/permits/{id}`** inserts an **`integration_events`** row (migration `005_integration_events_and_notification_log.sql`) with JSON **`payload`** containing **`resident_id`** and **`permit_id`**. A separate process sends email to **`residents.email`**.

1. Add real SMTP settings to **`.env.local`** (do not commit):

```
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your-smtp-user
SMTP_PASS=your-smtp-password
SMTP_SECURE=tls
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME=Barangay Inventory
```

Use **`SMTP_SECURE=none`** (and often port **1025**) for local tools like Mailpit/MailHog without TLS.

2. From the app directory, run the worker manually (demo / cron-style):

```bash
cd inventoryProjBrgy/inventoryProjBrgy
composer install
php worker/send_outbox.php
```

The worker selects **`status = 'pending'`** rows, sends via PHPMailer, writes **`notification_log`**, and sets **`integration_events.status`** to **`processed`** or **`failed`**. If **`SMTP_HOST`** is unset and there are pending events, the script exits with code **2**.

---

## 6. Confirm app → DB connectivity

Create and run this one-off connectivity test (delete after confirming):

```php
<?php
// File: inventoryProjBrgy/inventoryProjBrgy/db_ping.php
// DELETE this file after the Leader confirms connectivity.

require_once __DIR__ . '/env_bootstrap.php';

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'mimds';

$dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Connected. users rows: " . $row['cnt'] . PHP_EOL;

    // Verify new tables exist
    $tables = ['residents','permit_types','permits','payments',
               'integration_events','notification_log'];
    foreach ($tables as $t) {
        $pdo->query("SELECT 1 FROM `$t` LIMIT 1");
        echo "✓ Table `$t` OK" . PHP_EOL;
    }
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
```

Access via browser: `http://localhost/inventoryProjBrgy/inventoryProjBrgy/db_ping.php`
or CLI: `php inventoryProjBrgy/inventoryProjBrgy/db_ping.php`

Expected output:
```
✓ Connected. users rows: 1
✓ Table `residents` OK
✓ Table `permit_types` OK
✓ Table `permits` OK
✓ Table `payments` OK
✓ Table `integration_events` OK
✓ Table `notification_log` OK
```

**Delete `db_ping.php` after the Group Leader confirms this output.**

---

## 7. Regular backups

```bash
# Windows XAMPP
cd C:\xampp\mysql\bin
mysqldump -u root -p mimds > backup_mimds_%date:~-4,4%%date:~-10,2%%date:~-7,2%.sql

# Linux / Mac
mysqldump -u root -p mimds > backup_mimds_$(date +%Y%m%d).sql
```

Schedule weekly minimum; daily before any merge to `main`.

---

## 8. Troubleshooting

| Symptom | Fix |
|---------|-----|
| `No such file or directory` on Linux with `DB_HOST=localhost` | Change to `DB_HOST=127.0.0.1` (forces TCP instead of Unix socket) |
| `Access denied for user 'root'@'localhost'` | XAMPP root has no password by default — leave `DB_PASS=` blank, or set one in phpMyAdmin |
| `Unknown column 'id' in users` | Migration 001 not yet run |
| `Table 'mimds.residents' doesn't exist` | Migrations 002–007 not yet run |
| FK error on migration 001 | Run `mimds.sql` first before any migration |
| FK error on migration 006 | Residents table must exist (migration 002) before linking patient |
