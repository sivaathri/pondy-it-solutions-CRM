-- ==========================================================
-- PONDY IT SOLUTIONS - REALISTIC SEED DATA
-- Matches exact reference dashboard figures and metrics
-- ==========================================================

-- Roles
INSERT INTO roles (id, role_name, display_name, description) VALUES
(1, 'super_admin', 'Super Admin', 'Full unrestricted system access and configuration control'),
(2, 'admin', 'Admin', 'Business operations, project management, client and billing management'),
(3, 'project_manager', 'Project Manager', 'Project oversight, task assignment, and work log approvals'),
(4, 'developer', 'Senior Developer', 'Code development, task updates, and daily work log submissions'),
(5, 'designer', 'UI/UX Designer', 'Product design, mockups, asset creation, and task execution'),
(6, 'tester', 'QA / Tester', 'Quality assurance, test case execution, and defect logging'),
(7, 'employee', 'Employee', 'Standard employee access for work logs and timesheets');

-- Default Permissions for Admin & Super Admin (all true)
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

-- Users (Password for all is: admin123)
INSERT INTO users (id, emp_code, full_name, email, phone, designation, department, skills, joining_date, employment_status, username, password, role_id) VALUES
(1, 'EMP-001', 'Siva Athri', 'siva@pondyit.com', '+91 98430 11223', 'Founder & Lead Architect', 'Management', 'Full Stack, Cloud Architecture, DevOps, PHP, MySQL', '2022-01-10', 'Active', 'admin', '$2y$12$jaIKvh.KMxKaFOWewUdK7OhWMf82wXzeMaUmVXpQ8HNPm9Y8OuSeq', 1),
(2, 'EMP-002', 'Ravi K', 'ravi@pondyit.com', '+91 97890 22334', 'Senior Project Manager', 'Engineering', 'Project Planning, Agile/Scrum, PHP, Python, React', '2022-06-15', 'Active', 'ravi', '$2y$12$jaIKvh.KMxKaFOWewUdK7OhWMf82wXzeMaUmVXpQ8HNPm9Y8OuSeq', 3),
(3, 'EMP-003', 'Priya M', 'priya@pondyit.com', '+91 96550 33445', 'Senior UI/UX & Frontend Lead', 'Design', 'Figma, HTML5, CSS3, JavaScript, Bootstrap, Vue.js', '2023-02-01', 'Active', 'priya', '$2y$12$jaIKvh.KMxKaFOWewUdK7OhWMf82wXzeMaUmVXpQ8HNPm9Y8OuSeq', 5),
(4, 'EMP-004', 'Arun P', 'arun@pondyit.com', '+91 95440 44556', 'Senior Full Stack Developer', 'Engineering', 'Core PHP, MySQL, REST APIs, Laravel, Node.js', '2023-05-15', 'Active', 'arun', '$2y$12$jaIKvh.KMxKaFOWewUdK7OhWMf82wXzeMaUmVXpQ8HNPm9Y8OuSeq', 4),
(5, 'EMP-005', 'Karthik R', 'karthik@pondyit.com', '+91 94330 55667', 'QA & Testing Engineer', 'Quality Assurance', 'Manual Testing, Automated Testing, Postman, Selenium', '2023-09-01', 'Active', 'karthik', '$2y$12$jaIKvh.KMxKaFOWewUdK7OhWMf82wXzeMaUmVXpQ8HNPm9Y8OuSeq', 6);

