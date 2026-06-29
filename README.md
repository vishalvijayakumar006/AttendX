# Smart Event Entry System (QR-Based Check-in Portal)

A professional-grade Event Management and QR-based Entry Validation System built using Core PHP, MySQL, Vanilla HTML5, CSS3, and JavaScript. Designed for conferences, workshops, webinars, hackathons, and corporate event check-ins.

This project is a resume-highlight portfolio application illustrating clean MVC-style folder structures, relational database relationships, secure session management, cryptographic ticket pass generation, and real-time camera scanning integrations.

---

## 🚀 Key Features

### 👤 For Attendees
* **Secure Registration & Login**: Multi-role portal allowing attendees and organizers to register separate profiles securely.
* **Events Discovery**: Browse active workshops, conferences, and hackathons with real-time remaining seat counters.
* **Interactive Dashboard**: View registered event passes, statuses (Approved, Pending, Rejected), and check-in logs.
* **Encrypted QR Badges**: Downloadable entry passes embedded with unique, secure tokens generated on the server.
* **Print-Friendly Tickets**: Specialized print layouts that clean browser navigations when executing print triggers (`Ctrl + P`).

### 🔑 For Organizers / Admins
* **Metrics Dashboard**: Clean statistics counters showing total users, events, check-ins, and booking ratios.
* **Interactive Analytics Charts**: Responsive registration density bar charts and attendance conversion rate doughnut charts (using Chart.js).
* **Event Planner**: Create, edit, publish, and delete events with custom capacities, venue descriptions, and dates.
* **Vetting Console**: Review participant lists and approve or reject registrations in real time.
* **Camera QR scanner**: Browser-based webcam scanner that reads passes, verifies tokens, and prevents duplicate entries.
* **Manual Roster Check-in**: Backup manual roster triggers to check in users with smudged or broken tickets.
* **Attendance Logs & Reports**: Comprehensive filterable reports displaying check-in times and show-up rates.
* **CSV Data Export**: Stream event participants rosters directly into downloadable spreadsheets (Excel / Sheets compatible).

---

## 🛠️ Technology Stack

* **Frontend**: HTML5, CSS3 (Vanilla Dark Glassmorphism Theme, Grid/Flexbox), JavaScript (ES6+), Chart.js (CDN), FontAwesome Icons, Google Fonts (Outfit).
* **Backend**: Core PHP 8.x (PDO Driver, Prepared Statements, Singleton Connection Pool).
* **Database**: MySQL 8.x (InnoDB Transactional Engine, Foreign Key Cascades, Performance Indexes).
* **QR Generation**: Standalone PHP QR Code Library (`phpqrcode`).
* **QR Scanning**: Client-side camera scanner library (`html5-qrcode`).

---

## 📂 Project Directory Structure

```text
smart-event-entry/
├── admin/
│   ├── events/
│   │   ├── create.php         # Publish new event form
│   │   ├── edit.php           # Edit published event details
│   │   └── delete.php         # Delete event handler (cascades registrations)
│   ├── participants/
│   │   ├── list.php           # Manage approvals & manual attendance
│   │   └── export.php         # Exports participant list as a CSV sheet
│   ├── dashboard.php          # Organizer console dashboard with metrics & Chart.js
│   ├── reports.php            # Filterable check-in log history & show-up rates
│   └── verify.php             # Camera scanner portal & manual validation checks
├── assets/
│   ├── css/
│   │   └── style.css          # Shared global stylesheets (to be created)
│   └── js/
│       └── main.js            # Shared utility scripts
├── config/
│   └── database.php           # PDO database connection setup (Singleton Pattern)
├── database/
│   └── schema.sql             # Production database schema creation queries
├── includes/
│   ├── auth_check.php         # Security guards for page access control
│   ├── footer.php             # Common webpage footer template
│   └── header.php             # Dynamic navigation bar based on active roles
├── libs/
│   └── phpqrcode/             # Standalone QR Code generation library
├── uploads/
│   └── qrcodes/               # Folder storing generated ticket pass PNG images
├── user/
│   ├── dashboard.php          # Attendee home portal showing booked ticket cards
│   ├── event_details.php      # Full descriptions and booking actions
│   ├── events.php             # Events discover list with keywords searching
│   ├── profile.php            # Edit profile details & password updates
│   └── register_event.php     # Registration logic & server-side QR compiler
├── ticket.php                 # Renders printable ticket passes containing QR codes
├── index.php                  # Public landing page displaying active events & features
├── login.php                  # User & Admin credentials authentication page
├── logout.php                 # Safely destroys sessions and cookies
└── register.php               # Account registration form with Admin Secret Key guard
```

---

## ⚙️ Installation & Local Setup (XAMPP on Windows)

### 1. Prerequisite Installations
* Download and install **Visual Studio Code** from the [Official site](https://code.visualstudio.com/).
* Download and install **XAMPP (with PHP 8.x)** from [Apache Friends](https://www.apachefriends.org/).

### 2. File Configuration
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Clone or copy this project folder (`smart-event-entry/`) into your XAMPP's public folder:
   `C:\xampp\htdocs\smart-event-entry\`
3. Open your browser and verify the database panel is accessible at: `http://localhost/phpmyadmin/`.

### 3. Database Schema Import
1. In phpMyAdmin, click the **SQL** tab.
2. Open the [schema.sql](file:///C:/xampp/htdocs/smart-event-entry/database/schema.sql) file from this project.
3. Copy all the code, paste it into the phpMyAdmin SQL input box, and click **Go**.
4. This creates a database named `smart_event_db` containing 5 connected InnoDB tables with primary/foreign keys.

### 4. Enable PHP GD Library (Mandatory for QR Code PNG Creation)
1. In the XAMPP Control Panel, next to Apache, click the **Config** button and select **`php.ini`**.
2. Press `Ctrl + F` and search for: **`extension=gd`**.
3. If it has a semicolon at the start (`;extension=gd`), **delete the semicolon** to uncomment it.
4. Save the file (`Ctrl + S`) and close Notepad.
5. In the XAMPP Control Panel, click **Stop** next to Apache, and then click **Start** to restart it.

### 5. Accessing the Application
* Public Homepage: `http://localhost/smart-event-entry/index.php`
* Account Creation: `http://localhost/smart-event-entry/register.php`
* Accessing Organizer (Admin) accounts requires entering the secret access key on sign-up: **`AdminEntryPass2026`**.

---

## 🔒 Security Practices Implemented

* **SQL Injection Prevention**: Using PDO driver with disabled emulate prepares (`PDO::ATTR_EMULATE_PREPARES => false`). Every query utilizes **Prepared Statements** with bound parameters to ensure data inputs are not interpreted as SQL commands.
* **Secure Password Hashing**: Passwords are hashed using the standard **Bcrypt** algorithm via PHP's `password_hash()` function.
* **Tamper-proof Ticket Tokens**: QR codes do not store raw registration IDs (e.g. ID `7`), which would be easy to guess. Instead, they store a cryptographically random, 32-character token. If a token does not match the database, entry is denied.
* **Double Check-In Protection**: The `attendance` table has a `UNIQUE` constraint on the `registration_id` field. Attempting to scan a ticket a second time throws a database constraint conflict, blocking duplicate check-ins at a system level.
* **Session Guarding**: Custom routing helpers (`require_login()` and `require_admin()`) verify session attributes at the top of protected pages, preventing direct URL access from unauthorized users.

---

## 📄 License
This project is open-source and free to use for educational, placement, and portfolio purposes.
