-- TEKVITO
-- Migration 014: Backfill email_verified_at for existing users

USE tekvito_db;

UPDATE users
SET email_verified_at = COALESCE(email_verified_at, created_at),
    account_status = CASE
        WHEN account_status = 'pending' THEN 'active'
        ELSE account_status
    END
WHERE id > 0 AND email_verified_at IS NULL;

INSERT IGNORE INTO schema_migrations (migration, batch)
VALUES ('014_backfill_email_verified.sql', 3);