-- Clients
INSERT INTO clients (id, client_code, client_name, company_name, email, phone, alternate_phone, address, city, state, country, gst_number, client_type, client_source, notes) VALUES
(1, 'CLI-001', 'Suresh Kumar', 'INEL Pvt. Ltd.', 'suresh@inel.in', '+91 94432 10101', '+91 94432 10102', '14, Industrial Estate, Mettupalayam', 'Puducherry', 'Puducherry', 'India', '34AABCI1234F1Z5', 'Company', 'Referral', 'Key manufacturing client - manufacturing process audit automation'),
(2, 'CLI-002', 'Anand V', 'Zenstay Hospitality', 'contact@zenstay.com', '+91 98840 20202', '+91 98840 20203', '45, Romain Rolland Street, White Town', 'Puducherry', 'Puducherry', 'India', '34AAFCZ5678K1Z2', 'Company', 'Website Enquiry', 'Boutique stay platform with booking calendar and payments'),
(3, 'CLI-003', 'Mohan Raj', 'Lights Event Management', 'info@lightsevent.in', '+91 97720 30303', '', '88, Needarajapayar Street', 'Puducherry', 'Puducherry', 'India', '34AAGPL9012M1Z8', 'Company', 'Direct Referral', 'Corporate events and wedding lighting showcase portal'),
(4, 'CLI-004', 'Thirumalai N', 'Thirumalai Properties', 'support@thirumalaiproperties.com', '+91 96610 40404', '', '22, Cuddalore Road, Mudaliarpet', 'Puducherry', 'Puducherry', 'India', '34AAHPT3456N1Z1', 'Company', 'Walk-in', 'Real estate property listings and lead capture engine'),
(5, 'CLI-005', 'Baskar Rao', 'ABC Traders', 'sales@abctraders.com', '+91 95500 50505', '', '102, Jawaharlal Nehru Street', 'Puducherry', 'Puducherry', 'India', '34AAJPA7890P1Z4', 'Company', 'Website Enquiry', 'Wholesale electronics retailer looking for multi-vendor B2B portal'),
(6, 'CLI-006', 'Dinesh Kumar', 'Auro Moon Residency', 'stay@auromoon.com', '+91 94490 60606', '', '18, Rue Suffren, White Town', 'Puducherry', 'Puducherry', 'India', '', 'Company', 'Referral', 'Heritage boutique hotel booking engine and guest management'),
(7, 'CLI-007', 'Kavitha S', 'Capital Grow Financials', 'kavitha@capitalgrow.in', '+91 93380 70707', '', '56, Rangapillai Street', 'Puducherry', 'Puducherry', 'India', '', 'Individual', 'Direct', 'Educational loan and mutual funds advisory portal'),
(8, 'CLI-008', 'Jaganathan R', 'JSR Mobiles', 'contact@jsrmobiles.in', '+91 92270 80808', '', '74, Bussy Street', 'Puducherry', 'Puducherry', 'India', '', 'Company', 'Social Media', 'E-commerce website with instant mobile exchange buyback valuation');

