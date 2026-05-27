CREATE DATABASE IF NOT EXISTS calculator_investment
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE calculator_investment;

CREATE TABLE investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    initial_capital DECIMAL(16,2) NOT NULL,
    investment_type ENUM('cdb', 'lci', 'lca') NOT NULL,
    rate_type ENUM('pre', 'pos') NOT NULL,
    cdi_percentage DECIMAL(8,2) NOT NULL DEFAULT 0,
    selic_meta DECIMAL(8,2) NOT NULL DEFAULT 0,
    pre_fixed_annual_rate DECIMAL(8,4) NOT NULL DEFAULT 0,
    application_date DATE NOT NULL,
    redemption_date DATE NOT NULL,
    months INT NOT NULL,
    selic_is_over TINYINT(1) NOT NULL DEFAULT 0,
    cdi_over VARCHAR(20) NOT NULL DEFAULT '',
    is_isento TINYINT(1) GENERATED ALWAYS AS (investment_type != 'cdb') STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE investment_estimate (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investment_id INT NOT NULL UNIQUE,
    amount_bruto DECIMAL(16,2) NOT NULL,
    amount_liquid DECIMAL(16,2) NOT NULL,
    profit_bruto DECIMAL(16,2) NOT NULL,
    profit_liquid DECIMAL(16,2) NOT NULL,
    iof_value DECIMAL(16,2) NOT NULL DEFAULT 0,
    ir_tax_amount DECIMAL(16,2) NOT NULL DEFAULT 0,
    monthly_profit_liquid DECIMAL(16,2) NOT NULL DEFAULT 0,
    daily_profit_display DECIMAL(16,2) NOT NULL DEFAULT 0,
    is_isento TINYINT(1) NOT NULL,
    days INT NOT NULL,
    business_days INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_estimate_investment
        FOREIGN KEY (investment_id) REFERENCES investments(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cdi_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate_date DATE NOT NULL UNIQUE,
    daily_rate DECIMAL(10,6) NOT NULL,
    annual_rate DECIMAL(10,6) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE selic_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate_date DATE NOT NULL UNIQUE,
    daily_rate DECIMAL(10,6) NOT NULL,
    annual_rate DECIMAL(10,6) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_cdi_date ON cdi_rates (rate_date DESC);
CREATE INDEX idx_selic_date ON selic_rates (rate_date DESC);
