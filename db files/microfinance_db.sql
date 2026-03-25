-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 07:59 AM
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
-- Table structure for table `chat_support_messages`
--

CREATE TABLE `chat_support_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `replied_by` varchar(100) DEFAULT NULL,
  `status` enum('pending','read','replied') DEFAULT 'pending',
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_complaint` tinyint(1) DEFAULT 0,
  `ticket_id` varchar(20) DEFAULT NULL,
  `priority` enum('LOW','MEDIUM','HIGH') DEFAULT 'LOW',
  `is_resolved` tinyint(1) DEFAULT 0,
  `escalation_notes` text DEFAULT NULL,
  `client_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `loan_disbursement_id` int(11) DEFAULT NULL,
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
  `status` enum('ACTIVE','COMPLETED','RESTRUCTURED') NOT NULL DEFAULT 'ACTIVE',
  `receipt_no` varchar(50) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `release_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('WAITING FOR DISBURSEMENT','DISBURSED','REJECTED') DEFAULT 'WAITING FOR DISBURSEMENT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'default_interest_rate', '4.0'),
(2, 'interest_method', 'FLAT'),
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
  `principal_amount` decimal(10,2) DEFAULT 0.00,
  `interest_amount` decimal(10,2) DEFAULT 0.00,
  `penalty_amount` decimal(10,2) DEFAULT 0.00,
  `monthly_due` decimal(10,2) DEFAULT 0.00,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_status` varchar(50) DEFAULT 'ACTIVE',
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `device_hash` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_accounts`
--

CREATE TABLE `wallet_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `loan_wallet_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('ACTIVE','INACTIVE','BLOCKED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_sync_logs`
--

CREATE TABLE `wallet_sync_logs` (
  `id` int(11) NOT NULL,
  `wallet_transaction_id` int(11) DEFAULT NULL,
  `sync_action` varchar(50) NOT NULL,
  `payload_json` longtext DEFAULT NULL,
  `response_json` longtext DEFAULT NULL,
  `status` enum('SUCCESS','FAILED') NOT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `wallet_account_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `restructured_loan_id` int(11) DEFAULT NULL,
  `transaction_type` enum('CASH_IN','CASH_OUT','LOAN_PAYMENT','RESTRUCTURED_PAYMENT','ADJUSTMENT','LOAN_DISBURSEMENT') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `running_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','SUCCESS','FAILED','SYNCED','PARTIAL_SYNC') NOT NULL DEFAULT 'SUCCESS',
  `sync_status` enum('PENDING','SYNCED','FAILED') NOT NULL DEFAULT 'PENDING',
  `sync_error` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_support_messages`
--
ALTER TABLE `chat_support_messages`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `idx_restructure_status` (`status`),
  ADD KEY `idx_lrr_loan_id` (`loan_id`),
  ADD KEY `idx_lrr_user_id` (`user_id`),
  ADD KEY `idx_lrr_status` (`status`);

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
  ADD KEY `idx_restructured_user` (`user_id`),
  ADD KEY `idx_rl_original_loan_id` (`original_loan_id`),
  ADD KEY `idx_rl_restructure_request_id` (`restructure_request_id`),
  ADD KEY `idx_rl_user_id` (`user_id`),
  ADD KEY `idx_rl_status` (`status`);

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
  ADD KEY `idx_tx_user_id` (`user_id`),
  ADD KEY `idx_restructured_loan_id` (`restructured_loan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallet_accounts`
--
ALTER TABLE `wallet_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD UNIQUE KEY `uniq_wallet_user` (`user_id`);

--
-- Indexes for table `wallet_sync_logs`
--
ALTER TABLE `wallet_sync_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_wallet_tx_wallet` (`wallet_account_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_support_messages`
--
ALTER TABLE `chat_support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance_admin`
--
ALTER TABLE `finance_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `loan_disbursement`
--
ALTER TABLE `loan_disbursement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `loan_documents`
--
ALTER TABLE `loan_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `loan_restructure_requests`
--
ALTER TABLE `loan_restructure_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `restructured_loans`
--
ALTER TABLE `restructured_loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wallet_accounts`
--
ALTER TABLE `wallet_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wallet_sync_logs`
--
ALTER TABLE `wallet_sync_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
-- Constraints for table `loan_restructure_requests`
--
ALTER TABLE `loan_restructure_requests`
  ADD CONSTRAINT `fk_lrr_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lrr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `restructured_loans`
--
ALTER TABLE `restructured_loans`
  ADD CONSTRAINT `fk_rl_original_loan` FOREIGN KEY (`original_loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rl_request` FOREIGN KEY (`restructure_request_id`) REFERENCES `loan_restructure_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tx_restructured_loan` FOREIGN KEY (`restructured_loan_id`) REFERENCES `restructured_loans` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_tx_wallet` FOREIGN KEY (`wallet_account_id`) REFERENCES `wallet_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
