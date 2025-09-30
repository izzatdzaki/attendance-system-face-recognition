<?php
require_once 'db_connect.php';

try {
    $pdo = connectDB();
    echo "Koneksi database berhasil!" . PHP_EOL;
    
    // Baca file SQL
    $sql = file_get_contents('update_database_shifts.sql');
    
    // Split SQL berdasarkan delimiter
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            try {
                $pdo->exec($statement);
                echo "✓ Query berhasil dijalankan" . PHP_EOL;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                    strpos($e->getMessage(), 'already exists') !== false) {
                    echo "~ Query diabaikan (sudah ada): " . substr($statement, 0, 50) . "..." . PHP_EOL;
                } else {
                    echo "✗ Error pada query: " . $e->getMessage() . PHP_EOL;
                    echo "Query: " . substr($statement, 0, 100) . "..." . PHP_EOL;
                }
            }
        }
    }
    
    echo PHP_EOL . "Migration selesai!" . PHP_EOL;
    
    // Tampilkan status tabel
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_shifts");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "Total shifts: $count" . PHP_EOL;
        
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'tbl_shifts'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo "✓ Tabel tbl_shifts berhasil dibuat" . PHP_EOL;
        }
        
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'tbl_attendance_log'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo "✓ Tabel tbl_attendance_log berhasil dibuat" . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "Info: " . $e->getMessage() . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>