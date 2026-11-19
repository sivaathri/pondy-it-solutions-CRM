-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2026 at 06:56 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pondy_crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `module`, `action`, `description`, `record_id`, `ip_address`, `created_at`) VALUES
(1, 1, 'auth', 'login', 'User logged in successfully', NULL, '::1', '2026-11-12 12:39:11'),
(2, 1, 'settings', 'update', 'Updated company profile settings', NULL, '::1', '2026-11-12 12:40:33'),
(3, 1, 'settings', 'update', 'Updated company profile settings', NULL, '::1', '2026-11-12 13:13:45'),
(4, 1, 'settings', 'update', 'Updated company profile settings', NULL, '::1', '2026-11-12 13:13:57'),
(5, 1, 'clients', 'create', 'Created client profile: Anna Sound Services (CLI-001)', 1, '::1', '2026-11-12 13:19:26'),
(6, 1, 'clients', 'create', 'Created client profile: Nodal Scholar (CLI-002)', 2, '::1', '2026-11-12 13:20:43'),
(7, 1, 'clients', 'create', 'Created client profile: CapitalGrow Edu Loans (CLI-003)', 3, '::1', '2026-11-12 13:22:33'),
(8, 1, 'clients', 'create', 'Created client profile: Lights Event Management (CLI-004)', 4, '::1', '2026-11-12 13:23:43'),
(9, 1, 'clients', 'create', 'Created client profile: thirumalaiproperties (CLI-005)', 5, '::1', '2026-11-12 13:24:34'),
(10, 1, 'clients', 'create', 'Created client profile: rajalakshmyassociates (CLI-006)', 6, '::1', '2026-11-12 13:26:13'),
(11, 1, 'clients', 'create', 'Created client profile: Auro Moon Residency (CLI-007)', 7, '::1', '2026-11-12 13:28:12'),
(12, 1, 'clients', 'create', 'Created client profile: EazyLend (CLI-008)', 8, '::1', '2026-11-12 13:29:49'),
(13, 1, 'projects', 'create', 'Created project: CapitalGrow-CRM (P001)', 1, '::1', '2026-11-12 13:34:22'),
(14, 1, 'projects', 'edit', 'Updated project P001: CapitalGrow-CRM', 1, '::1', '2026-11-12 13:36:25'),
(15, 1, 'projects', 'edit', 'Updated project P001: CapitalGrow-CRM', 1, '::1', '2026-11-12 13:36:49'),
(16, 1, 'finance', 'invoice_create', 'Generated invoice INV-2026-001 for ₹20,000', 1, '::1', '2026-11-12 13:38:20'),
(17, 1, 'auth', 'login', 'User logged in successfully', NULL, '::1', '2026-11-13 04:54:15'),
(18, 1, 'projects', 'create', 'Created project: Billing software (P001)', 2, '::1', '2026-11-13 05:04:26'),
(19, 1, 'projects', 'create', 'Created project: Nodal Scholar (P002)', 3, '::1', '2026-11-13 05:08:33'),
(20, 1, 'projects', 'create', 'Created project: Lights Event Managements (P004)', 5, '::1', '2026-11-13 05:11:24'),
(21, 1, 'projects', 'create', 'Created project: thirumalaiproperties (P005)', 6, '::1', '2026-11-13 05:13:07'),
(22, 1, 'projects', 'create', 'Created project: rajalakshmyassociates (P006)', 7, '::1', '2026-11-13 05:15:15'),
(23, 1, 'projects', 'create', 'Created project: Capital Grow (P003)', 4, '::1', '2026-11-13 05:19:38'),
(24, 1, 'projects', 'create', 'Created project: Auro moon residency (P007)', 8, '::1', '2026-11-13 05:24:36'),
(25, 1, 'projects', 'create', 'Created project: Eazy Lend (P008)', 9, '::1', '2026-11-13 05:26:21'),
(26, 1, 'projects', 'create', 'Created project: Capital grow-CRM (P009)', 10, '::1', '2026-11-13 05:28:45'),
(27, 1, 'finance', 'invoice_create', 'Generated invoice INV-2026-001 for ₹4,000', 2, '::1', '2026-11-13 05:53:38'),
(28, 1, 'finance', 'payment', 'Recorded payment of ₹4,000 (PAY-2026-001)', 1, '::1', '2026-11-13 05:54:27'),
(29, 1, 'finance', 'invoice_create', 'Generated invoice INV-2026-002 for ₹4,000', 3, '::1', '2026-11-13 05:55:56'),
(30, 1, 'finance', 'payment', 'Recorded payment of ₹4,000 (PAY-2026-002)', 2, '::1', '2026-11-13 05:56:33'),
(31, 1, 'finance', 'invoice_create', 'Generated invoice INV-2026-003 for ₹4,000', 4, '::1', '2026-11-13 05:57:22'),
(32, 1, 'finance', 'payment', 'Recorded payment of ₹4,000 (PAY-2026-003)', 3, '::1', '2026-11-13 05:57:32'),
(33, 1, 'finance', 'invoice_create', 'Generated invoice INV-2026-004 for ₹7,000', 5, '::1', '2026-11-13 05:58:32'),
(34, 1, 'finance', 'payment', 'Recorded payment of ₹6,000 (PAY-2026-004)', 4, '::1', '2026-11-13 05:58:48'),
(35, 1, 'finance', 'invoice_create', 'Generated invoice INV-2026-005 for ₹7,000', 6, '::1', '2026-11-13 05:59:31'),
(36, 1, 'finance', 'payment', 'Recorded payment of ₹3,000 (PAY-2026-005)', 5, '::1', '2026-11-13 06:00:22'),
(37, 1, 'finance', 'invoice_create', 'Generated invoice INV-2026-006 for ₹4,000', 7, '::1', '2026-11-13 06:01:21'),
(38, 1, 'finance', 'payment', 'Recorded payment of ₹1,000 (PAY-2026-006)', 6, '::1', '2026-11-13 06:01:48'),
(39, 1, 'auth', 'login', 'User logged in successfully', NULL, '::1', '2026-11-16 05:29:28'),
(40, 1, 'projects', 'create', 'Created project: rajalakshmyassociates-CRM (P010)', 702, '::1', '2026-11-16 05:39:02'),
(41, 1, 'projects', 'add_url', 'Added Demo URL: https://gregarious-moonbeam-561c87.netlify.app', 702, '::1', '2026-11-16 10:23:09'),
(42, 1, 'worklogs', 'submit', 'Submitted daily work log (7 hrs) for Project ID 702', 1, '::1', '2026-11-16 10:24:39'),
(43, 1, 'auth', 'login', 'User logged in successfully', NULL, '::1', '2026-11-17 10:57:31'),
(44, 1, 'auth', 'logout', 'User logged out', NULL, '::1', '2026-11-17 12:20:13'),
(45, 1, 'auth', 'login', 'User logged in successfully', NULL, '::1', '2026-11-17 12:58:34'),
(46, 1, 'auth', 'logout', 'User logged out', NULL, '::1', '2026-11-17 12:58:39'),
(47, 1, 'auth', 'login', 'User logged in successfully', NULL, '::1', '2026-11-19 05:54:44');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `client_code` varchar(30) NOT NULL,
  `client_name` varchar(150) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `alternate_phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT 'Puducherry',
  `state` varchar(100) DEFAULT 'Puducherry',
  `country` varchar(100) DEFAULT 'India',
  `gst_number` varchar(30) DEFAULT NULL,
  `client_type` enum('Individual','Company') DEFAULT 'Company',
  `client_source` varchar(100) DEFAULT 'Direct / Referral',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `client_code`, `client_name`, `company_name`, `email`, `phone`, `alternate_phone`, `address`, `city`, `state`, `country`, `gst_number`, `client_type`, `client_source`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'CLI-001', 'Hari', 'Anna Sound Services', '', '9597615244', '', 'Kootroad', 'TC-kootroad', 'TamilNadu', 'India', '', 'Individual', 'Direct Referral', '', '2026-11-12 13:19:26', '2026-11-12 13:19:26'),
(2, 'CLI-002', 'Bashir', 'Nodal Scholar', '', '9514064005', '', 'Lawspet', 'Puducherry', 'Puducherry', 'India', '', 'Company', 'Website Enquiry', '', '2026-11-12 13:20:43', '2026-11-12 13:20:43'),
(3, 'CLI-003', 'Amar', 'CapitalGrow Edu Loans', 'info@capitalgrow.co.in', '+91 83442 32397', '', '#14 , Anna Nagar Main Road\r\nPUDUCHERRY,605005\r\nINDIA', 'Puducherry', 'Puducherry', 'India', '', 'Company', 'Website Enquiry', '', '2026-11-12 13:22:33', '2026-11-12 13:22:33'),
(4, 'CLI-004', 'Dider Jean', 'Lights Event Management', '', '9677585986', '', 'Lawspet', 'Puducherry', 'Puducherry', 'India', '', 'Company', 'Website Enquiry', '', '2026-11-12 13:23:43', '2026-11-12 13:23:43'),
(5, 'CLI-005', 'Vishnu', 'thirumalaiproperties', 'mail.thirumalaiproperties@gmail.com', '95144 76555', '', 'No. 17 sakthi Nagar main road saram Puducherry - 605013', 'Puducherry', 'Puducherry', 'India', '', 'Company', 'Website Enquiry', '', '2026-11-12 13:24:34', '2026-11-12 13:24:34'),
(6, 'CLI-006', 'Ganesan', 'rajalakshmyassociates', 'rajalakshmyassociates@gmail.com', '7904634737', '', '1st Floor, No. 151/35, Plot 19, Tindivanam Byp Rd, near KBS Travels, Gorimedu, Puducherry - 605013', 'Puducherry', 'Puducherry', 'India', '', 'Company', 'Website Enquiry', '', '2026-11-12 13:26:13', '2026-11-12 13:26:13'),
(7, 'CLI-007', 'Mainthan (Police)', 'Auro Moon Residency', '', '9940972657', '', 'Kootroad', 'TC-Kootroad', 'TamilNadu', 'India', '', 'Company', 'Website Enquiry', '', '2026-11-12 13:28:12', '2026-11-12 13:28:12'),
(8, 'CLI-008', 'Ashok', 'EazyLend', 'eazylend.fs@gmail.com', '9659590505', '', 'Thilaaspet', 'Puducherry', 'Puducherry', 'India', '', 'Company', 'Website Enquiry', '', '2026-11-12 13:29:49', '2026-11-12 13:29:49');

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'company_name', 'PONDY IT SOLUTIONS', '2026-11-12 12:38:13'),
(2, 'company_tagline', 'Professional Web, Mobile & Enterprise Cloud Software', '2026-11-12 12:38:13'),
(3, 'company_email', 'pondyitsolutions26@gmail.com', '2026-11-12 12:40:33'),
(4, 'company_phone', '+91 9659774019', '2026-11-12 12:40:33'),
(5, 'company_website', 'pondyitsolutions.com', '2026-11-12 13:13:57'),
(6, 'company_address', 'Puducherry - 605001, India', '2026-11-12 12:40:33'),
(7, 'company_gstin', '', '2026-11-12 13:13:45'),
(8, 'currency_symbol', '₹', '2026-11-12 12:38:13'),
(9, 'currency_code', 'INR', '2026-11-12 12:38:13'),
(10, 'standard_working_hours', '8', '2026-11-12 12:38:13'),
(11, 'tax_rate_percent', '18.00', '2026-11-12 12:38:13'),
(12, 'primary_color', '#092B50', '2026-11-12 12:38:13'),
(13, 'secondary_color', '#123D68', '2026-11-12 12:38:13'),
(14, 'accent_color', '#E6B422', '2026-11-12 12:38:13'),
(15, 'date_format', 'd M Y', '2026-11-12 12:38:13'),
(16, 'time_format', 'h:i A', '2026-11-12 12:38:13');

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` int(11) NOT NULL,
  `enquiry_code` varchar(30) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `contact_person` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `project_title` varchar(200) NOT NULL,
  `category` enum('Website Development','Web Application','E-commerce','Mobile Application','CRM / ERP','Desktop Application','API Development','Maintenance','Other') DEFAULT 'Web Application',
  `description` text DEFAULT NULL,
  `expected_budget` decimal(12,2) DEFAULT 0.00,
  `expected_delivery_date` date DEFAULT NULL,
  `source` varchar(100) DEFAULT 'Website Enquiry',
  `assigned_to` int(11) DEFAULT NULL,
  `enquiry_date` date NOT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` enum('New','Contacted','Discussion','Follow-up','Quotation Sent','Negotiation','Approved','Rejected','On Hold') DEFAULT 'New',
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `attachments` varchar(255) DEFAULT NULL,
  `converted_to_project` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enquiry_followups`
