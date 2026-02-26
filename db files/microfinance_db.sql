-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2026 at 03:13 AM
-- Generation Time: Feb 24, 2026 at 08:18 PM
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
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `application_id`, `loan_amount`, `term_months`, `monthly_due`, `interest_rate`, `interest_method`, `outstanding`, `next_payment`, `due_date`, `start_date`, `status`) VALUES
(1, 1, NULL, 10000.00, 6, 2016.67, 3.50, 'FLAT', 0.00, '2026-10-22', '2026-08-22', '2026-02-22', 'ACTIVE');

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
  `status` enum('PENDING','APPROVED','REJECTED','CANCELLED') DEFAULT 'PENDING',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_applications`
--

INSERT INTO `loan_applications` (`id`, `user_id`, `principal_amount`, `term_months`, `loan_purpose`, `source_of_income`, `estimated_monthly_income`, `interest_rate`, `interest_type`, `interest_method`, `total_interest`, `total_payable`, `monthly_due`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(3, 1, 10000.00, 6, 'Business Capital', 'ASAWA KO AY AFAM', 20000.00, 3.50, 'MONTHLY', 'FLAT', NULL, NULL, NULL, 'APPROVED', NULL, '2026-02-21 12:16:00', '2026-02-21 15:58:02'),
(4, 1, 10000.00, 1, 'Business Capital', 'ASAWA KO AY AFAM', 35000.00, 3.50, 'MONTHLY', 'FLAT', NULL, NULL, NULL, 'REJECTED', 'Expired Documents', '2026-02-21 12:19:05', '2026-02-24 18:12:19'),
(5, 1, 10000.00, 6, 'Business Capital', 'shabusilog', 30000.00, 3.50, 'MONTHLY', 'FLAT', NULL, NULL, NULL, 'REJECTED', NULL, '2026-02-21 12:34:58', '2026-02-24 17:33:16'),
(6, 1, 10000.00, 6, 'Business Capital', 'SHABU SELLER', 50000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'REJECTED', 'Blurry Id', '2026-02-21 14:48:55', '2026-02-24 17:40:51'),
(7, 2, 10000.00, 6, 'Business Capital', 'ASAWA KO AY AFAM', 30000.00, 3.50, 'MONTHLY', 'FLAT', 2100.00, 12100.00, 2016.67, 'APPROVED', NULL, '2026-02-24 03:59:26', '2026-02-24 04:00:07'),
(8, 4, 29000.00, 3, 'Medical Emergency', 'drag racing', 50000.00, 3.50, 'MONTHLY', 'FLAT', 3045.00, 32045.00, 10681.67, 'APPROVED', 'Blurry ID, Expired Documents', '2026-02-24 17:50:04', '2026-02-24 17:54:50');

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
(1, 3, 'GOV_ID', 'uploads/loan_docs/GOV_ID_3_62af609e7cd78ca2.png', '2026-02-21 12:16:00'),
(2, 3, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_3_7669a19c8a6854b4.png', '2026-02-21 12:16:00'),
(3, 3, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_3_cb3b1a90ee463174.png', '2026-02-21 12:16:00'),
(4, 4, 'GOV_ID', 'uploads/loan_docs/GOV_ID_4_f53f155aa27577f2.png', '2026-02-21 12:19:05'),
(5, 4, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_4_032ce0e4fb44b434.png', '2026-02-21 12:19:05'),
(6, 4, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_4_f2ef52aeac6495ca.png', '2026-02-21 12:19:05'),
(7, 5, 'GOV_ID', 'uploads/loan_docs/GOV_ID_5_94258c36426f7f4f.jpg', '2026-02-21 12:34:58'),
(8, 5, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_5_11786295cfa34909.png', '2026-02-21 12:34:58'),
(9, 5, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_5_7d2c1c87557749d0.jpg', '2026-02-21 12:34:59'),
(10, 6, 'GOV_ID', 'uploads/loan_docs/GOV_ID_6_85500060e92e9a0b.jpg', '2026-02-21 14:48:55'),
(11, 6, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_6_3dec4a1e79e7ce9e.png', '2026-02-21 14:48:55'),
(12, 6, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_6_54e205d751f6b671.jpg', '2026-02-21 14:48:55'),
(13, 7, 'GOV_ID', 'uploads/loan_docs/GOV_ID_7_d1e60b8dd710dc41.jpg', '2026-02-24 03:59:26'),
(14, 7, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_7_274f1617fd4ca505.png', '2026-02-24 03:59:26'),
(15, 7, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_7_bd3b082780a0eadd.jpg', '2026-02-24 03:59:26'),
(16, 8, 'GOV_ID', 'uploads/loan_docs/GOV_ID_8_745310883b8b348a.jpg', '2026-02-24 17:50:04'),
(17, 8, 'PROOF_OF_INCOME', 'uploads/loan_docs/PROOF_OF_INCOME_8_bbf809f11cbb007a.png', '2026-02-24 17:50:04'),
(18, 8, 'PROOF_OF_BILLING', 'uploads/loan_docs/PROOF_OF_BILLING_8_7ed7c25974b43baf.jpg', '2026-02-24 17:50:04');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
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

INSERT INTO `transactions` (`id`, `user_id`, `loan_id`, `amount`, `status`, `trans_date`, `provider_method`, `paymongo_checkout_id`, `paymongo_payment_id`, `receipt_number`, `receipt_image_pending_url`, `receipt_image_final_url`) VALUES
(1, 1, 1, 2016.67, 'SUCCESS', '2026-02-25 10:00:32', 'GCASH', 'cs_ca7c4b1de80149cd5c300df5', 'pay_fBkzcqkZEcVUo1SQyAi1Ek8U', 'RCPT-20260225-000001', 'receipts/RCPT-20260225-000001-PENDING.png', 'receipts/RCPT-20260225-000001-FINAL.png'),
(2, 1, 1, 2016.67, 'SUCCESS', '2026-02-25 10:07:29', 'GCASH', 'cs_b997ad2110260ea47609e3ab', 'pay_zonD6AdtEDHd2DdRAZrPNogQ', 'RCPT-20260225-000002', 'receipts/RCPT-20260225-000002-PENDING.png', 'receipts/RCPT-20260225-000002-FINAL.png');

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
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `phone`, `email`, `password`, `created_at`) VALUES
(1, 'try123', '09912345678', 'try1@gmail.com', '$2y$10$wb7Q4T64e6Uis3xCxY24suIA9.ETku2kTq4Zw2vIRLEp4qiuIMy9a', '2026-02-20 05:01:41'),
(2, 'try123', '09987654321', 'try2@gmail.com', '$2y$10$n3ljtxFhUfjG1zpkeUNmHOi.dpUT.3X9hqNnbSK3txWJZuC8.isiy', '2026-02-20 05:11:10'),
(3, 'try3', '0976543212', 'try3@gmail.com', '$2y$10$cSDsybcnOFSw5Jntw3iUCuwCwGbdcU2tLrCtqqT7qwUOTwtr9QQaG', '2026-02-20 05:26:06'),
(4, 'Francis Leo Marcos', '0915 738 1992', 'breadpan@gmail.com', '$2y$10$hzj4NUFDsrBS7oXwSIreB.ng5VCJ72/HRASc9UNzebJwgg/aKByyG', '2026-02-24 17:46:03');

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
-- Indexes for table `loan_documents`
--
ALTER TABLE `loan_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_doc` (`loan_application_id`,`doc_type`),
  ADD KEY `idx_ld_app_id` (`loan_application_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `loan_documents`
--
ALTER TABLE `loan_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `loan_documents`
--
ALTER TABLE `loan_documents`
  ADD CONSTRAINT `fk_doc_application` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`),
  ADD CONSTRAINT `fk_ld_app` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
