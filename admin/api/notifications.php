<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

try {
    $pdo = connectDB();
    $notifications = [];
    
    // Get today's late arrivals
    $late_query = "
        SELECT u.name, a.jam_datang, a.tanggal_absen
        FROM tbl_attendance a 
        JOIN tbl_user u ON a.user_id = u.id 
        WHERE DATE(a.tanggal_absen) = CURDATE() 
        AND a.status_absen = 'Terlambat'
        AND a.jam_datang >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ORDER BY a.jam_datang DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->query($late_query);
    $late_arrivals = $stmt->fetchAll();
    
    foreach ($late_arrivals as $late) {
        $notifications[] = [
            'type' => 'warning',
            'title' => 'Keterlambatan',
            'message' => $late['name'] . ' terlambat masuk pada ' . date('H:i', strtotime($late['jam_datang'])),
            'time' => date('H:i', strtotime($late['jam_datang'])),
            'icon' => '⚠️'
        ];
    }
    
    // Get recent check-ins (last 15 minutes)
    $recent_query = "
        SELECT u.name, a.jam_datang, a.status_absen
        FROM tbl_attendance a 
        JOIN tbl_user u ON a.user_id = u.id 
        WHERE DATE(a.tanggal_absen) = CURDATE() 
        AND a.jam_datang >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        AND a.status_absen = 'Hadir'
        ORDER BY a.jam_datang DESC
        LIMIT 3
    ";
    
    $stmt = $pdo->query($recent_query);
    $recent_checkins = $stmt->fetchAll();
    
    foreach ($recent_checkins as $checkin) {
        $notifications[] = [
            'type' => 'success',
            'title' => 'Check-in Baru',
            'message' => $checkin['name'] . ' telah melakukan check-in',
            'time' => date('H:i', strtotime($checkin['jam_datang'])),
            'icon' => '✅'
        ];
    }
    
    // Get users who haven't checked in yet (after 09:00)
    if (date('H:i') > '09:00') {
        $missing_query = "
            SELECT u.name 
            FROM tbl_user u 
            LEFT JOIN tbl_attendance a ON u.id = a.user_id AND DATE(a.tanggal_absen) = CURDATE()
            WHERE a.id IS NULL 
            AND u.role = 'user'
            LIMIT 5
        ";
        
        $stmt = $pdo->query($missing_query);
        $missing_checkins = $stmt->fetchAll();
        
        if (count($missing_checkins) > 0) {
            $names = array_column($missing_checkins, 'name');
            $notifications[] = [
                'type' => 'info',
                'title' => 'Belum Check-in',
                'message' => count($missing_checkins) . ' karyawan belum melakukan check-in: ' . implode(', ', array_slice($names, 0, 3)) . (count($names) > 3 ? '...' : ''),
                'time' => date('H:i'),
                'icon' => 'ℹ️'
            ];
        }
    }
    
    // Get system stats for alerts
    $stats_query = "
        SELECT 
            COUNT(*) as total_today,
            SUM(CASE WHEN status_absen = 'Terlambat' THEN 1 ELSE 0 END) as late_today
        FROM tbl_attendance 
        WHERE DATE(tanggal_absen) = CURDATE()
    ";
    
    $stmt = $pdo->query($stats_query);
    $stats = $stmt->fetch();
    
    // Alert if high late percentage
    if ($stats['total_today'] > 0) {
        $late_percentage = ($stats['late_today'] / $stats['total_today']) * 100;
        if ($late_percentage > 20) {
            $notifications[] = [
                'type' => 'error',
                'title' => 'Alert Keterlambatan Tinggi',
                'message' => 'Tingkat keterlambatan hari ini: ' . round($late_percentage, 1) . '%',
                'time' => date('H:i'),
                'icon' => '🚨'
            ];
        }
    }
    
    // Sort notifications by time (newest first)
    usort($notifications, function($a, $b) {
        return strcmp($b['time'], $a['time']);
    });
    
    echo json_encode([
        'success' => true,
        'notifications' => array_slice($notifications, 0, 10), // Limit to 10 notifications
        'count' => count($notifications),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>