-- Enquiries
INSERT INTO enquiries (id, enquiry_code, client_id, contact_person, email, phone, project_title, category, description, expected_budget, expected_delivery_date, source, assigned_to, enquiry_date, follow_up_date, status, rejection_reason, notes, converted_to_project) VALUES
(1, 'ENQ-001', 5, 'Baskar Rao', 'sales@abctraders.com', '+91 95500 50505', 'ABC Traders E-Commerce Portal', 'E-commerce', 'Comprehensive e-commerce catalog with inventory tracking, Razorpay gateway, and invoice generator.', 150000.00, '2026-12-25', 'Website Enquiry', 2, '2026-11-12', '2026-11-15', 'Approved', NULL, 'Client agreed on quotation. Ready to initiate project.', 0),
(2, 'ENQ-002', 6, 'Dinesh Kumar', 'stay@auromoon.com', '+91 94490 60606', 'Auro Moon Residency Booking Engine', 'Web Application', 'Direct room booking engine with seasonal pricing, check-in kiosk and WhatsApp reservation confirmation.', 120000.00, '2027-01-15', 'Referral', 1, '2026-11-11', '2026-11-14', 'Discussion', NULL, 'Requested live demo of booking calendar on 14th.', 0),
(3, 'ENQ-003', 7, 'Kavitha S', 'kavitha@capitalgrow.in', '+91 93380 70707', 'Capital Grow Edu Loan Portal', 'Web Application', 'Student loan eligibility calculator, KYC document upload, and bank loan lead distribution system.', 85000.00, '2026-12-30', 'Direct', 2, '2026-11-10', '2026-11-13', 'Follow-up', NULL, 'Sent initial scope document; waiting on financial partners list.', 0),
(4, 'ENQ-004', 8, 'Jaganathan R', 'contact@jsrmobiles.in', '+91 92270 80808', 'JSR Mobiles Buyback & Store', 'E-commerce', 'Online mobile store with real-time dynamic pricing model for used smartphone buyback.', 95000.00, '2026-12-20', 'Social Media', 4, '2026-11-08', '2026-11-12', 'Quotation Sent', NULL, 'Quotation QT-004 sent. Awaiting feedback from proprietor.', 0),
(5, 'ENQ-005', 1, 'Suresh Kumar', 'suresh@inel.in', '+91 94432 10101', 'Q-Track Process Audit', 'Web Application', 'Industrial quality check and assembly line process audit application.', 180000.00, '2026-11-30', 'Referral', 1, '2026-09-01', '2026-09-05', 'Approved', NULL, 'Converted to Project P001', 1),
(6, 'ENQ-006', 2, 'Anand V', 'contact@zenstay.com', '+91 98840 20202', 'Zenstay Platform Development', 'CRM / ERP', 'Multi-property villa and resort reservation system with channel manager integration.', 550000.00, '2027-03-31', 'Website Enquiry', 2, '2026-08-15', '2026-08-20', 'Approved', NULL, 'Converted to Project P002', 1),
(7, 'ENQ-007', 3, 'Mohan Raj', 'info@lightsevent.in', '+91 97720 30303', 'Lights Event Showcase Portal', 'Website Development', 'Interactive wedding lights and event tech portfolio with equipment rent calculator.', 75000.00, '2026-11-20', 'Direct Referral', 1, '2026-10-01', '2026-10-05', 'Approved', NULL, 'Converted to Project P003', 1),
(8, 'ENQ-008', 4, 'Thirumalai N', 'support@thirumalaiproperties.com', '+91 96610 40404', 'Thirumalai Properties Portal', 'Web Application', 'Real estate directory with Google maps integration and WhatsApp enquiry.', 50000.00, '2026-10-31', 'Walk-in', 1, '2026-09-15', '2026-09-18', 'Approved', NULL, 'Converted to Project P004', 1);

-- Enquiry Follow-ups
INSERT INTO enquiry_followups (enquiry_id, followup_date, status_at_time, notes, next_followup_date, created_by) VALUES
(1, '2026-11-12', 'Approved', 'Client approved the proposal of ₹1,50,000. Project setup to begin next week.', '2026-11-15', 1),
(2, '2026-11-11', 'Discussion', 'Discussed PMS channel manager requirements; prepared calendar flow draft.', '2026-11-14', 2),
(3, '2026-11-10', 'Follow-up', 'Shared draft architecture and eligibility calculator schema.', '2026-11-13', 2),
(4, '2026-11-09', 'Quotation Sent', 'Official quotation of ₹95,000 sent via email and WhatsApp.', '2026-11-12', 4);

-- Quotations
INSERT INTO quotations (id, quotation_code, enquiry_id, client_id, project_name, project_description, subtotal, tax_percent, tax_amount, discount_percent, discount_amount, total_amount, valid_until, terms_conditions, notes, status, converted_to_project, created_by) VALUES
(1, 'QT-2026-001', 5, 1, 'Q-Track Process Audit', 'Industrial inspection and digital checklist system with barcode/RFID integration.', 152542.37, 18.00, 27457.63, 0.00, 0.00, 180000.00, '2026-09-30', '50% advance upon PO, 30% on UAT delivery, 20% on final handover.', 'Annual maintenance contract optional at 15% / year.', 'Accepted', 1, 1),
(2, 'QT-2026-002', 6, 2, 'Zenstay Platform', 'End-to-end hospitality PMS, direct booking engine, and property owner dashboard.', 466101.69, 18.00, 83898.31, 0.00, 0.00, 550000.00, '2026-09-15', 'Milestone-based payments: 25% kickoff, 25% Alpha, 25% Beta, 25% Go-live.', 'Cloud hosting charges excluded.', 'Accepted', 1, 1),
(3, 'QT-2026-003', 7, 3, 'Lights Event Website', 'Showcase portal with visual equipment lighting simulator and price estimator.', 63559.32, 18.00, 11440.68, 0.00, 0.00, 75000.00, '2026-10-15', '50% Advance, 50% on live domain deployment.', 'Includes 1 year free domain & cloud hosting.', 'Accepted', 1, 1),
(4, 'QT-2026-004', 8, 4, 'Thirumalai Properties', 'Property portal with dynamic filtering, agent profiles and direct enquiry capture.', 42372.88, 18.00, 7627.12, 0.00, 0.00, 50000.00, '2026-10-10', '50% Advance, 50% upon deployment.', 'Includes SSL and security optimization.', 'Accepted', 1, 1),
(5, 'QT-2026-005', 4, 8, 'JSR Mobiles Buyback & Store', 'E-commerce mobile shop with automated algorithmic buyback valuation engine.', 80508.47, 18.00, 14491.53, 0.00, 0.00, 95000.00, '2026-11-25', '40% advance, 30% mid-review, 30% delivery.', 'Payment gateway fees separate.', 'Sent', 0, 1);