--

CREATE TABLE `enquiry_followups` (
  `id` int(11) NOT NULL,
  `enquiry_id` int(11) NOT NULL,
  `followup_date` date NOT NULL,
  `status_at_time` varchar(50) DEFAULT NULL,
  `notes` text NOT NULL,
  `next_followup_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_code` varchar(30) NOT NULL,
  `category` enum('Hosting & Server','Domain & SSL','Software License','Hardware & Assets','Office & Utilities','Travel & Fuel','Contractor / Freelancer','Marketing & Sales','Miscellaneous') DEFAULT 'Software License',
  `project_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','UPI','Cheque','Credit Card','Other') DEFAULT 'UPI',
  `receipt_file` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_code` varchar(30) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) DEFAULT 0.00,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `balance_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('Draft','Sent','Partially Paid','Paid','Overdue','Cancelled') DEFAULT 'Sent',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_code`, `client_id`, `project_id`, `quotation_id`, `invoice_date`, `due_date`, `description`, `subtotal`, `tax_amount`, `discount_amount`, `grand_total`, `paid_amount`, `balance_amount`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'INV-2026-001', 3, 4, NULL, '2026-03-30', '2026-11-27', '', 4000.00, 0.00, 0.00, 4000.00, 4000.00, 0.00, 'Paid', 1, '2026-11-13 05:53:38', '2026-11-13 05:54:27'),
