<?php
/**
 * Idempotent Database Migration Script for Stock Movement History
 * Janurshop Fuzzy Mamdani
 * 
 * Usage:
 *   CLI: php migrate_stock_movements.php
 *   Web: Accessible only to authenticated administrators.
 */

if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die("Akses ditolak: Hanya administrator yang dapat menjalankan migrasi.");
    }
}

require_once __DIR__ . '/config/database.php';

echo "=== Memulai Migrasi Database: Riwayat Mutasi Stok ===\n";

global $conn;

// 1. Create stock_movements table
$createTableSql = "
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
";

if (!$conn->query($createTableSql)) {
    die("ERROR pembuatan tabel stock_movements: " . $conn->error . "\n");
}
echo "[OK] Tabel stock_movements berhasil diverifikasi/dibuat.\n";

// 2. Determine admin user ID for opening balance attribution
$adminRes = $conn->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
$adminId = 1;
if ($adminRes && $adminRes->num_rows > 0) {
    $adminId = (int)$adminRes->fetch_assoc()['id'];
}

// 3. Idempotent Opening Balance Migration
$migrationSql = "
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
    $adminId AS `user_id`,
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
";

if (!$conn->query($migrationSql)) {
    die("ERROR migrasi saldo awal: " . $conn->error . "\n");
}

$affected = $conn->affected_rows;
echo "[OK] Migrasi Saldo Awal selesai. Jumlah saldo awal ditambahkan: $affected barang.\n";
echo "=== Migrasi Selesai dengan Sukses ===\n";
