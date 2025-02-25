CREATE DATABASE project_ecopay;
USE project_ecopay;

-- Users Table
CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    name VARCHAR(100) NOT NULL, 
    email VARCHAR(150) UNIQUE NOT NULL, 
    phone VARCHAR(20) UNIQUE NOT NULL, 
    password VARCHAR(255) NOT NULL, -- Hashed password
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
    -- verification_status ENUM('unverified', 'pending', 'verified') DEFAULT 'unverified', -- Moved to VerificationStatuses table
);

-- User Profiles Table
CREATE TABLE UserProfiles (
    user_id INT PRIMARY KEY, 
    address VARCHAR(255), 
    dob DATE, 
    profile_pic VARCHAR(255), 
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE 
);

-- Wallets Table
CREATE TABLE Wallets (
    user_id INT PRIMARY KEY,
    balance DECIMAL(15,2) DEFAULT 0.00, -- Current wallet balance
    currency VARCHAR(10) DEFAULT 'USD', 
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE 
);

-- Transactions Table
CREATE TABLE Transactions (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT, 
    type ENUM('deposit', 'withdraw', 'transfer') NOT NULL, 
    amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE 
);

-- Transfers Table
CREATE TABLE Transfers (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    sender_id INT, 
    receiver_id INT, 
    amount DECIMAL(15,2) NOT NULL, 
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending', 
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (sender_id) REFERENCES Users(id) ON DELETE CASCADE, 
    FOREIGN KEY (receiver_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Payment Schedules Table
CREATE TABLE PaymentSchedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(15,2) NOT NULL,
    frequency ENUM('daily', 'weekly' ,'monthly', 'yearly') NOT NULL,
    next_execution DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- OTPs Table
CREATE TABLE OTPs (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT, 
    otp VARCHAR(255) NOT NULL, 
    expiry_timestamp TIMESTAMP NOT NULL, -- OTP expiration timestamp
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE 
);

-- VerificationStatuses Table
CREATE TABLE VerificationStatuses (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT, 
    email_verified BOOLEAN DEFAULT FALSE, 
    document_verified BOOLEAN DEFAULT FALSE,
    super_verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE 
);

-- ID Documents Table
CREATE TABLE IDDocuments (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT, 
    link VARCHAR(255) NOT NULL, 
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE 
);
-- Admins Table
CREATE TABLE Admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Hashed password
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);