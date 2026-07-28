# Ecommerce Auth System (Pure PHP + MySQL)

Signup, login, and a protected dashboard — built entirely in PHP with MySQL,
no Node.js, no frameworks.

## Files

```
php-ecommerce-auth/
  config/db.php          MySQL (PDO) connection
  includes/functions.php  sessions, validation, CSRF, require_login()
  schema.sql               run this first to create the DB + table
  login.php                 professional split-screen login page
  signup.php                 matching signup page
  dashboard.php               PROTECTED — redirects to login.php if not authed
  logout.php
  index.php                    convenience redirect
  assets/css/style.css        all styling
  assets/js/app.js             password show/hide toggle + live validation
```

## How it works

- **Passwords**: hashed with PHP's `password_hash()` (bcrypt, cost 12). Never stored in plain text. Checked with `password_verify()`.
- **Session/token**: PHP's native session, configured as an **HttpOnly, SameSite=Lax** cookie (Secure too, automatically, once you're on HTTPS). That cookie *is* the auth token — JavaScript can never read it, so it can't be stolen via XSS. `session_regenerate_id(true)` runs on every successful login to prevent session fixation.
- **CSRF protection**: every form includes a hidden token checked against the session on submit (`includes/functions.php`).
- **Validation**: required fields, email format, and the password rule (8+ chars, upper/lower/number/special char) run twice — instantly in the browser (`assets/js/app.js`) for a smooth UX, and again in PHP (the real gatekeeper, since client checks can always be bypassed).
- **Password show/hide**: the eye icon button next to each password field toggles the input's `type` between `password` and `text` — pure JS, no page reload.
- **Protected route**: `dashboard.php` calls `require_login()` as its very first line. No valid session → immediate `header('Location: login.php')` redirect, before any page content is rendered.

## Setup

### 1. MySQL

```bash
mysql -u root -p < schema.sql
```

Creates `ecommerce_auth` database and the `users` table
(`id, full_name, email UNIQUE, password_hash, created_at, updated_at`).

### 2. Configure the DB connection

Edit `config/db.php` and set your real credentials:

```php
$DB_HOST = 'localhost';
$DB_NAME = 'ecommerce_auth';
$DB_USER = 'root';
$DB_PASS = 'your_mysql_password';
```

### 3. Run it

Any local PHP server works — e.g. PHP's built-in server:

```bash
php -S localhost:8000
```

Then open `http://localhost:8000`. Or drop the whole folder into your
XAMPP/WAMP/Laragon `htdocs`/`www` directory and open it via `http://localhost/php-ecommerce-auth/`.

## Try it

1. Open `signup.php` — try a weak password first to see the live validation and the red hint.
2. After signup you land on `login.php` with a success banner. Log in — use the eye icon to check your password before submitting.
3. You land on the protected `dashboard.php`, showing your saved profile from MySQL.
4. Click **Log out**, then try opening `dashboard.php` directly by URL — you'll be redirected straight to `login.php`.

## Extending for your store

- Add `orders`/`cart` tables and query them in `dashboard.php` using `current_user_id()`.
- Add a `role` column to `users` for admin areas, and check it inside a `require_admin()` helper alongside `require_login()`.
- For "Forgot password", generate a random token, store it (with an expiry) in a `password_resets` table, email a reset link, and verify the token before allowing a new `password_hash()` to be saved.