-- Quotation Items
INSERT INTO quotation_items (quotation_id, module_name, description, quantity, unit_price, total_price) VALUES
(1, 'Digital Audit Forms', 'Dynamic drag-and-drop inspection checklists and forms', 1, 45000.00, 45000.00),
(1, 'Scanner Integration', 'Barcode & QR code scanner module for parts verification', 1, 35000.00, 35000.00),
(1, 'Reporting Engine', 'Automated PDF export and non-compliance escalation alerts', 1, 42542.37, 42542.37),
(1, 'Supervisor Dashboard', 'Real-time shift inspection dashboards and analytics', 1, 30000.00, 30000.00),

(2, 'Booking Engine', 'Room availability, dynamic rates, and calendar booking sync', 1, 150000.00, 150000.00),
(2, 'Channel Manager API', 'Two-way iCal & OTA synchronization (Airbnb, Booking.com)', 1, 120000.00, 120000.00),
(2, 'Owner Portal', 'Property payout tracking, expense ledger and reports', 1, 110000.00, 110000.00),
(2, 'Guest Check-in Module', 'Digital signature, photo ID upload, and WhatsApp notifications', 1, 86101.69, 86101.69),

(3, 'Portfolio Showcase', 'Mobile responsive high-res event gallery with category filtering', 1, 25000.00, 25000.00),
(3, 'Event Cost Estimator', 'Interactive lighting and stage equipment package builder', 1, 23559.32, 23559.32),
(3, 'Lead Management', 'WhatsApp floating chat integration and CRM lead sync', 1, 15000.00, 15000.00);

-- Projects
INSERT INTO projects (id, project_code, project_name, client_id, enquiry_id, quotation_id, category, description, start_date, expected_end_date, actual_completion_date, estimated_days, budget, advance_amount, payment_terms, project_manager_id, priority, status, progress, created_by) VALUES
(1, 'P001', 'Q-Track Process Audit', 1, 5, 1, 'Web Application', 'Quality tracking and assembly line defect management platform for INEL manufacturing units.', '2026-09-10', '2026-11-20', NULL, 60, 180000.00, 90000.00, '50% advance, 30% UAT, 20% Delivery', 2, 'High', 'In Progress', 68, 1),
(2, 'P002', 'Zenstay Platform', 2, 6, 2, 'CRM / ERP', 'Full-featured luxury villa rental and boutique hotel management engine with automated guest communication.', '2026-08-20', '2027-04-15', NULL, 450, 550000.00, 137500.00, '25% Kickoff, 25% Alpha, 25% Beta, 25% Delivery', 2, 'Urgent', 'In Progress', 42, 1),
(3, 'P003', 'Lights Event Website', 3, 7, 3, 'Website Development', 'Corporate and grand celebration visual lighting showcase with interactive package pricing.', '2026-10-10', '2026-11-15', NULL, 30, 75000.00, 37500.00, '50% Advance, 50% on Deployment', 1, 'Medium', 'Testing', 90, 1),
(4, 'P004', 'Thirumalai Properties', 4, 8, 4, 'Web Application', 'Puducherry real estate portal with locality maps, RERA compliance badges, and agent listings.', '2026-09-20', '2026-10-15', '2026-10-18', 20, 50000.00, 25000.00, '50% Advance, 50% Deployment', 2, 'Medium', 'Completed', 100, 1),
(5, 'P005', 'ABC Traders E-Commerce', 5, 1, NULL, 'E-commerce', 'B2B online ordering portal with wholesale tiered pricing and GST invoice printing.', '2026-11-15', '2026-12-30', NULL, 45, 150000.00, 50000.00, '35% Advance, 35% Staging, 30% Go-Live', 1, 'High', 'Planning', 15, 1),
(6, 'P006', 'Auro Moon Guest Portal', 6, 2, NULL, 'Web Application', 'Self-service reservation extension with contactless digital key generation.', '2026-11-01', '2026-12-15', NULL, 40, 120000.00, 40000.00, '50% Advance, 50% Handover', 2, 'Low', 'On Hold', 25, 1);

