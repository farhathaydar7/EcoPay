-- Create Database
-- CREATE DATABASE project_ecopay;
USE project_ecopay;

-- Users Table
CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userName VARCHAR(50) UNIQUE NOT NULL,
    fName VARCHAR(50) NOT NULL,
    lName VARCHAR(50) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Will be hashed in the future
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    otp VARCHAR(6),
    otp_expiry TIMESTAMP NULL,
    activatedAcc TINYINT(1) DEFAULT 0
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
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wallet_name VARCHAR(100) NOT NULL DEFAULT 'Main Wallet',
    balance DECIMAL(15,2) DEFAULT 0.00, -- Current wallet balance
    currency VARCHAR(10) DEFAULT 'USD',
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    UNIQUE (user_id, wallet_name)
);


-- Cards Table
CREATE TABLE Cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT NOT NULL,
    card_number VARCHAR(255) UNIQUE NOT NULL,
    FOREIGN KEY (wallet_id) REFERENCES Wallets(id) ON DELETE CASCADE
);

-- Borrowed Cards Table
CREATE TABLE BorrowedCards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrower_wallet_id INT UNIQUE NOT NULL, -- Each wallet can borrow only one card at a time
    owner_card_id INT NOT NULL,
    borrowed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    return_date TIMESTAMP NULL,
    FOREIGN KEY (borrower_wallet_id) REFERENCES Wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_card_id) REFERENCES Cards(id) ON DELETE CASCADE
);

-- Transactions Table
CREATE TABLE Transactions (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT NOT NULL, 
    wallet_id INT NOT NULL, -- New column
    type ENUM('deposit', 'withdraw', 'transfer', 'payment','p2p','rp') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES Wallets(id) ON DELETE CASCADE
);

-- Transfers Table
CREATE TABLE Transfers (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    sender_id INT NOT NULL, 
    receiver_id INT NOT NULL, 
    amount DECIMAL(15,2) NOT NULL,
    sender_wallet_id INT NOT NULL,
    receiver_wallet_id INT NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_wallet_id) REFERENCES Wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_wallet_id) REFERENCES Wallets(id) ON DELETE CASCADE
);

-- Payment Schedules Table
CREATE TABLE PaymentSchedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    frequency ENUM('daily', 'weekly' ,'monthly', 'yearly') NOT NULL,
    next_execution DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- Verification Statuses Table
CREATE TABLE VerificationStatuses (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT NOT NULL, 
    email_verified BOOLEAN DEFAULT FALSE, 
    document_verified BOOLEAN DEFAULT FALSE,
    super_verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- ID Documents Table
CREATE TABLE IDDocuments (
   id INT AUTO_INCREMENT PRIMARY KEY,
   user_id INT NOT NULL,
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

-- P2P Transfers Table
CREATE TABLE P2P_Transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES Transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- QRCodes Table
CREATE TABLE QRCodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wallet_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    sender_id INT NULL, -- Sender who scanned the QR code
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE, -- Receiver of the QR code
    FOREIGN KEY (wallet_id) REFERENCES Wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES Users(id) ON DELETE CASCADE -- Sender of the QR code
);
-- Receipts Table
CREATE TABLE Receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    user_id INT NOT NULL,
    wallet_id INT NOT NULL,
    transaction_type ENUM('deposit', 'withdraw', 'p2p','rp') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    extra_data JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES Transactions(id),
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (wallet_id) REFERENCES Wallets(id)
);


