# PONDY IT SOLUTIONS – CLIENT & PROJECT MANAGEMENT SYSTEM

A complete, professional, responsive Client Relationship Management (CRM) and Project Management System built specifically for **PONDY IT SOLUTIONS**, managing the complete business lifecycle from initial client enquiry to quotation, project approval, employee allocation, daily work logging, timesheet effort tracking, milestone invoicing, expense monitoring, and payment collection.

---

## 1. REFERENCE DESIGN REPRODUCTION

The application faithfully reproduces the design system specified in the reference dashboard:

- **Sidebar Branding:** Dark navy linear gradient (`#092B50` to `#072240`) featuring the official **PONDY IT SOLUTIONS** 3D monogram logo and the historic **Puducherry Lighthouse and palm trees** coastal watermark silhouette at the bottom.
- **Top Header:** Clean enterprise SaaS header with page title & subtitle, global search pill, notification dropdown with active alerts, help link, and user profile pill with initials avatar (`SA` for Siva Athri, Founder & Admin).
- **Summary Metric Cards:** Top 5 KPI metric cards with SVG sparkline wave graphics, color-coded icon boxes, bold values, and percentage trend comparisons vs. last month:
  1. *Total Enquiries* (48, Blue Sparkline)
  2. *Approved Projects* (24, Yellow Sparkline)
  3. *Active Projects* (12, Cyan Sparkline)
  4. *Completed Projects* (26, Green Sparkline)
  5. *Pending Payments* (₹85,000, Red Sparkline)
- **Interactive Charts:**
  1. **Enquiries vs Approved Projects:** Grouped bar chart with blue (`#2563EB`) and golden yellow (`#E6B422`) bars.
  2. **Project Status Donut Chart:** Donut chart with total project count in the center (`24 Projects`) and legend breakdown.
  3. **Team Workload:** Rounded pill horizontal progress bars showing working days contributed per employee.
- **Active Projects Table:** Real-time table with project code, client, team avatar clusters (`SA`, `RK`, `+2`), visual progress bars, duration, and status pills.
- **Project Timeline (Gantt View):** Visual multi-week calendar Gantt timeline across monthly milestones.
- **Recent Activities & Recent Enquiries:** Real-time audit trail with colored status bullets and timestamps.
- **Payment Summary Card:** Circular SVG progress ring displaying the **65% Collection Rate**, alongside Invoiced, Received, and Pending totals.

---

## 2. TECHNOLOGY STACK

- **Frontend:** HTML5, CSS3, Bootstrap 5.3, Vanilla JavaScript, AJAX / Fetch API, Chart.js 4.4, Lucide Icons.
- **Backend:** Core PHP 8+ (No heavy frameworks, strict adherence to native Core PHP standards).
- **Database:** MySQL 8.0+ / MariaDB 10.4+ with normalized relational design, PDO prepared statements, and foreign key cascades.
- **Security:** Bcrypt password hashing (`password_hash` / `password_verify`), CSRF protection tokens, XSS output sanitization (`e()`), role-based access control (RBAC), and session protection.

---

## 3. PROJECT DIRECTORY STRUCTURE