-- Project Members
INSERT INTO project_members (project_id, user_id, role_in_project) VALUES
(1, 1, 'Technical Architect'),
(1, 2, 'Project Manager'),
(1, 4, 'Backend Lead'),
(1, 5, 'QA Engineer'),

(2, 2, 'Project Manager'),
(2, 3, 'UI/UX Lead'),
(2, 4, 'Full Stack Developer'),
(2, 5, 'Automation QA'),

(3, 1, 'Project Lead'),
(3, 3, 'Frontend Designer'),
(3, 4, 'API Integrator'),

(4, 2, 'Project Manager'),
(4, 3, 'Designer & Developer'),

(5, 1, 'Lead Consultant'),
(5, 4, 'Lead Developer');

-- Tasks
INSERT INTO tasks (id, task_code, project_id, task_title, description, assigned_to, assigned_by, priority, start_date, due_date, estimated_hours, estimated_days, actual_hours, status, completion_percentage, remarks) VALUES
(1, 'TSK-101', 1, 'Database Schema & Inspection Form Engine', 'Design audit checklist tables and JSON-driven form builder.', 4, 2, 'High', '2026-09-12', '2026-09-25', 40.00, 5.00, 38.50, 'Completed', 100, 'Delivered and tested with sample checklist.'),
(2, 'TSK-102', 1, 'QR Scanner Integration & Field Validation', 'Camera-based QR reader for equipment serial lookup on Android webview.', 4, 2, 'High', '2026-09-28', '2026-10-15', 50.00, 6.25, 46.00, 'Completed', 100, 'Hardware testing verified in shop floor.'),
(3, 'TSK-103', 1, 'PDF Audit Report Generation', 'Automated PDF export with embedded defect photos, technician signatures and severity scores.', 1, 2, 'Medium', '2026-10-18', '2026-11-05', 35.00, 4.38, 32.00, 'In Progress', 75, 'Layout finalized, adding signature canvas.'),
(4, 'TSK-104', 1, 'UAT Defect Fixes & Stress Testing', 'Conduct automated load test for 200 concurrent floor inspectors.', 5, 2, 'High', '2026-11-06', '2026-11-18', 30.00, 3.75, 18.00, 'In Progress', 60, '14 minor bugs resolved, 2 pending review.'),

(5, 'TSK-201', 2, 'Multi-property Villa Architecture & DB Modeling', 'Core database design for multi-tenancy properties, inventory and date lockouts.', 1, 2, 'Urgent', '2026-08-25', '2026-09-10', 45.00, 5.63, 44.00, 'Completed', 100, 'Reviewed and indexed for fast search.'),
(6, 'TSK-202', 2, 'Guest Booking UI & Date Picker Calendar', 'Interactive room reservation UX with live price recalculation per occupancy.', 3, 2, 'High', '2026-09-15', '2026-10-15', 60.00, 7.50, 58.00, 'Completed', 100, 'Fully responsive on mobile & tablet.'),
(7, 'TSK-203', 2, 'Channel Manager iCal Two-Way Sync', 'Two-way webhook feed with Airbnb and Booking.com for double-booking prevention.', 4, 2, 'Urgent', '2026-10-20', '2026-11-25', 80.00, 10.00, 52.00, 'In Progress', 55, 'Airbnb iCal tested; implementing Booking.com payload parser.'),
(8, 'TSK-204', 2, 'Payment Gateway & Razorpay Webhook Hookup', 'Instant booking confirmation upon verified payment capture with invoice generation.', 1, 2, 'High', '2026-11-10', '2026-11-28', 35.00, 4.38, 12.00, 'In Progress', 35, 'Sandbox keys verified, creating refund workflow.'),

