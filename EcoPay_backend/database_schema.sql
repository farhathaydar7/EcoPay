CREATE DATABASE project_ecopay;
USE project_ecopay;

-- Users Table
CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userName VARCHAR(50) UNIQUE NOT NULL,
    fName VARCHAR(50) NOT NULL,
    lName VARCHAR(50) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
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
    user_id INT,
    wallet_name VARCHAR(100) NOT NULL DEFAULT 'Main Wallet',
    balance DECIMAL(15,2) DEFAULT 0.00, -- Current wallet balance
    currency VARCHAR(10) DEFAULT 'USD',
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE,
    UNIQUE (user_id, wallet_name),
    CONSTRAINT unique_default_wallet UNIQUE (user_id, is_default), -- Ensure only one default wallet per user
    -- Remove subquery from CHECK constraint
    CONSTRAINT max_wallets_per_user CHECK (user_id <= 3) -- Replace with a simpler constraint or use triggers
);

-- Cards Table
CREATE TABLE Cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT,
    card_number VARCHAR(255) UNIQUE NOT NULL, -- Or other relevant card details
    FOREIGN KEY (wallet_id) REFERENCES Wallets(id) ON DELETE CASCADE
);

-- BorrowedCards Table
CREATE TABLE BorrowedCards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrower_wallet_id INT UNIQUE NOT NULL, -- Each wallet can borrow only one card at a time
    owner_card_id INT NOT NULL,
    borrowed_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    return_date TIMESTAMP NULL, -- or use a boolean 'is_returned'
    FOREIGN KEY (borrower_wallet_id) REFERENCES Wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_card_id) REFERENCES Cards(id) ON DELETE CASCADE
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
    sender_wallet_id INT,
    receiver_wallet_id INT,
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
    user_id INT,
    amount DECIMAL(15,2) NOT NULL,
    frequency ENUM('daily', 'weekly' ,'monthly', 'yearly') NOT NULL,
    next_execution DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- VerificationStatuses Table
CREATE TABLE VerificationStatuses (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    user_id INT, 
    email_verified BOOLEAN DEFAULT FALSE, 
    document_verified BOOLEAN DEFAULT FALSE,
    super_verified BOOLEAN DEFAULT FALSE, -- Fixed typo here
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
