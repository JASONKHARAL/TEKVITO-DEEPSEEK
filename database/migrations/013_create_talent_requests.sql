-- TEKVITO
-- Migration 013: Talent requests (employers requesting job posting help)
-- MySQL 8.0+

USE tekvito_db;

CREATE TABLE IF NOT EXISTS talent_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contact_name VARCHAR(150) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(30) NULL,
    company_name VARCHAR(255) NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    job_description TEXT NOT NULL,
    required_skills TEXT NULL,
    location VARCHAR(255) NULL,
    country VARCHAR(100) NULL,
    budget VARCHAR(150) NULL,
    timeline ENUM('asap','1_2_weeks','1_month','flexible') NOT NULL DEFAULT 'flexible',
    preferred_contact ENUM('email','phone','either') NOT NULL DEFAULT 'email',
    status ENUM('new','in_progress','posted','closed') NOT NULL DEFAULT 'new',
    admin_notes TEXT NULL,
    created_job_id BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tr_status (status),
    INDEX idx_tr_created (created_at),

    CONSTRAINT fk_tr_job
        FOREIGN KEY (created_job_id) REFERENCES jobs(id) ON DELETE SET NULL,
    CONSTRAINT fk_tr_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (migration, batch)
VALUES ('013_create_talent_requests.sql', 3);