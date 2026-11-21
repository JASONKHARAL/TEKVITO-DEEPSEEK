-- TEKVITO
-- Migration 010: Schema Migrations Tracker
-- MySQL 8.0+
--
-- Purpose: record which migration files have been applied so they are never
-- accidentally re-run. This is the standard pattern used by Laravel, Rails,
-- Flyway, etc.
--
-- After this runs, backfill it with the 001-009 rows so the tracker reflects
-- the current state.

USE tekvito_db;

CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,   -- filename, e.g. '009_fix_and_extend_schema.sql'
    batch INT UNSIGNED NOT NULL DEFAULT 1,    -- groups migrations run together
    checksum CHAR(64) NULL,                   -- optional SHA-256 of file contents
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Backfill: mark 001-009 as already applied.
-- Adjust the checksums only if you want integrity checks (they are optional).
INSERT IGNORE INTO schema_migrations (migration, batch, checksum) VALUES
('001_create_users_and_access_control.sql', 1, NULL),
('002_create_candidate_management.sql',     1, NULL),
('003_create_job_management.sql',           1, NULL),
('004_create_applications.sql',             1, NULL),
('005_create_organizations.sql',            1, NULL),
('006_create_recruiter_management.sql',     1, NULL),
('007_create_communication.sql',            1, NULL),
('008_create_administration.sql',           1, NULL),
('009_fix_and_extend_schema.sql',           1, NULL);