(3, 'INV-2026-002', 4, 5, NULL, '2026-07-30', '2026-07-10', '', 4000.00, 0.00, 0.00, 4000.00, 4000.00, 0.00, 'Paid', 1, '2026-11-13 05:55:56', '2026-11-13 05:56:33'),
(4, 'INV-2026-003', 5, 6, NULL, '2026-07-20', '2026-07-30', '', 4000.00, 0.00, 0.00, 4000.00, 4000.00, 0.00, 'Paid', 1, '2026-11-13 05:57:22', '2026-11-13 05:57:32'),
(5, 'INV-2026-004', 6, 7, NULL, '2026-08-05', '2026-10-27', '', 7000.00, 0.00, 0.00, 7000.00, 6000.00, 1000.00, 'Partially Paid', 1, '2026-11-13 05:58:32', '2026-11-13 05:58:48'),
(6, 'INV-2026-005', 7, 8, NULL, '2026-11-13', '2026-11-27', '', 7000.00, 0.00, 0.00, 7000.00, 3000.00, 4000.00, 'Partially Paid', 1, '2026-11-13 05:59:31', '2026-11-13 06:00:22'),
(7, 'INV-2026-006', 8, 9, NULL, '2026-10-30', '2026-12-30', '', 4000.00, 0.00, 0.00, 4000.00, 1000.00, 3000.00, 'Partially Paid', 1, '2026-11-13 06:01:21', '2026-11-13 06:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(8,2) DEFAULT 1.00,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `total`) VALUES
(2, 2, 'Website and SEO Basic and Google Profile', 1.00, 4000.00, 4000.00),
(3, 3, 'Website and basic SEO and Google profile', 1.00, 4000.00, 4000.00),
(4, 4, 'Website and basic SEO and Google profile', 1.00, 4000.00, 4000.00),
(5, 5, 'Website and basic SEO and Google profile', 1.00, 7000.00, 7000.00),
(6, 6, 'Website and basic SEO and Google profile', 1.00, 7000.00, 7000.00),
(7, 7, 'Website and basic SEO and Google profile', 1.00, 4000.00, 4000.00);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_code` varchar(30) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','UPI','Cheque','Credit Card','Other') DEFAULT 'Bank Transfer',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_code`, `client_id`, `project_id`, `invoice_id`, `payment_date`, `amount`, `payment_method`, `transaction_reference`, `notes`, `receipt_file`, `created_by`, `created_at`) VALUES
(1, 'PAY-2026-001', 3, 4, 2, '2026-11-13', 4000.00, 'Bank Transfer', '-', '', NULL, 1, '2026-11-13 05:54:27'),
(2, 'PAY-2026-002', 4, 5, 3, '2026-11-13', 4000.00, 'Bank Transfer', '', '', NULL, 1, '2026-11-13 05:56:33'),
(3, 'PAY-2026-003', 5, 6, 4, '2026-11-13', 4000.00, 'Bank Transfer', '', '', NULL, 1, '2026-11-13 05:57:32'),
(4, 'PAY-2026-004', 6, 7, 5, '2026-11-13', 6000.00, 'Bank Transfer', '', '', NULL, 1, '2026-11-13 05:58:48'),
(5, 'PAY-2026-005', 7, 8, 6, '2026-11-13', 3000.00, 'Bank Transfer', '', '', NULL, 1, '2026-11-13 06:00:22'),
(6, 'PAY-2026-006', 8, 9, 7, '2026-11-13', 1000.00, 'Bank Transfer', '', '', NULL, 1, '2026-11-13 06:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `module` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_code` varchar(30) NOT NULL,
  `project_name` varchar(200) NOT NULL,
  `client_id` int(11) NOT NULL,
  `enquiry_id` int(11) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `category` enum('Website Development','Web Application','E-commerce','Mobile Application','CRM / ERP','Desktop Application','API Development','Maintenance','Other') DEFAULT 'Web Application',
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `expected_end_date` date NOT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `estimated_days` int(11) DEFAULT 0,
  `budget` decimal(12,2) DEFAULT 0.00,
  `advance_amount` decimal(12,2) DEFAULT 0.00,
  `payment_terms` text DEFAULT NULL,
  `project_manager_id` int(11) DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Planning','Not Started','In Progress','Testing','Client Review','On Hold','Completed','Cancelled') DEFAULT 'In Progress',
  `progress` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_code`, `project_name`, `client_id`, `enquiry_id`, `quotation_id`, `category`, `description`, `start_date`, `expected_end_date`, `actual_completion_date`, `estimated_days`, `budget`, `advance_amount`, `payment_terms`, `project_manager_id`, `priority`, `status`, `progress`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'P001', 'Billing software', 1, NULL, NULL, 'Desktop Application', '', '2026-02-17', '2026-03-10', NULL, 21, 0.00, 0.00, '0', 1, 'Medium', 'Completed', 0, 1, '2026-11-13 05:04:26', '2026-11-13 05:04:26'),
(3, 'P002', 'Nodal Scholar', 2, NULL, NULL, 'Website Development', '', '2026-05-13', '2026-06-02', NULL, 20, 0.00, 0.00, '0', 1, 'Medium', 'Completed', 0, 1, '2026-11-13 05:08:33', '2026-11-13 05:08:33'),
(4, 'P003', 'Capital Grow', 3, NULL, NULL, 'Web Application', '', '2026-03-28', '2026-04-10', NULL, 13, 4000.00, 0.00, '0', 1, 'Low', 'Completed', 0, 1, '2026-11-13 05:19:38', '2026-11-13 05:32:29'),
(5, 'P004', 'Lights Event Managements', 4, NULL, NULL, 'Website Development', '', '2026-06-26', '2026-07-08', NULL, 12, 4000.00, 0.00, '', 1, 'Medium', 'Completed', 0, 1, '2026-11-13 05:11:24', '2026-11-13 05:32:29'),
(6, 'P005', 'thirumalaiproperties', 5, NULL, NULL, 'Website Development', '', '2026-07-11', '2026-07-20', NULL, 9, 4000.00, 0.00, '', 1, 'Low', 'Completed', 0, 1, '2026-11-13 05:13:07', '2026-11-13 05:32:29'),
(7, 'P006', 'rajalakshmyassociates', 6, NULL, NULL, 'Website Development', '', '2026-08-05', '2026-10-10', NULL, 60, 7000.00, 0.00, '0', 1, 'Low', 'Completed', 0, 1, '2026-11-13 05:15:15', '2026-11-13 05:32:29'),
(8, 'P007', 'Auro moon residency', 7, NULL, NULL, 'Website Development', '', '2026-08-11', '2026-09-11', NULL, 31, 7000.00, 0.00, '', 1, 'Medium', 'In Progress', 0, 1, '2026-11-13 05:24:36', '2026-11-13 05:24:36'),
(9, 'P008', 'Eazy Lend', 8, NULL, NULL, 'Website Development', '', '2026-10-30', '2026-11-18', NULL, 19, 4000.00, 1000.00, '', 1, 'Medium', 'In Progress', 0, 1, '2026-11-13 05:26:21', '2026-11-13 05:26:21'),
(10, 'P009', 'Capital grow-CRM', 3, NULL, NULL, 'Website Development', '', '2026-11-05', '2027-01-04', NULL, 60, 20000.00, 0.00, '0', 1, 'Medium', 'Planning', 0, 1, '2026-11-13 05:28:45', '2026-11-13 05:28:45'),
(702, 'P010', 'rajalakshmyassociates-CRM', 6, NULL, NULL, 'Desktop Application', '', '2026-11-16', '2026-12-28', NULL, 42, 65000.00, 0.00, '', 1, 'High', 'Planning', 0, 1, '2026-11-16 05:39:02', '2026-11-16 05:39:02');

-- --------------------------------------------------------

--
-- Table structure for table `project_files`
--

CREATE TABLE `project_files` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

CREATE TABLE `project_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_in_project` varchar(100) DEFAULT 'Developer',
  `assigned_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_members`
--

INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role_in_project`, `assigned_at`) VALUES
(4, 2, 1, 'Project Manager', '2026-11-13 05:04:26'),
(5, 3, 1, 'Project Manager', '2026-11-13 05:08:33'),
(6, 5, 1, 'Project Manager', '2026-11-13 05:11:24'),
(7, 6, 1, 'Project Manager', '2026-11-13 05:13:07'),
(8, 7, 1, 'Project Manager', '2026-11-13 05:15:15'),
(9, 4, 1, 'Project Manager', '2026-11-13 05:19:38'),
(10, 8, 1, 'Project Manager', '2026-11-13 05:24:36'),
(11, 9, 1, 'Project Manager', '2026-11-13 05:26:21'),
(12, 10, 1, 'Project Manager', '2026-11-13 05:28:45'),
(13, 702, 1, 'Project Manager', '2026-11-16 05:39:02');

