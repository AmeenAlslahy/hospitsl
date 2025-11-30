-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2025 at 11:47 PM
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
-- Database: `hospital_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled','no_show') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cancellation_reason` text DEFAULT NULL,
  `cancellation_requested_by` enum('patient','doctor','system','admin') DEFAULT NULL,
  `is_follow_up` tinyint(1) DEFAULT 0,
  `original_appointment_id` int(11) DEFAULT NULL COMMENT 'للمواعيد المتابعة',
  `payment_status` enum('pending','paid','partially_paid','cancelled') DEFAULT 'pending',
  `payment_amount` decimal(10,2) DEFAULT 0.00
) ;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `start_time`, `end_time`, `status`, `notes`, `created_at`, `updated_at`, `cancellation_reason`, `cancellation_requested_by`, `is_follow_up`, `original_appointment_id`, `payment_status`, `payment_amount`) VALUES
(1, 7, 4, '2025-07-20', '09:00:00', '10:00:00', 'cancelled', 'تشخيص', '2025-06-18 22:55:27', '2025-06-21 16:07:18', NULL, NULL, 0, NULL, 'pending', 0.00),
(6, 7, 4, '2025-07-10', '10:00:00', '11:00:00', 'confirmed', 'حجز', '2025-06-21 13:26:01', '2025-06-21 16:03:24', NULL, NULL, 0, NULL, 'pending', 0.00),
(7, 7, 5, '2025-06-21', '11:00:00', '12:00:00', 'confirmed', 'استشارة طبية', '2025-06-21 14:10:17', '2025-06-21 16:03:30', NULL, NULL, 0, NULL, 'pending', 0.00),
(12, 18, 5, '2025-07-20', '10:00:00', '11:00:00', 'confirmed', '', '2025-06-21 17:45:58', '2025-06-21 17:48:47', NULL, NULL, 0, NULL, 'pending', 0.00),
(13, 14, 4, '2025-06-22', '10:00:00', '11:00:00', 'cancelled', 'aaaaa', '2025-06-21 21:06:26', '2025-06-21 21:31:19', NULL, NULL, 0, NULL, 'pending', 0.00),
(14, 14, 4, '2025-06-22', '11:00:00', '12:00:00', 'confirmed', 'حجز', '2025-06-21 21:16:54', '2025-06-21 21:30:56', NULL, NULL, 0, NULL, 'pending', 0.00),
(15, 14, 4, '2025-06-22', '12:00:00', '13:00:00', 'cancelled', 'any thing', '2025-06-21 21:20:36', '2025-06-21 22:29:23', NULL, NULL, 0, NULL, 'pending', 0.00),
(16, 21, 5, '2025-06-22', '10:00:00', '11:00:00', 'confirmed', ';l;,;/', '2025-06-22 12:29:54', '2025-06-22 12:45:31', NULL, NULL, 0, NULL, 'pending', 0.00),
(17, 22, 5, '2025-06-23', '09:00:00', '10:00:00', 'pending', 'jklklj', '2025-06-23 09:02:04', '2025-06-23 09:02:04', NULL, NULL, 0, NULL, 'pending', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `blood_donations`
--

CREATE TABLE `blood_donations` (
  `donation_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `donation_date` datetime NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `amount` decimal(5,2) NOT NULL COMMENT 'بالملليلتر',
  `hemoglobin_level` decimal(4,2) NOT NULL,
  `status` enum('pending','completed','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `donation_duration` int(11) DEFAULT NULL COMMENT 'مدة التبرع بالدقائق',
  `donor_weight` decimal(5,2) DEFAULT NULL,
  `donor_temperature` decimal(3,1) DEFAULT NULL,
  `donation_location` varchar(100) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialty_id` int(11) NOT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `qualification` text NOT NULL,
  `experience` text DEFAULT NULL,
  `consultation_fee` decimal(10,2) NOT NULL CHECK (`consultation_fee` >= 0),
  `bio` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `years_of_experience` int(11) DEFAULT 0,
  `available_days` set('saturday','sunday','monday','tuesday','wednesday','thursday','friday') DEFAULT NULL,
  `working_hours_start` time DEFAULT '08:00:00',
  `working_hours_end` time DEFAULT '16:00:00',
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `is_accepting_new_patients` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `specialty_id`, `license_number`, `qualification`, `experience`, `consultation_fee`, `bio`, `is_available`, `years_of_experience`, `available_days`, `working_hours_start`, `working_hours_end`, `rating`, `total_reviews`, `is_accepting_new_patients`) VALUES
(2, 2, 1, 'DOC12345', 'دكتوراه في الطب البشري', NULL, 0.00, NULL, 1, 0, NULL, '08:00:00', '16:00:00', 0.00, 0, 1),
(4, 4, 2, '1010', 'دكتوراة-ماجستير', '', 2000.00, '', 1, 0, 'saturday,sunday,monday,tuesday,wednesday', '08:00:00', '16:00:00', 0.00, 0, 1),
(5, 32, 2, '55555', 'دكتوراة البورد العربي جراحة عامة\r\nماجستير جراحة البورد العربي اليمني\r\nباكالاريوس طب بشري جامعة صنعاء', 'اشتغل في مستشفى الامين', 2000.00, 'تحمل ضغط العمل\r\nالامانه والثقة ', 1, 3, 'saturday,sunday,monday,tuesday,wednesday,thursday', '09:00:00', '13:00:00', 0.00, 0, 1),
(6, 42, 1, '10', 'باكلاريوس \r\nثانوي', 'خبرة بالعمل', 2000.00, 'مخزن دائما', 1, 3, 'saturday,sunday,monday,tuesday,wednesday,thursday', '08:00:00', '16:00:00', 0.00, 0, 1),
(7, 43, 2, '20', 'jhjkhk', 'gjhgjh', 1000.00, 'gjghghg', 1, 2, 'saturday,sunday,monday,tuesday', '08:00:00', '16:00:00', 0.00, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_reviews`
--

CREATE TABLE `doctor_reviews` (
  `review_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `posted_date` date NOT NULL,
  `closing_date` date NOT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`job_id`, `title`, `department`, `description`, `requirements`, `salary_range`, `posted_date`, `closing_date`, `status`, `created_at`) VALUES
(1, 'تمريض', 'التمريض', 'دوام ليل', 'شهادة خبرة', NULL, '2025-06-16', '2025-06-20', 'open', '2025-06-16 16:04:23'),
(2, 'تمريض', 'التمريض', 'ليل', 'خبرة', NULL, '2025-06-16', '2025-06-20', 'open', '2025-06-16 16:26:28'),
(3, 'صيدله سريريه', 'الصيدلية', 'صيدله', 'شهاده خبره \r\nشهاده جامعيه', NULL, '2025-06-21', '2025-06-26', 'open', '2025-06-21 13:45:08'),
(4, 'طب عام', 'الطب', 'سيسبسيبسي', 'لبلشسيبلب', NULL, '2025-06-22', '2025-06-24', 'open', '2025-06-22 15:18:36'),
(5, 'عالم', 'التمريض', 'ابالباب', 'ابالبلاب', NULL, '2025-06-23', '2025-06-25', 'open', '2025-06-22 22:18:23');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `applicant_name` varchar(100) NOT NULL,
  `applicant_email` varchar(100) NOT NULL,
  `applicant_phone` varchar(20) NOT NULL,
  `cv_path` varchar(255) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('pending','reviewed','interviewed','hired','rejected') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`application_id`, `job_id`, `applicant_name`, `applicant_email`, `applicant_phone`, `cv_path`, `cover_letter`, `status`, `applied_at`, `reviewed_at`) VALUES
