
-- TEKVITO
-- Migration 002: Candidate Management
-- MySQL 8.0+

USE tekvito_db;

CREATE TABLE skills (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE candidate_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    headline VARCHAR(255) NULL,
    professional_summary TEXT NULL,
    location VARCHAR(255) NULL,
    country VARCHAR(100) NULL,
    years_experience DECIMAL(4,1) NULL,
    employment_status ENUM(
        'employed',
        'unemployed',
        'open_to_work',
        'not_looking'
    ) NULL,
    linkedin_url VARCHAR(500) NULL,
    portfolio_url VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_candidate_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE candidate_skills (
    candidate_id BIGINT UNSIGNED NOT NULL,
    skill_id BIGINT UNSIGNED NOT NULL,
    proficiency ENUM(
        'beginner',
        'intermediate',
        'advanced',
        'expert'
    ) NULL,
    years_experience DECIMAL(4,1) NULL,
    PRIMARY KEY (candidate_id, skill_id),

    CONSTRAINT fk_candidate_skills_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_candidate_skills_skill
        FOREIGN KEY (skill_id) REFERENCES skills(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE candidate_resumes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size_bytes BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_candidate_resumes_candidate (candidate_id),

    CONSTRAINT fk_candidate_resumes_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE candidate_consents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    consent_type VARCHAR(100) NOT NULL,
    consent_version VARCHAR(50) NOT NULL,
    is_granted BOOLEAN NOT NULL,
    granted_at DATETIME NULL,
    withdrawn_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_candidate_consents_candidate (candidate_id),

    CONSTRAINT fk_candidate_consents_candidate
        FOREIGN KEY (candidate_id) REFERENCES candidate_profiles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
