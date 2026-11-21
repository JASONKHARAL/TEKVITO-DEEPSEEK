-- TEKVITO
-- Migration 009: Fixes and Extensions
-- MySQL 8.0+
--
-- Adds:
--   1. password_reset_tokens
--   2. email_verification_tokens
--   3. UNIQUE(candidate_id, consent_type, consent_version) on candidate_consents
--   4. interviewer_id on interviews
--   5. organization_id on recruiter_profiles
--   6. ENUM-ify recruiter_activity.activity_type (via validation note — see bottom)
--   7. sessions table (optional JWT refresh/session tracking)
--   8. Seed: job categories + common skills (optional starter data)

USE tekvito_db;

-- ---------------------------------------------------------------
-- 1. Password reset tokens
-- ---------------------------------------------------------------

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,      -- SHA-256 hex of the raw token
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    requested_ip VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_password_reset_user (user_id),
    INDEX idx_password_reset_expires (expires_at),

    CONSTRAINT fk_password_reset_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------
-- 2. Email verification tokens
-- ---------------------------------------------------------------

CREATE TABLE email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email_verify_user (user_id),
    INDEX idx_email_verify_expires (expires_at),

    CONSTRAINT fk_email_verify_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------
-- 3. Prevent duplicate consent rows
-- ---------------------------------------------------------------

ALTER TABLE candidate_consents
    ADD UNIQUE KEY uq_candidate_consent (candidate_id, consent_type, consent_version);


-- ---------------------------------------------------------------
-- 4. Add interviewer_id to interviews
-- ---------------------------------------------------------------

ALTER TABLE interviews
    ADD COLUMN interviewer_id BIGINT UNSIGNED NULL AFTER scheduled_by,
    ADD CONSTRAINT fk_interviews_interviewer
        FOREIGN KEY (interviewer_id) REFERENCES users(id)
        ON DELETE SET NULL;


-- ---------------------------------------------------------------
-- 5. Link recruiter profile to organization
-- ---------------------------------------------------------------

ALTER TABLE recruiter_profiles
    ADD COLUMN organization_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD CONSTRAINT fk_recruiter_org
        FOREIGN KEY (organization_id) REFERENCES organizations(id)
        ON DELETE SET NULL;


-- ---------------------------------------------------------------
-- 6. Optional: session tracking table
--    (only if you plan to use opaque tokens / refresh tokens)
-- ---------------------------------------------------------------

CREATE TABLE sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_token_hash CHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL,

    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_expires (expires_at),

    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------
-- 7. Starter seed data (safe to re-run guard: INSERT IGNORE)
-- ---------------------------------------------------------------

INSERT IGNORE INTO job_categories (name, slug, description) VALUES
('Information Technology',      'information-technology', 'Software, systems, and IT roles'),
('Engineering',                 'engineering',            'Mechanical, civil, electrical, and related'),
('Healthcare',                  'healthcare',             'Medical, nursing, and clinical roles'),
('Finance & Accounting',        'finance-accounting',     'Banking, audit, tax, and accounting'),
('Sales & Marketing',           'sales-marketing',        'Sales, marketing, and growth'),
('Human Resources',             'human-resources',        'Recruiting, HR operations, and people'),
('Design',                      'design',                 'Product, UX, UI, and visual design'),
('Customer Support',            'customer-support',       'Support, success, and call center'),
('Operations & Logistics',      'operations-logistics',   'Supply chain, logistics, and operations'),
('Education & Training',        'education-training',     'Teaching and training');

INSERT IGNORE INTO skills (name) VALUES
('PHP'), ('MySQL'), ('JavaScript'), ('TypeScript'), ('React'),
('Vue.js'), ('Node.js'), ('Python'), ('Java'), ('C#'),
('Laravel'), ('REST API'), ('Git'), ('Docker'), ('AWS'),
('SQL'), ('HTML'), ('CSS'), ('Tailwind CSS'), ('Figma');