-- IT Asset Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS itam_system;
USE itam_system;

-- Users table (System users for login)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'User') DEFAULT 'User',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employees table (Company employees who use assets)
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    company VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Assets table
CREATE TABLE assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    model VARCHAR(100),
    device_type ENUM('Laptop', 'Desktop', 'Projector', 'Monitor', 'Tablet', 'Phone', 'Server', 'Printer', 'Other') NOT NULL,
    site VARCHAR(50),
    purchased_by VARCHAR(100),
    current_user_id INT,
    previous_user_id INT,
    license VARCHAR(255),
    status ENUM('Active', 'Spare', 'Retired', 'Maintenance', 'Lost') DEFAULT 'Active',
    ram VARCHAR(20),
    os VARCHAR(50),
    purchase_date DATE,
    warranty_expiry DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (current_user_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (previous_user_id) REFERENCES employees(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'),
('user', 'user@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User');

-- Insert sample employees
INSERT INTO employees (name, department, company, email) VALUES
('John Smith', 'IT', 'TechCorp', 'john.smith@company.com'),
('Jane Doe', 'Marketing', 'TechCorp', 'jane.doe@company.com'),
('Mike Johnson', 'Finance', 'TechCorp', 'mike.johnson@company.com'),
('Sarah Wilson', 'HR', 'TechCorp', 'sarah.wilson@company.com'),
('David Brown', 'Operations', 'TechCorp', 'david.brown@company.com');

-- Insert sample assets
INSERT INTO assets (serial_number, model, device_type, site, purchased_by, current_user_id, status, ram, os, purchase_date) VALUES
('LP001', 'Dell Latitude 7420', 'Laptop', 'HQ', 'IT Department', 1, 'Active', '16GB', 'Windows 11', '2023-01-15'),
('LP002', 'MacBook Pro M1', 'Laptop', 'HQ', 'IT Department', 2, 'Active', '16GB', 'macOS Monterey', '2023-02-20'),
('DT001', 'Dell OptiPlex 7090', 'Desktop', 'HQ2', 'IT Department', 3, 'Active', '32GB', 'Windows 11', '2023-03-10'),
('MN001', 'Dell 27" 4K Monitor', 'Monitor', 'HQ', 'IT Department', 1, 'Active', NULL, NULL, '2023-01-15'),
('PJ001', 'Epson PowerLite', 'Projector', 'HQ', 'IT Department', NULL, 'Spare', NULL, NULL, '2022-12-01'),
('LP003', 'HP EliteBook 850', 'Laptop', 'HQ', 'IT Department', NULL, 'Retired', '8GB', 'Windows 10', '2021-06-15');

-- Create indexes for better performance
CREATE INDEX idx_assets_device_type ON assets(device_type);
CREATE INDEX idx_assets_status ON assets(status);
CREATE INDEX idx_assets_current_user ON assets(current_user_id);
CREATE INDEX idx_employees_email ON employees(email);
CREATE INDEX idx_users_email ON users(email);