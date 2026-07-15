# DentalPortal — Complete Deployment Guide
## Dental Service Management Portal System

---

## 📁 FILE STRUCTURE

```
dental-portal/
│
├── index.php                    ← Main Dashboard
├── db_conn.php                  ← Database Connection
├── manifest.json                ← PWA Manifest
├── sw.js                        ← Service Worker (PWA)
├── offline.php                  ← Offline Fallback Page
├── .htaccess                    ← Apache Server Config
├── database.sql                 ← Database Schema + Sample Data
│
├── assets/
│   ├── style.css                ← All Styles
│   ├── app.js                   ← All JavaScript
│   └── icons/
│       ├── icon-192.png         ← PWA Icon (192×192) — CREATE THIS
│       └── icon-512.png         ← PWA Icon (512×512) — CREATE THIS
│
├── partials/
│   ├── sidebar.php              ← Navigation Sidebar
│   └── topbar.php               ← Top Header Bar
│
├── customers/
│   ├── list.php                 ← All Patients List
│   ├── create.php               ← Add New Patient
│   ├── view.php                 ← Patient Detail + Chat
│   ├── edit.php                 ← Edit Patient
│   └── delete.php               ← Delete Patient
│
├── appointments/
│   ├── list.php                 ← All Appointments
│   ├── create.php               ← New Appointment
│   ├── mark_done.php            ← Mark Appointment Done
│   └── delete.php               ← Delete Appointment
│
└── messages/
    └── index.php                ← Messages Inbox
```

---

## 🖥️ STEP 1 — LOCAL SETUP (XAMPP)

### 1. Copy Files
```
Copy the entire `dental-portal/` folder to:
D:\Xampp\htdocs\dental-portal\
```

### 2. Start XAMPP Services
- Open **XAMPP Control Panel**
- Start **Apache**
- Start **MySQL**

### 3. Create the Database
1. Open browser → go to `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Database name: `dental_portal_db` → Click **Create**
4. Click the **Import** tab
5. Choose file: `dental-portal/database.sql`
6. Click **Go** → database and sample data are created

### 4. Configure db_conn.php
Open `db_conn.php` and set your credentials:
```php
$sName   = "127.0.0.1:3307";   // Use 3306 for default XAMPP port
$uName   = "root";
$pass    = "";                   // Your MySQL password (blank by default in XAMPP)
$db_name = "dental_portal_db";
```

### 5. Test Locally
Open: `http://localhost/dental-portal/`

---

## 🌐 STEP 2 — DEPLOY TO LIVE SERVER

### Option A — Shared Hosting (Recommended for Beginners)
> Works on Hostinger, InfinityFree, 000webhost, cPanel hosts