(1, 3, 'ايمن شايف عبداللة خالد الادريسي', 'aymanshaif@gmail.com', '716031727', 'uploads/cvs/20250621220546_1833e03f8ca7c96d.pdf', 'اااااااااااااااااااااااااا', 'pending', '2025-06-21 19:05:46', NULL),
(5, 5, 'ايمن شايف عبداللة خالد الادريسي', 'ayshaif@gmail.com', '714864221', 'uploads/cvs/20250623011946_5e01ed41bbf00a82.pdf', 'عهغغغغغغغغغغغغغغغغغغ', 'pending', '2025-06-22 22:19:46', NULL),
(7, 5, 'وسيم علي عبدالله الادريسي', 'waseem@gmail.com', '714864221', 'uploads/cvs/20250623012217_eb6353269a466092.pdf', 'ryyyyyyyyyyyyyyyyyyy', 'pending', '2025-06-22 22:22:17', NULL),
(15, 5, 'علي وسيم علي الدريسي', 'ali@gmail.com', '714589652', 'uploads/cvs/20250623013610_89cb1c9c42d0c308.pdf', 'jjjjjjjjjjjjjjjjjj', 'pending', '2025-06-22 22:36:10', NULL),
(26, 5, 'علي وسيم علي الدريسي', 'aliw@gmail.com', '789456123', 'uploads/cvs/20250623014750_16eaaa49a651d8d8.pdf', 'llllll', 'pending', '2025-06-22 22:47:50', NULL),
(29, 4, 'تنتناانت لتالتال تالتال ت', 'ajkjhanshaif@gmail.com', '852147963', 'uploads/cvs/20250623015741_def4832c95d00615.pdf', 'jkklj', 'pending', '2025-06-22 22:57:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`attempt_id`, `username`, `success`, `ip_address`, `user_agent`, `attempt_time`) VALUES
(1, 'alslahyamyn95@gmail.com', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 15:48:06'),
(2, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 15:48:27'),
(3, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 17:55:27'),
(4, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 17:58:26'),
(5, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 18:03:20'),
(6, 'admin', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 18:16:19'),
(7, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 18:16:27'),
(8, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 19:48:51'),
(9, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-16 19:59:58'),
(10, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 13:53:28'),
(11, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 15:14:24'),
(12, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:25:23'),
(13, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:26:32'),
(14, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:37:31'),
(15, 'admin', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:48:09'),
(16, 'admin', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:48:16'),
(17, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:48:39'),
(18, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 16:51:05'),
(19, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 17:34:13'),
(20, 'doctor1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 20:41:10'),
(21, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 20:56:32'),
(22, 'doctor1', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 20:58:41'),
(23, 'doctor1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 20:59:01'),
(24, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 21:00:12'),
(25, 'doctor1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 21:14:23'),
(26, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 22:39:10'),
(27, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 22:54:40'),
(28, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 22:55:54'),
(29, 'ali1', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 23:41:16'),
(30, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-17 23:41:23'),
(31, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 16:55:07'),
(32, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 22:14:52'),
(33, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 22:53:15'),
(34, 'doctor1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 22:57:22'),
(35, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 23:03:28'),
(36, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-18 23:08:59'),
(37, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 10:59:47'),
(38, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 11:01:09'),
(39, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 11:02:24'),
(40, 'عدنان الجنيد', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 11:15:26'),
(41, 'عدنان الجنيد', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 11:16:31'),
(42, 'عدنان الجنيد', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 11:16:43'),
(43, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 20:52:33'),
(44, 'ads', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 20:55:57'),
(45, 'dsf', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 20:56:08'),
(46, 'dsf', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 20:56:12'),
(47, 'dsf', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 20:56:15'),
(48, 'dsf', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 20:56:18'),
(49, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 21:34:21'),
(50, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-19 21:35:31'),
(51, 'ali', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 12:36:19'),
(52, 'ali', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 12:36:27'),
(53, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 12:36:48'),
(54, 'doctor1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 15:08:08'),
(55, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 15:14:24'),
(56, 'doctor1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 15:19:40'),
(57, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 17:08:03'),
(58, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 17:25:52'),
(59, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 18:50:34'),
(60, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 19:00:33'),
(61, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 23:16:50'),
(62, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 23:21:58'),
(63, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 23:25:40'),
(64, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 23:29:35'),
(65, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 13:17:27'),
(66, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 13:27:22'),
(67, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 13:28:18'),
(68, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 13:28:53'),
(69, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 13:31:57'),
(70, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 13:42:48'),
(71, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 13:47:23'),
(72, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 15:05:08'),
(73, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 15:06:45'),
(74, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 15:39:11'),
(75, 'amin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 15:40:39'),
(76, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 17:23:49'),
(77, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 17:24:56'),
(78, 'ali1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 17:33:14'),
(79, 'doctor1', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 20:11:40'),
(80, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 20:25:55'),
(81, 'shaif', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:05:00'),
(82, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:05:53'),
(83, 'ayman', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:17:37'),
(84, 'ayman', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:17:44'),
(85, 'ayman', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:17:50'),
(86, 'ayman', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:18:34'),
(87, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:20:10'),
(88, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:22:53'),
(89, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:31:55'),
(90, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 21:33:44'),
(91, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 12:25:27'),
(92, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 12:30:15'),
(93, 'عدنان الجنيد', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 12:33:38'),
(94, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 12:37:27'),
(95, 'wassem', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 13:05:01'),
(96, 'admin', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 13:27:56'),
(97, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 13:28:05'),
(98, 'عدنان الجنيد', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 17:23:13'),
(99, 'امين الصلاحي', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 17:23:29'),
(100, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 17:23:56'),
(101, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 20:50:38'),
(102, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 23:08:09'),
(103, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 23:13:11'),
(104, 'عدنان الجنيد', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 23:15:03'),
(105, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 23:32:34'),
(106, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 23:33:33'),
(107, 'عدنان الجنيد', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 23:40:30'),
(108, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 00:08:15'),
(109, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 06:37:24'),
(110, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 06:44:17'),
(111, 'ayman', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 07:04:43'),
(112, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 07:04:50'),
(113, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 07:21:09'),
(114, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 07:22:41'),
(115, 'امين الصلاحي', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 07:30:09'),
(116, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 07:30:17'),
(117, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 08:46:43'),
(118, 'امين الصلاحي', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 09:10:37'),
(119, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-23 09:10:46'),
(120, 'امين الصلاحي', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 02:49:36'),
(121, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 02:49:43'),
(122, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 03:02:33'),
(123, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 03:29:24'),
(124, 'امين الصلاحي', 0, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 04:02:37'),
(125, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 04:02:42'),
(126, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 04:09:34'),
(127, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 04:11:57'),
(128, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 05:35:56'),
(129, 'امين الصلاحي', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 05:48:49'),
(130, 'ayman', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-24 06:40:23');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `diagnosis` text NOT NULL,
  `prescription` text DEFAULT NULL,
  `tests` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_type` enum('diagnosis','treatment','lab_result','imaging','prescription') DEFAULT 'diagnosis',
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `follow_up_date` date DEFAULT NULL,
  `is_confidential` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `patient_id`, `doctor_id`, `appointment_id`, `diagnosis`, `prescription`, `tests`, `notes`, `created_at`, `updated_at`, `record_type`, `severity`, `follow_up_date`, `is_confidential`) VALUES
(5, 14, 4, 13, 'hi', 'koko', 'l;lkljlkj', 'trtdf', '2025-06-22 19:09:56', '2025-06-22 19:09:56', '', '', NULL, NULL),
(6, 14, 4, 14, 'ajkjkajkjk', 'kefsjdfsdjf', 'kdufskjhfdsjk', 'khfjhkjashkjdfh', '2025-06-22 19:30:50', '2025-06-22 19:30:50', '', '', NULL, NULL),
(7, 14, 4, 14, 'بياليبايب', 'خيهلعتيبنلمت', 'منيبتلمنيبتل', 'يبمنتليمبنت', '2025-06-22 20:07:39', '2025-06-22 20:07:39', '', '', NULL, NULL),
(8, 14, 4, 14, 'نمسيتبمسنيبت', 'نمتتنمت', 'متنمتنم', 'تنمتنمت', '2025-06-22 20:14:59', '2025-06-22 20:14:59', '', '', NULL, NULL),
(9, 14, 4, 13, 'يبرءؤ', 'ءرء', 'يرءؤر', 'ءؤرءؤئ', '2025-06-22 20:30:34', '2025-06-22 20:30:34', '', '', NULL, NULL),
(10, 7, 5, 7, 'زهايمر', 'سم زعاف', 'ولا شي', 'اكثر من النوم', '2025-06-22 23:48:43', '2025-06-22 23:48:43', '', '', NULL, NULL),
(11, 14, 4, 15, 'هانتا', 'نتاا', 'نلناتل', 'الت', '2025-06-23 07:23:53', '2025-06-23 07:23:53', '', '', NULL, NULL),
(12, 14, 4, 13, 'ااتللنتات', 'تناتنتاتنا', 'ااتاتناتتنا', 'ااتاتلااا', '2025-06-23 09:19:39', '2025-06-23 09:19:39', '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL COMMENT 'بالسنتيمتر',
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'بالكيلوجرام',
  `allergies` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `insurance_provider` varchar(100) DEFAULT NULL,
  `insurance_policy_number` varchar(50) DEFAULT NULL,
  `primary_physician` int(11) DEFAULT NULL COMMENT 'الطبيب المعالج الأساسي',
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `user_id`, `date_of_birth`, `blood_type`, `height`, `weight`, `allergies`, `medical_history`, `emergency_contact`, `insurance_provider`, `insurance_policy_number`, `primary_physician`, `marital_status`, `occupation`) VALUES
(1, 6, NULL, 'A+', 999.99, 60.00, 'صداع', '', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 7, NULL, 'O+', 999.99, 70.00, 'معدة', '', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 8, NULL, 'A+', 999.99, 65.00, 'كبد', '', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 14, NULL, 'A+', 999.99, 55.00, 'معدة', '06/15/2025', NULL, 'شركة الصلاحي', '1', NULL, NULL, NULL),
(7, 17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 18, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 19, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 22, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 33, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, 34, '2015-06-24', 'A+', 999.99, 60.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 35, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 36, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 37, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 38, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 39, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 41, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 44, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pharmacies`
--

CREATE TABLE `pharmacies` (
  `pharmacy_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `prescription_code` varchar(50) DEFAULT NULL,
  `status` enum('new','filled','cancelled') DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pharmacy_id` int(11) DEFAULT NULL,
  `is_printed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `item_id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medication_name` varchar(100) NOT NULL,
  `dosage` varchar(50) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `specialties`
--

CREATE TABLE `specialties` (
  `specialty_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `average_consultation_time` int(11) DEFAULT 30 COMMENT 'بالدقائق',
  `category` varchar(50) DEFAULT NULL COMMENT 'مثل: جراحة، باطنة، أطفال إلخ',
  `is_surgical` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `specialties`
--

INSERT INTO `specialties` (`specialty_id`, `name`, `description`, `image`, `is_active`, `created_at`, `average_consultation_time`, `category`, `is_surgical`) VALUES
(1, 'طب عام', 'التشخيص والعلاج العام للأمراض', NULL, 1, '2025-06-15 20:07:39', 30, NULL, 0),
(2, 'جراحة', 'العمليات الجراحية بأنواعها', NULL, 1, '2025-06-15 20:07:39', 30, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('admin','doctor','patient','staff') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL COMMENT 'رقم الهوية الوطنية',
  `address` text DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `account_verification_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `phone`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`, `profile_picture`, `gender`, `national_id`, `address`, `last_password_change`, `password_reset_token`, `account_verification_status`) VALUES
(1, 'admin', '123456', 'admin@hospital.com', 'مدير النظام', '0512345678', 'admin', 1, NULL, '2025-06-15 20:00:55', '2025-06-15 20:00:55', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(2, 'doctor1', '123456', 'doctor@hospital.com', 'د. أحمد محمد', '0511111111', 'doctor', 1, NULL, '2025-06-15 20:00:55', '2025-06-18 21:28:26', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(4, 'امين الصلاحي', '12345678', 'alslahyamyn95@gmail.com', 'امين عبده محمد ناجي الصلاحي', '713555262', 'doctor', 1, NULL, '2025-06-15 21:38:26', '2025-06-15 23:50:01', 'doctor_1750023506.jpg', NULL, NULL, NULL, NULL, NULL, 0),
(6, 'hesham1', '12345678', 'hesham@gmail.com', 'هشام احمد بن احمد', '777458632', 'patient', 1, NULL, '2025-06-15 22:21:58', '2025-06-16 12:32:09', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(7, 'ali1', '12345678', 'alimuhammed@gmail.com', 'علي محمد', '778452125', 'patient', 1, NULL, '2025-06-15 22:26:35', '2025-06-16 12:31:53', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(8, 'ali2', '$2y$12$KffYGBVzfEYI3Fp5R2L1euDHsvkiRWrPSaFAJ1JC0JhgyNN5jry86', 'alimuhammedsaleh@gmail.com', 'علي محمد صالح', '778452125', 'patient', 1, NULL, '2025-06-15 22:31:19', '2025-06-18 20:46:38', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(14, 'zahra@gmail.com', '$2y$12$HdO26XunlFdpXvZUf7lEMuuKTkIudETjakAlsWKSzH4B9RUR7u3oa', 'zahra@gmail.com', 'زهراء علي', '778147258', 'patient', 1, NULL, '2025-06-15 23:09:45', '2025-06-18 20:46:38', 'default-patient.png', NULL, NULL, NULL, NULL, NULL, 0),
(17, 'amin', '123456', 'fare@gmail.com', 'فارع ناجي علي', '777745632', 'patient', 1, NULL, '2025-06-16 15:15:25', '2025-06-21 15:40:04', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(18, '891', '$2y$12$IHqKzLCNmnGkMHJbgKycUO2IN/9ZIjQkBDqLkA6cAK61cuNxtGLOa', 'dfdsfsd@gmail.com', 'عبده فارع', '777456123', 'patient', 1, NULL, '2025-06-17 13:51:16', '2025-06-18 20:46:40', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(19, '701', '$2y$12$KgY1kR5mZ0X3Ypa4.RjsreJt3FOUVGqpT11bs4TWq0pPbAg1YtUxS', 'aaa@gmail.com', 'امين الصلاحي', '778778778', 'patient', 1, NULL, '2025-06-17 14:45:46', '2025-06-18 20:46:40', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(22, 'musheer179', '$2y$12$JB3qgQHHGTcmaMybv3b4x.8Ll6VzC/ycPJcVydqXZ3VDV8j6a5Nh2', 'musheer@gmail.com', 'musheer', '778774775', 'patient', 1, NULL, '2025-06-17 14:56:01', '2025-06-18 20:46:41', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(25, 'khaled200', '$2y$12$P73cEsYyo8B/Du1xijTtH.ERsUEgnl9JnHn8TM9eWBu9biHk79wLm', 'khaled@gmail.com', 'khaled', '774771772', 'patient', 1, NULL, '2025-06-17 15:54:05', '2025-06-18 20:46:41', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(26, 'ameen935', '$2y$12$QCHLcG5v3iSAboTFyxvNZOuaLZ6OIc74EmYbh46ZZhRlQszZbFS/.', 'ameen@gmail.com', 'ameen', '716715834', 'patient', 1, NULL, '2025-06-17 16:20:46', '2025-06-18 20:46:42', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(27, '400', '$2y$12$url/8ESllFBwBZC5yyFd3.FZx8ddTeERDCydCz44Zuw6o17MtGJCq', 'kaled@gmail.com', 'خالد علي', '777444111', 'patient', 1, NULL, '2025-06-18 20:19:09', '2025-06-18 20:19:09', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(28, 'ameen', '$2y$12$Ls6JEZ/CAeJg8o8TT2uvI.9UFHjks.0uZDi3LeGXoYEZurzAt2rOq', 'aliali@gmail.com', 'علي علي', '777888999', 'patient', 1, NULL, '2025-06-18 20:20:14', '2025-06-18 20:48:53', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(29, '417', '$2y$12$xxO4YrQEfrgxrGfT.N6r1uTtSjfUzdDL2bwNBn63Qj7FzqPIWx14G', 'asm@gmail.com', 'عاصم', '777555333', 'patient', 1, NULL, '2025-06-18 22:05:38', '2025-06-18 22:05:38', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(32, 'عدنان الجنيد ', '12345678', 'adnan@gmail.com', 'عدنان قحطان ناجي ناصر الجنيد', '777469960', 'doctor', 1, NULL, '2025-06-19 11:13:28', '2025-06-22 12:33:16', 'doctor_1750331608.jpg', NULL, NULL, NULL, NULL, NULL, 0),
(33, '640', '$2y$10$UAeL4bn0cVA.sX6kwFPI2.jZKiowDqhuxbX/kXCc8xBRoIPjthmN.', 'aziz@gmail.com', 'عبدالعزيز اعبدالسلام احمد خالد الادريسي', '774778771', 'patient', 1, NULL, '2025-06-19 20:07:22', '2025-06-19 20:07:22', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(34, 'ayman', '123456', 'ayman@gmail.com', 'ايمن لطف محمد ناجي الصلاحي', '711032518', 'patient', 1, NULL, '2025-06-19 20:11:20', '2025-06-20 23:21:32', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(35, '815', '123456789', 'mhmd@gmail.com', 'محمد عبده محمد ناجي', '713714715', 'patient', 1, NULL, '2025-06-19 21:13:15', '2025-06-19 21:13:15', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(36, '415', '123456789', 'ahlam@gmail.com', 'احلام علي سالم', '774775778', 'patient', 1, NULL, '2025-06-19 21:20:53', '2025-06-19 21:20:53', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(37, 'muhammedalisale482', '123456789', 'muham@gmil.com', 'muhammed ali  saleh ahmad ali', '777888999', 'patient', 1, NULL, '2025-06-19 21:27:03', '2025-06-19 21:27:03', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(38, 'aymanshaifaledr143', '12345678', 'shaif@gmail.com', 'ayman shaif aledreesi', '716031727', 'patient', 1, NULL, '2025-06-21 17:34:25', '2025-06-21 17:34:25', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(39, '700', '123456789', 'aladrysy@gmail.com', 'حسام شايف الادريسي', '444444444', 'patient', 1, NULL, '2025-06-21 20:02:59', '2025-06-21 20:02:59', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(40, 'husam44', '123456789+', 'husam@gmail.com', 'husam shaif aladrysy', '111111111', 'patient', 1, NULL, '2025-06-21 20:10:07', '2025-06-21 20:10:07', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(41, 'wassem', '00000555', 'waseem@gmail.com', 'وسيم علي عبدالله', '714864221', 'patient', 1, NULL, '2025-06-22 12:29:15', '2025-06-22 12:29:15', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(42, 'muhammad', '$2y$10$eg81Wq5ND8uhiU/mPt51mukmKaS1L01tOx59ep1ELki8QaChoTW/a', 'mmm@gmail.com', 'محمد امين الماس', '777888999', 'doctor', 1, NULL, '2025-06-23 06:48:20', '2025-06-23 06:48:20', 'doctor_1750661300.jpg', NULL, NULL, NULL, NULL, NULL, 0),
(43, 'aaaa', '$2y$10$URLjUxfy5dG7HMKaNFfuqeom9t19u2ROfuJ5U2pU5kCBKsxqh13pS', 'aaaa@gmail.com', 'امين عبده', '777888999', 'doctor', 1, NULL, '2025-06-23 08:50:17', '2025-06-23 08:50:17', 'doctor_1750668617.jpg', NULL, NULL, NULL, NULL, NULL, 0),
(44, 'user_934', '12345678', 'zzz@gmail.com', 'اسلام احمد', '777444111', 'patient', 1, NULL, '2025-06-23 09:01:17', '2025-06-23 09:01:17', NULL, NULL, NULL, NULL, NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `idx_doctor_date` (`doctor_id`,`appointment_date`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `original_appointment_id` (`original_appointment_id`);

--
-- Indexes for table `blood_donations`
--
ALTER TABLE `blood_donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `donor_id` (`donor_id`),
  ADD KEY `idx_donation_date` (`donation_date`),
  ADD KEY `idx_blood_type` (`blood_type`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_specialty` (`specialty_id`);

--
-- Indexes for table `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`applicant_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_job` (`job_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_doctor` (`doctor_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_blood_type` (`blood_type`),
  ADD KEY `primary_physician` (`primary_physician`);

--
-- Indexes for table `pharmacies`
--
ALTER TABLE `pharmacies`
  ADD PRIMARY KEY (`pharmacy_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD UNIQUE KEY `prescription_code` (`prescription_code`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `pharmacy_id` (`pharmacy_id`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`specialty_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blood_donations`
--
ALTER TABLE `blood_donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pharmacies`
--
ALTER TABLE `pharmacies`
  MODIFY `pharmacy_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `specialty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`original_appointment_id`) REFERENCES `appointments` (`appointment_id`);

--
-- Constraints for table `blood_donations`
--
ALTER TABLE `blood_donations`
  ADD CONSTRAINT `blood_donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`specialty_id`);

--
-- Constraints for table `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  ADD CONSTRAINT `doctor_reviews_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `doctor_reviews_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `doctor_reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`);

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patients_ibfk_2` FOREIGN KEY (`primary_physician`) REFERENCES `doctors` (`doctor_id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`pharmacy_id`) REFERENCES `pharmacies` (`pharmacy_id`);

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
