-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2026 at 04:49 PM
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
-- Database: `microfinance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `finance_admin`
--

CREATE TABLE `finance_admin` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finance_admin`
--

INSERT INTO `finance_admin` (`id`, `full_name`, `username`, `password`, `created_at`) VALUES
(1, 'Master Admin', 'admin', 'password123', '2026-03-11 15:44:30');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `loan_amount` decimal(10,2) DEFAULT NULL,
  `term_months` int(11) DEFAULT NULL,
  `monthly_due` decimal(10,2) DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `interest_method` enum('FLAT','DIMINISHING') DEFAULT NULL,
  `outstanding` decimal(10,2) DEFAULT NULL,
  `next_payment` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `last_penalty_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `status` enum('ACTIVE','COMPLETED','RESTRUCTURED') NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `application_id`, `loan_amount`, `term_months`, `monthly_due`, `interest_rate`, `interest_method`, `outstanding`, `next_payment`, `due_date`, `last_penalty_date`, `start_date`, `status`) VALUES
(8, 7, 18, 10000.00, 6, 2016.67, 3.50, 'FLAT', 8066.67, '2026-05-09', '2026-09-09', NULL, '2026-03-09', 'RESTRUCTURED'),
(9, 2, 19, 10000.00, 6, 2016.67, 3.50, 'FLAT', 12100.00, '2026-04-10', '2026-09-10', NULL, '2026-03-10', 'RESTRUCTURED'),
(13, 1, 22, 10000.00, 6, 1966.67, 3.00, '', 11800.00, '2026-04-11', '2026-09-11', NULL, '2026-03-11', 'RESTRUCTURED'),
(14, 9, 23, 10000.00, 6, 1966.67, 3.00, '', 11800.00, '2026-04-11', '2026-09-11', NULL, '2026-03-11', 'ACTIVE'),
(15, 10, 24, 10000.00, 6, 1966.67, 3.00, 'FLAT', 10000.00, '2026-04-12', '2026-09-12', NULL, '2026-03-12', 'ACTIVE');

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `principal_amount` decimal(10,2) NOT NULL,
  `term_months` int(11) NOT NULL,
  `loan_purpose` text NOT NULL,
  `source_of_income` varchar(255) NOT NULL,
  `estimated_monthly_income` decimal(10,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `interest_type` enum('MONTHLY','ANNUAL') DEFAULT 'MONTHLY',
  `interest_method` enum('FLAT') DEFAULT 'FLAT',
  `total_interest` decimal(10,2) DEFAULT NULL,
  `total_payable` decimal(10,2) DEFAULT NULL,
  `monthly_due` decimal(10,2) DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','APPROVED','REJECTED') DEFAULT 'PENDING',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_applications`
--

