-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2025 at 01:29 AM
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
  `borrow_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_date` timestamp NULL DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_transactions`
--

INSERT INTO `borrow_transactions` (`id`, `user_id`, `game_id`, `borrow_date`, `return_date`, `status`) VALUES
(1, 2, 8, '2025-08-31 16:13:58', NULL, 'overdue');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `status` enum('available','borrowed','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `title`, `platform`, `status`, `created_at`, `updated_at`) VALUES
(1, 'The Legend of Zelda: Breath of the Wild', 'Nintendo Switch', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(2, 'God of War Ragnarök', 'PlayStation 5', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(3, 'Elden Ring', 'PC', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(4, 'Super Mario Odyssey', 'Nintendo Switch', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(5, 'Spider-Man: Miles Morales', 'PlayStation 5', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(6, 'Halo Infinite', 'Xbox Series X', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(7, 'Red Dead Redemption 2', 'PC', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(8, 'Animal Crossing: New Horizons', 'Nintendo Switch', 'borrowed', '2025-08-31 15:02:02', '2025-08-31 16:13:58'),
(9, 'The Last of Us Part II', 'PlayStation 4', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02'),
(10, 'Cyberpunk 2077', 'PC', 'available', '2025-08-31 15:02:02', '2025-08-31 15:02:02');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `first_name`, `last_name`, `gender`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gamelend.com', '$2y$10$xGrpcJPgYu0UQgBICJd9ZOTcyCTCmpa1.tsCd1lnzAeBXJFuwVYSi', 'Admin', 'User', 'prefer_not_to_say', 'admin', '2025-08-31 15:02:02', '2025-08-31 15:25:18'),
(2, 'joyieks', 'diocampojoanjoy24@gmail.com', '$2y$10$sbjkDr/lyd0NkcZuB0azZuogFOW4pMfRi5wy8rvlrCKV5MhsMzZWq', 'Joan Joy', 'Diocampo', 'female', 'customer', '2025-08-31 15:43:01', '2025-08-31 15:43:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `game_id` (`game_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_transactions`
--
ALTER TABLE `borrow_transactions`
  ADD CONSTRAINT `borrow_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrow_transactions_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
