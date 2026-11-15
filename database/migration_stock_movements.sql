-- ============================================================
-- Migration: Stock Movement History (Janurshop Fuzzy Mamdani)
-- Branch: feature/stock-movement-history
-- Description:
--   1. Creates `stock_movements` table with InnoDB & ON DELETE RESTRICT
--   2. Provides idempotent baseline opening balance insertion
-- ============================================================

-- Step 1: Create stock_movements table
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `type` ENUM('in', 'out', 'adjustment', 'opening_balance') NOT NULL COMMENT 'Movement classification',
  `quantity` FLOAT NOT NULL COMMENT 'Absolute quantity moved or initial baseline (must be >= 0)',
  `stock_before` FLOAT NOT NULL COMMENT 'Physical stock level immediately prior to transaction',
  `stock_after` FLOAT NOT NULL COMMENT 'Physical stock level immediately after transaction',
  `reference_no` VARCHAR(100) DEFAULT NULL COMMENT 'Optional PO, invoice, delivery order, or internal reference',
  `notes` VARCHAR(255) DEFAULT NULL COMMENT 'Reason, recipient, supplier info, or adjustment justification',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sm_item_id` (`item_id`),
  KEY `idx_sm_user_id` (`user_id`),
  KEY `idx_sm_type` (`type`),
  KEY `idx_sm_created_at` (`created_at`),
  CONSTRAINT `fk_sm_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 2: Idempotent Opening Balance Migration
-- Creates an opening balance record for each existing product in `items`
-- that does not already have an opening_balance record in `stock_movements`.
-- Existing stock values in `items` are left completely untouched.

INSERT INTO `stock_movements` (
    `item_id`,
    `user_id`,
    `type`,
    `quantity`,
    `stock_before`,
    `stock_after`,
    `reference_no`,
    `notes`,
    `created_at`
)
SELECT 
    i.`id`,
    COALESCE(
        (SELECT u.`id` FROM `users` u WHERE u.`role` = 'admin' ORDER BY u.`id` ASC LIMIT 1),
        1
    ) AS `user_id`,
    'opening_balance' AS `type`,
    i.`stock` AS `quantity`,
    0 AS `stock_before`,
    i.`stock` AS `stock_after`,
    'INIT-BASELINE' AS `reference_no`,
    'Saldo awal sistem saat implementasi fitur Riwayat Mutasi Stok' AS `notes`,
    NOW() AS `created_at`
FROM `items` i
WHERE i.`id` NOT IN (
    SELECT sm.`item_id` 
    FROM `stock_movements` sm 
    WHERE sm.`type` = 'opening_balance'
);
