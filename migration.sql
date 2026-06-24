CREATE DATABASE IF NOT EXISTS inventaris_medis
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE inventaris_medis;

CREATE TABLE IF NOT EXISTS medical_equipment (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(150) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    location VARCHAR(150) DEFAULT NULL,
    condition_status VARCHAR(50) NOT NULL DEFAULT 'baik',
    min_stock INT NOT NULL DEFAULT 0,
    total_stock INT NOT NULL DEFAULT 0,
    notes TEXT DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medical_equipment_code (code),
    KEY idx_medical_equipment_name (name),
    KEY idx_medical_equipment_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    equipment_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(100) DEFAULT NULL,
    reference_id BIGINT UNSIGNED DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_stock_movements_equipment_id (equipment_id),
    KEY idx_stock_movements_created_at (created_at),
    CONSTRAINT fk_stock_movements_equipment
        FOREIGN KEY (equipment_id) REFERENCES medical_equipment (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS borrow_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    equipment_id BIGINT UNSIGNED NOT NULL,
    borrower_name VARCHAR(255) NOT NULL,
    borrower_unit VARCHAR(255) DEFAULT NULL,
    quantity INT NOT NULL,
    borrowed_at DATETIME NOT NULL,
    due_at DATETIME DEFAULT NULL,
    returned_at DATETIME DEFAULT NULL,
    status ENUM('borrowed', 'returned') NOT NULL DEFAULT 'borrowed',
    notes TEXT DEFAULT NULL,
    return_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_borrow_transactions_equipment_id (equipment_id),
    KEY idx_borrow_transactions_status (status),
    KEY idx_borrow_transactions_borrowed_at (borrowed_at),
    CONSTRAINT fk_borrow_transactions_equipment
        FOREIGN KEY (equipment_id) REFERENCES medical_equipment (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
