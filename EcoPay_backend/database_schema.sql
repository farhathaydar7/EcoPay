CREATE DATABASE digital_wallet;
USE digital_wallet;

-- Users Table
CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    verification_status ENUM('unverified', 'pending', 'verified') DEFAULT 'unverified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Profiles Table
CREATE TABLE UserProfiles (
    user_id INT PRIMARY KEY,
    address VARCHAR(255),
    dob DATE,
    profile_pic VARCHAR(255),
    id_document VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Wallets Table
CREATE TABLE Wallets (
    user_id INT PRIMARY KEY,
    balance DECIMAL(15,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'USD',
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);
