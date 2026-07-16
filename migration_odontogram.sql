-- ============================================================
-- ODONTOGRAM MIGRATION
-- Run this once against your existing database (dental_portal_db
-- or defaultdb, whichever you're using).
--
-- NOTE: "IF NOT EXISTS" is intentionally omitted from ADD COLUMN —
-- standard MySQL 8.x doesn't support it there (MariaDB-only feature,
-- same caveat as the service_label column in database.sql). If you
-- re-run this and teeth_data already exists, just skip this file.
-- ============================================================

ALTER TABLE dental_services
    ADD COLUMN teeth_data TEXT DEFAULT NULL AFTER tooth_lower;

-- teeth_data stores a comma-separated list of FDI two-digit tooth
-- codes the admin clicked in the odontogram, e.g. "18,17,21,41".
-- tooth_upper / tooth_lower stay in the table and keep working
-- exactly as before — they're now auto-calculated FROM teeth_data
-- instead of being typed in manually, so every existing dashboard
-- card, analytics query, and list view keeps working unchanged.
