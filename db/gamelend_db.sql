-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2025 at 11:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gamelend_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrow_transactions`
--

CREATE TABLE `borrow_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `quantity` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `borrow_date` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` datetime DEFAULT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue','cancelled') NOT NULL DEFAULT 'borrowed',
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `total_quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `available_quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('available','borrowed','maintenance') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `title`, `platform`, `total_quantity`, `available_quantity`, `status`, `created_at`, `updated_at`) VALUES
(2, 'God of War Ragnarök', 'PlayStation 5', 1, 1, 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(3, 'Elden Ring', 'PC', 1, 1, 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(4, 'Super Mario Odyssey', 'Nintendo Switch', 1, 1, 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(5, 'Spider-Man: Miles Morales', 'PlayStation 5', 1, 1, 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(6, 'Halo Infinite', 'Xbox Series X', 1, 1, 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(7, 'Red Dead Redemption 2', 'PC', 1, 1, 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(8, 'Animal Crossing: New Horizons', 'Nintendo Switch', 1, 0, 'borrowed', '2025-08-31 15:02:02', '2025-09-22 11:28:48'),
(9, 'The Last of Us Part II', 'PlayStation 4', 1, 1, 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(10, 'Cyberpunk 2077', 'PC', 1, 1, 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(11, 'The Last of Us Part 1: Remastered', 'PC, PlayStation 4, PlayStation 5, Xbox One, Xbox S', 1, 1, 'available', '2025-09-22 08:32:22', '2025-09-22 08:32:22'),
(37, 'Final Fantasy 15', 'PC', 2, 2, 'available', '2025-09-22 11:43:05', '2025-09-22 11:43:05'),
(38, 'Final Fantasy 15', 'PlayStation 5', 3, 2, 'available', '2025-09-22 11:43:05', '2025-09-22 11:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `status` enum('active','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `gender`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gamelend.com', '$2y$10$xGrpcJPgYu0UQgBICJd9ZOTcyCTCmpa1.tsCd1lnzAeBXJFuwVYSi', 'Admin', 'User', 'prefer_not_to_say', 'admin', 'active', '2025-08-31 15:02:02', '2025-08-31 15:25:18'),
(2, 'joyieks', 'diocampojoanjoy24@gmail.com', '$2y$10$sbjkDr/lyd0NkcZuB0azZuogFOW4pMfRi5wy8rvlrCKV5MhsMzZWq', 'Joan Joy', 'Diocampo', 'female', 'customer', 'active', '2025-08-31 15:43:01', '2025-08-31 15:43:01'),
(3, 'kfpriego', 'kfparilla@gmail.com', '$2y$10$/U6tpPT3cUBxTnwVG4pmZ.gZMbisW1ZryrIOlNGtyJlIpsyZAqYqC', 'Kenji', 'Parilla', 'male', 'customer', 'active', '2025-09-22 08:19:18', '2025-09-22 08:19:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_game_active` (`user_id`,`game_id`,`active`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `fk_bt_game` (`game_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD CONSTRAINT `fk_bt_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
