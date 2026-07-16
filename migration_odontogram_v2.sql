-- ============================================================
-- ODONTOGRAM MIGRATION (v2 — safe to re-run)
--
-- The original migration_odontogram.sql uses a plain ADD COLUMN, which
-- errors out on standard MySQL 8.x if teeth_data already exists (no
-- IF NOT EXISTS support outside MariaDB). This version checks
-- information_schema first, so it can be run again safely on any
-- environment without knowing in advance whether it's already applied.
--
-- Run this against whichever database your db_conn.php currently
-- points to (check DB_NAME / defaults to `defaultdb`).
-- ============================================================

SET @dbname   = DATABASE();
SET @tablename = 'dental_services';
SET @columnname = 'teeth_data';

SET @ddl = (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @dbname
        AND TABLE_NAME   = @tablename
        AND COLUMN_NAME  = @columnname) > 0,
    "SELECT 'teeth_data already exists — skipping.' AS result",
    "ALTER TABLE dental_services ADD COLUMN teeth_data TEXT DEFAULT NULL AFTER tooth_lower"
  )
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- teeth_data stores a JSON array of per-tooth records, one object per
-- selected tooth, e.g.:
--   [{"fdi":18,"status":"planned","shade":"A3","size":"64","notes":""}]
--
-- tooth_upper / tooth_lower stay in the table and keep working exactly
-- as before — they are now auto-calculated FROM teeth_data by
-- odonto_counts() in db_conn.php instead of being typed in manually,
-- so every existing dashboard card, analytics query, and list view
-- keeps working unchanged.
