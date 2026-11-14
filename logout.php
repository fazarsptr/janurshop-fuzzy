<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    

    db_query("INSERT INTO activity_logs (user_id, action) VALUES (" . $_SESSION['user_id'] . ", 'Logout berhasil')");
}

session_destroy();
header("Location: login.php");
exit;
?>
