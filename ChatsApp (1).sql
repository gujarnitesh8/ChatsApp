-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 03, 2020 at 01:35 PM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ChatsApp`
--

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(10) NOT NULL,
  `sender_id` int(5) NOT NULL,
  `receiver_id` int(5) NOT NULL,
  `crid` int(5) NOT NULL,
  `message` text NOT NULL,
  `is_group` tinyint(4) NOT NULL,
  `is_sent` varchar(10) NOT NULL,
  `is_read` tinyint(4) NOT NULL DEFAULT 0,
  `is_delete_partial` tinyint(4) NOT NULL DEFAULT 0,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  `is_testdata` tinyint(4) NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `receiver_id`, `crid`, `message`, `is_group`, `is_sent`, `is_read`, `is_delete_partial`, `is_delete`, `is_testdata`, `created_date`, `modified_date`) VALUES
(1, 1, 2, 1, 'Hi1', 0, 'false', 0, 0, 0, 1, '2020-03-03 17:14:04', '2020-03-03 17:14:04'),
(2, 3, 2, 2, 'Hi1', 0, 'false', 0, 0, 0, 1, '2020-03-03 17:17:36', '2020-03-03 17:17:36'),
(3, 3, 2, 2, 'Hi2', 0, 'false', 0, 0, 0, 1, '2020-03-03 17:17:39', '2020-03-03 17:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `chat_room`
--

CREATE TABLE `chat_room` (
  `id` int(5) NOT NULL,
  `last_message` text NOT NULL,
  `group_name` varchar(55) NOT NULL,
  `is_group` tinyint(4) NOT NULL,
  `sender_id` int(5) NOT NULL,
  `receiver_id` int(5) NOT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  `is_testdata` tinyint(4) NOT NULL DEFAULT 1,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chat_room`
--

INSERT INTO `chat_room` (`id`, `last_message`, `group_name`, `is_group`, `sender_id`, `receiver_id`, `is_delete`, `is_testdata`, `created_date`, `modified_date`) VALUES
(1, 'Hi1', '', 0, 1, 2, 0, 1, '2020-03-03 17:14:04', '2020-03-03 17:14:04'),
(2, 'Hi2', '', 0, 3, 2, 0, 1, '2020-03-03 17:17:36', '2020-03-03 17:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `ref_user_id` int(11) NOT NULL,
  `mobile_number` varchar(14) NOT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  `is_testdata` int(11) NOT NULL DEFAULT 1,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `generated_otp`
--

CREATE TABLE `generated_otp` (
  `id` int(5) NOT NULL,
  `otp_message` varchar(10) NOT NULL,
  `userId` int(5) NOT NULL,
  `is_otp_verified` tinyint(4) NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `generated_otp`
--

INSERT INTO `generated_otp` (`id`, `otp_message`, `userId`, `is_otp_verified`, `created_date`, `modified_date`) VALUES
(1, '731859', 1, 1, '2020-03-02 17:51:52', '2020-03-03 10:25:18'),
(2, '423477', 2, 1, '2020-03-03 12:02:55', '2020-03-03 12:03:12'),
(3, '326270', 3, 1, '2020-03-03 12:31:34', '2020-03-03 12:32:13'),
(4, '778531', 4, 1, '2020-03-03 12:51:52', '2020-03-03 12:52:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) NOT NULL,
  `name` varchar(55) DEFAULT NULL,
  `photo` varchar(20) DEFAULT NULL,
  `mobile_number` varchar(14) NOT NULL,
  `user_country_code` varchar(5) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL COMMENT 'Users bio status',
  `is_notifications` tinyint(4) NOT NULL DEFAULT 1,
  `is_private` tinyint(4) NOT NULL DEFAULT 0,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  `is_testdata` tinyint(4) NOT NULL DEFAULT 1,
  `is_active` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'changes when verification completed',
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `modifed_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `photo`, `mobile_number`, `user_country_code`, `password`, `status`, `is_notifications`, `is_private`, `is_delete`, `is_testdata`, `is_active`, `created_date`, `modifed_date`) VALUES
(1, '', '', '8866924934', '+91', '123452', NULL, 1, 0, 0, 1, 0, '2020-03-02 12:21:52', '2020-03-02 17:51:52'),
(2, '', '', '8866924935', '+91', NULL, NULL, 1, 0, 0, 1, 0, '2020-03-03 06:32:55', '2020-03-03 12:02:55'),
(3, '', '', '8866924936', '+91', NULL, NULL, 1, 0, 0, 1, 0, '2020-03-03 07:01:34', '2020-03-03 12:31:34'),
(4, '', '', '8866924937', '+91', NULL, NULL, 1, 0, 0, 1, 0, '2020-03-03 07:21:52', '2020-03-03 12:51:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_room`
--
ALTER TABLE `chat_room`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `generated_otp`
--
ALTER TABLE `generated_otp`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_room`
--
ALTER TABLE `chat_room`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generated_otp`
--
ALTER TABLE `generated_otp`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