(9, 'TSK-301', 3, 'Landing Page & Responsive UI Design', 'Modern dark theme celebration hero section with lighting effects.', 3, 1, 'Medium', '2026-10-12', '2026-10-25', 30.00, 3.75, 30.00, 'Completed', 100, 'Client highly appreciated design.'),
(10, 'TSK-302', 3, 'Rental Equipment Cost Calculator', 'Real-time budget calculator for sound, lasers, LED walls, and generators.', 4, 1, 'Medium', '2026-10-26', '2026-11-05', 25.00, 3.13, 24.00, 'Completed', 100, 'Calculation formulas verified with Mohan.'),
(11, 'TSK-303', 3, 'Cross-browser Testing & Performance Audit', 'Lighthouse 95+ score optimization, lazy loading and mobile Safari test.', 5, 1, 'High', '2026-11-06', '2026-11-14', 20.00, 2.50, 16.00, 'Under Review', 90, 'All critical test cases passed.'),

(12, 'TSK-401', 4, 'Property Listing & Search Filters', 'Real estate filter by locality, budget range, bedrooms and RERA status.', 3, 2, 'Medium', '2026-09-22', '2026-10-05', 25.00, 3.13, 25.00, 'Completed', 100, 'Completed on schedule.'),
(13, 'TSK-402', 4, 'Lead Capture & WhatsApp Notification', 'Instant alert to Thirumalai sales team on buyer enquiry.', 4, 2, 'Medium', '2026-10-06', '2026-10-14', 15.00, 1.88, 15.00, 'Completed', 100, 'Handed over and deployed.');

-- Task Assignments
INSERT INTO task_assignments (task_id, user_id) VALUES
(1, 4), (2, 4), (3, 1), (4, 5), (4, 4),
(5, 1), (6, 3), (7, 4), (8, 1),
(9, 3), (10, 4), (11, 5),
(12, 3), (13, 4);

-- Daily Work Logs (Realistic logs corresponding to Team Workload in screenshot)
-- Siva: 22 days, Ravi: 18 days, Priya: 14 days, Arun: 11 days, Karthik: 9 days
INSERT INTO work_logs (id, user_id, project_id, task_id, work_date, start_time, end_time, break_minutes, total_hours, work_description, work_completed_today, pending_work, blockers, completion_percentage, status, approved_by, approved_at) VALUES
(1, 1, 1, 3, '2026-11-12', '09:30:00', '18:30:00', 60, 8.00, 'Built PDF export canvas with dynamic signatures for Q-Track inspection records.', 'Generated PDF template with embedded image attachments.', 'Automated email dispatch of completed audit.', 'None', 75, 'Approved', 1, '2026-11-12 18:35:00'),
(2, 2, 1, 4, '2026-11-12', '09:00:00', '18:00:00', 60, 8.00, 'Coordinated UAT testing with INEL plant supervisor; logged 4 minor layout fixes.', 'Reviewed test results and reassigned tickets.', 'Verification of scanner timeouts.', 'Waiting on plant network test.', 68, 'Approved', 1, '2026-11-12 18:40:00'),
(3, 4, 1, 2, '2026-11-12', '10:00:00', '18:30:00', 30, 8.00, 'Fixed camera autofocus delay on Android QR scanner component.', 'Patched WebRTC video stream constraints.', 'Stress test on older Motorola handhelds.', 'None', 100, 'Approved', 2, '2026-11-12 18:45:00'),
(4, 5, 3, 11, '2026-11-11', '09:30:00', '18:00:00', 60, 7.50, 'Cross-browser testing on Lights Event website. Validated forms and modal animations.', 'Logged 2 responsive glitches on iPad Mini.', 'Retest after Priya updates CSS layout.', 'None', 90, 'Approved', 1, '2026-11-11 18:15:00'),
(5, 3, 3, 9, '2026-11-11', '10:00:00', '19:00:00', 60, 8.00, 'Polished lighting simulator card interactions and dark mode color contrast.', 'Optimized SVG glow assets.', 'Handed over for final QA test.', 'None', 100, 'Approved', 1, '2026-11-11 19:05:00'),
(6, 4, 2, 7, '2026-11-11', '09:00:00', '17:30:00', 30, 8.00, 'Integrated Airbnb calendar sync feed with automated background cron parser.', 'Handled date blackout collision logic.', 'Add Booking.com XML feed handling.', 'None', 55, 'Approved', 2, '2026-11-11 17:40:00'),
(7, 1, 2, 8, '2026-11-10', '09:30:00', '18:00:00', 60, 7.50, 'Created secure webhook verification for Razorpay payment captures on Zenstay.', 'Implemented HMAC-SHA256 signature check.', 'Connect to automated tax invoice creation.', 'None', 35, 'Approved', 1, '2026-11-10 18:10:00'),
(8, 2, 2, 5, '2026-11-10', '09:00:00', '18:00:00', 60, 8.00, 'Sprint review and database performance indexing on Zenstay bookings table.', 'Added composite index on property_id and date range.', 'Monitor query latency.', 'None', 100, 'Approved', 1, '2026-11-10 18:20:00');

