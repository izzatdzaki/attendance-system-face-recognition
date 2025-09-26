<?php
// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

function connectDB() {
    $host = 'localhost';    // Alamat server database
    $dbname = 'absensi_face';  // Nama database yang dibuat
    $username = 'root';     // Username database (sesuaikan)
    $password = '';         // Password database (sesuaikan)

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        
        // Atur mode error PDO ke exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        // Set timezone untuk MySQL
        $pdo->exec("SET time_zone = '+07:00'");
        
        // Tes koneksi
        $pdo->query("SELECT 1");
        
        return $pdo;
    } catch (PDOException $e) {
        // Catat error ke log file jika perlu
        error_log("Database connection failed: " . $e->getMessage(), 0);
        
        // Tampilkan pesan error yang ramah pengguna
        die(json_encode([
            'success' => false,
            'message' => 'Koneksi database gagal. Silakan hubungi administrator.',
            'error' => $e->getMessage() // Hanya untuk development, hapus di production
        ]));
    }
}
?>
