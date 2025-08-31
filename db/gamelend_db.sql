-- GameLend Database Schema
-- This file contains the complete database structure for the GameLend system

-- Create the database
CREATE DATABASE IF NOT EXISTS gamelend_db;
USE gamelend_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Games table
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    status ENUM('available', 'borrowed', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Borrow transactions table
CREATE TABLE IF NOT EXISTS borrow_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    return_date TIMESTAMP NULL,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- Insert default admin user
-- Password: admin123 (hashed)
INSERT INTO users (username, email, password, first_name, last_name, gender, role) VALUES 
('admin', 'admin@gamelend.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'prefer_not_to_say', 'admin');

-- Insert sample games
INSERT INTO games (title, platform) VALUES
('The Legend of Zelda: Breath of the Wild', 'Nintendo Switch'),
('God of War Ragnarök', 'PlayStation 5'),
('Elden Ring', 'PC'),
('Super Mario Odyssey', 'Nintendo Switch'),
('Spider-Man: Miles Morales', 'PlayStation 5'),
('Halo Infinite', 'Xbox Series X'),
('Red Dead Redemption 2', 'PC'),
('Animal Crossing: New Horizons', 'Nintendo Switch'),
('The Last of Us Part II', 'PlayStation 4'),
('Cyberpunk 2077', 'PC');