-- --------------------------------------------------------

--
-- Table structure for table `project_requirements`
--

CREATE TABLE `project_requirements` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `module_name` varchar(150) DEFAULT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `status` enum('Pending','Approved','Implemented','Cancelled') DEFAULT 'Approved',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_urls`
--

CREATE TABLE `project_urls` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `url_type` enum('Production','Testing','Demo','Staging','Development','Repository','Figma','Documentation','Other') DEFAULT 'Production',
  `title` varchar(150) DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `project_urls`
--

INSERT INTO `project_urls` (`id`, `project_id`, `url_type`, `title`, `url`, `created_at`, `updated_at`) VALUES
(1, 7, 'Production', 'Production Live', 'rajalakshmyassociates.com', '2026-11-13 05:15:15', '2026-11-13 05:32:29'),
(2, 4, 'Production', 'Production Live', 'capitalgrow.co.in', '2026-11-13 05:19:38', '2026-11-13 05:32:29'),
(3, 8, 'Production', 'Production Live', 'https://auromoonresidency.com', '2026-11-13 05:24:36', '2026-11-13 05:24:36'),
(4, 9, 'Production', 'Production Live', 'https://EazyLend.com', '2026-11-13 05:26:21', '2026-11-13 05:26:21'),
(5, 10, 'Demo', 'Client Demo', 'https://venerable-axolotl-e3adc8.netlify.app', '2026-11-13 05:28:45', '2026-11-13 05:28:45'),
(6, 702, 'Demo', 'Prototype', 'https://gregarious-moonbeam-561c87.netlify.app', '2026-11-16 10:23:09', '2026-11-16 10:23:09');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quotation_code` varchar(30) NOT NULL,
  `enquiry_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `project_name` varchar(200) NOT NULL,
  `project_description` text DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `tax_percent` decimal(5,2) DEFAULT 18.00,
  `tax_amount` decimal(12,2) DEFAULT 0.00,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `valid_until` date DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Draft','Sent','Accepted','Rejected','Expired') DEFAULT 'Sent',
  `converted_to_project` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `module_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(8,2) DEFAULT 1.00,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `total_price` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `display_name`, `description`, `created_at`) VALUES
