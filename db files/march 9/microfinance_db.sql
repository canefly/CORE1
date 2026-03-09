-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2026 at 05:07 PM
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
  `start_date` date DEFAULT NULL,
  `status` enum('ACTIVE','COMPLETED') NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `application_id`, `loan_amount`, `term_months`, `monthly_due`, `interest_rate`, `interest_method`, `outstanding`, `next_payment`, `due_date`, `start_date`, `status`) VALUES
(6, 1, 15, 36000.00, 6, 7260.00, 3.50, 'FLAT', 43560.00, '2026-03-27', '2026-08-27', '2026-02-27', 'ACTIVE'),
(7, 7, 16, 10000.00, 6, 2016.67, 3.50, 'FLAT', 12100.00, '2026-04-07', '2026-09-07', '2026-03-07', 'ACTIVE');

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
(15, 1, 36000.00, 6, 'Business Capital', 'ASAWA KO AY AFAM', 70000.00, 3.50, 'MONTHLY', 'FLAT', 7560.00, 43560.00, 7260.00, 'APPROVED', '', '2026-02-27 18:33:15', '2026-02-27 18:34:09'),
(16, 7, 10000.00, 6, 'Home Repair', 'ASAWA KO AY AFAM', 50000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'APPROVED', '', '2026-03-07 14:09:41', '2026-03-07 17:45:51'),
(17, 6, 37000.00, 6, 'Business Capital', 'drag racing', 10000.00, 3.50, 'MONTHLY', 'FLAT', 7770.00, 44770.00, 7461.67, 'REJECTED', 'Blurry ID, Suspicious Documents', '2026-03-09 15:37:17', '2026-03-09 15:41:18');

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
(2, 16, 7, 10000.00, 6, 'Home Repair', 'ASAWA KO AY AFAM', 50000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'WAITING FOR DISBURSEMENT', '2026-03-07 17:45:52', '2026-03-07 17:45:52');

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
(37, 15, 'GOV_ID', 'uploads/loan_docs/GOV_ID_15_615074dfd02e251c.jpg', '2026-02-27 18:33:15'),
(38, 15, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_15_e548748b2f8d6f94.png', '2026-02-27 18:33:15'),
(39, 15, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_15_e2ad75118e1584e4.jpg', '2026-02-27 18:33:15'),
(40, 16, 'GOV_ID', 'uploads/loan_docs/GOV_ID_16_39acfa32aa474c42.jpg', '2026-03-07 14:09:41'),
(41, 16, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_16_a88894e77acd824b.png', '2026-03-07 14:09:41'),
(42, 16, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_16_c064849ff25a2702.jpg', '2026-03-07 14:09:41'),
(43, 17, 'GOV_ID', 'uploads/loan_docs/GOV_ID_17_2ad3de4b237eeb76.jpg', '2026-03-09 15:37:17'),
(44, 17, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_17_853174af0513fa83.jpg', '2026-03-09 15:37:17'),
(45, 17, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_17_40a96597d07235fd.jpg', '2026-03-09 15:37:17');

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
(2, 6, 'Loan Application Declined', 'We regret to inform you that your application <strong>#LA-17</strong> was declined after review. Reason: Blurry ID, Suspicious Documents', 'danger', 'bi-x-circle-fill', 'myloans.php', 1, '2026-03-09 15:41:18');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `loan_id` int(11) DEFAULT NULL,
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
(7, 'MARC LLOYD DE LEON', NULL, '09812345667', 'try4@gmail.com', '$2y$10$AYgd9ovitJo/yUr5/KJ4euxPFUAZN.Ftqa04vmofFuQ2O9fjr.47.', '2026-03-07 14:07:53'),
(8, 'Anonymous', NULL, '09254385712', 'anony@gmail.com', '$2y$10$uznGuBhy00/ed/RH/CwQ1ekxxQRxbNqUbXZXsmqoXVdT2tEw7s/ai', '2026-03-07 18:29:58');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`);

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
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `loan_disbursement`
--
ALTER TABLE `loan_disbursement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loan_documents`
--
ALTER TABLE `loan_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
