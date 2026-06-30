# 🎟️ AttendX – Smart Event Entry System

> A Full Stack PHP & MySQL web application for QR-based event registration and attendance management.

![PHP](https://img.shields.io/badge/PHP-Core_PHP-blue)
![MySQL](https://img.shields.io/badge/Database-MySQL-orange)
![HTML5](https://img.shields.io/badge/Frontend-HTML5-red)
![CSS3](https://img.shields.io/badge/CSS-CSS3-blue)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-yellow)
![License](https://img.shields.io/badge/License-MIT-green)

---

# 📌 Overview

AttendX is a full-stack web application designed to simplify event registration, participant management, QR-based entry verification, and attendance tracking.

Instead of manual registration and attendance sheets, AttendX provides a secure digital workflow where every event registration generates a unique QR code that organizers can scan to verify participants and record attendance instantly.

The system includes separate dashboards for **Participants** and **Administrators**, making event management efficient and secure.

---

# 🎯 Problem Statement

Traditional event management systems often face challenges such as:

- Manual registration
- Manual attendance tracking
- Duplicate registrations
- Fake event entries
- Difficulty managing large participant lists
- Slow verification process
- No real-time attendance monitoring

AttendX addresses these issues with automated registration, QR-based verification, and centralized event management.

---

# 💡 Solution

AttendX provides a complete event management workflow:

1. Participant Registration
2. Event Creation
3. Event Enrollment
4. QR Code Generation
5. QR Code Verification
6. Attendance Recording
7. Dashboard Analytics & Reports

---

# 🏗️ Technology Stack

## Frontend

- HTML5
- CSS3
- JavaScript
- Responsive Design

## Backend

- Core PHP
- PDO (PHP Data Objects)
- Session-Based Authentication

## Database

- MySQL

## Libraries

- phpqrcode
- Chart.js

---

# 👥 User Roles

## 👤 Participant

- Register Account
- Login Securely
- Browse Events
- Register for Events
- View Tickets
- Download QR Codes
- Check Attendance Status
- Update Profile

---

## 👨‍💼 Administrator

- Create Events
- Edit Events
- Delete Events
- View Participants
- Verify QR Codes
- Mark Attendance
- Generate Reports
- View Dashboard Analytics

---

# 🔄 System Workflow

## Participant Workflow

```text
Register
      │
      ▼
Login
      │
      ▼
Browse Events
      │
      ▼
Register Event
      │
      ▼
Generate QR Ticket
      │
      ▼
Event Entry
      │
      ▼
QR Verification
      │
      ▼
Attendance Recorded
```

## Administrator Workflow

```text
Admin Login
      │
      ▼
Create Events
      │
      ▼
Manage Participants
      │
      ▼
Scan QR Codes
      │
      ▼
Verify Entry
      │
      ▼
Record Attendance
      │
      ▼
Generate Reports
```

---

# 📊 Database Design

## Tables

| Table | Description |
|--------|-------------|
| users | Stores user accounts |
| events | Stores event details |
| registrations | Event registrations |
| qr_codes | Generated QR codes |
| attendance | Attendance records |

---

## Database Relationships

```text
Users
  │
  ├──────────────┐
  ▼              │
Registrations ◄──┘
      │
      ▼
Events

Registrations
      │
      ├────────► QR Codes
      │
      └────────► Attendance
```

---

# 🔐 Security Features

- Password Hashing
- Session-Based Authentication
- Role-Based Access Control (RBAC)
- Prepared Statements (PDO)
- Duplicate Registration Prevention
- Duplicate Attendance Prevention
- QR Token Validation

---

# 📂 Project Structure

```text
smart-event-entry/
│
├── admin/
├── assets/
├── config/
├── database/
├── includes/
├── libs/
├── uploads/
├── user/
│
├── index.php
├── login.php
├── register.php
├── logout.php
└── ticket.php
```

---

# ⚡ Features

## 🎯 Event Management

- Create Event
- Edit Event
- Delete Event
- Dashboard

---

## 📝 Registration

- Event Registration
- Registration History
- Duplicate Registration Prevention

---

## 🎫 QR Code System

- Unique QR Generation
- QR Download
- QR Validation

---

## ✅ Attendance

- QR Scanning
- Attendance Recording
- Attendance Reports
- Attendance History

---

## 📈 Dashboard Analytics

- Total Users
- Total Events
- Total Registrations
- Attendance Statistics
- Event Reports

---

# 🚀 Installation

## 1️⃣ Clone Repository

```bash
git clone <repository-url>
```

---

## 2️⃣ Move Project

Place the project inside:

```text
C:\xampp\htdocs\smart-event-entry
```

---

## 3️⃣ Create Database

Import the SQL file:

```text
database/schema.sql
```

using **phpMyAdmin**.

---

## 4️⃣ Configure Database

Edit:

```text
config/database.php
```

Update:

```php
$host = "localhost";
$dbname = "your_database";
$username = "root";
$password = "";
```

---

## 5️⃣ Start XAMPP

Start:

- Apache
- MySQL

---

## 6️⃣ Run Project

Open your browser:

```text
http://localhost/smart-event-entry
```

---

# 📈 Future Enhancements

- 📧 Email Ticket Delivery
- 📱 Mobile Application
- ⭐ Event Feedback System
- 📊 Advanced Analytics
- 👥 Multi-Organizer Support
- 🔔 Real-Time Notifications

---

# 🎯 Project Outcome

AttendX demonstrates a production-style Event Management System implementing:

- Full Stack PHP Development
- QR-Based Attendance Verification
- Secure Authentication
- Role-Based Authorization
- Relational Database Design
- Dashboard Reporting
- Deployment Readiness

---

# 👨‍💻 Developer

**Vishal Vijayakumar**

🎓 Rajalakshmi Engineering College


---
