<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$filter_date_start = $_POST['filter_date_start'] ?? date('Y-m-01');
$filter_date_end = $_POST['filter_date_end'] ?? date('Y-m-d');
$filter_department = $_POST['filter_department'] ?? '';
$filter_status = $_POST['filter_status'] ?? '';

try {
    $pdo = connectDB();
    
    // Build WHERE conditions for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($filter_date_start) && !empty($filter_date_end)) {
        $where_conditions[] = "DATE(a.tanggal_absen) BETWEEN ? AND ?";
        $params[] = $filter_date_start;
        $params[] = $filter_date_end;
    }
    
    if (!empty($filter_department)) {
        $where_conditions[] = "u.jabatan = ?";
        $params[] = $filter_department;
    }
    
    if (!empty($filter_status)) {
        $where_conditions[] = "a.status_absen = ?";
        $params[] = $filter_status;
    }
    
    // Get filtered attendance data
    $export_query = "
        SELECT 
            u.NIP,
            u.name,
            u.jabatan,
            a.tanggal_absen,
            a.jam_datang,
            a.jam_pulang,
            a.status_absen,
            a.jam_lembur_mulai,
            a.jam_lembur_selesai,
            a.status_lembur,
            a.location_name,
            a.location_verified,
            CASE 
                WHEN a.jam_datang IS NOT NULL AND a.jam_pulang IS NOT NULL 
                THEN TIMEDIFF(a.jam_pulang, a.jam_datang)
                ELSE NULL 
            END as durasi_kerja,
            CASE 
                WHEN a.jam_lembur_mulai IS NOT NULL AND a.jam_lembur_selesai IS NOT NULL 
                THEN TIMEDIFF(a.jam_lembur_selesai, a.jam_lembur_mulai)
                ELSE NULL 
            END as durasi_lembur
        FROM tbl_attendance a 
        JOIN tbl_user u ON a.user_id = u.id 
    ";
    
    if (!empty($where_conditions)) {
        $export_query .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $export_query .= " ORDER BY a.tanggal_absen DESC, u.name ASC";
    
    $stmt = $pdo->prepare($export_query);
    $stmt->execute($params);
    $export_data = $stmt->fetchAll();
    
    // Set headers for Excel download
    $filename = "Dashboard_Absensi_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers
    fputcsv($output, [
        'NIP',
        'Nama',
        'Jabatan',
        'Tanggal',
        'Jam Datang',
        'Jam Pulang',
        'Status Absen',
        'Durasi Kerja',
        'Lembur Mulai',
        'Lembur Selesai',
        'Status Lembur',
        'Durasi Lembur',
        'Lokasi',
        'Lokasi Terverifikasi'
    ]);
    
    // Add data rows
    foreach ($export_data as $row) {
        fputcsv($output, [
            $row['NIP'],
            $row['name'],
            $row['jabatan'],
            $row['tanggal_absen'] ? date('d/m/Y', strtotime($row['tanggal_absen'])) : '',
            $row['jam_datang'] ? date('H:i:s', strtotime($row['jam_datang'])) : '',
            $row['jam_pulang'] ? date('H:i:s', strtotime($row['jam_pulang'])) : '',
            $row['status_absen'],
            $row['durasi_kerja'] ?? '',
            $row['jam_lembur_mulai'] ? date('H:i:s', strtotime($row['jam_lembur_mulai'])) : '',
            $row['jam_lembur_selesai'] ? date('H:i:s', strtotime($row['jam_lembur_selesai'])) : '',
            $row['status_lembur'] ?? '',
            $row['durasi_lembur'] ?? '',
            $row['location_name'] ?? '',
            $row['location_verified'] ? 'Ya' : 'Tidak'
        ]);
    }
    
    // Add summary at the end
    fputcsv($output, []);
    fputcsv($output, ['=== RINGKASAN ===']);
    fputcsv($output, ['Total Data:', count($export_data)]);
    fputcsv($output, ['Periode:', $filter_date_start . ' s/d ' . $filter_date_end]);
    if (!empty($filter_department)) {
        fputcsv($output, ['Departemen:', $filter_department]);
    }
    if (!empty($filter_status)) {
        fputcsv($output, ['Status:', $filter_status]);
    }
    fputcsv($output, ['Diekspor pada:', date('d/m/Y H:i:s')]);
    
    fclose($output);
    exit();
    
} catch (PDOException $e) {
    die("Error mengekspor data: " . $e->getMessage());
}
?>