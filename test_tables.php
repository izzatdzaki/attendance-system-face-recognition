<?php
require_once 'db_connect.php';

try {
    $pdo = connectDB();
    
    // Test apakah tabel shifts sudah ada
    try {
        $result = $pdo->query("SELECT COUNT(*) FROM tbl_shifts");
        $count = $result->fetchColumn();
        echo "✓ Tabel tbl_shifts berhasil dibuat dengan $count shifts" . PHP_EOL;
    } catch (Exception $e) {
        echo "✗ Tabel tbl_shifts belum ada: " . $e->getMessage() . PHP_EOL;
    }
    
    // Test apakah kolom shift_id sudah ada di tbl_user
    try {
        $result = $pdo->query("DESCRIBE tbl_user");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('shift_id', $columns)) {
            echo "✓ Kolom shift_id sudah ditambahkan ke tbl_user" . PHP_EOL;
        } else {
            echo "✗ Kolom shift_id belum ada di tbl_user" . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "Error checking tbl_user: " . $e->getMessage() . PHP_EOL;
    }
    
    // Test apakah tabel attendance_log sudah ada
    try {
        $result = $pdo->query("SELECT COUNT(*) FROM tbl_attendance_log");
        $count = $result->fetchColumn();
        echo "✓ Tabel tbl_attendance_log berhasil dibuat dengan $count records" . PHP_EOL;
    } catch (Exception $e) {
        echo "✗ Tabel tbl_attendance_log belum ada: " . $e->getMessage() . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>