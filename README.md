# Green Valley Farm

A PHP-based livestock records and farm management system for tracking animals, health records, inventory, messages, and reporting. The app is designed for local development with XAMPP and includes both a public-facing farm website and an internal admin/staff dashboard.

## Overview

Green Valley Farm helps manage a livestock operation by organizing:

- animal records and profiles
- health and vaccination logs
- feed, medicine, and equipment stock
- low-stock alerts
- contact submissions from site visitors
- operational reports for staff and administrators

## Features

- User registration and login
- Role-based access for staff and admin users
- Animal management with create, read, update, and delete actions
- Health record tracking by animal
- Inventory management with reorder thresholds
- Contact form message management
- Dashboard summary for activity and operational metrics
- Responsive public pages for the farm website

## Tech Stack

- PHP
- MySQL/MariaDB
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
├── assets/
├── CSS/
├── dashboard.php
├── edit_animal.php
├── edit_item.php
├── edit_record.php
├── health_records.php
├── includes/
│   ├── auth_check.php
│   └── db.php
├── index.php
├── inventory.php
├── JS/
│   └── contact.js
├── login.php
├── logout.php
├── messages.php
├── README.md
├── register.php
├── reports.php
└── .gitignore
```

## Prerequisites

Before running the app locally, install and start:

- XAMPP or a similar PHP + MySQL environment
- Apache
- MySQL/MariaDB
- A modern browser

## Local Setup

1. Place the project folder inside your web server root.

    For XAMPP on Linux:

    ```bash
    /opt/lampp/htdocs/livestock-records-system
    ```

2. Start Apache and MySQL from XAMPP.
3. Open phpMyAdmin and create a database named `farm_db`.
4. Import or create the required tables for the app.
5. Visit the app in the browser:

    ```text
    http://localhost/livestock-records-system/
    ```

## Database Configuration

The project connects to the database in `includes/db.php` using the default local configuration:

- host: `localhost`
- username: `root`
- password: empty string
- database: `farm_db`

For a production deployment, replace this with a secure configuration and environment-based values.

## Default Database Schema

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

## User Roles

The app supports:

- `staff`: can manage animals, record health information, and maintain inventory
- `admin`: has access to all staff functionality plus messages and reports

To promote a user to admin, update the `users` table in phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

## Security Notes

- Passwords are hashed with PHP's password functions.
- SQL queries use prepared statements where applicable.
- User sessions are checked for protected pages.
- Data displayed in the browser is escaped with `htmlspecialchars()`.

## Notes

This project is built for local development and demonstration use. It does not include a full production deployment setup, and database credentials should be adjusted before deployment to a live environment.

## License

This project is intended for local educational and development use unless otherwise specified by the project owner.

## Contributing

To extend the project:

- keep validation and sanitization in place
- maintain the role-based access rules
- write secure SQL queries
- test CRUD flows before creating a pull request
