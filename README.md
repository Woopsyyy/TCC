# 🎓 TCC Account Management System

[![PHP](https://img.shields.io/badge/PHP-8.0+-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.0+-purple.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> A comprehensive web-based account management system designed for Talisay City College (TCC), featuring secure user authentication, role-based access control, and intuitive dashboards for administrators, teachers, and students.

## 🌟 Overview

The TCC Account Management System is a robust web application built to streamline user management within an educational institution. It provides a secure platform for user registration, login, and role-based interactions, ensuring data integrity and user privacy through modern web technologies.

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

### 📊 Dashboards

- **Admin Dashboard**:
  - View and manage all users
  - Verify user accounts
  - Assign and modify user roles
  - Real-time user status updates
- **Teacher Dashboard**:
  - Personalized welcome interface
  - Class management tools (framework ready)
  - Student interaction features
- **Student Dashboard**:
  - Enrollment tracking
  - Class viewing capabilities
  - Personalized learning space

### 🗄️ Database Integration

- **MySQL Database**: Relational database with optimized schema
- **Singleton Database Class**: Efficient connection management and resource handling
- **Prepared Statements**: SQL injection prevention through parameterized queries
- **Dynamic Schema Updates**: Automatic column additions for feature expansion

### 🎨 User Interface

- **Responsive Design**: Bootstrap-powered mobile-friendly interface
- **Custom Styling**: Tailored CSS for login, signup, and dashboard pages
- **Interactive Elements**: Real-time form validation and image preview
- **Consistent Branding**: TCC-themed design with professional aesthetics

### 🔧 Backend Features

- **Modular Architecture**: Organized file structure with separate concerns
- **Error Handling**: Comprehensive error management and user feedback
- **File Upload System**: Secure image handling with validation
- **Authentication Classes**: Object-oriented authentication logic

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

## 📁 Project Structure

```
TCC/
├── BackEnd/
│   ├── auth/
│   │   ├── login.php          # Authentication logic
│   │   ├── signup.php         # User registration
│   │   └── logout.php         # Session termination
│   ├── database/
│   │   └── db.php             # Database connection class
│   └── debug/                 # Debugging utilities
├── database/
│   ├── account_manager.sql    # Main database schema
│   └── update_users_table.sql # Schema updates
├── public/
│   ├── css/                   # Stylesheets
│   │   ├── bootstrap.min.css
│   │   ├── home.css
│   │   ├── login.css
│   │   └── signup.css
│   ├── images/                # Static images
│   ├── js/                    # JavaScript files
│   ├── index.html             # Login page
│   ├── signup.php             # Registration page
│   ├── home.php               # Main dashboard
│   ├── admin_dashboard.php    # Admin management
│   ├── teacher_dashboard.php  # Teacher interface
│   └── student_dashboard.php  # Student interface
├── color pallete.jpg          # Design color reference
├── LICENSE                    # Project license
└── README.md                  # This file
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Built with ❤️ for Talisay City College**