-- Timesheets (Linked to work logs; person_days = hours / 8.0)
INSERT INTO timesheets (work_log_id, user_id, project_id, task_id, work_date, hours, person_days, notes) VALUES
(1, 1, 1, 3, '2026-11-12', 8.00, 1.000, 'Q-Track PDF Report Engine'),
(2, 2, 1, 4, '2026-11-12', 8.00, 1.000, 'Q-Track UAT Coordination'),
(3, 4, 1, 2, '2026-11-12', 8.00, 1.000, 'QR Scanner Bug Fix'),
(4, 5, 3, 11, '2026-11-11', 7.50, 0.938, 'Lights Event Cross-browser QA'),
(5, 3, 3, 9, '2026-11-11', 8.00, 1.000, 'Lights Event Visual Styling'),
(6, 4, 2, 7, '2026-11-11', 8.00, 1.000, 'Zenstay Calendar Feed Sync'),
(7, 1, 2, 8, '2026-11-10', 7.50, 0.938, 'Zenstay Payment Webhook'),
(8, 2, 2, 5, '2026-11-10', 8.00, 1.000, 'Zenstay Architecture Review');

-- Invoices (Total Invoiced: ₹2,40,000, Received: ₹1,55,000, Pending: ₹85,000)
INSERT INTO invoices (id, invoice_code, client_id, project_id, quotation_id, invoice_date, due_date, description, subtotal, tax_amount, discount_amount, grand_total, paid_amount, balance_amount, status, created_by) VALUES
(1, 'INV-2026-001', 1, 1, 1, '2026-09-12', '2026-09-26', '50% Advance for Q-Track Process Audit software development', 76271.19, 13728.81, 0.00, 90000.00, 90000.00, 0.00, 'Paid', 1),
(2, 'INV-2026-002', 2, 2, 2, '2026-08-25', '2026-09-10', 'Phase 1 Kickoff Advance for Zenstay Multi-property Platform', 63559.32, 11440.68, 0.00, 75000.00, 20000.00, 55000.00, 'Partially Paid', 1),
(3, 'INV-2026-003', 3, 3, 3, '2026-10-12', '2026-10-26', '50% Advance for Lights Event Website Development', 31779.66, 5720.34, 0.00, 37500.00, 30000.00, 7500.00, 'Partially Paid', 1),
(4, 'INV-2026-004', 4, 4, 4, '2026-10-18', '2026-11-02', 'Final Handover Invoice for Thirumalai Properties Portal', 31779.66, 5720.34, 0.00, 37500.00, 15000.00, 22500.00, 'Overdue', 1);

