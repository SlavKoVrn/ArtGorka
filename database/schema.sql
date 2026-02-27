CREATE DATABASE IF NOT EXISTS project_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE project_manager;

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    platform ENUM('WordPress', 'Bitrix', 'Custom', 'Other') NOT NULL DEFAULT 'Other',
    status ENUM('development', 'production', 'maintenance', 'archived') NOT NULL DEFAULT 'development',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status)
) ENGINE=InnoDB;
