<?php
require_once 'config/database.php';
include 'views/header.php';

$query = "
    SELECT l.*, u.name as user_name 
    FROM activity_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.timestamp DESC 
    LIMIT 100
";
$logs = db_query($query);
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Log Aktivitas Sistem</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th width="180">Waktu</th>
                        <th width="200">Pengguna</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($logs && $logs->num_rows > 0): ?>
                        <?php while($row = $logs->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted"><?php echo $row['timestamp']; ?></td>
                            <td><strong><?php echo ($row['user_name'] == 'Administrator') ? 'Admin/Pemilik' : htmlspecialchars($row['user_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">Belum ada log.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>
