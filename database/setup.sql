-- Trek Sales Database Schema
-- Version: 1.0
-- Date: 2025-01-15

CREATE DATABASE IF NOT EXISTS trek_sales_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trek_sales_db;

-- Employees Table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    depot VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    specialty VARCHAR(100),
    total_sales INT DEFAULT 0,
    total_commission DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_depot (depot),
    INDEX idx_city (city)
);

-- Sales Table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    list_price DECIMAL(10,2) NOT NULL,
    invoice_amount DECIMAL(10,2) NOT NULL,
    account_price DECIMAL(10,2) NOT NULL,
    commission DECIMAL(10,2) NOT NULL,
    depot VARCHAR(100) NOT NULL,
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    month_key VARCHAR(7), -- Format: YYYY-MM
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_employee (employee_id),
    INDEX idx_category (category),
    INDEX idx_month (month_key),
    INDEX idx_sale_date (sale_date)
);

-- Unknown Sales Table (Bilinmeyen Satışlar)
CREATE TABLE IF NOT EXISTS unknown_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    depot VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_employee_id INT,
    list_price DECIMAL(10,2),
    invoice_amount DECIMAL(10,2),
    account_price DECIMAL(10,2),
    commission DECIMAL(10,2),
    status ENUM('pending', 'assigned', 'confirmed') DEFAULT 'pending',
    source VARCHAR(50) DEFAULT 'auto-detection',
    assigned_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_employee_id) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_depot (depot)
);

-- Targets Table (Hedefler)
CREATE TABLE IF NOT EXISTS targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL UNIQUE,
    target_amount INT NOT NULL DEFAULT 0,
    month_key VARCHAR(7), -- Format: YYYY-MM
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_month (month_key)
);

-- Stock Snapshots Table (Stok Görüntüleri)
CREATE TABLE IF NOT EXISTS stock_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    depot VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    snapshot_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_depot (depot),
    INDEX idx_category (category),
    INDEX idx_snapshot_time (snapshot_time)
);

-- Transfers Table (Transfer Kayıtları)
CREATE TABLE IF NOT EXISTS transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_depot VARCHAR(100) NOT NULL,
    to_depot VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    transfer_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_depot (from_depot),
    INDEX idx_to_depot (to_depot),
    INDEX idx_transfer_date (transfer_date)
);

-- Commission Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('commission_multiplier', '0.83', 'İndirim çarpanı (0.83 = %17 indirim)'),
('income_tax_rate', '0.35', 'Gelir vergisi oranı (%35)'),
('working_days_exclude', 'sunday', 'Çalışılmayan günler'),
('auto_stock_check', 'enabled', 'Otomatik stok kontrolü'),
('stock_check_interval', '30', 'Stok kontrol aralığı (saniye)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert default targets
INSERT INTO targets (category, target_amount, month_key) VALUES
('madone', 15, DATE_FORMAT(NOW(), '%Y-%m')),
('fx', 25, DATE_FORMAT(NOW(), '%Y-%m')),
('marlin', 30, DATE_FORMAT(NOW(), '%Y-%m')),
('domane', 12, DATE_FORMAT(NOW(), '%Y-%m')),
('checkpoint', 8, DATE_FORMAT(NOW(), '%Y-%m')),
('elektrikli', 20, DATE_FORMAT(NOW(), '%Y-%m')),
('dag', 18, DATE_FORMAT(NOW(), '%Y-%m'))
ON DUPLICATE KEY UPDATE target_amount = VALUES(target_amount);

-- Monthly Reports Table (Aylık Raporlar)
CREATE TABLE IF NOT EXISTS monthly_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_key VARCHAR(7) NOT NULL UNIQUE,
    total_sales INT DEFAULT 0,
    total_commission DECIMAL(10,2) DEFAULT 0,
    targets_achieved JSON,
    employee_rankings JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_month (month_key)
);

-- Session/Activity Log (Optional)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);