(1, 'super_admin', 'Super Admin', 'Full unrestricted system access and configuration control', '2026-11-12 12:37:48'),
(2, 'admin', 'Admin', 'Business operations, project management, client and billing management', '2026-11-12 12:37:48'),
(3, 'project_manager', 'Project Manager', 'Project oversight, task assignment, and work log approvals', '2026-11-12 12:37:48'),
(4, 'developer', 'Senior Developer', 'Code development, task updates, and daily work log submissions', '2026-11-12 12:37:48'),
(5, 'designer', 'UI/UX Designer', 'Product design, mockups, asset creation, and task execution', '2026-11-12 12:37:48'),
(6, 'tester', 'QA / Tester', 'Quality assurance, test case execution, and defect logging', '2026-11-12 12:37:48'),
(7, 'employee', 'Employee', 'Standard employee access for work logs and timesheets', '2026-11-12 12:37:48');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_code` varchar(30) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `estimated_hours` decimal(6,2) DEFAULT 0.00,
  `estimated_days` decimal(6,2) DEFAULT 0.00,
  `actual_hours` decimal(6,2) DEFAULT 0.00,
  `status` enum('To Do','Assigned','In Progress','Under Review','Rework','Completed','Cancelled') DEFAULT 'In Progress',
  `completion_percentage` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheets`