```text
pondy-it-solutions-CRM/
├── assets/
│   ├── css/
│   │   └── style.css                 # Enterprise SaaS CSS & Pondy IT Design System
│   ├── js/
│   │   └── app.js                   # Global JS, icons, modals, and toasts
│   ├── images/
│   │   ├── logo.png                 # Official Pondy IT Solutions logo
│   │   ├── pondy-lighthouse.svg     # Pondicherry lighthouse silhouette watermark
│   │   ├── default-avatar.svg       # Default avatar
│   │   └── dashboard-reference.jpg  # Reference UI screenshot
│   └── uploads/                     # Secure project documents and receipts
├── config/
│   ├── app.php                      # App constants, currency (₹), base URL helper
│   └── database.php                 # PDO database connection
├── database/
│   ├── schema.sql                   # Relational MySQL schema (24 tables)
│   ├── sample_data.sql              # Realistic seed data matching reference figures
│   └── install.php                  # Web and CLI 1-click database installer
├── includes/
│   ├── auth.php                     # Authentication and session management
│   ├── functions.php                # Helpers, formatting, CSRF, alerts, audit logger
│   ├── permissions.php              # Role-based permissions checker
│   ├── header.php                   # Global header and topbar template
│   ├── sidebar.php                  # Global sidebar with lighthouse branding
│   └── footer.php                   # Global footer template
├── modules/
│   ├── clients/                     # Client CRM (List, Add, Edit, View 360, Delete)
│   ├── enquiries/                   # Lead pipeline & 1-click project conversion
│   ├── quotations/                  # Proposal builder, calculation & printable document
│   ├── projects/                    # 9-Tab workspace (Overview, Tasks, Logs, Timesheet...)
│   ├── team/                        # Employee accounts, profiles & workload
│   ├── tasks/                       # Task boards, assignments, subtasks & comments
│   ├── worklogs/                    # Daily work logs with time overlap validation
│   ├── timesheets/                  # Person-days (hours / 8.0) & effort analytics
│   ├── finance/                     # Invoices, Receipts, Overdue Aging, Expenses, Profit
│   ├── reports/                     # 14 business reports, Chart.js & CSV export
│   └── settings/                    # Company info, tax, standard hours, backup guides
├── .htaccess                        # Apache access rules
├── index.php                        # Root router
├── login.php                        # Secure authentication portal
├── logout.php                       # Logout handler
├── dashboard.php                    # Executive dashboard (reference UI)
└── README.md                        # Documentation & setup guide
```

---

## 4. XAMPP LOCAL INSTALLATION GUIDE

### Step 1: Place Files in XAMPP
Copy or clone this repository folder into your XAMPP `htdocs` directory:
```text
C:\xampp\htdocs\pondy-it-solutions-CRM
```
*(Or keep in your existing folder `f:\pondy-it-solutions-CRM` and configure an Apache virtual host or alias).*

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Run the Automated Database Installer
Open your web browser and navigate to:
```text
http://localhost/pondy-it-solutions-CRM/database/install.php
```
*Alternatively, run it via command prompt:*
```powershell
C:\xampp\php\php.exe database/install.php
```
The installer will automatically:
- Create the database `pondy_crm` if it doesn't already exist.
- Import all 24 normalized tables from `database/schema.sql`.
- Seed realistic projects, clients, enquiries, tasks, work logs, invoices, and payments matching the reference dashboard.

### Step 4: Login to the System
Navigate to:
```text
http://localhost/pondy-it-solutions-CRM/login.php
```