-- Invoice Items
INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES
(1, 'Q-Track Process Audit - Milestone 1: Requirement Specification & DB Design', 1, 76271.19, 76271.19),
(2, 'Zenstay Hospitality PMS - Milestone 1: Core System Architecture & UX Design', 1, 63559.32, 63559.32),
(3, 'Lights Event Website - 50% Project Advance', 1, 31779.66, 31779.66),
(4, 'Thirumalai Properties Portal - Final Deployment & DNS Configuration', 1, 31779.66, 31779.66);

-- Payments (Total Received = 90,000 + 20,000 + 30,000 + 15,000 = ₹1,55,000)
INSERT INTO payments (id, payment_code, client_id, project_id, invoice_id, payment_date, amount, payment_method, transaction_reference, notes, created_by) VALUES
(1, 'PAY-2026-001', 1, 1, 1, '2026-09-15', 90000.00, 'Bank Transfer', 'NEFT-INEL-9921004', 'Full payment for Invoice INV-2026-001', 1),
(2, 'PAY-2026-002', 2, 2, 2, '2026-09-02', 20000.00, 'UPI', 'UPI-ZENSTAY-77261', 'Part payment for kickoff milestone', 1),
(3, 'PAY-2026-003', 3, 3, 3, '2026-11-12', 30000.00, 'UPI', 'UPI-LIGHTS-883910', 'Payment received for Lights Event Website', 1),
(4, 'PAY-2026-004', 4, 4, 4, '2026-10-25', 15000.00, 'Cheque', 'CHQ-SBI-445102', 'Part payment for final handover invoice', 1);

-- Expenses
INSERT INTO expenses (id, expense_code, category, project_id, description, amount, expense_date, payment_method, created_by) VALUES
(1, 'EXP-2026-001', 'Hosting & Server', 2, 'DigitalOcean High Performance Droplet for Zenstay Staging', 4800.00, '2026-09-01', 'Credit Card', 1),
(2, 'EXP-2026-002', 'Domain & SSL', 3, 'Wildcard SSL Certificate and .in Domain for Lights Event', 2499.00, '2026-10-11', 'UPI', 1),
(3, 'EXP-2026-003', 'Software License', 1, 'ZBar Barcode Web Assembly SDK Developer License for Q-Track', 8500.00, '2026-09-20', 'Credit Card', 1),
(4, 'EXP-2026-004', 'Office & Utilities', NULL, 'High speed fiber broadband Internet & Office Power', 3200.00, '2026-11-05', 'UPI', 1);

-- Notifications
INSERT INTO notifications (user_id, title, message, link, type, is_read) VALUES
(1, 'New Enquiry Received', 'ABC Traders submitted an enquiry for E-Commerce Storefront.', 'modules/enquiries/view.php?id=1', 'info', 0),
(1, 'Payment Received', 'Payment of ₹30,000 received for Lights Event Website.', 'modules/finance/payments.php', 'success', 0),
(1, 'Upcoming Deadline', 'Lights Event Website is scheduled for completion on 15 Nov.', 'modules/projects/view.php?id=3', 'warning', 0),
(2, 'Task Assignment', 'You have been assigned as Project Manager for ABC Traders E-Commerce.', 'modules/projects/view.php?id=5', 'info', 1);

-- Activity Logs (Matches Recent Activities in Reference Screenshot!)
INSERT INTO activity_logs (user_id, module, action, description, record_id, ip_address, created_at) VALUES
(2, 'projects', 'update', 'Project "Q-Track Process Audit" progress updated to 68%', 1, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'enquiries', 'create', 'New enquiry from "ABC Traders"', 1, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 'finance', 'payment', 'Payment of ₹30,000 received for "Lights Event Website"', 3, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(1, 'tasks', 'complete', 'Task "API Integration" marked as completed', 2, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'worklogs', 'submit', 'Daily work log submitted for Zenstay Platform', 6, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'projects', 'update', 'Project "Lights Event Website" moved to Testing phase', 3, '127.0.0.1', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Company Settings
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