--

CREATE TABLE `timesheets` (
  `id` int(11) NOT NULL,
  `work_log_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `work_date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `person_days` decimal(6,3) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timesheets`
--

INSERT INTO `timesheets` (`id`, `work_log_id`, `user_id`, `project_id`, `task_id`, `work_date`, `hours`, `person_days`, `notes`, `created_at`) VALUES
(1, 1, 1, 702, NULL, '2026-11-16', 7.00, 0.880, 'prototype working', '2026-11-16 10:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `emp_code` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `employment_status` enum('Active','On Leave','Probation','Resigned','Terminated') DEFAULT 'Active',
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `avatar` varchar(255) DEFAULT 'assets/images/default-avatar.png',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `emp_code`, `full_name`, `email`, `phone`, `designation`, `department`, `skills`, `joining_date`, `employment_status`, `username`, `password`, `role_id`, `avatar`, `created_at`, `updated_at`) VALUES
(1, 'EMP-001', 'Siva Athri', 'siva@pondyit.com', '+91 98430 11223', 'Founder & Lead Architect', 'Management', 'Full Stack, Cloud Architecture, DevOps, PHP, MySQL', '2022-01-10', 'Active', 'admin', '$2y$12$jaIKvh.KMxKaFOWewUdK7OhWMf82wXzeMaUmVXpQ8HNPm9Y8OuSeq', 1, 'assets/images/default-avatar.png', '2026-11-12 12:39:07', '2026-11-12 12:39:07');

-- --------------------------------------------------------

--
-- Table structure for table `work_logs`
--

CREATE TABLE `work_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `work_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_minutes` int(11) DEFAULT 0,
  `total_hours` decimal(5,2) NOT NULL,
  `work_description` text NOT NULL,
  `work_completed_today` text DEFAULT NULL,
  `pending_work` text DEFAULT NULL,
  `blockers` text DEFAULT NULL,
  `completion_percentage` int(11) DEFAULT 0,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Approved',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `work_logs`
--

INSERT INTO `work_logs` (`id`, `user_id`, `project_id`, `task_id`, `work_date`, `start_time`, `end_time`, `break_minutes`, `total_hours`, `work_description`, `work_completed_today`, `pending_work`, `blockers`, `completion_percentage`, `attachment`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 702, NULL, '2026-11-16', '11:00:00', '19:00:00', 60, 7.00, 'prototype working', '', 'changes like role based', '', 0, NULL, 'Approved', 1, '2026-11-16 15:54:39', NULL, '2026-11-16 10:24:39', '2026-11-16 10:24:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_code` (`client_code`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enquiry_code` (`enquiry_code`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `enquiry_followups`
--
ALTER TABLE `enquiry_followups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enquiry_id` (`enquiry_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_code` (`expense_code`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_code` (`invoice_code`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_code` (`payment_code`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_module` (`role_id`,`module`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD UNIQUE KEY `enquiry_id` (`enquiry_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `project_manager_id` (`project_manager_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `project_files`
--
ALTER TABLE `project_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_proj_user` (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `project_requirements`
--
ALTER TABLE `project_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `project_urls`
--
ALTER TABLE `project_urls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_code` (`quotation_code`),
  ADD KEY `enquiry_id` (`enquiry_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `task_code` (`task_code`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_task_user` (`task_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `work_log_id` (`work_log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_code` (`emp_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `work_logs`
--
ALTER TABLE `work_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `enquiry_followups`
--
ALTER TABLE `enquiry_followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=703;

--
-- AUTO_INCREMENT for table `project_files`
--
ALTER TABLE `project_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `project_requirements`
--
ALTER TABLE `project_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_urls`
--
ALTER TABLE `project_urls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheets`
--
ALTER TABLE `timesheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `work_logs`
--
ALTER TABLE `work_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD CONSTRAINT `enquiries_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `enquiries_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `enquiry_followups`
--
ALTER TABLE `enquiry_followups`
  ADD CONSTRAINT `enquiry_followups_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `enquiry_followups_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `projects_ibfk_3` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `projects_ibfk_4` FOREIGN KEY (`project_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `projects_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `project_files`
--
ALTER TABLE `project_files`
  ADD CONSTRAINT `project_files_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `project_files_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `project_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `project_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project_requirements`
--
ALTER TABLE `project_requirements`
  ADD CONSTRAINT `project_requirements_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project_urls`
--
ALTER TABLE `project_urls`
  ADD CONSTRAINT `project_urls_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `quotations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD CONSTRAINT `timesheets_ibfk_1` FOREIGN KEY (`work_log_id`) REFERENCES `work_logs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `timesheets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `timesheets_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `timesheets_ibfk_4` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `work_logs`
--
ALTER TABLE `work_logs`
  ADD CONSTRAINT `work_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `work_logs_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `work_logs_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `work_logs_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
