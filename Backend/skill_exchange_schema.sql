-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2026 at 09:26 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skill_exchange_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `availability_slots`
--

CREATE TABLE `availability_slots` (
  `availability_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `day_of_week` varchar(15) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `availability_slots`
--


-- --------------------------------------------------------

--
-- Table structure for table `connection_requests`
--

CREATE TABLE `connection_requests` (
  `request_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `connection_requests`
--


-- --------------------------------------------------------

--
-- Table structure for table `learning_sessions`
--

CREATE TABLE `learning_sessions` (
  `session_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_sessions`
--


-- --------------------------------------------------------

--
-- Table structure for table `match_recommendations`
--

CREATE TABLE `match_recommendations` (
  `match_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `matched_user_id` int(11) NOT NULL,
  `compatibility_score` decimal(5,2) NOT NULL,
  `match_type` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `match_recommendations`
--


-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_content` text NOT NULL,
  `sent_at` timestamp NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--


-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `skill_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Seed general skills so new accounts have choices immediately.
INSERT INTO `skills` (`skill_id`, `skill_name`, `category`) VALUES
(1, 'Web Development', 'Technology'),
(2, 'Graphic Design', 'Design'),
(3, 'Photography', 'Creative'),
(4, 'Video Editing', 'Creative'),
(5, 'Data Analysis', 'Technology'),
(6, 'Digital Marketing', 'Business'),
(7, 'UI/UX Design', 'Design'),
(8, 'Python Programming', 'Technology'),
(9, 'Microsoft Excel', 'Productivity'),
(10, 'Content Writing', 'Communication'),
(11, 'Public Speaking', 'Communication'),
(12, 'Mobile App Development', 'Technology');


-- --------------------------------------------------------

--
-- Table structure for table `skills_offered`
--

CREATE TABLE `skills_offered` (
  `offered_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency_level` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills_offered`
--


-- --------------------------------------------------------

--
-- Table structure for table `skills_wanted`
--

CREATE TABLE `skills_wanted` (
  `wanted_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills_wanted`
--


-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL,
  `level_of_study` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profiles`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `availability_slots`
--
ALTER TABLE `availability_slots`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `connection_requests`
--
ALTER TABLE `connection_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `sender_id` (`sender_id`,`receiver_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `learning_sessions`
--
ALTER TABLE `learning_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `partner_id` (`partner_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `match_recommendations`
--
ALTER TABLE `match_recommendations`
  ADD PRIMARY KEY (`match_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`matched_user_id`),
  ADD KEY `matched_user_id` (`matched_user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD UNIQUE KEY `skill_name` (`skill_name`);

--
-- Indexes for table `skills_offered`
--
ALTER TABLE `skills_offered`
  ADD PRIMARY KEY (`offered_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `skills_wanted`
--
ALTER TABLE `skills_wanted`
  ADD PRIMARY KEY (`wanted_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`skill_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `availability_slots`
--
ALTER TABLE `availability_slots`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `connection_requests`
--
ALTER TABLE `connection_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_sessions`
--
ALTER TABLE `learning_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `match_recommendations`
--
ALTER TABLE `match_recommendations`
  MODIFY `match_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `skills_offered`
--
ALTER TABLE `skills_offered`
  MODIFY `offered_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills_wanted`
--
ALTER TABLE `skills_wanted`
  MODIFY `wanted_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `availability_slots`
--
ALTER TABLE `availability_slots`
  ADD CONSTRAINT `availability_slots_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `connection_requests`
--
ALTER TABLE `connection_requests`
  ADD CONSTRAINT `connection_requests_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `connection_requests_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `learning_sessions`
--
ALTER TABLE `learning_sessions`
  ADD CONSTRAINT `learning_sessions_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `learning_sessions_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `learning_sessions_ibfk_3` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `match_recommendations`
--
ALTER TABLE `match_recommendations`
  ADD CONSTRAINT `match_recommendations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_recommendations_ibfk_2` FOREIGN KEY (`matched_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `skills_offered`
--
ALTER TABLE `skills_offered`
  ADD CONSTRAINT `skills_offered_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `skills_offered_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `skills_wanted`
--
ALTER TABLE `skills_wanted`
  ADD CONSTRAINT `skills_wanted_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `skills_wanted_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
