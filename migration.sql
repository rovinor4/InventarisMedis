CREATE DATABASE IF NOT EXISTS inventaris_medis
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventaris_medis;

CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_id (role_id),
    KEY idx_users_deleted_at (deleted_at),
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_tokens_token_hash (token_hash),
    KEY idx_user_tokens_user_id (user_id),
    KEY idx_user_tokens_expires_at (expires_at),
    CONSTRAINT fk_user_tokens_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medical_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100) NOT NULL,
    category VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    stock INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL,
    `condition` ENUM('baik', 'rusak', 'maintenance') NOT NULL DEFAULT 'baik',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medical_items_code (code),
    KEY idx_medical_items_category (category),
    KEY idx_medical_items_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_histories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    medical_item_id BIGINT UNSIGNED NOT NULL,
    type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    stock_after INT NOT NULL,
    condition_after ENUM('baik', 'rusak', 'maintenance') DEFAULT NULL,
    reference_type VARCHAR(100) DEFAULT NULL,
    reference_id BIGINT UNSIGNED DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_stock_histories_medical_item_id (medical_item_id),
    KEY idx_stock_histories_created_by (created_by),
    KEY idx_stock_histories_created_at (created_at),
    CONSTRAINT fk_stock_histories_medical_item
        FOREIGN KEY (medical_item_id) REFERENCES medical_items (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_stock_histories_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS borrowings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    medical_item_id BIGINT UNSIGNED NOT NULL,
    borrower_name VARCHAR(255) NOT NULL,
    borrower_unit VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    borrowed_at DATETIME NOT NULL,
    returned_at DATETIME DEFAULT NULL,
    status ENUM('borrowed', 'partially_returned', 'returned', 'cancelled') NOT NULL DEFAULT 'borrowed',
    notes TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_borrowings_medical_item_id (medical_item_id),
    KEY idx_borrowings_created_by (created_by),
    KEY idx_borrowings_status (status),
    KEY idx_borrowings_deleted_at (deleted_at),
    CONSTRAINT fk_borrowings_medical_item
        FOREIGN KEY (medical_item_id) REFERENCES medical_items (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_borrowings_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `returns` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    borrowing_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    returned_at DATETIME NOT NULL,
    condition_after ENUM('baik', 'rusak', 'maintenance') NOT NULL,
    damage_notes TEXT DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_returns_borrowing_id (borrowing_id),
    KEY idx_returns_created_by (created_by),
    CONSTRAINT fk_returns_borrowing
        FOREIGN KEY (borrowing_id) REFERENCES borrowings (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_returns_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) DEFAULT NULL,
    record_id BIGINT UNSIGNED DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_activity_logs_user_id (user_id),
    KEY idx_activity_logs_action (action),
    KEY idx_activity_logs_created_at (created_at),
    CONSTRAINT fk_activity_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (id, name, slug, created_at, updated_at) VALUES
    (1, 'Super Admin', 'super_admin', NOW(), NOW()),
    (2, 'Admin Inventaris', 'admin_inventaris', NOW(), NOW()),
    (3, 'Petugas Gudang', 'petugas_gudang', NOW(), NOW()),
    (4, 'Petugas Medis', 'petugas_medis', NOW(), NOW()),
    (5, 'Auditor', 'auditor', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    updated_at = NOW();

INSERT INTO users (id, name, email, password_hash, role_id, is_active, created_at, updated_at) VALUES
    (1, 'Super Admin', 'superadmin@example.com', '$2y$12$z4ml7Rh/PKZr7Ko0n4864OMqeRHreEZIR5hQ2k/bztimGv1CBCoMG', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role_id = VALUES(role_id),
    is_active = VALUES(is_active),
    updated_at = NOW();
