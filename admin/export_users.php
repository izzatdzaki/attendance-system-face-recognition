<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

try {
    $conn = connectDB();
    
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $filter_jabatan = $_GET['filter_jabatan'] ?? '';
    
    // Build WHERE conditions for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(name LIKE ? OR NIP LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($filter_jabatan)) {
        $where_conditions[] = "jabatan = ?";
        $params[] = $filter_jabatan;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get user data with attendance statistics
    $export_query = "
        SELECT 
            u.id,
            u.NIP,
            u.name,
            u.jabatan,
            u.created_at,
            COUNT(a.id) as total_attendance,
            SUM(CASE WHEN a.status_absen = 'lengkap' THEN 1 ELSE 0 END) as lengkap_count,
            SUM(CASE WHEN a.status_absen = 'datang' THEN 1 ELSE 0 END) as datang_count,
            SUM(CASE WHEN a.status_absen = 'pulang' THEN 1 ELSE 0 END) as pulang_count,
            SUM(CASE WHEN a.status_lembur = 'lembur_mulai' OR a.status_lembur = 'lembur_selesai' THEN 1 ELSE 0 END) as lembur_count,
            MAX(a.tanggal_absen) as last_attendance
        FROM tbl_user u 
        LEFT JOIN tbl_attendance a ON u.id = a.user_id 
        $where_clause
        GROUP BY u.id, u.NIP, u.name, u.jabatan, u.created_at
        ORDER BY u.name ASC
    ";
    
    $stmt = $conn->prepare($export_query);
    $stmt->execute($params);
    $users_data = $stmt->fetchAll();
    
    // Get summary statistics
    $summary_query = "
        SELECT 
            COUNT(*) as total_users,
            COUNT(DISTINCT jabatan) as total_departments
        FROM tbl_user 
        $where_clause
    ";
    
    $stmt = $conn->prepare($summary_query);
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    // Set headers for CSV download
    $filename = "Data_User_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add title and export info
    fputcsv($output, ['LAPORAN DATA USER SISTEM ABSENSI']);
    fputcsv($output, ['Diekspor pada: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    
    // Add summary
    fputcsv($output, ['=== RINGKASAN ===']);
    fputcsv($output, ['Total User', $summary['total_users']]);
    fputcsv($output, ['Total Departemen', $summary['total_departments']]);
    
    if (!empty($search)) {
        fputcsv($output, ['Filter Pencarian', $search]);
    }
    if (!empty($filter_jabatan)) {
        fputcsv($output, ['Filter Jabatan', $filter_jabatan]);
    }
    
    fputcsv($output, []);
    fputcsv($output, []);
    
    // Add CSV headers for user data
    fputcsv($output, [
        'No',
        'ID User',
        'NIP',
        'Nama Lengkap',
        'Jabatan',
        'Tanggal Dibuat',
        'Total Absensi',
        'Absensi Lengkap',
        'Hanya Datang',
        'Hanya Pulang',
        'Total Lembur',
        'Absensi Terakhir'
    ]);
    
    // Add user data rows
    foreach ($users_data as $index => $user) {
        fputcsv($output, [
            $index + 1,
            $user['id'],
            $user['NIP'] ?? '-',
            $user['name'],
            $user['jabatan'] ?? '-',
            $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : '-',
            $user['total_attendance'],
            $user['lengkap_count'],
            $user['datang_count'],
            $user['pulang_count'],
            $user['lembur_count'],
            $user['last_attendance'] ? date('d/m/Y', strtotime($user['last_attendance'])) : 'Belum pernah absen'
        ]);
    }
    
    // Add department statistics
    fputcsv($output, []);
    fputcsv($output, ['=== STATISTIK DEPARTEMEN ===']);
    
    $dept_query = "
        SELECT 
            u.jabatan,
            COUNT(DISTINCT u.id) as total_users,
            COUNT(a.id) as total_attendance,
            SUM(CASE WHEN a.status_absen = 'lengkap' THEN 1 ELSE 0 END) as lengkap_count,
            SUM(CASE WHEN a.status_lembur = 'lembur_mulai' OR a.status_lembur = 'lembur_selesai' THEN 1 ELSE 0 END) as lembur_count
        FROM tbl_user u 
        LEFT JOIN tbl_attendance a ON u.id = a.user_id
        $where_clause
        GROUP BY u.jabatan
        ORDER BY total_users DESC
    ";
    
    $dept_stmt = $conn->prepare($dept_query);
    $dept_stmt->execute($params);
    $dept_data = $dept_stmt->fetchAll();
    
    fputcsv($output, ['Departemen', 'Total User', 'Total Absensi', 'Absensi Lengkap', 'Total Lembur']);
    foreach ($dept_data as $dept) {
        fputcsv($output, [
            $dept['jabatan'] ?? 'Tidak ada jabatan',
            $dept['total_users'],
            $dept['total_attendance'],
            $dept['lengkap_count'],
            $dept['lembur_count']
        ]);
    }
    
    // Add attendance statistics
    fputcsv($output, []);
    fputcsv($output, ['=== STATISTIK ABSENSI ===']);
    
    $attendance_query = "
        SELECT 
            COUNT(a.id) as total_attendance,
            SUM(CASE WHEN a.status_absen = 'lengkap' THEN 1 ELSE 0 END) as lengkap_count,
            SUM(CASE WHEN a.status_absen = 'datang' THEN 1 ELSE 0 END) as datang_count,
            SUM(CASE WHEN a.status_absen = 'pulang' THEN 1 ELSE 0 END) as pulang_count,
            SUM(CASE WHEN a.status_lembur = 'lembur_mulai' THEN 1 ELSE 0 END) as lembur_mulai_count,
            SUM(CASE WHEN a.status_lembur = 'lembur_selesai' THEN 1 ELSE 0 END) as lembur_selesai_count
        FROM tbl_user u 
        LEFT JOIN tbl_attendance a ON u.id = a.user_id 
        $where_clause
    ";
    
    $attendance_stmt = $conn->prepare($attendance_query);
    $attendance_stmt->execute($params);
    $attendance_data = $attendance_stmt->fetch();
    
    fputcsv($output, ['Jenis', 'Jumlah']);
    fputcsv($output, ['Total Absensi', $attendance_data['total_attendance']]);
    fputcsv($output, ['Absensi Lengkap', $attendance_data['lengkap_count']]);
    fputcsv($output, ['Hanya Datang', $attendance_data['datang_count']]);
    fputcsv($output, ['Hanya Pulang', $attendance_data['pulang_count']]);
    fputcsv($output, ['Lembur Mulai', $attendance_data['lembur_mulai_count']]);
    fputcsv($output, ['Lembur Selesai', $attendance_data['lembur_selesai_count']]);
    
    fputcsv($output, []);
    fputcsv($output, ['=== CATATAN ===']);
    fputcsv($output, ['- Data ini mencakup semua user yang sesuai dengan filter yang dipilih']);
    fputcsv($output, ['- Statistik absensi dihitung berdasarkan seluruh riwayat absensi']);
    fputcsv($output, ['- Tingkat kehadiran = (Jumlah Hadir / Total Absensi) x 100%']);
    
    fclose($output);
    exit();
    
} catch (PDOException $e) {
    die("Error mengekspor data: " . $e->getMessage());
}
?>