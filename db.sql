-- Create database
CREATE DATABASE IF NOT EXISTS s2100827_jm;
USE s2100827_jm;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    address TEXT,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admins table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Parking slots table
CREATE TABLE parking_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slot_number VARCHAR(10) NOT NULL UNIQUE,
    floor_number INT NOT NULL,
    status ENUM('Vacant', 'Occupied', 'Reserved', 'Out of Service') DEFAULT 'Vacant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Reservations table
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    parking_slot_id INT NOT NULL,
    car_type VARCHAR(50) NOT NULL,
    plate_number VARCHAR(20) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parking_slot_id) REFERENCES parking_slots(id)
);

-- Messages table for user-admin communication
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    admin_id INT,
    message TEXT NOT NULL,
    is_from_user BOOLEAN NOT NULL DEFAULT TRUE,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Add indexes for better performance
CREATE INDEX idx_parking_slots_status ON parking_slots(status);
CREATE INDEX idx_reservations_dates ON reservations(start_time, end_time);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_admins_username ON admins(username);
CREATE INDEX idx_messages_user ON messages(user_id);
CREATE INDEX idx_messages_admin ON messages(admin_id);
CREATE INDEX idx_messages_created ON messages(created_at); 