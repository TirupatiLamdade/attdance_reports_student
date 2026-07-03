# 🎓 Polytechnic Student Attendance Management System

A secure, enterprise-level **Student Attendance Management System** designed specifically for Polytechnic institutes. The platform enables faculty to record, monitor, and analyze student attendance through **dynamic QR Code scanning**, **daily live attendance**, and **monthly attendance reports** while ensuring data security and integrity.

---

# 📌 Project Overview

The Polytechnic Student Attendance Management System is a web-based application developed to simplify and automate attendance management.

Traditional attendance methods are time-consuming and prone to errors. This system replaces manual attendance with a secure QR Code-based solution that records attendance instantly and generates accurate reports.

The application provides real-time attendance tracking, detailed reporting, secure authentication, and administrative controls.

---

# 🎯 Objectives

* Eliminate manual attendance registers.
* Reduce attendance fraud.
* Generate accurate daily and monthly reports.
* Improve attendance monitoring.
* Secure all attendance records.
* Provide real-time attendance analytics.
* Make attendance management simple for faculty and administrators.

---

# ✨ Key Features

## 🔐 Authentication

* Secure Login
* User Registration
* Forgot Password
* JWT Authentication
* Password Encryption
* Session Management
* Role-Based Access Control (Admin, Faculty, Student)

---

## 👨‍🎓 Student Management

* Add Student
* Update Student Details
* Delete Student
* Student Profile
* Department Management
* Semester Management
* Branch Management
* Class Assignment

---

## 👨‍🏫 Faculty Management

* Faculty Login
* Subject Allocation
* Attendance Dashboard
* Daily Attendance
* Attendance History
* Report Generation

---

# 📷 QR Code Attendance System

The application uses a **Dynamic QR Code** for attendance.

### How it Works

1. Faculty starts the attendance session.
2. The system generates a **new QR Code**.
3. Students scan the QR Code.
4. Attendance is recorded instantly.
5. After the session ends, the QR Code expires automatically.
6. A new QR Code is generated for the next session.

### Security Benefits

* Dynamic QR Code changes every attendance session.
* Expired QR Codes cannot be reused.
* Prevents proxy attendance.
* Prevents screenshot misuse.
* One attendance per student per session.
* Time-based validation.
* Secure attendance verification.

---

# 📅 Daily Live Attendance

Faculty can monitor attendance in real time.

Features:

* Live attendance count
* Present students
* Absent students
* Late entries
* Attendance percentage
* Live dashboard updates
* Instant synchronization

---

# 📊 Attendance Reports

## Daily Attendance Report

Faculty can generate reports for any specific date.

Report includes:

* Student Name
* Roll Number
* Department
* Semester
* Subject
* Attendance Status
* Attendance Time
* QR Scan Time

---

## Monthly Attendance Report

Generate attendance reports for an entire month.

Includes:

* Total Working Days
* Present Days
* Absent Days
* Attendance Percentage
* Leave Count
* Student-wise Summary
* Department-wise Summary

---

# 🔍 Search & Filter

Search attendance by:

* Student Name
* Roll Number
* Department
* Semester
* Subject
* Date
* Month
* Attendance Status

---

# 📈 Dashboard

The dashboard displays:

* Total Students
* Present Today
* Absent Today
* Monthly Attendance
* Attendance Percentage
* Live Attendance
* Recent Activities

---

# 🔒 Security Features

The application prioritizes data security.

## Authentication Security

* JWT Authentication
* Secure Sessions
* Password Hashing
* Role-Based Access Control

---

## Data Protection

* Encrypted Password Storage
* Secure Database Access
* SQL Injection Protection
* XSS Protection
* CSRF Protection
* Input Validation
* File Validation

---

## Attendance Security

* Dynamic QR Codes
* QR Expiration
* One Scan Per Student
* Session Validation
* Duplicate Attendance Prevention
* Attendance Timestamp Verification

---

## Audit & Logs

* User Login Logs
* Attendance Activity Logs
* Report Generation Logs
* Administrative Actions

---

# 📱 Modules

* Authentication
* Dashboard
* Student Management
* Faculty Management
* Department Management
* Subject Management
* Attendance Management
* QR Attendance
* Daily Reports
* Monthly Reports
* Search
* Analytics
* User Management
* Settings

---

# 🛠 Technology Stack

## Frontend

* React.js
* TypeScript
* Tailwind CSS

## Backend

* Spring Boot
* Java 21
* REST API

## Database

* MySQL

## Build Tool

* Maven

## IDE

* IntelliJ IDEA

## API Testing

* Postman

## Version Control

* Git
* GitHub

---

# 📂 Project Structure

```text
attendance-management-system/

├── frontend/
│   ├── src/
│   ├── components/
│   ├── pages/
│   ├── layouts/
│   ├── hooks/
│   ├── services/
│   ├── assets/
│   └── utils/
│
├── backend/
│   ├── controller/
│   ├── service/
│   ├── repository/
│   ├── entity/
│   ├── dto/
│   ├── security/
│   ├── config/
│   ├── exception/
│   └── utils/
│
├── database/
│
├── uploads/
│
├── reports/
│
├── screenshots/
│
├── docs/
│
└── README.md
```

---

# 🚀 Future Enhancements

* Face Recognition Attendance
* Biometric Authentication
* GPS Location Verification
* Mobile Application
* Push Notifications
* SMS Alerts
* Email Notifications
* AI Attendance Analytics
* Cloud Backup
* Multi-Institute Support

---

# ✅ Benefits

* Faster Attendance Process
* Accurate Attendance Records
* Secure QR-Based Verification
* Real-Time Attendance Monitoring
* Reliable Daily Reports
* Reliable Monthly Reports
* Reduced Manual Work
* Enterprise-Level Security
* Easy Report Generation
* User-Friendly Interface

---

# 📄 License

This project is developed for educational and academic purposes. You may modify and extend it according to your institution's requirements.

---

# 👨‍💻 Author

**Developer:** Tirupati Lamdade

**Project:** Polytechnic Student Attendance Management System

A secure, modern, and scalable attendance platform built using **React.js**, **Spring Boot**, **Java 21**, **MySQL**, and **Dynamic QR Code Technology** for real-time attendance tracking and enterprise-grade security.
