# 🎟️ AttendX – Smart Event Entry System

## 📌 Overview

AttendX is a full-stack web application designed to simplify event registration, participant management, QR code-based entry verification, and attendance tracking.

The system replaces manual registration and attendance processes with a secure digital workflow. Each event registration generates a unique QR code that organizers can scan to verify participant entry and record attendance in real time.

The application supports two user roles—**Participant** and **Administrator**—with dedicated dashboards and management features for each.

---

## 🎯 Problem Statement

Traditional event management often faces challenges such as:

- Manual registration and attendance tracking
- Duplicate registrations
- Time-consuming entry verification
- Difficulty managing participant records
- Lack of real-time attendance monitoring

AttendX addresses these issues by providing a centralized and automated event management solution.

---

## 💡 Solution

AttendX enables complete event lifecycle management by allowing users to:

### Participants

- Create an account
- Browse available events
- Register for events
- Receive a unique QR code ticket
- View registration history
- Track attendance status
- Manage their profile

### Administrators

- Create, edit, and delete events
- View registered participants
- Verify QR codes during event entry
- Mark attendance instantly
- Monitor event statistics
- Generate attendance reports

---

# 🏗️ Technology Stack

## Frontend

- HTML5
- CSS3
- JavaScript
- Responsive Design

## Backend

- PHP (Core PHP)
- PDO
- Session-Based Authentication

## Database

- MySQL

## Libraries

- phpqrcode
- Chart.js

---

# 👥 User Roles

## Participant

- Register and log in
- Browse available events
- Register for events
- Download QR tickets
- View attendance history
- Update profile

## Administrator

- Manage events
- Manage participants
- Verify QR codes
- Record attendance
- Generate reports
- View dashboard analytics

---

# 🔄 System Workflow

## Participant Workflow

1. Register Account
2. Login
3. Browse Events
4. Register for an Event
5. Receive QR Code Ticket
6. QR Verification at Entry
7. Attendance Recorded

## Administrator Workflow

1. Login
2. Create or Manage Events
3. View Registered Participants
4. Scan QR Codes
5. Record Attendance
6. Generate Reports

---

# 📊 Database Design

### Main Tables

- users
- events
- registrations
- qr_codes
- attendance

### Relationships

- One User → Many Registrations
- One Event → Many Registrations
- One Registration → One QR Code
- One Registration → One Attendance Record

---

# 🔐 Security Features

- Password Hashing
- Session-Based Authentication
- Role-Based Access Control
- PDO Prepared Statements
- Duplicate Registration Prevention
- Duplicate Attendance Prevention
- Secure QR Token Validation

---

# 📂 Project Structure

```text
smart-event-entry/
├── admin/
├── assets/
├── config/
├── database/
├── includes/
├── libs/
├── uploads/
├── user/
├── index.php
├── login.php
├── register.php
├── logout.php
└── ticket.php
```

---

# ✨ Key Features

### Event Management

- Create Events
- Edit Events
- Delete Events
- Event Dashboard

### Registration System

- Online Event Registration
- Registration History
- Duplicate Registration Prevention

### QR Code System

- Automatic QR Code Generation
- QR Code Download
- QR Validation

### Attendance Management

- QR-Based Attendance
- Attendance Tracking
- Attendance Reports

### Dashboard & Analytics

- Total Users
- Total Events
- Total Registrations
- Attendance Statistics
- Event Reports

---

# 🚀 Future Enhancements

- Email Notifications
- Event Reminder System
- Mobile Application
- Certificate Generation
- Payment Gateway Integration
- Multi-Organizer Support

---

## 👨‍💻 Author

Developed as a full-stack web application project using **PHP, MySQL, HTML, CSS, JavaScript, PDO, and QR Code technology**.