#### Default Credentials:
| Account | Username | Password | Role Description |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin` | `admin123` | Siva Athri (Founder & Lead Architect) – Full system control |
| **Project Manager** | `ravi` | `admin123` | Ravi K (Senior Project Manager) – Projects, Tasks & Approvals |
| **Designer Lead** | `priya` | `admin123` | Priya M (Senior UI/UX & Frontend Lead) |
| **Developer Lead** | `arun` | `admin123` | Arun P (Senior Full Stack Developer) |
| **QA Engineer** | `karthik` | `admin123` | Karthik R (QA & Testing Engineer) |

---

## 5. CORE BUSINESS MODULES & FEATURES

### 1. Dashboard
- 5 summary metric cards with live counts and trend percentages.
- 6 interactive Chart.js widgets (Enquiries vs. Projects, Project Status donut, Team workload bars, Gantt timeline, Payment summary with circular progress ring).
- Real-time recent activities and recent enquiries widgets.

### 2. Client Management
- Full CRUD operations with auto-generated client codes (`CLI-001` to `CLI-008`).
- Client 360° view containing their complete project history, enquiry history, invoice records, payments received, and remaining outstanding balances.

### 3. Enquiry Management & 1-Click Project Conversion
- Complete pipeline tracking (`New` &rarr; `Contacted` &rarr; `Discussion` &rarr; `Follow-up` &rarr; `Quotation Sent` &rarr; `Approved`).
- Interactive follow-up history log.
- **Conversion to Project:** Approved enquiries can be converted into active projects with a single click, copying client details and budget while preventing duplicate project creation.

### 4. Quotations Management
- Dynamic quotation generator with real-time math calculation for module deliverables, quantities, unit prices, discounts, and 18% GST.
- Print-ready and downloadable quotation document complete with company branding, terms & conditions, and authorized signature.
- Convert accepted quotations into active projects with auto-populated requirements.

### 5. Project Management (Comprehensive 9-Tab Workspace)
Every project detail page features 9 comprehensive functional tabs:
1. **Overview:** Executive summary, budget, remaining receivables, duration tracking, allocated team.
2. **Requirements:** Module features and specification criteria with priority badges.
3. **Team Members:** Dynamic allocation of employees with designated project roles.
4. **Tasks:** Project task pipeline, progress bars, assignees, and quick task creation.
5. **Work Logs:** Daily time records submitted by team members specifically for this project.
6. **Timesheets:** Effort comparison: Calendar Duration vs. Unique Worked Days vs. Employee Person-Days.
7. **Files:** Secure asset and document uploads with file size and type validation.
8. **Payments:** Linked milestone invoices, cash receipts, and remaining balances.
9. **Activity History:** Full audit trail tracking updates, role assignments, and file uploads.

### 6. Team Management
- Employee profiles with skills, designations, departments, joining dates, and employment statuses.
- Dedicated user login account with role-based access control.
- 360° employee performance profile showing assigned projects, tasks, total person-days, and work history.

### 7. Task Management
- Task codes (`TSK-101`), start and due dates, estimated hours/days, and actual logged hours.
- Interactive progress slider that automatically updates overall project completion percentage.
- Discussion thread for team comments and updates.

### 8. Daily Work Logs
- Standard daily work entry form with automated time duration calculation:
  $$\text{Working Hours} = (\text{End Time} - \text{Start Time}) - \text{Break Duration}$$
- Overlap prevention algorithm prevents employees from submitting duplicate or conflicting time windows on the same date.
- Admin and Project Manager approval/rejection workflow with real-time notifications.

### 9. Timesheets & Person-Days Calculation
- Strict calculation formula as requested:
  $$\text{Person-Days} = \frac{\text{Total Actual Working Hours}}{\text{Configured Standard Hours per Day (8.0)}}$$
- Clearly distinguishes between:
  - **Calendar Duration:** Total days from project start to end date.
  - **Unique Project Working Days:** Distinct calendar days where work was performed.
  - **Employee Person-Days:** Total cumulative human effort invested.

### 10. Payments & Financial Management
- **Invoices:** Professional GST tax invoice generation with line items, milestone descriptions, and print-ready templates.
- **Payments:** Record cash, UPI, bank transfer, and cheque receipts; automatically deducts from invoice balances and updates status (`Paid` / `Partially Paid`).
- **Pending Payments:** Aging receivables report tracking overdue invoices.
- **Expenses:** Categorized expense tracking (Hosting & Server, Domain & SSL, Software License, Utilities, Freelancers).
- **Project Profitability:** Strictly accounts for recorded direct expenses:
  $$\text{Net Profit} = \text{Cash Inflow Received} - \text{Direct Expenses}$$
  $$\text{Operating Profit Margin} = \left(\frac{\text{Net Profit}}{\text{Cash Received}}\right) \times 100$$

### 11. Reports & Analytics
- Complete interactive reporting dashboard with Chart.js:
  1. Monthly Enquiry Report
  2. Enquiry Conversion Report
  3. Approved Projects Report
  4. Project Status Report
  5. Project Completion Report
  6. Employee Performance Report
  7. Employee Workload Report
  8. Project-wise Person-Days Report
  9. Employee-wise Working Days Report
  10. Task Completion Report
  11. Monthly Revenue Report
  12. Pending Payments Report
  13. Expense Report
  14. Project Profitability Report
- Includes 1-click **CSV Data Export** and **Print Report** views.

### 12. Settings
- Company information, official branding, address, GSTIN, and currency configuration.
- Configurable standard working hours per day and default tax percentages.
- User password update and security management.
- Complete database backup and restore command reference.

---

## 6. VERIFICATION & QUALITY ASSURANCE

- All PHP files are syntax-checked with zero syntax or parse errors.
- Every form contains CSRF tokens and server-side validation.
- Responsive design tested across desktop, tablet, and mobile breakpoints.
- All database queries use PDO prepared statements to eliminate SQL injection vulnerabilities.
- Real-time data queries ensure no hardcoded metrics or static dummy charts remain in the application.

Developed exclusively for **PONDY IT SOLUTIONS**.

