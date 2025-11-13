# 🎓 TCC Campus Management Platform

A full-stack PHP + MySQL application that centralizes daily operations for Talisay City College. The suite covers authentication, student records, academic sections, facilities, announcements, projects, and an all-new admin settings hub for data backups.

---

## 🚀 What’s Inside
- **Role-aware dashboards** for admins, teachers, and students.
- **Student & section management** with grade tracking and building assignments.
- **Content tools** for announcements, projects, and transparency updates.
- **Facilities module** for buildings, floors, and room allocations.
- **Database backup center** allowing instant exports and scheduled jobs.

---

## 📁 Project Layout
```
TCC/
├── BackEnd/
│   ├── admin/
│   │   ├── backup_settings.php         # Runs manual & scheduled backups
│   │   ├── manage_sections.php         # CRUD for sections & assignments
│   │   ├── manage_projects.php         # Campus project workflows
│   │   ├── manage_buildings.php        # Building/facility maintenance
│   │   ├── manage_users.php            # Admin actions for accounts
│   │   └── settings_section.php        # Partial that renders the admin settings UI
│   ├── auth/
│   │   ├── login.php | signup.php      # Authentication endpoints
│   │   └── logout.php                  # Session teardown
│   ├── database/
│   │   └── db.php                      # Singleton DB connector & auto-migrations
│   └── helpers/, debug/, migrations    # Supporting scripts and utilities
├── public/
│   ├── admin_dashboard.php             # Main admin console (announcements → backups)
│   ├── home.php                        # Role router after login
│   ├── teacher_dashboard.php           # Teacher UX (class tools placeholder)
│   ├── student_dashboard.php           # Student UX (records, finances)
│   ├── css/                            # Bootstrap + custom styles
│   ├── js/ (if present)                # Client behaviour helpers
│   └── assets/                         # Images and uploads
├── database/
│   ├── account_manager.sql             # Primary schema seed
│   ├── *.json                          # Legacy data snapshots
│   └── README.md                       # Database-specific usage notes
└── README.md                           # You are here
```

> Tip: `BackEnd/backups/` is created automatically when you run the admin backup tools. Add it to your VCS ignore list if you don’t want dumps tracked.

---

## 🧰 Technology Stack
- **Language:** PHP 8.2+
- **Database:** MySQL (InnoDB)
- **Server:** Apache via XAMPP (Windows dev target)
- **Frontend:** HTML5, Bootstrap 5, custom SCSS/CSS
- **Security:** Password hashing, prepared statements, session hardening

---

## ⚙️ Getting Started

### 1. Prerequisites
- XAMPP (Apache + MySQL)
- PHP 8.2 or higher (bundled with modern XAMPP builds)
- Composer (optional, for future package management)

### 2. Install Source
```bash
# place inside your htdocs directory
cd C:/xampp/htdocs
git clone <repo-url> TCC
cd TCC
```

### 3. Configure Database
1. Start Apache and MySQL in XAMPP.
2. Import `database/account_manager.sql` using phpMyAdmin or the MySQL CLI.
3. Update credentials in `BackEnd/database/db.php` only if you don’t use the default `root`/empty password setup.
4. First load of the site auto-creates any missing tables (see `Database::ensureTablesExist`).

### 4. Launch
- Visit `http://localhost/TCC/public/index.html` to sign in.
- An initial admin account can be created via `BackEnd/setup_admin.php` if needed.

---

## 🖥️ Admin Console Modules (`admin_dashboard.php`)
| Module | Description |
| ------ | ----------- |
| Announcements | Create and pin notices filtered by year/department. |
| Buildings & Sections | Maintain campus buildings, assign rooms, and map class sections. |
| Projects | Track campus project budgets and milestones. |
| Manage Students | Control enrolments, financial standing, sanctions, and assignments. |
| Grade System | Log prelim/midterm/final grades with year/section filters. |
| Settings | Trigger manual database backups, enable daily scheduling, and download recent dumps. |

---

## 💾 Database Backups
1. Navigate to **Settings** within the admin dashboard.
2. Use **Run Backup** for an immediate `.sql` export (stored under `BackEnd/backups/`).
3. Toggle **Enable daily backup** and set a time to save a schedule.
4. Hook an OS task/cron to call `http://localhost/TCC/BackEnd/admin/backup_settings.php?action=run_schedule` near the configured time for unattended runs.

---

## 🧪 QA & Development Notes
- PHP errors are logged through the default XAMPP configuration; supplement with `error_log()` when tracing.
- JSON fallback data (`database/*.json`) mirrors legacy content used during migrations—handy for seeding or testing.
- Keep an eye on automatic schema alterations performed in `db.php` if you rename columns.

---

## 📜 License
Released under the MIT License. See [`LICENSE`](LICENSE) for full text.

---

> Built with ❤️ for Talisay City College – empowering administrators, teachers, and students alike.