INSERT INTO `loan_applications` (`id`, `user_id`, `principal_amount`, `term_months`, `loan_purpose`, `source_of_income`, `estimated_monthly_income`, `interest_rate`, `interest_type`, `interest_method`, `total_interest`, `total_payable`, `monthly_due`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(18, 7, 10000.00, 6, 'Business Capital', 'SHABU SELLER', 30000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'APPROVED', '', '2026-03-09 18:11:25', '2026-03-09 18:12:35'),
(19, 2, 10000.00, 6, 'Home Repair', 'ASAWA KO AY AFAM', 30000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'APPROVED', '', '2026-03-10 15:27:26', '2026-03-11 16:10:19'),
(20, 3, 10000.00, 6, 'Business Capital', 'ASAWA KO AY AFAM', 40000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'APPROVED', '', '2026-03-10 16:31:17', '2026-03-10 16:54:59'),
(21, 3, 12100.00, 9, 'RESTRUCTURE REQUEST | Type: Extension | Loan ID: 12 | Old Term: 6 | New Term: 9 | Old Monthly: 2016.67 | Est New Monthly: 1767.9444444444 | Reason: dami problema yahhhh', 'Restructure Request', 0.00, 3.50, 'MONTHLY', 'FLAT', NULL, NULL, NULL, 'REJECTED', '', '2026-03-10 17:35:17', '2026-03-11 20:16:43'),
(22, 1, 10000.00, 6, 'Medical Emergency', 'ASAWA KO AY AFAM', 40000.00, 3.00, 'MONTHLY', '', 1800.00, 11800.00, 1966.67, 'APPROVED', '', '2026-03-11 19:41:14', '2026-03-11 21:08:23'),
(23, 9, 10000.00, 6, 'Business Capital', 'ASAWA KO AY AFAM', 4000.00, 3.00, 'MONTHLY', '', 1800.00, 11800.00, 1966.67, 'APPROVED', '', '2026-03-11 21:17:30', '2026-03-12 01:09:19'),
(24, 10, 10000.00, 6, 'Medical Emergency', 'ASAWA KO AY AFAM', 50000.00, 3.00, 'MONTHLY', '', 1800.00, 11800.00, 1966.67, 'APPROVED', '', '2026-03-12 01:32:14', '2026-03-12 01:33:04'),
(25, 12, 10000.00, 6, 'Business Capital', 'ASAWA KO AY AFAM', 50000.00, 3.00, 'MONTHLY', '', 1800.00, 11800.00, 1966.67, 'APPROVED', '', '2026-03-12 03:09:10', '2026-03-12 03:26:06');

-- --------------------------------------------------------

--
-- Table structure for table `loan_disbursement`
--

CREATE TABLE `loan_disbursement` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `principal_amount` decimal(10,2) NOT NULL,
  `term_months` int(11) NOT NULL,
  `loan_purpose` text NOT NULL,
  `source_of_income` varchar(255) NOT NULL,
  `estimated_monthly_income` decimal(10,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `interest_type` enum('MONTHLY','ANNUAL') DEFAULT 'MONTHLY',
  `interest_method` enum('FLAT') DEFAULT 'FLAT',
  `total_interest` decimal(10,2) DEFAULT NULL,
  `total_payable` decimal(10,2) DEFAULT NULL,
  `monthly_due` decimal(10,2) DEFAULT NULL,
  `status` enum('WAITING FOR DISBURSEMENT','DISBURSED') DEFAULT 'WAITING FOR DISBURSEMENT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_disbursement`
--

INSERT INTO `loan_disbursement` (`id`, `application_id`, `user_id`, `principal_amount`, `term_months`, `loan_purpose`, `source_of_income`, `estimated_monthly_income`, `interest_rate`, `interest_type`, `interest_method`, `total_interest`, `total_payable`, `monthly_due`, `status`, `created_at`, `updated_at`) VALUES
(3, 18, 7, 10000.00, 6, 'Business Capital', 'SHABU SELLER', 30000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'DISBURSED', '2026-03-09 18:12:35', '2026-03-09 18:13:31'),
(10, 19, 2, 10000.00, 6, 'Home Repair', 'ASAWA KO AY AFAM', 30000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'DISBURSED', '2026-03-11 16:10:19', '2026-03-11 20:17:40'),
(11, 22, 1, 10000.00, 6, 'Medical Emergency', 'ASAWA KO AY AFAM', 40000.00, 3.00, 'MONTHLY', '', 1800.00, 11800.00, 1966.67, 'DISBURSED', '2026-03-11 21:08:23', '2026-03-11 21:08:50'),
(12, 23, 9, 10000.00, 6, 'Business Capital', 'ASAWA KO AY AFAM', 4000.00, 3.00, 'MONTHLY', '', 1800.00, 11800.00, 1966.67, 'WAITING FOR DISBURSEMENT', '2026-03-11 21:18:29', '2026-03-11 21:18:29'),
(13, 24, 10, 10000.00, 6, 'Medical Emergency', 'ASAWA KO AY AFAM', 50000.00, 3.00, 'MONTHLY', '', 1800.00, 11800.00, 1966.67, 'DISBURSED', '2026-03-12 01:33:04', '2026-03-12 02:02:01'),
(16, 25, 12, 10000.00, 6, 'Business Capital', 'ASAWA KO AY AFAM', 50000.00, 3.00, 'MONTHLY', '', 1800.00, 11800.00, 1966.67, 'WAITING FOR DISBURSEMENT', '2026-03-12 03:26:06', '2026-03-12 03:26:06');

-- --------------------------------------------------------

--
-- Table structure for table `loan_documents`
--

CREATE TABLE `loan_documents` (
  `id` int(11) NOT NULL,
  `loan_application_id` int(11) NOT NULL,
  `doc_type` enum('GOV_ID','PROOF_OF_INCOME','PROOF_OF_BILLING') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_documents`
--

INSERT INTO `loan_documents` (`id`, `loan_application_id`, `doc_type`, `file_path`, `uploaded_at`) VALUES
(46, 18, 'GOV_ID', 'uploads/loan_docs/GOV_ID_18_1dc386ac0a6c8e1f.jpg', '2026-03-09 18:11:25'),
(47, 18, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_18_7ea31cc0bd604038.png', '2026-03-09 18:11:25'),
(48, 18, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_18_9c33422370fcb78f.jpg', '2026-03-09 18:11:25'),
(49, 19, 'GOV_ID', 'uploads/loan_docs/GOV_ID_19_65187b3e85c58887.jpg', '2026-03-10 15:27:26'),
(50, 19, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_19_b0ec74d0fa989ec0.png', '2026-03-10 15:27:26'),
(51, 19, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_19_a7b512376fee2ef9.jpg', '2026-03-10 15:27:26'),
(52, 20, 'GOV_ID', 'uploads/loan_docs/GOV_ID_20_b5965f18be85d402.jpg', '2026-03-10 16:31:17'),
(53, 20, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_20_81de4dd75f469426.png', '2026-03-10 16:31:17'),
(54, 20, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_20_5c33cf7466edc5da.jpg', '2026-03-10 16:31:17'),
(55, 21, 'PROOF_OF_INCOME', 'uploads/loan_docs/RESTRUCTURE_PROOF_21_970a744b481d2374.png', '2026-03-10 17:35:17'),
(56, 22, 'GOV_ID', 'uploads/loan_docs/GOV_ID_22_d06bbb8838fd4f14.jpg', '2026-03-11 19:41:14'),
(57, 22, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_22_b985d9d2ecc98636.png', '2026-03-11 19:41:14'),
(58, 22, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_22_2c2a7a966df4cbe9.jpg', '2026-03-11 19:41:14'),
(59, 23, 'GOV_ID', 'uploads/loan_docs/GOV_ID_23_f052c6bd88428ccd.jpg', '2026-03-11 21:17:30'),
(60, 23, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_23_3c072c6a785e896d.png', '2026-03-11 21:17:30'),
(61, 23, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_23_cc1856852a7db9be.jpg', '2026-03-11 21:17:30'),
(62, 24, 'GOV_ID', 'uploads/loan_docs/GOV_ID_24_41af46ad205b0510.jpg', '2026-03-12 01:32:14'),
(63, 24, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_24_a30abd786a02143a.png', '2026-03-12 01:32:14'),
(64, 24, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_24_4eb7c423197710b5.jpg', '2026-03-12 01:32:14'),
(65, 25, 'GOV_ID', 'uploads/loan_docs/GOV_ID_25_4de9780c648d9d50.jpg', '2026-03-12 03:09:10'),
(66, 25, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_25_a53a03f2ae80c647.png', '2026-03-12 03:09:10'),
(67, 25, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_25_c5897b25c066bd4c.jpg', '2026-03-12 03:09:10');

-- --------------------------------------------------------

--
-- Table structure for table `loan_restructure_requests`
--

CREATE TABLE `loan_restructure_requests` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `restructure_type` enum('Extension','Holiday','Shorten') NOT NULL,
  `outstanding_snapshot` decimal(10,2) NOT NULL,
  `current_term_months` int(11) NOT NULL,
  `requested_term_months` int(11) NOT NULL,
  `current_monthly_due` decimal(10,2) NOT NULL,
  `estimated_monthly_due` decimal(10,2) NOT NULL,
  `interest_rate_snapshot` decimal(5,2) NOT NULL,
  `interest_method_snapshot` enum('FLAT','DIMINISHING') NOT NULL,
  `reason` text NOT NULL,
  `proof_doc_path` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','APPROVED','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verifier_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_restructure_requests`
--

INSERT INTO `loan_restructure_requests` (`id`, `loan_id`, `user_id`, `restructure_type`, `outstanding_snapshot`, `current_term_months`, `requested_term_months`, `current_monthly_due`, `estimated_monthly_due`, `interest_rate_snapshot`, `interest_method_snapshot`, `reason`, `proof_doc_path`, `status`, `verified_by`, `verified_at`, `verifier_notes`, `reviewed_by`, `review_notes`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(4, 9, 2, 'Extension', 12100.00, 6, 9, 2016.67, 1767.94, 3.50, 'FLAT', 'NATALO SCATTER', 'uploads/loan_docs/RESTRUCTURE_PROOF_9_880cd15b26d6cdcc.jpg', 'APPROVED', 0, '2026-03-12 04:18:51', 'OKI', 1, 'OKI', '2026-03-12 04:54:14', '2026-03-12 04:18:08', '2026-03-12 04:54:14'),
(5, 13, 1, 'Extension', 11800.00, 6, 9, 1966.67, 1665.11, 3.00, '', 'TALO SCATTER', 'uploads/loan_docs/RESTRUCTURE_PROOF_13_5393b9fa7ab24298.jpg', 'APPROVED', 0, '2026-03-12 10:07:49', '', 1, 'OKI', '2026-03-12 10:08:40', '2026-03-12 10:06:42', '2026-03-12 10:08:40');

-- --------------------------------------------------------

--
-- Table structure for table `lo_users`
--

CREATE TABLE `lo_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('ACTIVE','SUSPENDED') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT 'default_avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lo_users`
--

INSERT INTO `lo_users` (`id`, `full_name`, `username`, `password`, `status`, `created_at`, `profile_pic`) VALUES
(1, 'Neca Moratin', 'Neca@LO', '$2y$10$S51PY.SD4pEW6KyvHxOw8eIGnVZi1TWBHSV0Lhk9DR2McfuALbOym', 'ACTIVE', '2026-03-11 16:23:13', 'LO_1_1773247561.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `lsa_users`
--

CREATE TABLE `lsa_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('ACTIVE','SUSPENDED') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT 'default_avatar.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lsa_users`
--

INSERT INTO `lsa_users` (`id`, `full_name`, `username`, `password`, `status`, `created_at`, `profile_pic`) VALUES
(1, 'Jella Arañes', 'Jella@LSA', '$2y$10$mhqQ95pyY2pfHwcI6C50Y.HNwm.joDgfhZBFNW4575jtTKGh/r9si', 'ACTIVE', '2026-03-11 16:21:52', 'LSA_1_1773247569.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `icon` varchar(50) DEFAULT 'bi-bell-fill',
  `link` varchar(255) DEFAULT '#',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `icon`, `link`, `is_read`, `created_at`) VALUES
(1, 6, 'Application Verified', 'Good news! Your application <strong>#LA-17</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php', 1, '2026-03-09 15:37:51'),
(2, 6, 'Loan Application Declined', 'We regret to inform you that your application <strong>#LA-17</strong> was declined after review. Reason: Blurry ID, Suspicious Documents', 'danger', 'bi-x-circle-fill', 'myloans.php', 1, '2026-03-09 15:41:18'),
(3, 2, 'Application Verified', 'Good news! Your application <strong>#LA-19</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=19', 0, '2026-03-10 15:27:55'),
(4, 2, 'Loan Application Approved', 'Congratulations! Your loan application <strong>#LA-19</strong> has been formally approved by the Loan Officer. The funds are now queued for disbursement.', 'success', 'bi-patch-check-fill', 'myloans.php?app_id=19', 0, '2026-03-10 15:28:20'),
(5, 3, 'Application Verified', 'Good news! Your application <strong>#LA-20</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=20', 1, '2026-03-10 16:32:31'),
(6, 3, 'Loan Application Approved', 'Congratulations! Your loan application <strong>#LA-20</strong> has been formally approved by the Loan Officer. The funds are now queued for disbursement.', 'success', 'bi-patch-check-fill', 'myloans.php?app_id=20', 1, '2026-03-10 16:32:52'),
(7, 3, 'Loan Application Approved', 'Congratulations! Your loan application <strong>#LA-20</strong> has been formally approved by the Loan Officer. The funds are now queued for disbursement.', 'success', 'bi-patch-check-fill', 'myloans.php?app_id=20', 1, '2026-03-10 16:36:51'),
(8, 3, 'Loan Application Approved', 'Congratulations! Your loan application <strong>#LA-20</strong> has been formally approved by the Loan Officer. The funds are now queued for disbursement.', 'success', 'bi-patch-check-fill', 'myloans.php?app_id=20', 1, '2026-03-10 16:37:48'),
(9, 2, 'Application Verified', 'Good news! Your application <strong>#LA-19</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=19', 0, '2026-03-11 15:59:07'),
(10, 2, 'Application Verified', 'Good news! Your application <strong>#LA-19</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=19', 0, '2026-03-11 16:09:52'),
(11, 3, 'Application Verified', 'Good news! Your application <strong>#LA-21</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=21', 0, '2026-03-11 19:34:12'),
(12, 1, 'Application Verified', 'Good news! Your application <strong>#LA-22</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=22', 0, '2026-03-11 19:44:31'),
(13, 9, 'Application Verified', 'Good news! Your application <strong>#LA-23</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=23', 0, '2026-03-11 21:17:52'),
(14, 9, 'Application Verified', 'Good news! Your application <strong>#LA-23</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=23', 0, '2026-03-12 01:08:53'),
(15, 10, 'Application Verified', 'Good news! Your application <strong>#LA-24</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=24', 0, '2026-03-12 01:32:42'),
(16, 12, 'Application Verified', 'Good news! Your application <strong>#LA-25</strong> has been verified by our support team and forwarded to the Loan Officer for final review.', 'info', 'bi-check2-circle', 'myloans.php?app_id=25', 0, '2026-03-12 03:09:46');

-- --------------------------------------------------------

--
-- Table structure for table `restructured_loans`
--

CREATE TABLE `restructured_loans` (
  `id` int(11) NOT NULL,
  `original_loan_id` int(11) NOT NULL,
  `restructure_request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `principal_amount` decimal(10,2) NOT NULL,
  `term_months` int(11) NOT NULL,
  `monthly_due` decimal(10,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `interest_method` enum('FLAT','DIMINISHING') NOT NULL,
  `outstanding` decimal(10,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `next_payment` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('ACTIVE','COMPLETED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restructured_loans`
--

INSERT INTO `restructured_loans` (`id`, `original_loan_id`, `restructure_request_id`, `user_id`, `principal_amount`, `term_months`, `monthly_due`, `interest_rate`, `interest_method`, `outstanding`, `start_date`, `next_payment`, `due_date`, `status`, `created_at`) VALUES
(3, 9, 4, 2, 12100.00, 9, 1767.94, 3.50, 'FLAT', 12100.00, '2026-03-12', NULL, NULL, 'ACTIVE', '2026-03-12 04:54:14'),
(4, 13, 5, 1, 11800.00, 9, 1665.11, 3.00, '', 11800.00, '2026-03-12', NULL, NULL, 'ACTIVE', '2026-03-12 10:08:40');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'default_interest_rate', '3.0'),
(2, 'interest_method', 'DIMINISHING'),
(3, 'penalty_rate', '5.0'),
(4, 'processing_fee', '500'),
(5, 'grace_period', '3');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `restructured_loan_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('PENDING','PAID_PENDING','SUCCESS','FAILED') NOT NULL DEFAULT 'PENDING',
  `trans_date` datetime DEFAULT NULL,
  `provider_method` varchar(30) DEFAULT NULL,
  `paymongo_checkout_id` varchar(80) DEFAULT NULL,
  `paymongo_payment_id` varchar(80) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `receipt_image_pending_url` text DEFAULT NULL,
  `receipt_image_final_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `loan_id`, `restructured_loan_id`, `amount`, `status`, `trans_date`, `provider_method`, `paymongo_checkout_id`, `paymongo_payment_id`, `receipt_number`, `receipt_image_pending_url`, `receipt_image_final_url`) VALUES
(46, 7, 8, NULL, 2016.67, 'SUCCESS', '2026-03-11 00:02:21', 'GCASH', 'cs_e4b1a69ad48aa3ea581cfd58', 'pay_poFp2btAtcANn2LWytTc8v38', 'RCPT-20260310-000046', 'receipts/RCPT-20260310-000046-PENDING.png', NULL),
(48, 7, 8, NULL, 2016.67, 'SUCCESS', '2026-03-11 19:36:17', 'GCASH', 'cs_c3f63162be9772efe0aa8921', 'pay_MCS12qoDSxghuiH6sWr5Svwp', 'RCPT-20260311-000048', 'receipts/RCPT-20260311-000048-PENDING.png', NULL),
(54, 1, 13, 0, 1966.67, 'PENDING', '2026-03-12 05:08:56', '', 'cs_1a26108284d7d901f61a1a82', NULL, 'RCPT-20260311-000054', 'receipts/RCPT-20260311-000054-PENDING.png', NULL),
(55, 10, 15, 0, 1966.67, 'PAID_PENDING', '2026-03-12 10:03:29', 'GCASH', 'cs_4a173be8783e57d6bedc0006', 'pay_GCEQi9qHSxUWhB3rVGVqRCht', 'RCPT-20260312-000055', 'receipts/RCPT-20260312-000055-PENDING.png', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions_backup`
--

CREATE TABLE `transactions_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `trans_date` datetime DEFAULT NULL,
  `provider_ref` varchar(80) DEFAULT NULL,
  `provider_method` varchar(30) DEFAULT NULL,
  `paymongo_payment_id` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions_backup`
--

INSERT INTO `transactions_backup` (`id`, `user_id`, `loan_id`, `amount`, `status`, `trans_date`, `provider_ref`, `provider_method`, `paymongo_payment_id`) VALUES
(1, NULL, 1, 2016.67, 'PENDING', '2026-02-24 00:00:00', NULL, NULL, NULL),
(2, NULL, 1, 2016.67, 'PENDING', '2026-02-24 00:00:00', NULL, NULL, NULL),
(3, NULL, 1, 2016.67, 'PENDING', '2026-02-24 00:00:00', NULL, NULL, NULL),
(4, 1, 1, 2016.67, 'PENDING', '2026-02-24 13:26:51', NULL, NULL, NULL),
(5, NULL, 1, 2016.67, 'PENDING', '2026-02-24 13:29:16', NULL, NULL, NULL),
(6, NULL, 1, 2016.67, 'PENDING', '2026-02-24 22:08:56', NULL, NULL, NULL),
(7, NULL, 1, 2016.67, 'PENDING', '2026-02-24 22:11:08', NULL, NULL, NULL),
(8, NULL, 1, 2016.67, 'PENDING', '2026-02-24 23:25:07', NULL, NULL, NULL),
(9, NULL, 1, 2016.67, 'PENDING', '2026-02-25 00:04:41', NULL, NULL, NULL),
(10, NULL, 1, 2016.67, 'PENDING', '2026-02-25 00:11:40', NULL, NULL, NULL),
(11, NULL, 1, 2016.67, 'SUCCESS', '2026-02-25 00:20:43', NULL, NULL, NULL),
(12, 1, 1, 2016.67, 'SUCCESS', '2026-02-25 00:52:49', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `profile_pic`, `phone`, `email`, `password`, `created_at`) VALUES
(1, 'try123', NULL, '09912345678', 'try1@gmail.com', '$2y$10$wb7Q4T64e6Uis3xCxY24suIA9.ETku2kTq4Zw2vIRLEp4qiuIMy9a', '2026-02-20 05:01:41'),
(2, 'try123', NULL, '09987654321', 'try2@gmail.com', '$2y$10$n3ljtxFhUfjG1zpkeUNmHOi.dpUT.3X9hqNnbSK3txWJZuC8.isiy', '2026-02-20 05:11:10'),
(3, 'try3', NULL, '0976543212', 'try3@gmail.com', '$2y$10$cSDsybcnOFSw5Jntw3iUCuwCwGbdcU2tLrCtqqT7qwUOTwtr9QQaG', '2026-02-20 05:26:06'),
(4, 'Francis Leo Marcos', NULL, '0915 738 1992', 'breadpan@gmail.com', '$2y$10$hzj4NUFDsrBS7oXwSIreB.ng5VCJ72/HRASc9UNzebJwgg/aKByyG', '2026-02-24 17:46:03'),
(5, 'juan miguel', NULL, '09918713153', 'jm1@gmail.com', '$2y$10$CqnvDwZiK0E6V12e12cgKu2r6AlpuVnPS6HJWw8lsmngHAGtGhNES', '2026-02-26 03:15:37'),
(6, 'LebWrong James', 'user_6_1773070078.jpg', '0912 654 9865', 'libron@gmail.com', '$2y$10$RzIQRAVkbuIS0oYcGWImgeNdRg/eDn/iyFjBfV1z0.fG5w1d9ZaDG', '2026-02-27 17:56:40'),
(7, 'MARC LLOYD DE LEON', 'user_7_1773079628.jpg', '09812345667', 'try4@gmail.com', '$2y$10$i4VFTu4XuHlCgcs3mD.tOupqyhzPr815O840uosuTyokIncp7oFNe', '2026-03-07 14:07:53'),
(8, 'Anonymous', NULL, '09254385712', 'anony@gmail.com', '$2y$10$uznGuBhy00/ed/RH/CwQ1ekxxQRxbNqUbXZXsmqoXVdT2tEw7s/ai', '2026-03-07 18:29:58'),
(9, 'JUAN PADRE', NULL, '091234567890', 'JUAN@gmail.com', '$2y$10$.IdAPQGKFoUVF18yno18puVaNKtmlVfPBM75Fo6JyWY1.ShlVRjNW', '2026-03-11 21:16:45'),
(10, 'MIKE DAVU', NULL, '091234567809', 'MIKE@gmail.com', '$2y$10$hdvqycc3eVeksLpX83IwYec1Q4VPfJsYmb4rmte8.Xumuz5keLpqC', '2026-03-12 01:28:09'),
(11, 'TAYOYO', NULL, '091234567867', 'TAYOYO@gmail.com', '$2y$10$RMOsRRWs/UPtn6/Pih1RBub/Fcfo6EJTieYd3eujAsNFwOqzYhFnO', '2026-03-12 03:05:20'),
(12, 'JELLA@gmail.com', NULL, '091234567845', 'JELLA@gmail.com', '$2y$10$0FspP8h6ofmak6Omv6T86OefFP3a8objunCRkqdYg6rTn9pFbnRVm', '2026-03-12 03:08:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `finance_admin`
--
ALTER TABLE `finance_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loans_application` (`application_id`),
  ADD KEY `idx_loans_user_id` (`user_id`);

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_la_user_id` (`user_id`);

--
-- Indexes for table `loan_disbursement`
--
ALTER TABLE `loan_disbursement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_disbursement_application` (`application_id`),
  ADD KEY `fk_disbursement_user` (`user_id`);

--
-- Indexes for table `loan_documents`
--
ALTER TABLE `loan_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_doc` (`loan_application_id`,`doc_type`),
  ADD KEY `idx_ld_app_id` (`loan_application_id`);

--
-- Indexes for table `loan_restructure_requests`
--
ALTER TABLE `loan_restructure_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_restructure_loan` (`loan_id`),
  ADD KEY `idx_restructure_user` (`user_id`),
  ADD KEY `idx_restructure_status` (`status`);

--
-- Indexes for table `lo_users`
--
ALTER TABLE `lo_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `lsa_users`
--
ALTER TABLE `lsa_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`);

--
-- Indexes for table `restructured_loans`
--
ALTER TABLE `restructured_loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_restructured_original_loan` (`original_loan_id`),
  ADD KEY `idx_restructured_request` (`restructure_request_id`),
  ADD KEY `idx_restructured_user` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_setting_key` (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_paymongo_payment_id` (`paymongo_payment_id`),
  ADD KEY `fk_tx_loan` (`loan_id`),
  ADD KEY `idx_tx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `finance_admin`
--
ALTER TABLE `finance_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `loan_disbursement`
--
ALTER TABLE `loan_disbursement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `loan_documents`
--
ALTER TABLE `loan_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `loan_restructure_requests`
--
ALTER TABLE `loan_restructure_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lo_users`
--
ALTER TABLE `lo_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lsa_users`
--
ALTER TABLE `lsa_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `restructured_loans`
--
ALTER TABLE `restructured_loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_application` FOREIGN KEY (`application_id`) REFERENCES `loan_applications` (`id`),
  ADD CONSTRAINT `fk_loans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD CONSTRAINT `fk_application_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_la_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `loan_disbursement`
--
ALTER TABLE `loan_disbursement`
  ADD CONSTRAINT `fk_disbursement_application` FOREIGN KEY (`application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_disbursement_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `loan_documents`
--
ALTER TABLE `loan_documents`
  ADD CONSTRAINT `fk_doc_application` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`),
  ADD CONSTRAINT `fk_ld_app` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
