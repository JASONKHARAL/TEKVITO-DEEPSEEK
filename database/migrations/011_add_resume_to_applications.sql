-- TEKVITO
-- Migration 011: Add resume_id to applications
-- MySQL 8.0+

USE tekvito_db;

ALTER TABLE applications
    ADD COLUMN resume_id BIGINT UNSIGNED NULL AFTER candidate_id,
    ADD CONSTRAINT fk_applications_resume
        FOREIGN KEY (resume_id) REFERENCES candidate_resumes(id)
        ON DELETE SET NULL;

INSERT IGNORE INTO schema_migrations (migration, batch)
VALUES ('011_add_resume_to_applications.sql', 2);