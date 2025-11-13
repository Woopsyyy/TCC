# 🎓 TCC Account Management System

[![PHP](https://img.shields.io/badge/PHP-8.0+-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.0+-purple.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> A comprehensive web-based management platform for Talisay City College (TCC), featuring secure user authentication, role-based access control, academic record management, financial tracking, facility management, and administrative tools for seamless educational institution operations.

## 🌟 Overview

The TCC Account Management System is a robust web application built to streamline comprehensive management within an educational institution. It provides a secure platform for user registration, login, and role-based interactions while managing academic records, financial information, facility assignments, announcements, projects, and administrative oversight, ensuring data integrity and user privacy through modern web technologies.

## ✨ Features

### 🔐 Authentication & Security

- **Secure Login System**: Password hashing with PHP's `password_hash()` for enhanced security
- **User Registration**: Seamless signup with profile image upload capability
- **Session Management**: Robust session handling with automatic logout and cookie management
- **Role-Based Access Control**: Three distinct user roles (Admin, Teacher, Student) with specific permissions

### 👤 User Management

- **Profile Customization**: Users can upload and display custom profile images
- **Admin Dashboard**: Comprehensive user verification and role assignment interface
- **User Verification**: Admin-controlled verification process for new accounts
- **Dynamic Role Assignment**: Flexible role changes (Student ↔ Teacher ↔ Admin)
- **User Search & Filtering**: Advanced search functionality for user management
- **Financial Tracking**: Payment status, sanctions, and owing amount management

### 📢 Content Management

- **Announcements System**: Create, edit, and manage system-wide announcements
- **Project Management**: Track project budgets, completion status, and timelines
- **Building & Facility Management**: Manage campus buildings, floors, and room assignments
- **Section Assignments**: Assign classes to specific buildings and rooms

### 📊 Academic Records

- **Student Records**: Year, section, department, and enrollment tracking
- **Financial Records**: Payment status, sanctions, and outstanding balances
- **Assignment Mapping**: Link user accounts to academic records
- **Audit Logging**: Complete administrative action tracking

### 📊 Dashboards

- **Admin Dashboard**:
  - View and manage all users
  - Verify user accounts
  - Assign and modify user roles
  - Manage announcements, projects, and facilities
  - Real-time user status updates
  - Audit log monitoring
- **Teacher Dashboard**:
  - Personalized welcome interface
  - Class management tools (framework ready)
  - Student interaction features
- **Student Dashboard**:
  - Enrollment tracking
  - Class viewing capabilities
  - Financial status overview
  - Personalized learning space

### 🗄️ Database Integration

- **MySQL Database**: Relational database with optimized schema
- **Multiple Tables**: users, announcements, buildings, projects, section_assignments, user_assignments, audit_log
- **Singleton Database Class**: Efficient connection management and resource handling
- **Prepared Statements**: SQL injection prevention through parameterized queries
- **Dynamic Schema Updates**: Automatic column additions for feature expansion
- **Foreign Key Relationships**: Data integrity through proper table relationships

### 🎨 User Interface

- **Responsive Design**: Bootstrap-powered mobile-friendly interface
- **Custom Styling**: Tailored CSS for login, signup, and dashboard pages
- **Interactive Elements**: Real-time form validation and image preview
- **Consistent Branding**: TCC-themed design with professional aesthetics
- **Modern UI Components**: Cards, modals, tooltips, and navigation elements

### 🔧 Backend Features

- **Modular Architecture**: Organized file structure with separate concerns
- **Error Handling**: Comprehensive error management and user feedback
- **File Upload System**: Secure image handling with validation
- **Authentication Classes**: Object-oriented authentication logic
- **Admin API Endpoints**: RESTful endpoints for administrative operations
- **Migration Tools**: JSON to database migration utilities

## 🛠️ Tech Stack

- **Backend**: PHP 8.0+ with OOP principles
- **Database**: MySQL 8.0+ with InnoDB engine
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.0+ for responsive design
- **Server**: Apache/XAMPP for local development
- **Security**: Password hashing, prepared statements, session security

## 📦 Installation

### Prerequisites

- XAMPP (or similar Apache/MySQL stack)
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Modern web browser

### Setup Steps

1. **Clone the Repository**

   ```bash
   git clone https://github.com/yourusername/tcc-account-management.git
   cd tcc-account-management
   ```

2. **Database Setup**

   - Start XAMPP and ensure Apache and MySQL are running
   - Import `database/account_manager.sql` into MySQL
   - Optionally run `database/update_users_table.sql` for additional columns

3. **Configuration**

   - Ensure the project is placed in `C:/xampp/htdocs/TCC/`
   - Update database credentials in `BackEnd/database/db.php` if needed

## 🚀 Usage

### For New Users

1. Visit the signup page (`signup.php`)
2. Fill in your details and upload a profile image
3. Submit the form to create your account
4. Wait for admin verification

### For Existing Users

1. Navigate to the login page (`index.html`)
2. Enter your username and password
3. Access your role-specific dashboard

### Key Relationships

- **id**: Unique identifier for each user
- **username**: Unique login identifier
- **password**: Hashed password for security
- **role**: Determines user permissions and dashboard access
- **verified**: Admin-controlled verification status

## 🗄️ Database Schema

The system uses a MySQL database with the following tables:

### Core Tables

- **`users`** - User accounts (admin, teacher, student)
  - `id`, `username`, `password`, `full_name`, `role`, `verified`, `image_path`
- **`announcements`** - System announcements
  - `id`, `title`, `content`, `year`, `department`, `date`
- **`projects`** - Project information
  - `id`, `name`, `budget`, `started`, `completed`
- **`buildings`** - Building details
  - `id`, `name`, `floors`, `rooms_per_floor`
- **`section_assignments`** - Section to building/room assignments
  - `id`, `year`, `section`, `building`, `floor`, `room`
- **`user_assignments`** - User academic and financial records
  - `id`, `user_id`, `username`, `year`, `section`, `department`, `payment`, `sanctions`, `owing_amount`
- **`audit_log`** - Admin action logs
  - `id`, `admin_user`, `action`, `target_table`, `target_id`, `details`, `created_at`

## 📁 Project Structure

```
TCC/
├── BackEnd/
│   ├── admin/
│   │   ├── delete_announcement.php    # Announcement deletion
│   │   ├── get_announcements.php      # Announcement retrieval API
│   │   ├── manage_announcement.php    # Announcement CRUD operations
│   │   ├── manage_buildings.php       # Building management
│   │   ├── manage_projects.php        # Project management
│   │   ├── manage_section_assignments.php # Section assignment management
│   │   ├── manage_users.php           # User management operations
│   │   ├── map_assignment.php         # User assignment mapping
│   │   ├── save_announcement.php      # Announcement saving
│   │   └── user_search.php            # User search API
│   ├── auth/
│   │   ├── login.php                  # Authentication logic
│   │   ├── signup.php                 # User registration
│   │   ├── logout.php                 # Session termination
│   │   └── update_profile.php         # Profile updates
│   ├── database/
│   │   └── db.php                     # Database connection class
│   ├── debug/                         # Debugging utilities
│   └── setup_admin.php                # Admin account setup
├── database/
│   ├── account_manager.sql            # Main database schema
│   ├── announcements.json             # Sample announcements
│   ├── buildings.json                 # Sample building data
│   ├── projects.json                  # Sample project data
│   ├── schema.sql                     # Complete schema documentation
│   ├── section_assignments.json       # Sample section assignments
│   ├── update_users_table.sql         # Schema updates
│   └── user_assignments.json          # Sample user assignments
├── public/
│   ├── admin/
│   │   └── unmapped_assignments.php   # Unmapped assignment management
│   ├── css/
│   │   ├── admin_dashboard.css        # Admin dashboard styling
│   │   ├── bootstrap.min.css          # Bootstrap framework
│   │   ├── home.css                   # Main application styles
│   │   ├── login.css                  # Login page styling
│   │   └── signup.css                 # Signup page styling
│   ├── images/                        # Static images and assets
│   ├── index.html                     # Login page
│   ├── signup.php                     # Registration page
│   ├── home.php                       # Main dashboard (role-based)
│   ├── admin_dashboard.php            # Admin management interface
│   ├── teacher_dashboard.php          # Teacher interface
│   ├── student_dashboard.php          # Student interface
│   ├── user_management.php            # User management interface
│   ├── settings.php                   # User settings page
│   ├── records.php                    # Records viewing page
│   ├── transparency.php               # Transparency/projects page
│   └── signup.php                     # User registration
├── color pallete.jpg                  # Design color reference
├── LICENSE                            # Project license
├── README.md                          # This documentation
└── TODO.md                            # Development task tracking
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Built with ❤️ for Talisay City College**
