# Livestock Records System

A PHP/MySQL web application for managing a livestock farm's animal records, health tracking, inventory, and customer contact messages. The project combines a public-facing farm website with a login-protected management dashboard for staff and administrators.

## Overview

This system is designed for a farm operation that needs to:

- track animals and their status
- record vaccinations, treatments, and checkups
- manage feed, medicine, and equipment inventory
- monitor low-stock levels
- receive and review contact form submissions
- generate filterable reports for operational management

It is built for a local XAMPP development environment and uses plain PHP, MySQL, HTML, CSS, and JavaScript without a framework.

## Features

### Public site

- Home page
- About page
- Contact page with form validation
- Responsive layout for desktop and mobile screens

### Staff/admin dashboard

- Login and registration
- Dashboard with summary cards and recent activity
- Animal CRUD operations
- Health record management linked to animals
- Inventory tracking with low-stock indicators
- Filtered search and sorting
- Role-based access control

### Admin-only tools

- Messages inbox for contact submissions
- View and delete submitted inquiries
- Reports page with filters and summary statistics

## Tech Stack

- PHP 8.x
- MySQL / MariaDB
- Apache via XAMPP
- HTML5
- CSS3
- JavaScript

## Project Structure

```text
livestock-records-system/
├── about.php
├── add_animal.php
├── add_item.php
├── add_record.php
├── animals.php
├── contact.php
├── dashboard.php
├── edit_animal.php
├── edit_item.php
├── edit_record.php
├── health_records.php
├── index.php
├── inventory.php
├── login.php
├── logout.php
├── messages.php
├── register.php
├── reports.php
├── README.md
├── assets/
├── CSS/
├── includes/
│   ├── auth_check.php
│   └── db.php
├── JS/
│   └── contact.js
└── ...
```

## Prerequisites

Before running the project, make sure you have:

- XAMPP installed
- Apache and MySQL started in XAMPP Control Panel
- PHP enabled in local environment
- A browser for testing the app

## Local Setup

1. Place the project folder inside your XAMPP `htdocs` directory.

    Example:

    ```bash
    C:/xampp/htdocs/livestock-records-system
    ```

    or on Linux:

    ```bash
    /opt/lampp/htdocs/livestock-records-system
    ```

2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and create a database named `farm_db`.
4. Create the required tables.

### Database schema

```sql
CREATE DATABASE IF NOT EXISTS farm_db;
USE farm_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('staff', 'admin') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE animals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_number VARCHAR(100) NOT NULL UNIQUE,
    species VARCHAR(100) NOT NULL,
    breed VARCHAR(100) DEFAULT NULL,
    sex ENUM('male', 'female') DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    weight_kg DECIMAL(10,2) DEFAULT NULL,
    status ENUM('active', 'sold', 'deceased') NOT NULL DEFAULT 'active',
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    record_type ENUM('vaccination', 'treatment', 'checkup', 'other') NOT NULL DEFAULT 'checkup',
    record_date DATE NOT NULL,
    description TEXT DEFAULT NULL,
    cost DECIMAL(10,2) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE
);

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    category ENUM('feed', 'medication', 'equipment', 'other') NOT NULL DEFAULT 'other',
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit VARCHAR(50) DEFAULT NULL,
    reorder_level DECIMAL(10,2) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Database Configuration

The app is configured in `includes/db.php` to connect using:

- host: `localhost`
- username: `root`
- password: empty string
- database: `farm_db`

This is suitable for local development, but for production you should replace it with a safer configuration using a dedicated database user and environment variables.

## Accessing the App

After setup, open the project in a browser:

- Public site: `http://localhost/livestock-records-system/`
- Login page: `http://localhost/livestock-records-system/login.php`
- Dashboard: `http://localhost/livestock-records-system/dashboard.php`

## User Roles

The system supports two roles:

- `staff`: can manage animals, health records, and inventory
- `admin`: has access to all staff features plus messages and reports

### Creating an admin account

The registration page currently creates users with the default role `staff` on the server side. To grant admin access, either:

- insert a user directly in the `users` table with role `admin`, or
- update an existing user row in phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

## Usage Notes

- Users must log in before accessing protected pages.
- Protected pages are guarded by session checks.
- The system uses prepared statements for database queries.
- Passwords are hashed using PHP's `password_hash()` and verified with `password_verify()`.
- Dynamic output is escaped using `htmlspecialchars()` to reduce XSS risk.

## Known Notes

- This project is intentionally built for local XAMPP use.
- It does not include a full production-ready deployment setup.
- Sensitive configuration should be moved out of the project for production deployment.

## License

This project is for educational and local development use unless otherwise specified by the owner.

## Contributing

If you want to extend the project:

- keep validation and sanitization in place
- use prepared statements for new queries
- preserve the role-based access model
- test forms and CRUD flows before pushing changes

## Summary

This project is a complete farm management system built around daily livestock operations, offering a practical balance of public information pages and internal record management. It is ideal for local development, demos, coursework, or a base for a larger farm system.
