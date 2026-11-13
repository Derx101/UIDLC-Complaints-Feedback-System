-- Select the uidlc_feedback database in phpMyAdmin before importing this file.
-- Use only to initialize a new, empty application database. Do not overwrite live records.

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS complaints (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    reference_code VARCHAR(24) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    type ENUM('complaint', 'feedback') NOT NULL,
    category VARCHAR(80) NOT NULL,
    subject VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Submitted', 'Under Review', 'In Progress', 'Resolved', 'Closed') NOT NULL DEFAULT 'Submitted',
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_complaints_user_created (user_id, created_at),
    INDEX idx_complaints_status_created (status, created_at),
    CONSTRAINT fk_complaints_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    complaint_id BIGINT NOT NULL,
    changed_by INT NOT NULL,
    old_status VARCHAR(40) NULL,
    new_status VARCHAR(40) NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_history_complaint_created (complaint_id, created_at),
    CONSTRAINT fk_history_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session data must live in the database because Vercel container filesystems are ephemeral.
CREATE TABLE IF NOT EXISTS uidlc_sessions (
    session_id VARCHAR(128) NOT NULL PRIMARY KEY,
    session_data MEDIUMBLOB NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    INDEX idx_uidlc_sessions_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