#### A1. Get a Hosting Plan
- **Free option:** [InfinityFree](https://infinityfree.net) or [000webhost](https://www.000webhost.com)
- **Paid option (recommended):** [Hostinger](https://hostinger.ph) — starts at ~₱99/month
- Requirements: **PHP 7.4+**, **MySQL 5.7+**, **Apache with mod_rewrite**

#### A2. Upload Files via File Manager or FTP

**Using cPanel File Manager:**
1. Log in to your hosting cPanel
2. Open **File Manager** → navigate to `public_html/`
3. Upload all files from `dental-portal/` directly into `public_html/`
   - Or create a subfolder: `public_html/dental-portal/`
4. Make sure `.htaccess` is uploaded (it may be hidden — enable "Show Hidden Files")

**Using FileZilla (FTP):**
1. Download [FileZilla](https://filezilla-project.org/)
2. Enter your FTP credentials from your hosting panel
3. Drag your `dental-portal/` files to `public_html/` on the right panel

#### A3. Create Live Database
1. In cPanel → go to **MySQL Databases**
2. Create a new database: e.g., `yourusername_dental_db`
3. Create a new user with a strong password
4. Add user to database → grant **All Privileges**
5. Go to **phpMyAdmin** → select your new database → **Import** → upload `database.sql`

#### A4. Update db_conn.php for Live Server
```php
$sName   = "localhost";                     // Usually "localhost" on shared hosting
$uName   = "yourusername_dbuser";           // Your DB username
$pass    = "YourStrongPassword123!";        // Your DB password
$db_name = "yourusername_dental_db";        // Your DB name
```

#### A5. Enable HTTPS (SSL)
1. In cPanel → **SSL/TLS** → Install a **Let's Encrypt** free certificate
2. Once installed, uncomment these lines in `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

### Option B — VPS / Cloud Server (Advanced)
> Works on DigitalOcean, Linode, AWS EC2, Google Cloud

```bash
# 1. SSH into your server
ssh root@your-server-ip

# 2. Install LAMP stack
sudo apt update
sudo apt install apache2 php8.1 php8.1-pdo php8.1-mysql mysql-server -y

# 3. Enable Apache modules
sudo a2enmod rewrite headers expires deflate
sudo systemctl restart apache2

# 4. Upload your files
# Use scp or sftp:
scp -r dental-portal/ root@your-server-ip:/var/www/html/

# 5. Set permissions
sudo chown -R www-data:www-data /var/www/html/dental-portal/
sudo chmod -R 755 /var/www/html/dental-portal/

# 6. Create database
mysql -u root -p
CREATE DATABASE dental_portal_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dentaluser'@'localhost' IDENTIFIED BY 'StrongPass123!';
GRANT ALL PRIVILEGES ON dental_portal_db.* TO 'dentaluser'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import SQL
mysql -u dentaluser -p dental_portal_db < /var/www/html/dental-portal/database.sql

# 7. Configure Apache Virtual Host
sudo nano /etc/apache2/sites-available/dental.conf
```

**Virtual Host config (`dental.conf`):**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html/dental-portal
    
    <Directory /var/www/html/dental-portal>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/dental_error.log
    CustomLog ${APACHE_LOG_DIR}/dental_access.log combined
</VirtualHost>
```

```bash
sudo a2ensite dental.conf
sudo systemctl reload apache2

# 8. Install SSL with Certbot
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com
```

---

## 📱 STEP 3 — MAKE IT INSTALLABLE ON MOBILE (PWA)

### Create App Icons
You need two PNG icons for PWA installation:

1. Create a **192×192 px** PNG logo/icon
2. Create a **512×512 px** PNG logo/icon
3. Save them as:
   - `assets/icons/icon-192.png`
   - `assets/icons/icon-512.png`

> **Free tool:** Use [Favicon.io](https://favicon.io) or [RealFaviconGenerator](https://realfavicongenerator.net) to generate icons in all sizes

### How to Install on Android
1. Open Chrome on Android
2. Navigate to your live website
3. Tap the **3-dot menu** (top right)
4. Tap **"Add to Home Screen"** or **"Install App"**
5. Confirm — app icon appears on home screen
6. Opens like a native app (no browser UI)

### How to Install on iPhone (iOS)
1. Open **Safari** on iPhone (must be Safari, not Chrome)
2. Navigate to your live website
3. Tap the **Share button** (box with arrow, bottom center)
4. Scroll down → tap **"Add to Home Screen"**
5. Name it "DentalPortal" → tap **Add**
6. App icon appears on home screen

### Requirements for PWA Install Prompt
- ✅ Site must be served over **HTTPS**
- ✅ `manifest.json` must be linked in HTML
- ✅ `sw.js` (service worker) must be registered
- ✅ Icons must exist at `assets/icons/icon-192.png` and `icon-512.png`

---

## 🗄️ DATABASE TABLES REFERENCE

| Table | Purpose |
|---|---|
| `customers` | Patient name, phone, email, address |
| `dental_services` | Tooth counts, shade, size, billing info |
| `appointments` | Date, time, type (trial/final/etc), status |
| `messages` | Admin ↔ Patient conversation history |

---

## 🔧 COMMON ISSUES & FIXES

| Problem | Fix |
|---|---|
| White page / no output | Check `db_conn.php` credentials; enable PHP error display |
| "Connection failed" | Confirm MySQL is running; check port (3306 vs 3307) |
| `.htaccess` not working | Enable `mod_rewrite` in Apache; set `AllowOverride All` |
| PWA not installing | Must use HTTPS; check icons exist; validate manifest |
| Icons missing | Create `assets/icons/` folder; add icon-192.png and icon-512.png |
| 500 Server Error | Check `.htaccess` syntax; contact hosting support |
| CSS not loading | Check file paths are correct relative to document root |

---

## 🔐 SECURITY CHECKLIST (Before Going Live)

- [ ] Change default MySQL `root` password
- [ ] Set strong DB user password in `db_conn.php`
- [ ] Enable HTTPS / SSL certificate
- [ ] Uncomment HTTPS redirect in `.htaccess`
- [ ] Delete or protect `database.sql` after importing
- [ ] Test all forms for SQL injection (PDO prepared statements already protect you)
- [ ] Set `display_errors = Off` in PHP for production

---

## 📞 QUICK REFERENCE URLS

After deployment, your pages will be at:

| Page | URL |
|---|---|
| Dashboard | `yourdomain.com/index.php` |
| Add Patient | `yourdomain.com/customers/create.php` |
| All Patients | `yourdomain.com/customers/list.php` |
| Appointments | `yourdomain.com/appointments/list.php` |
| Messages | `yourdomain.com/messages/index.php` |

---

*DentalPortal v1.0 — Built with PHP + MySQL + PWA*
