-- ==========================================================
-- PONDY IT SOLUTIONS - COMPLETE RESET (WITH USERS)
-- Cleans EVERYTHING back to Fresh Install state
-- Re-creates default Roles, Permissions, Company Settings
-- and creates the default Super Admin user (admin / admin123)
-- Compatible with MySQL 8.0+ / MariaDB 10.4+
-- ==========================================================

USE pondy_crm;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Wipe All Operational Tables
TRUNCATE TABLE activity_logs;
TRUNCATE TABLE notifications;
TRUNCATE TABLE expenses;
TRUNCATE TABLE payments;
TRUNCATE TABLE invoice_items;
TRUNCATE TABLE invoices;
TRUNCATE TABLE timesheets;
TRUNCATE TABLE work_logs;
TRUNCATE TABLE task_comments;
TRUNCATE TABLE task_assignments;
TRUNCATE TABLE tasks;
TRUNCATE TABLE project_files;
TRUNCATE TABLE project_requirements;
TRUNCATE TABLE project_members;
TRUNCATE TABLE projects;
TRUNCATE TABLE quotation_items;
TRUNCATE TABLE quotations;
TRUNCATE TABLE enquiry_followups;
TRUNCATE TABLE enquiries;
TRUNCATE TABLE clients;

-- 2. Wipe User & System Configuration Tables
TRUNCATE TABLE users;
TRUNCATE TABLE permissions;
TRUNCATE TABLE roles;
TRUNCATE TABLE company_settings;

SET FOREIGN_KEY_CHECKS = 1;

-- 3. Re-insert Default System Roles
INSERT INTO roles (id, role_name, display_name, description) VALUES
(1, 'super_admin', 'Super Admin', 'Full unrestricted system access and configuration control'),
(2, 'admin', 'Admin', 'Business operations, project management, client and billing management'),
(3, 'project_manager', 'Project Manager', 'Project oversight, task assignment, and work log approvals'),
(4, 'developer', 'Senior Developer', 'Code development, task updates, and daily work log submissions'),
(5, 'designer', 'UI/UX Designer', 'Product design, mockups, asset creation, and task execution'),
(6, 'tester', 'QA / Tester', 'Quality assurance, test case execution, and defect logging'),
(7, 'employee', 'Employee', 'Standard employee access for work logs and timesheets');

-- 4. Re-insert Default Permissions
INSERT INTO permissions (role_id, module, can_view, can_create, can_edit, can_delete) VALUES
(1, 'clients', 1, 1, 1, 1),
(1, 'enquiries', 1, 1, 1, 1),
(1, 'quotations', 1, 1, 1, 1),
(1, 'projects', 1, 1, 1, 1),
(1, 'tasks', 1, 1, 1, 1),
(1, 'team', 1, 1, 1, 1),
(1, 'worklogs', 1, 1, 1, 1),
(1, 'timesheets', 1, 1, 1, 1),
(1, 'finance', 1, 1, 1, 1),
(1, 'reports', 1, 1, 1, 1),
(1, 'settings', 1, 1, 1, 1),

(2, 'clients', 1, 1, 1, 1),
(2, 'enquiries', 1, 1, 1, 1),
(2, 'quotations', 1, 1, 1, 1),
(2, 'projects', 1, 1, 1, 1),
(2, 'tasks', 1, 1, 1, 1),
(2, 'team', 1, 1, 1, 1),
(2, 'worklogs', 1, 1, 1, 1),
(2, 'timesheets', 1, 1, 1, 1),
(2, 'finance', 1, 1, 1, 1),
(2, 'reports', 1, 1, 1, 1),
(2, 'settings', 1, 1, 1, 0),

(3, 'clients', 1, 0, 0, 0),
(3, 'enquiries', 1, 0, 0, 0),
(3, 'quotations', 1, 0, 0, 0),
(3, 'projects', 1, 1, 1, 0),
(3, 'tasks', 1, 1, 1, 1),
(3, 'team', 1, 0, 0, 0),
(3, 'worklogs', 1, 1, 1, 0),
(3, 'timesheets', 1, 1, 1, 0),
(3, 'finance', 1, 0, 0, 0),
(3, 'reports', 1, 0, 0, 0),
(3, 'settings', 0, 0, 0, 0),

(4, 'clients', 0, 0, 0, 0),
(4, 'enquiries', 0, 0, 0, 0),
(4, 'quotations', 0, 0, 0, 0),
(4, 'projects', 1, 0, 0, 0),
(4, 'tasks', 1, 1, 1, 0),
(4, 'team', 1, 0, 0, 0),
(4, 'worklogs', 1, 1, 1, 0),
(4, 'timesheets', 1, 1, 0, 0),
(4, 'finance', 0, 0, 0, 0),
(4, 'reports', 0, 0, 0, 0),
(4, 'settings', 0, 0, 0, 0);

-- 5. Re-create Default Super Admin Account
-- Default Credentials: Username: admin | Password: admin123
INSERT INTO users (id, emp_code, full_name, email, phone, designation, department, skills, joining_date, employment_status, username, password, role_id) VALUES
(1, 'EMP-001', 'Super Admin', 'admin@pondyit.com', '+91 98430 11223', 'Super Admin', 'Management', 'System Administrator', CURDATE(), 'Active', 'admin', '$2y$12$jaIKvh.KMxKaFOWewUdK7OhWMf82wXzeMaUmVXpQ8HNPm9Y8OuSeq', 1);

-- 6. Re-insert Default Company Settings
INSERT INTO company_settings (setting_key, setting_value) VALUES
('company_name', 'PONDY IT SOLUTIONS'),
('company_tagline', 'Professional Web, Mobile & Enterprise Cloud Software'),
('company_email', 'contact@pondyitsolutions.com'),
('company_phone', '+91 98430 11223'),
('company_website', 'https://pondyitsolutions.com'),
('company_address', 'No. 24, Rue Suffren, White Town, Puducherry - 605001, India'),
('company_gstin', '34AABCP1122D1Z0'),
('currency_symbol', '₹'),
('currency_code', 'INR'),
('standard_working_hours', '8'),
('tax_rate_percent', '18.00'),
('primary_color', '#092B50'),
('secondary_color', '#123D68'),
('accent_color', '#E6B422'),
('date_format', 'd M Y'),
('time_format', 'h:i A');

-- Verification Check
SELECT 'users' AS table_name, COUNT(*) AS count FROM users
UNION ALL
SELECT 'roles', COUNT(*) FROM roles
UNION ALL
SELECT 'company_settings', COUNT(*) FROM company_settings
UNION ALL
SELECT 'projects', COUNT(*) FROM projects
UNION ALL
SELECT 'clients', COUNT(*) FROM clients;
