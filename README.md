🎟️ AttendX – Smart Event Entry System
📌 Overview

AttendX is a full-stack web application developed to streamline event registration, participant management, QR-based entry verification, and attendance tracking.

The system replaces manual event registration and attendance processes with a secure digital workflow. Each event registration generates a unique QR code that can be scanned by event organizers to validate entry and record attendance in real time.

The application supports both participant and administrator roles, providing dedicated dashboards and management tools for each user type.

🎯 Problem Statement

Traditional event management processes often face several challenges:

Manual registration and attendance tracking
Duplicate registrations and fake entries
Difficulty managing large participant lists
Lack of real-time attendance visibility
Time-consuming entry verification process

AttendX solves these challenges through automated registration, QR-based verification, and centralized event administration.

💡 Solution

AttendX provides a complete event lifecycle management platform where:

Participants register and manage event enrollments.
Organizers create and manage events.
Unique QR codes are generated for every registration.
Administrators verify entries using QR scanning.
Attendance is automatically recorded.
Event analytics and reports are generated through the admin dashboard.
🏗️ Technology Stack
Frontend
HTML5
CSS3
JavaScript
Responsive Design
Backend
PHP (Core PHP)
Session-Based Authentication
PDO Database Access Layer
Database
MySQL
Additional Libraries
phpqrcode (QR Code Generation)
Chart.js (Dashboard Analytics)
👥 User Roles
Participant
Register account
Login securely
Browse available events
Register for events
View event tickets
Download QR codes
Track attendance status
Update profile
Administrator
Create events
Edit events
Delete events
View participants
Verify QR codes
Mark attendance
Generate reports
Monitor dashboard analytics
🔄 System Workflow
Participant Flow
User Registration
User Login
Event Selection
Event Registration
QR Ticket Generation
Event Entry Verification
Attendance Recording
Administrator Flow
Admin Login
Event Creation
Participant Monitoring
QR Verification
Attendance Management
Report Generation
📊 Database Design
Core Tables
users
events
registrations
qr_codes
attendance
Relationships
One User → Multiple Registrations
One Event → Multiple Registrations
One Registration → One QR Code
One Registration → One Attendance Record
🔐 Security Features
Password Hashing
Session-Based Authentication
Role-Based Access Control
Prepared Statements using PDO
Duplicate Registration Prevention
Duplicate Attendance Prevention
QR Token Validation
📂 Project Structure
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
⚡ Key Features
Event Management
Create Event
Edit Event
Delete Event
Event Dashboard
Registration Management
Event Registration
Registration History
Duplicate Registration Prevention
QR Code System
Unique QR Generation
QR Download Support
QR Validation
Attendance Management
Attendance Recording
Attendance Reports
Attendance History
Analytics Dashboard
Total Users
Total Events
Total Registrations
Attendance Statistics
Event Reports
