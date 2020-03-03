-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 192.168.1.201
-- Generation Time: Sep 15, 2017 at 01:08 PM
-- Server version: 5.5.32
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `dosogas`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_config`
--

CREATE TABLE IF NOT EXISTS `admin_config` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`config_key` varchar(40) NOT NULL,
`config_value` varchar(200) NOT NULL,
`value_unit` varchar(20) NOT NULL,
`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Will be used for soft delete of record.',
`is_testdata` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Will help identify dirty data added during testing and live data',
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `admin_config`
--

INSERT INTO `admin_config` (`id`, `config_key`, `config_value`, `value_unit`, `created_date`, `modified_date`, `is_delete`, `is_testdata`) VALUES
(1, 'globalPassword', '(Dodogas)(Dodogas)14/9/2017', 'text', '2017-09-14 11:22:04', '2017-09-14 05:52:04', 0, 1),
(2, 'userAgent', 'iOS,Android,Mozilla/5.0,PostmanRuntime/2.5.0\r\n', 'comma-separated', '2017-09-14 11:22:04', '2017-09-14 05:52:04', 0, 1),
(3, 'tempToken', 'allowAccessToApp', 'text', '2017-09-14 11:22:04', '2017-09-14 05:52:04', 0, 1),
(4, 'expiry_duration', '3600', 'second', '2017-09-14 11:22:04', '2017-09-14 05:52:04', 0, 1),
(5, 'autologout', '1', 'boolean', '2017-09-14 11:22:04', '2017-09-14 05:52:04', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `app_tokens`
--

CREATE TABLE IF NOT EXISTS `app_tokens` (
`user_id` int(11) unsigned NOT NULL,
`token` varchar(200) DEFAULT '',
`token_type` enum('access_token') NOT NULL DEFAULT 'access_token',
`status` enum('active','expired') NOT NULL DEFAULT 'active',
`expiry` varchar(30) DEFAULT '',
`access_count` int(11) DEFAULT NULL,
`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`is_delete` tinyint(1) NOT NULL DEFAULT '0',
`is_testdata` tinyint(1) NOT NULL DEFAULT '1',
PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `app_tokens`
--

INSERT INTO `app_tokens` (`user_id`, `token`, `token_type`, `status`, `expiry`, `access_count`, `created_date`, `modified_date`, `is_delete`, `is_testdata`) VALUES
(7, 'YtcNIhR0', 'access_token', 'active', '150917111811', NULL, '2017-09-15 10:18:11', '2017-09-15 05:25:43', 0, 1),
(8, 'jnAhhWPV', 'access_token', 'active', '140917074403', NULL, '0000-00-00 00:00:00', '2017-09-14 06:44:03', 0, 1),
(9, '60E0NrGn', 'access_token', 'active', '150917120314', NULL, '2017-09-15 11:03:14', '2017-09-15 11:03:14', 0, 1),
(10, 'dQyMzlem', 'access_token', 'active', '140917121446', NULL, '0000-00-00 00:00:00', '2017-09-14 11:14:46', 0, 1),
(11, 'gBF2miRA', 'access_token', 'active', '150917115852', NULL, '2017-09-15 10:58:52', '2017-09-15 10:58:52', 0, 1),
(12, 'UPpCqTZM', 'access_token', 'active', '150917064535', NULL, '2017-09-15 05:45:35', '2017-09-15 05:45:35', 0, 1),
(14, 'LmPl6x0n', 'access_token', 'active', '150917110817', NULL, '0000-00-00 00:00:00', '2017-09-15 10:08:17', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`title` varchar(30) NOT NULL,
`description` text NOT NULL,
`start_date` date NOT NULL,
`start_time` time NOT NULL,
`end_date` date NOT NULL,
`end_time` time NOT NULL,
`is_active` enum('0','1') NOT NULL DEFAULT '1',
`is_testdata` enum('0','1') NOT NULL DEFAULT '1',
`is_delete` enum('0','1') NOT NULL,
`created_date` datetime NOT NULL,
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `exclusive_media`
--

CREATE TABLE IF NOT EXISTS `exclusive_media` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`title` varchar(255) DEFAULT '',
`media_type` enum('Image','Audio','Video') DEFAULT 'Image',
`media_name` varchar(255) DEFAULT '',
`media_url` varchar(255) DEFAULT '',
`content` text,
`thumb_image` varchar(255) DEFAULT '',
`is_active` enum('0','1') DEFAULT '1',
`is_delete` enum('0','1') DEFAULT '0' COMMENT 'Will be used for soft delete of record.',
`is_testdata` enum('0','1') DEFAULT '1' COMMENT 'Will help identify dirty data added during testing and live data',
`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `exclusive_media`
--

INSERT INTO `exclusive_media` (`id`, `title`, `media_type`, `media_name`, `media_url`, `content`, `thumb_image`, `is_active`, `is_delete`, `is_testdata`, `created_date`, `modified_date`) VALUES
(1, 'test', 'Image', 'hkjhk', '32378d1b52673f8a2074c5e913318c45.jpg', 'dfgdr g dfg dfg sdf sdf sdf df ', '', '1', '0', '1', '0000-00-00 00:00:00', '2017-09-15 11:06:57');

-- --------------------------------------------------------

--
-- Table structure for table `facebook_content`
--

CREATE TABLE IF NOT EXISTS `facebook_content` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`facebook_id` varchar(255) DEFAULT '',
`title` varchar(255) DEFAULT '',
`media_type` enum('Image','Audio','Video') DEFAULT 'Image',
`media_name` varchar(255) DEFAULT '',
`media_url` varchar(255) DEFAULT '',
`description` text,
`thumb_image` varchar(255) DEFAULT '',
`no_of_likes` int(11) DEFAULT '0',
`no_of_comments` int(11) DEFAULT '0',
`is_delete` enum('0','1') DEFAULT '0' COMMENT 'Will be used for soft delete of record.',
`is_testdata` enum('0','1') DEFAULT '1' COMMENT 'Will help identify dirty data added during testing and live data',
`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`user_id` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `instagram_content`
--

CREATE TABLE IF NOT EXISTS `instagram_content` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`intsagram_id` varchar(255) DEFAULT '',
`title` varchar(255) DEFAULT '',
`media_type` enum('Image','Audio','Video') DEFAULT 'Image',
`media_name` varchar(255) DEFAULT '',
`media_url` varchar(255) DEFAULT '',
`description` text,
`thumb_image` varchar(255) DEFAULT '',
`no_of_likes` int(11) DEFAULT '0',
`no_of_comments` int(11) DEFAULT '0',
`is_delete` enum('0','1') DEFAULT '0' COMMENT 'Will be used for soft delete of record.',
`is_testdata` enum('0','1') DEFAULT '1' COMMENT 'Will help identify dirty data added during testing and live data',
`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`user_id` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `notification_times`
--

CREATE TABLE IF NOT EXISTS `notification_times` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`notification_time` varchar(50) NOT NULL,
`is_testdata` enum('0','1') NOT NULL DEFAULT '1',
`is_delete` enum('0','1') NOT NULL,
`created_date` datetime NOT NULL,
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=33 ;

--
-- Dumping data for table `notification_times`
--

INSERT INTO `notification_times` (`id`, `notification_time`, `is_testdata`, `is_delete`, `created_date`, `modified_date`) VALUES
(1, 'After Upload New video', '1', '0', '2017-09-11 00:00:00', '2017-09-15 04:14:03'),
(2, 'dfsdf', '1', '1', '2017-09-12 02:09:21', '2017-09-15 04:39:09'),
(3, 'After Delete Image', '1', '0', '2017-09-12 02:09:46', '2017-09-15 04:14:03'),
(4, 'After Upload New Audio', '1', '1', '2017-09-12 02:10:49', '2017-09-15 04:54:11'),
(5, 'sdfsdfsdf', '1', '1', '2017-09-12 02:24:36', '2017-09-15 04:39:03'),
(6, 'sdfsdfsdf dgdfg v dv df dfv ', '1', '1', '2017-09-12 02:27:47', '2017-09-15 04:39:00'),
(7, 'hfgjhghjfghfh dfgdfg', '1', '1', '2017-09-12 02:27:55', '2017-09-15 04:39:01'),
(8, 'After Delete Imagec gbdfgdf', '1', '1', '2017-09-12 02:29:16', '2017-09-15 04:39:06'),
(9, 'After Upload New Image', '1', '0', '2017-09-12 03:17:11', '2017-09-15 04:38:30'),
(10, 'After Edit Image', '1', '0', '2017-09-12 03:17:31', '2017-09-15 04:14:04'),
(11, 'After Delete Video', '1', '0', '2017-09-12 03:18:20', '2017-09-12 07:18:20'),
(12, 'After Edit Video test', '1', '0', '2017-09-12 03:18:29', '2017-09-13 04:20:51'),
(13, 'Test new', '1', '1', '2017-09-13 12:17:01', '2017-09-15 04:39:24'),
(14, 'Test', '1', '0', '2017-09-13 12:21:46', '2017-09-15 04:14:04'),
(15, 'Test', '1', '1', '2017-09-13 12:51:16', '2017-09-15 04:39:17'),
(16, 'Test', '1', '1', '2017-09-13 12:51:32', '2017-09-15 04:39:15'),
(17, 'Two', '1', '1', '2017-09-13 12:51:32', '2017-09-15 04:38:52'),
(18, 'New', '1', '0', '2017-09-13 12:51:47', '2017-09-15 04:14:04'),
(19, 'New', '1', '1', '2017-09-13 12:51:48', '2017-09-15 04:38:50'),
(20, 'New', '1', '1', '2017-09-13 12:51:48', '2017-09-15 04:38:48'),
(21, 'Check', '1', '1', '2017-09-13 04:42:27', '2017-09-15 04:34:04'),
(22, 'Two', '1', '1', '2017-09-13 04:42:34', '2017-09-15 04:38:46'),
(23, 'Two', '1', '0', '2017-09-13 04:42:40', '2017-09-15 04:14:04'),
(24, 'Three new', '1', '1', '2017-09-13 04:43:54', '2017-09-15 04:39:28'),
(25, 'Three', '1', '0', '2017-09-13 06:24:50', '2017-09-15 04:14:04'),
(26, 'Two', '1', '1', '2017-09-13 06:45:23', '2017-09-14 13:43:58'),
(27, 'Check', '1', '1', '2017-09-13 06:45:38', '2017-09-13 11:57:06'),
(28, 'test', '1', '1', '2017-09-13 06:52:29', '2017-09-13 10:52:53'),
(29, 'test Teat', '1', '1', '2017-09-13 06:52:59', '2017-09-13 11:51:41'),
(30, 'Check', '1', '1', '2017-09-13 07:57:11', '2017-09-13 11:58:08'),
(31, 'Testing Test', '1', '1', '2017-09-14 09:39:43', '2017-09-14 13:44:03'),
(32, 'Test', '1', '1', '2017-09-14 09:53:51', '2017-09-15 03:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL,
`question` text NOT NULL,
`is_testdata` enum('0','1') NOT NULL DEFAULT '1',
`is_active` enum('0','1') NOT NULL DEFAULT '1',
`is_delete` enum('0','1') NOT NULL DEFAULT '0',
`created_date` datetime NOT NULL,
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `question_answers`
--

CREATE TABLE IF NOT EXISTS `question_answers` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL,
`question_id` int(11) NOT NULL,
`answer` text NOT NULL,
`is_testdata` enum('0','1') NOT NULL DEFAULT '1',
`is_delete` enum('0','1') NOT NULL DEFAULT '0',
`created_date` datetime NOT NULL,
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
KEY `user_id` (`user_id`),
KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(20) NOT NULL,
`value` text NOT NULL,
`is_testdata` enum('0','1') NOT NULL DEFAULT '1',
`is_delete` enum('0','1') NOT NULL DEFAULT '0',
`created_date` datetime NOT NULL,
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `social_media_comments`
--

CREATE TABLE IF NOT EXISTS `social_media_comments` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`media_type` enum('Facebook','Twitter','Instagram','Youtube','Exclusive_Media') DEFAULT NULL,
`social_media_id` int(11) NOT NULL,
`user_id` int(11) NOT NULL,
`comments` text,
`is_delete` enum('0','1') NOT NULL DEFAULT '0',
`is_testdata` enum('0','1') NOT NULL,
`created_date` datetime NOT NULL,
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `social_media_likes`
--

CREATE TABLE IF NOT EXISTS `social_media_likes` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`media_type` enum('Facebook','Twitter','Instagram','Youtube','Exclusive_Media') DEFAULT NULL,
`social_media_id` int(11) NOT NULL,
`user_id` int(11) NOT NULL,
`is_delete` enum('0','1') NOT NULL DEFAULT '0',
`is_testdata` enum('0','1') NOT NULL,
`created_date` datetime NOT NULL,
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `twitter_content`
--

CREATE TABLE IF NOT EXISTS `twitter_content` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`twitter_id` varchar(255) DEFAULT '',
`title` varchar(255) DEFAULT '',
`media_type` enum('Image','Audio','Video') DEFAULT 'Image',
`media_name` varchar(255) DEFAULT '',
`media_url` varchar(255) DEFAULT '',
`description` text,
`thumb_image` varchar(255) DEFAULT '',
`no_of_likes` int(11) DEFAULT '0',
`no_of_comments` int(11) DEFAULT '0',
`is_delete` enum('0','1') DEFAULT '0' COMMENT 'Will be used for soft delete of record.',
`is_testdata` enum('0','1') DEFAULT '1' COMMENT 'Will help identify dirty data added during testing and live data',
`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`user_id` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(50) DEFAULT NULL,
`email` varchar(30) NOT NULL,
`dob` date DEFAULT '0000-00-00',
`gender` tinyint(1) DEFAULT NULL COMMENT '1- male, 2 - female',
`password` varchar(255) DEFAULT NULL,
`profile_pic` varchar(255) DEFAULT NULL,
`device_type` varchar(7) DEFAULT NULL COMMENT '1-iOS, 2-Android, 3-web',
`device_token` text,
`user_role` enum('user','admin') NOT NULL DEFAULT 'user',
`notification_time_id` varchar(30) DEFAULT NULL,
`facebook_id` varchar(200) DEFAULT NULL,
`guid` varchar(100) DEFAULT NULL,
`last_seen` datetime NOT NULL,
`is_testdata` enum('0','1') NOT NULL DEFAULT '1',
`is_active` enum('0','1') NOT NULL DEFAULT '1',
`is_delete` enum('0','1') NOT NULL DEFAULT '0',
`created_date` datetime NOT NULL,
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
UNIQUE KEY `email` (`email`),
KEY `notification_time_id` (`notification_time_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `dob`, `gender`, `password`, `profile_pic`, `device_type`, `device_token`, `user_role`, `notification_time_id`, `facebook_id`, `guid`, `last_seen`, `is_testdata`, `is_active`, `is_delete`, `created_date`, `modified_date`) VALUES
(1, 'Admin Admin', 'admin@narola.email', '1969-12-31', 1, '0192023a7bbd73250516f069df18b500', '', NULL, NULL, 'admin', NULL, NULL, NULL, '2017-09-09 00:00:00', '1', '1', '0', '2017-09-09 09:48:24', '2017-09-14 13:38:21'),
(2, 'test test', 'test@gmail.com', '1990-07-06', 0, 'cc03e747a6afbbcbf8be7668acfebee5', NULL, NULL, NULL, 'user', NULL, NULL, NULL, '2017-09-11 00:00:00', '1', '1', '1', '2017-09-11 00:00:00', '2017-09-15 09:11:54'),
(5, 'Hiren Sutariya', 'hsu@narola.email', '2016-10-10', 0, '44ab8d8bd0a34fd37dd22d4cae597b92', NULL, NULL, NULL, 'user', '0', NULL, NULL, '2017-09-12 00:00:00', '1', '1', '0', '2017-09-12 04:18:24', '2017-09-15 09:28:13'),
(6, 'Kisan Mistry', 'kim@narola.email', '1969-12-31', 1, '98467a817e2ff8c8377c1bf085da7138', NULL, NULL, NULL, 'user', '9,11', NULL, NULL, '2017-09-12 04:18:24', '1', '1', '0', '2017-09-12 04:18:24', '2017-09-15 09:28:13'),
(7, 'Kinjal', 'kb@narola.email', '1969-12-31', 1, 'e10adc3949ba59abbe56e057f20f883e', '1505379758887F2Xu01n.jpg', '1', 'exYx0PPQ4To:APA91bF7isbLCyhJ04EGv5qKwSyFMXs6gXhbtE0wONr3Styj8EexVPQL9PD3GiyWSTb3XGY-8xHXiJlAVJ1veSaJhZtbOz-WTJ9Q1_oFlN6G6lVeF1-BS135wtBLqCrQ-P6TGCHMy2-b', 'user', '0', NULL, 'ece39af7-114a-41c0-a24c-2252dffb', '2017-09-14 06:27:02', '1', '1', '0', '2017-09-14 06:27:02', '2017-09-15 10:17:56'),
(8, 'Ararti', 'arp@narola.email', '1990-04-11', 1, 'e10adc3949ba59abbe56e057f20f883e', '1505458037755GliKLk6.jpg', '1', 'exYx0PPQ4To:APA91bF7isbLCyhJ04EGv5qKwSyFMXs6gXhbtE0wONr3Styj8EexVPQL9PD3GiyWSTb3XGY-8xHXiJlAVJ1veSaJhZtbOz-WTJ9Q1_oFlN6G6lVeF1-BS135wtBLqCrQ-P6TGCHMy2-b', 'user', '1,3', '123456789', 'ee50b66a-e4d5-4cda-9eb3-84d42e93', '2017-09-14 06:44:03', '1', '1', '1', '2017-09-14 06:44:03', '2017-09-15 09:23:30'),
(9, 'Pinkesh', 'pig@narola.email', '1969-12-31', 1, 'e10adc3949ba59abbe56e057f20f883e', '', '1', '0', 'user', NULL, NULL, 'de09b722-4482-44e6-bef6-44c2c98a', '2017-09-14 09:54:13', '1', '1', '0', '2017-09-14 09:54:13', '2017-09-15 09:57:14'),
(10, 'Kinjal', 'pl@narola.email', '1969-12-31', 1, 'e10adc3949ba59abbe56e057f20f883e', '', '1', 'exYx0PPQ4To:APA91bF7isbLCyhJ04EGv5qKwSyFMXs6gXhbtE0wONr3Styj8EexVPQL9PD3GiyWSTb3XGY-8xHXiJlAVJ1veSaJhZtbOz-WTJ9Q1_oFlN6G6lVeF1-BS135wtBLqCrQ-P6TGCHMy2-b', 'user', NULL, NULL, 'fc9fa1bc-8ed8-4af7-9e7e-e891a0ef', '2017-09-14 11:14:46', '1', '1', '0', '2017-09-14 11:14:46', '2017-09-15 10:00:54'),
(11, 'piyush lakhani', 'piyush85730@gmail.com', '1969-12-31', 1, '86f500cd7b7d38e5d4ae6cde3920f589', '', '1', 'exYx0PPQ4To:APA91bF7isbLCyhJ04EGv5qKwSyFMXs6gXhbtE0wONr3Styj8EexVPQL9PD3GiyWSTb3XGY-8xHXiJlAVJ1veSaJhZtbOz-WTJ9Q1_oFlN6G6lVeF1-BS135wtBLqCrQ-P6TGCHMy2-b', 'user', NULL, NULL, '7427c7e5-d05d-4d71-b5ff-2882c0bd', '2017-09-14 11:16:53', '1', '1', '0', '2017-09-14 11:16:53', '2017-09-15 11:06:15'),
(12, 'piyush k lakhani', 'dako.lakhani@gmail.com', '1969-12-31', 0, '3c28445e7c230e7066aee1d8786e7197', '', '1', 'exYx0PPQ4To:APA91bF7isbLCyhJ04EGv5qKwSyFMXs6gXhbtE0wONr3Styj8EexVPQL9PD3GiyWSTb3XGY-8xHXiJlAVJ1veSaJhZtbOz-WTJ9Q1_oFlN6G6lVeF1-BS135wtBLqCrQ-P6TGCHMy2-b', 'user', '3', NULL, 'f9cf11ae-208b-4265-8a4d-8475119e', '2017-09-15 05:45:22', '1', '1', '0', '2017-09-15 05:45:22', '2017-09-15 10:07:30'),
(13, '', 'hp@narola.email', '1991-11-16', 1, NULL, '', '1', 'exYx0PPQ4To:APA91bF7isbLCyhJ04EGv5qKwSyFMXs6gXhbtE0wONr3Styj8EexVPQL9PD3GiyWSTb3XGY-8xHXiJlAVJ1veSaJhZtbOz-WTJ9Q1_oFlN6G6lVeF1-BS135wtBLqCrQ-P6TGCHMy2-b', 'user', NULL, '12345678', NULL, '2017-09-15 09:21:03', '1', '1', '0', '2017-09-15 09:21:03', '2017-09-15 10:11:45'),
(14, 'Harshal', 'hb@narola.email', '0000-00-00', 1, 'e10adc3949ba59abbe56e057f20f883e', '', '1', '0', 'user', NULL, NULL, 'a4d2f2ca-c82f-460e-921e-52d4dc8f', '2017-09-15 10:08:17', '1', '1', '0', '2017-09-15 10:08:17', '2017-09-15 04:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `youtube_content`
--

CREATE TABLE IF NOT EXISTS `youtube_content` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`youtube_id` varchar(255) DEFAULT '',
`title` varchar(255) DEFAULT '',
`media_type` enum('Image','Audio','Video') DEFAULT 'Image',
`media_name` varchar(255) DEFAULT '',
`media_url` varchar(255) DEFAULT '',
`description` text,
`thumb_image` varchar(255) DEFAULT '',
`no_of_likes` int(11) DEFAULT '0',
`no_of_comments` int(11) DEFAULT '0',
`is_delete` enum('0','1') DEFAULT '0' COMMENT 'Will be used for soft delete of record.',
`is_testdata` enum('0','1') DEFAULT '1' COMMENT 'Will help identify dirty data added during testing and live data',
`created_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
`modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`user_id` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `question_answers`
--
ALTER TABLE `question_answers`
ADD CONSTRAINT `question_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `question_answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
