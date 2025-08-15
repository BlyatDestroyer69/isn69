-- Database Schema for Sistem Kehadiran ISN
-- Create database
CREATE DATABASE IF NOT EXISTS isn_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE isn_attendance;

-- Employees table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ic_number VARCHAR(14) UNIQUE NOT NULL,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(50),
    position VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    face_template LONGTEXT, -- Encrypted face recognition template
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Face scan templates table
CREATE TABLE face_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    template_data LONGTEXT NOT NULL,
    confidence_score DECIMAL(5,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Attendance records table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    clock_in DATETIME,
    clock_out DATETIME,
    clock_in_location_lat DECIMAL(10,8),
    clock_in_location_lng DECIMAL(11,8),
    clock_out_location_lat DECIMAL(10,8),
    clock_out_location_lng DECIMAL(11,8),
    device_mac_address VARCHAR(17),
    device_info TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('clocked_in', 'clocked_out', 'break_start', 'break_end') DEFAULT 'clocked_in',
    verification_method ENUM('ic', 'id', 'face_scan') NOT NULL,
    face_scan_confidence DECIMAL(5,4),
    spsm_sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
    spsm_sync_time TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- MAC Address blacklist for security
CREATE TABLE mac_address_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mac_address VARCHAR(17) UNIQUE NOT NULL,
    reason TEXT,
    blocked_until DATETIME NULL,
    is_permanent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Login attempts tracking
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ic_number VARCHAR(14) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(17),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(100)
);

-- SPSM sync log
CREATE TABLE spsm_sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    sync_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sync_status ENUM('success', 'failed') NOT NULL,
    response_data TEXT,
    error_message TEXT,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('isn_latitude', '3.1390', 'ISN Latitude coordinate'),
('isn_longitude', '101.6869', 'ISN Longitude coordinate'),
('allowed_radius_meters', '150', 'Allowed radius for clock-in/out in meters'),
('spsm_api_url', 'https://spsm.gov.my/api/attendance', 'SPSM API endpoint'),
('face_confidence_threshold', '0.8', 'Minimum face recognition confidence score');

-- Create indexes for better performance
CREATE INDEX idx_attendance_employee_date ON attendance(employee_id, DATE(clock_in));
CREATE INDEX idx_attendance_clock_in ON attendance(clock_in);
CREATE INDEX idx_attendance_mac_address ON attendance(device_mac_address);
CREATE INDEX idx_attendance_spsm_sync ON attendance(spsm_sync_status);
CREATE INDEX idx_login_attempts_ic_ip ON login_attempts(ic_number, ip_address);
CREATE INDEX idx_employees_ic ON employees(ic_number);
CREATE INDEX idx_employees_employee_id ON employees(employee_id);

-- Insert sample employee for testing
INSERT INTO employees (ic_number, employee_id, full_name, department, position, email) VALUES
('800101-01-1234', 'EMP001', 'Ahmad bin Abdullah', 'IT', 'System Administrator', 'ahmad@isn.gov.my'),
('850505-05-5678', 'EMP002', 'Siti binti Mohamed', 'HR', 'HR Officer', 'siti@isn.gov.my'),
('900909-09-9012', 'EMP003', 'Mohd Ali bin Hassan', 'Finance', 'Accountant', 'ali@isn.gov.my'); 