<?php
/**
 * StockManager Helper Class
 * Janurshop Fuzzy Mamdani
 * 
 * Centralizes all stock mutations, ensuring ACID transactions,
 * database row locking, prepared statements, and complete audit logging.
 */

require_once __DIR__ . '/../config/database.php';

class StockManager {

    /**
     * Generate or retrieve the CSRF token stored in session
     */
    public static function generateCsrfToken(): string {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate the submitted CSRF token
     */
    public static function validateCsrfToken(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Enforce administrator authorization on the server side
     */
    public static function verifyAdminPermission(): bool {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        return (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
    }

    /**
     * Record a stock movement atomically
     * 
     * @param int $itemId Product ID
     * @param int $userId Performing User ID (must be admin)
     * @param string $type Movement type ('in', 'out', 'adjustment', 'opening_balance')
     * @param float $qty Quantity to add/subtract or baseline
     * @param float|null $newStockTarget Target stock level (for adjustments)
     * @param string|null $notes Transaction notes or justification
     * @param string|null $refNo Reference number (PO, Invoice, etc.)
     * @return array Result status ['success' => bool, 'stock_after' => float, 'error' => string|null]
     */
    public static function recordMovement(
        int $itemId,
        int $userId,
        string $type,
        float $qty,
        ?float $newStockTarget = null,
        ?string $notes = '',
        ?string $refNo = null
    ): array {
        global $conn;

        // 1. Authorization check
        if (!self::verifyAdminPermission()) {
            return [
                'success' => false,
                'error' => 'Akses ditolak: Hanya administrator yang berhak mencatat mutasi stok.'
            ];
        }

        // 2. Validate movement type
        $validTypes = ['in', 'out', 'adjustment', 'opening_balance'];
        if (!in_array($type, $validTypes, true)) {
            return [
                'success' => false,
                'error' => "Jenis mutasi tidak valid: $type."
            ];
        }

        // 3. Validate input parameters based on type
        if ($type === 'in' || $type === 'out') {
            if ($qty <= 0) {
                return [
                    'success' => false,
                    'error' => 'Jumlah barang (kuantitas) harus lebih besar dari 0.'
                ];
            }
        } elseif ($type === 'adjustment') {
            if ($newStockTarget === null || $newStockTarget < 0) {
                return [
                    'success' => false,
                    'error' => 'Stok target penyesuaian harus bernilai 0 atau lebih.'
                ];
            }
        } elseif ($type === 'opening_balance') {
            if ($qty < 0) {
                return [
                    'success' => false,
                    'error' => 'Saldo awal tidak boleh bernilai negatif.'
                ];
            }
        }

        // 4. Begin ACID database transaction
        $conn->begin_transaction();

        try {
            // Row-level locking to prevent concurrent mutation conflicts
            $lockStmt = $conn->prepare("SELECT id, code, name, stock FROM items WHERE id = ? FOR UPDATE");
            if (!$lockStmt) {
                throw new Exception("Gagal menyiapkan kueri row lock: " . $conn->error);
            }
            $lockStmt->bind_param("i", $itemId);
            $lockStmt->execute();
            $lockRes = $lockStmt->get_result();

            if (!$lockRes || $lockRes->num_rows === 0) {
                $lockStmt->close();
                throw new Exception("Barang dengan ID $itemId tidak ditemukan.");
            }

            $item = $lockRes->fetch_assoc();
            $lockStmt->close();

            $stockBefore = (float)$item['stock'];
            $stockAfter = $stockBefore;
            $movementQty = $qty;

            // 5. Calculate stock_after and movement quantity
            if ($type === 'in') {
                $stockAfter = $stockBefore + $qty;
                $movementQty = $qty;
            } elseif ($type === 'out') {
                if ($stockBefore < $qty) {
                    throw new Exception("Stok tidak mencukupi. Sisa stok saat ini: " . number_format($stockBefore) . " Pcs, permintaan keluar: " . number_format($qty) . " Pcs.");
                }
                $stockAfter = $stockBefore - $qty;
                $movementQty = $qty;
            } elseif ($type === 'adjustment') {
                $stockAfter = (float)$newStockTarget;
                $movementQty = abs($stockAfter - $stockBefore);
            } elseif ($type === 'opening_balance') {
                $stockBefore = 0;
                $stockAfter = (float)$qty;
                $movementQty = (float)$qty;
            }

            // 6. Update physical stock in items table
            $updateStmt = $conn->prepare("UPDATE items SET stock = ? WHERE id = ?");
            if (!$updateStmt) {
                throw new Exception("Gagal menyiapkan kueri update stok: " . $conn->error);
            }
            $updateStmt->bind_param("di", $stockAfter, $itemId);
            if (!$updateStmt->execute()) {
                $updateStmt->close();
                throw new Exception("Gagal memperbarui stok barang: " . $updateStmt->error);
            }
            $updateStmt->close();

            // 7. Insert movement audit record into stock_movements
            $insertSql = "INSERT INTO stock_movements (item_id, user_id, type, quantity, stock_before, stock_after, reference_no, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            if (!$insertStmt) {
                throw new Exception("Gagal menyiapkan kueri mutasi stok: " . $conn->error);
            }

            $cleanNotes = trim((string)$notes);
            $cleanRefNo = !empty($refNo) ? trim((string)$refNo) : null;

            $insertStmt->bind_param(
                "iisdddss",
                $itemId,
                $userId,
                $type,
                $movementQty,
                $stockBefore,
                $stockAfter,
                $cleanRefNo,
                $cleanNotes
            );

            if (!$insertStmt->execute()) {
                $insertStmt->close();
                throw new Exception("Gagal mencatat data mutasi: " . $insertStmt->error);
            }
            $movementId = $insertStmt->insert_id;
            $insertStmt->close();

            // 8. Log into activity_logs within the same transaction
            $typeLabels = [
                'in' => 'Barang Masuk',
                'out' => 'Barang Keluar',
                'adjustment' => 'Penyesuaian Stok',
                'opening_balance' => 'Saldo Awal'
            ];
            $actionDesc = "Mutasi " . ($typeLabels[$type] ?? $type) . ": " . $item['name'] . " (" . $item['code'] . ") " .
                          number_format($stockBefore) . " -> " . number_format($stockAfter) . " Pcs";

            $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
            if ($logStmt) {
                $logStmt->bind_param("is", $userId, $actionDesc);
                $logStmt->execute();
                $logStmt->close();
            }

            // 9. Commit transaction
            $conn->commit();

            return [
                'success' => true,
                'stock_after' => $stockAfter,
                'movement_id' => $movementId,
                'error' => null
            ];

        } catch (Exception $e) {
            $conn->rollback();
            return [
                'success' => false,
                'stock_after' => $stockBefore ?? 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a new item and record its opening balance in one atomic transaction
     */
    public static function createItemWithInitialStock(array $data, int $userId): array {
        global $conn;

        if (!self::verifyAdminPermission()) {
            return [
                'success' => false,
                'error' => 'Akses ditolak: Hanya administrator yang berhak menambahkan barang.'
            ];
        }

        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $stock = (float)($data['stock'] ?? 0);
        $demand = (float)($data['demand'] ?? 0);
        $supplier_id = (int)($data['supplier_id'] ?? 0);
        $price = (float)($data['price'] ?? 0);
        $expired_days = (float)($data['expired_days'] ?? 0);
        $min_stock_limit = (float)($data['min_stock_limit'] ?? 0);
        $stok_max = (float)($data['stok_max'] ?? 100);

        if ($stock < 0) {
            return [
                'success' => false,
                'error' => 'Stok awal tidak boleh bernilai negatif.'
            ];
        }

        $conn->begin_transaction();

        try {
            $insertItemSql = "
                INSERT INTO items (
                    code, name, stock, demand, supplier_id, price, expired_days, min_stock_limit, stok_max,
                    fuzzy_stok_bawah, fuzzy_stok_tengah, fuzzy_stok_atas,
                    fuzzy_demand_bawah, fuzzy_demand_tengah, fuzzy_demand_atas,
                    fuzzy_leadtime_bawah, fuzzy_leadtime_tengah, fuzzy_leadtime_atas,
                    fuzzy_harga_bawah, fuzzy_harga_atas,
                    fuzzy_expired_bawah, fuzzy_expired_atas
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ";

            $stmt = $conn->prepare($insertItemSql);
            if (!$stmt) {
                throw new Exception("Gagal menyiapkan kueri tambah barang: " . $conn->error);
            }

            $fs_b = (float)($data['fuzzy_stok_bawah'] ?? 20);
            $fs_t = (float)($data['fuzzy_stok_tengah'] ?? 50);
            $fs_a = (float)($data['fuzzy_stok_atas'] ?? 100);
            $fd_b = (float)($data['fuzzy_demand_bawah'] ?? 10);
            $fd_t = (float)($data['fuzzy_demand_tengah'] ?? 20);
            $fd_a = (float)($data['fuzzy_demand_atas'] ?? 30);
            $fl_b = (float)($data['fuzzy_leadtime_bawah'] ?? 2);
            $fl_t = (float)($data['fuzzy_leadtime_tengah'] ?? 4);
            $fl_a = (float)($data['fuzzy_leadtime_atas'] ?? 7);
            $fh_b = (float)($data['fuzzy_harga_bawah'] ?? 20000);
            $fh_a = (float)($data['fuzzy_harga_atas'] ?? 100000);
            $fe_b = (float)($data['fuzzy_expired_bawah'] ?? 30);
            $fe_a = (float)($data['fuzzy_expired_atas'] ?? 90);

            $stmt->bind_param(
                "ssddiddddddddddddddddd",
                $code, $name, $stock, $demand, $supplier_id, $price, $expired_days, $min_stock_limit, $stok_max,
                $fs_b, $fs_t, $fs_a, $fd_b, $fd_t, $fd_a, $fl_b, $fl_t, $fl_a, $fh_b, $fh_a, $fe_b, $fe_a
            );

            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception("Gagal menyimpan data barang baru: " . $err);
            }

            $newItemId = $conn->insert_id;
            $stmt->close();

            // Record opening balance if stock_movements table exists and stock >= 0
            $checkTable = $conn->query("SHOW TABLES LIKE 'stock_movements'");
            if ($checkTable && $checkTable->num_rows > 0) {
                $smStmt = $conn->prepare("
                    INSERT INTO stock_movements (
                        item_id, user_id, type, quantity, stock_before, stock_after, reference_no, notes, created_at
                    ) VALUES (?, ?, 'opening_balance', ?, 0, ?, 'INIT-NEW', 'Saldo awal saat penambahan barang baru', NOW())
                ");
                if ($smStmt) {
                    $smStmt->bind_param("iidd", $newItemId, $userId, $stock, $stock);
                    $smStmt->execute();
                    $smStmt->close();
                }
            }

            // Activity log
            $logAction = "Tambah barang: $name (Kode: $code)";
            $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
            if ($logStmt) {
                $logStmt->bind_param("is", $userId, $logAction);
                $logStmt->execute();
                $logStmt->close();
            }

            $conn->commit();
            return [
                'success' => true,
                'item_id' => $newItemId,
                'error' => null
            ];

        } catch (Exception $e) {
            $conn->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if a product has any recorded stock movements
     */
    public static function hasMovements(int $itemId): bool {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM stock_movements WHERE item_id = ?");
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("i", $itemId);
            $stmt->execute();
            $res = $stmt->get_result();
            $count = 0;
            if ($res && $row = $res->fetch_assoc()) {
                $count = (int)$row['total'];
            }
            $stmt->close();
            return $count > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Retrieve stock movements with filtering
     */
    public static function getMovements(array $filters = [], int $limit = 100, int $offset = 0): array {
        global $conn;

        try {
            $whereConditions = [];
            $params = [];
            $types = "";

            if (!empty($filters['item_id']) && (int)$filters['item_id'] > 0) {
                $whereConditions[] = "sm.item_id = ?";
                $params[] = (int)$filters['item_id'];
                $types .= "i";
            }

            if (!empty($filters['type']) && in_array($filters['type'], ['in', 'out', 'adjustment', 'opening_balance'], true)) {
                $whereConditions[] = "sm.type = ?";
                $params[] = $filters['type'];
                $types .= "s";
            }

            if (!empty($filters['date_start'])) {
                $whereConditions[] = "DATE(sm.created_at) >= ?";
                $params[] = $filters['date_start'];
                $types .= "s";
            }

            if (!empty($filters['date_end'])) {
                $whereConditions[] = "DATE(sm.created_at) <= ?";
                $params[] = $filters['date_end'];
                $types .= "s";
            }

            if (!empty($filters['search'])) {
                $whereConditions[] = "(i.code LIKE ? OR i.name LIKE ? OR sm.reference_no LIKE ? OR sm.notes LIKE ?)";
                $searchParam = "%" . trim($filters['search']) . "%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= "ssss";
            }

            $whereSql = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            $sql = "
                SELECT sm.*, i.code AS item_code, i.name AS item_name, u.name AS user_name, u.username AS user_username
                FROM stock_movements sm
                JOIN items i ON sm.item_id = i.id
                LEFT JOIN users u ON sm.user_id = u.id
                $whereSql
                ORDER BY sm.created_at DESC, sm.id DESC
                LIMIT ? OFFSET ?
            ";

            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return [];
            }

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $movements = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $movements[] = $row;
                }
            }
            $stmt->close();
            return $movements;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Retrieve recent movements for a single item (e.g. for item_detail.php)
     */
    public static function getItemMovements(int $itemId, int $limit = 10): array {
        return self::getMovements(['item_id' => $itemId], $limit, 0);
    }
}
