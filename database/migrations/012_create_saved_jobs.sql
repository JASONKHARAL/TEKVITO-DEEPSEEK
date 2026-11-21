-- TEKVITO
-- Migration 012: Saved jobs (bookmarks)
-- MySQL 8.0+

USE tekvito_db;

CREATE TABLE IF NOT EXISTS saved_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_user_job (user_id, job_id),
    INDEX idx_saved_user (user_id),
    INDEX idx_saved_job (job_id),

    CONSTRAINT fk_saved_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_job
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration, batch)
VALUES ('012_create_saved_jobs.sql', 2);