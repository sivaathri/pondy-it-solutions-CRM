-- ==========================================================
-- PONDY IT SOLUTIONS - DATABASE RESET (WITHOUT USERS)
-- Cleans all transactional & operational CRM business data
-- PRESERVES: users, roles, permissions, company_settings
-- Compatible with MySQL 8.0+ / MariaDB 10.4+
-- ==========================================================

USE pondy_crm;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Reset Activity & Notification Logs
TRUNCATE TABLE activity_logs;
TRUNCATE TABLE notifications;

-- 2. Reset Financial Data (Expenses, Payments, Invoices)
TRUNCATE TABLE expenses;
TRUNCATE TABLE payments;
TRUNCATE TABLE invoice_items;
TRUNCATE TABLE invoices;

-- 3. Reset Timesheets & Work Logs
TRUNCATE TABLE timesheets;
TRUNCATE TABLE work_logs;

-- 4. Reset Tasks & Comments
TRUNCATE TABLE task_comments;
TRUNCATE TABLE task_assignments;
TRUNCATE TABLE tasks;

-- 5. Reset Projects & Requirements
TRUNCATE TABLE project_files;
TRUNCATE TABLE project_requirements;
TRUNCATE TABLE project_members;
TRUNCATE TABLE projects;

-- 6. Reset Quotations
TRUNCATE TABLE quotation_items;
TRUNCATE TABLE quotations;

-- 7. Reset Enquiries
TRUNCATE TABLE enquiry_followups;
TRUNCATE TABLE enquiries;

-- 8. Reset Clients
TRUNCATE TABLE clients;

SET FOREIGN_KEY_CHECKS = 1;

-- Verification Check (users, roles, permissions, company_settings remain intact)
SELECT 'users' AS table_name, COUNT(*) AS count FROM users
UNION ALL
SELECT 'roles', COUNT(*) FROM roles
UNION ALL
SELECT 'permissions', COUNT(*) FROM permissions
UNION ALL
SELECT 'company_settings', COUNT(*) FROM company_settings
UNION ALL
SELECT 'clients', COUNT(*) FROM clients
UNION ALL
SELECT 'projects', COUNT(*) FROM projects
UNION ALL
SELECT 'tasks', COUNT(*) FROM tasks
UNION ALL
SELECT 'invoices', COUNT(*) FROM invoices;
