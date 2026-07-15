# Deploying DentalPortal — GitHub → Aiven (MySQL) → Render

Render doesn't run PHP natively, so this deploys your app as a **Docker** web
service. Render's own free database is PostgreSQL, not MySQL, so we'll keep
MySQL and host it for free on **Aiven** instead of rewriting your SQL.

---

## 0. Files you need to add to your project first

Drop these into the **root** of `dental-portal/` (same level as `index.php`):

- `Dockerfile`
- `docker-entrypoint.sh`
- `.gitignore`
- Replace your existing `db_conn.php` with the updated version (reads DB
  credentials from environment variables instead of hardcoding them)

(All four were generated alongside this guide.)

---

## 1. Push the project to a NEW GitHub repo

Your screenshot showed `github.com/ruikhan/ruikhan` — that looks like your
GitHub **profile README repo**, not a project repo. Create a dedicated one
instead:

1. Go to [github.com/new](https://github.com/new)
2. Name it something like `dental-portal`
3. Leave it empty (no README/license) — you already have local files
4. Create the repo

Then, from inside your local `dental-portal/` folder:

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/dental-portal.git
git push -u origin main
```

**Double-check `db_conn.php` has no real password in it before you push** —
with the updated version this is safe by default since credentials come from
environment variables, not the file itself.

---

## 2. Create a free MySQL database on Aiven

1. Go to [aiven.io](https://aiven.io) → sign up (no credit card required)
2. Create a new service → choose **MySQL** → select the **Free** plan
3. Pick a region close to where Render will run your app (Aiven and Render
   are separate companies, so pick regions that are geographically close to
   minimize latency — e.g. both in the US or both in Europe)
4. Once it's provisioned, open the service → **Overview** tab. Note down:
   - **Host**
   - **Port**
   - **User** (usually `avnadmin`)
   - **Password**
   - **Default database name** (usually `defaultdb`)
5. Download the **CA Certificate** shown on that page — Aiven requires SSL
   connections. Save it somewhere safe (you'll upload it to Render in step 4).

### Import your schema

Your `database.sql` starts with `CREATE DATABASE ... USE dental_portal_db;`
— Aiven already gives you a database (`defaultdb`), so either:

- **Easiest:** open `database.sql` and `admin_auth_schema.sql`, delete the
  `CREATE DATABASE` and `USE dental_portal_db;` lines, and import the rest
  into Aiven's existing database, **or**
- Create an additional database named `dental_portal_db` inside your Aiven
  service via Aiven's console, then import as-is.

To actually run the import, use a MySQL client pointed at your Aiven host —
e.g. **HeidiSQL**, **TablePlus**, **DBeaver**, or the `mysql` command line:

```bash
mysql --host=YOUR_AIVEN_HOST --port=YOUR_PORT -u avnadmin -p \
      --ssl-mode=REQUIRED defaultdb < database.sql

mysql --host=YOUR_AIVEN_HOST --port=YOUR_PORT -u avnadmin -p \
      --ssl-mode=REQUIRED defaultdb < admin_auth_schema.sql
```

(phpMyAdmin won't work here since it's not installed on Aiven — a desktop
MySQL client is the easiest route.)

---

## 3. Create the Web Service on Render

1. Go to [dashboard.render.com](https://dashboard.render.com) → **New** →
   **Web Service**
2. Connect your GitHub account, select the `dental-portal` repo you just
   pushed
3. Render should detect the `Dockerfile` and offer **Docker** as the
   environment automatically. If not, select it manually.
4. Choose an instance type (Free is fine to start — note it spins down after
   15 minutes of inactivity, so the first request after idle time takes
   ~30-60 seconds to wake up)
5. Click **Advanced** to add environment variables (step 4 below) before
   deploying, or add them right after creation — either works.

---

## 4. Add environment variables on Render

In your new Web Service → **Environment** tab, add:

| Key | Value |
|---|---|
| `DB_HOST` | your Aiven host |
| `DB_PORT` | your Aiven port |
| `DB_USER` | `avnadmin` |
| `DB_PASS` | your Aiven password |
| `DB_NAME` | `defaultdb` (or `dental_portal_db` if you created that database) |
| `DB_SSL_CA` | `/etc/ssl/aiven-ca.pem` (see below) |

For the SSL certificate: in the same **Environment** tab, look for
**Secret Files**. Add a secret file with:
- **Filename:** `/etc/ssl/aiven-ca.pem`
- **Contents:** paste the full contents of the CA certificate you downloaded
  from Aiven in step 2

Render mounts this file into your container at that exact path at runtime,
which is what `DB_SSL_CA` points to in the updated `db_conn.php`.

---

## 5. Deploy

Click **Create Web Service** (or **Manual Deploy** if you already created it).
Render will build your Docker image and give you a live URL like:

```
https://dental-portal.onrender.com
```

Visit it — you should land on the login page. Log in with the `admin` /
`password` account from `admin_auth_schema.sql`, then immediately change it
under **Settings → Password**.

---

## 6. Ongoing updates

Every `git push` to `main` triggers an automatic redeploy on Render — no
manual steps needed after this initial setup.

---

## Notes / things to keep in mind

- **Free tier spin-down:** Render's free web services sleep after 15 minutes
  idle. For a live demo you're sharing with others, this means the very
  first visitor after a quiet period waits ~30-60 seconds. Upgrading to a
  paid Starter instance ($7/mo) removes this.
- **Aiven free MySQL** auto-powers-off after a period of total inactivity
  (you're notified by email first) — it wakes back up when you reconnect.
- **`uploads/` folder:** Docker containers on Render don't have persistent
  disk by default on the free tier, so anything written to `uploads/`
  (clinic logos) will be wiped on every redeploy. Fine for a demo; for real
  production use you'd want a persistent disk (paid) or object storage like
  S3/Cloudflare R2 for uploads.
