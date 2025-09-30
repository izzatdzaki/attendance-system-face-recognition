<?php
session_start();
require_once '../db_connect.php';

// Cek session admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Makassar');

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    try {
        $pdo = connectDB();
        
        // Filter parameters
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        $search_name = $_GET['search_name'] ?? '';
        $status_lembur_filter = $_GET['status_lembur'] ?? '';
        $department_filter = $_GET['department'] ?? '';
        
        // Query data absensi dengan filter termasuk data lembur, lokasi, dan shift
        $query = "SELECT a.id, u.name, u.jabatan, u.NIP, a.tanggal_absen, a.jam_datang, a.jam_pulang, a.status_absen,
                         a.jam_lembur_mulai, a.jam_lembur_selesai, a.status_lembur,
                         a.location_name, a.location_verified, a.user_latitude, a.user_longitude,
                         s.shift_name, s.department, s.start_time, s.end_time, s.tolerance_minutes,
                         u.shift_id
                  FROM tbl_attendance a
                  JOIN tbl_user u ON a.user_id = u.id
                  LEFT JOIN tbl_shifts s ON u.shift_id = s.id
                  WHERE a.tanggal_absen BETWEEN :start_date AND :end_date";
        
        $params = [
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ];
        
        if (!empty($search_name)) {
            $query .= " AND u.name LIKE :search_name";
            $params[':search_name'] = "%$search_name%";
        }
        
        if (!empty($status_lembur_filter)) {
            $query .= " AND a.status_lembur = :status_lembur";
            $params[':status_lembur'] = $status_lembur_filter;
        }
        
        if (!empty($department_filter)) {
            $query .= " AND s.department = :department";
            $params[':department'] = $department_filter;
        }
        
        $query .= " ORDER BY a.tanggal_absen DESC, a.jam_datang DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $attendance_data = $stmt->fetchAll();
        
        // Set headers for Excel download
        $filename = "Laporan_Absensi_" . date('Y-m-d_H-i-s') . ".xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Start Excel content
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
        echo '<body>';
        
        // Header
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><td colspan="15" style="text-align: center; font-size: 18px; font-weight: bold;">LAPORAN ABSENSI</td></tr>';
        echo '<tr><td colspan="15" style="text-align: center;">Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)) . '</td></tr>';
        echo '<tr><td colspan="15" style="text-align: center;">Dicetak pada: ' . date('d/m/Y H:i:s') . ' WITA</td></tr>';
        echo '<tr><td colspan="15"></td></tr>'; // Empty row
        
        // Table headers
        echo '<tr style="background-color: #f0f0f0; font-weight: bold;">';
        echo '<td>No.</td>';
        echo '<td>NIP</td>';
        echo '<td>Nama User</td>';
        echo '<td>Jabatan</td>';
        echo '<td>Departemen</td>';
        echo '<td>Shift</td>';
        echo '<td>Jam Shift</td>';
        echo '<td>Tanggal</td>';
        echo '<td>Jam Datang</td>';
        echo '<td>Jam Pulang</td>';
        echo '<td>Status Keterlambatan</td>';
        echo '<td>Lembur Mulai</td>';
        echo '<td>Lembur Selesai</td>';
        echo '<td>Status Lembur</td>';
        echo '<td>Lokasi</td>';
        echo '<td>Status Absen</td>';
        echo '</tr>';
        
        // Data rows
        foreach ($attendance_data as $index => $row) {
            // Calculate tardiness status
            $statusTerlambat = '-';
            if ($row['jam_datang'] && $row['start_time']) {
                $jamDatang = strtotime($row['jam_datang']);
                $jamMulaiShift = strtotime($row['start_time']);
                $toleransi = ($row['tolerance_minutes'] ?? 15) * 60; // in seconds
                
                if ($jamDatang <= $jamMulaiShift) {
                    $statusTerlambat = 'Tepat Waktu';
                } elseif ($jamDatang <= ($jamMulaiShift + $toleransi)) {
                    $menitTerlambat = ceil(($jamDatang - $jamMulaiShift) / 60);
                    $statusTerlambat = "Terlambat {$menitTerlambat}m (Toleransi)";
                } else {
                    $menitTerlambat = ceil(($jamDatang - $jamMulaiShift) / 60);
                    $statusTerlambat = "Terlambat {$menitTerlambat}m";
                }
            }
            
            // Status lembur
            $status_lembur = $row['status_lembur'] ?? 'tidak_lembur';
            switch($status_lembur) {
                case 'lembur_mulai':
                    $status_lembur = 'Mulai Lembur';
                    break;
                case 'lembur_selesai':
                    $status_lembur = 'Selesai Lembur';
                    break;
                case 'tidak_lembur':
                default:
                    $status_lembur = 'Tidak Lembur';
            }
            
            // Status absen
            $status_absen = $row['status_absen'];
            switch($status_absen) {
                case 'lengkap':
                    $status_absen = 'Lengkap';
                    break;
                case 'datang':
                    $status_absen = 'Datang';
                    break;
                case 'pulang':
                    $status_absen = 'Pulang';
                    break;
            }
            
            // Location
            $lokasi = '-';
            if ($row['location_name']) {
                $verified = $row['location_verified'] ? 'Verified' : 'Not Verified';
                $lokasi = $row['location_name'] . ' (' . $verified . ')';
            }
            
            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td>' . htmlspecialchars($row['NIP']) . '</td>';
            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['jabatan']) . '</td>';
            echo '<td>' . htmlspecialchars($row['department'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['shift_name'] ?? 'Belum Ada Shift') . '</td>';
            echo '<td>' . ($row['start_time'] ? $row['start_time'] . ' - ' . $row['end_time'] : '-') . '</td>';
            echo '<td>' . ($row['tanggal_absen'] ? date('d/m/Y', strtotime($row['tanggal_absen'])) : '-') . '</td>';
            echo '<td>' . ($row['jam_datang'] ? date('H:i:s', strtotime($row['jam_datang'])) : '-') . '</td>';
            echo '<td>' . ($row['jam_pulang'] ? date('H:i:s', strtotime($row['jam_pulang'])) : '-') . '</td>';
            echo '<td>' . $statusTerlambat . '</td>';
            echo '<td>' . ($row['jam_lembur_mulai'] ? date('H:i:s', strtotime($row['jam_lembur_mulai'])) : '-') . '</td>';
            echo '<td>' . ($row['jam_lembur_selesai'] ? date('H:i:s', strtotime($row['jam_lembur_selesai'])) : '-') . '</td>';
            echo '<td>' . $status_lembur . '</td>';
            echo '<td>' . $lokasi . '</td>';
            echo '<td>' . $status_absen . '</td>';
            echo '</tr>';
        }
        
        // Summary at the bottom
        $total_records = count($attendance_data);
        $total_hadir_lengkap = count(array_filter($attendance_data, function($row) {
            return $row['status_absen'] === 'lengkap';
        }));
        $total_lembur = count(array_filter($attendance_data, function($row) {
            return $row['status_lembur'] === 'lembur_selesai';
        }));
        $total_terlambat = 0;
        
        foreach ($attendance_data as $row) {
            if ($row['jam_datang'] && $row['start_time']) {
                $jamDatang = strtotime($row['jam_datang']);
                $jamMulaiShift = strtotime($row['start_time']);
                $toleransi = ($row['tolerance_minutes'] ?? 15) * 60;
                if ($jamDatang > ($jamMulaiShift + $toleransi)) {
                    $total_terlambat++;
                }
            }
        }
        
        echo '<tr><td colspan="16"></td></tr>'; // Empty row
        echo '<tr style="background-color: #e0e0e0; font-weight: bold;">';
        echo '<td colspan="4">RINGKASAN LAPORAN</td>';
        echo '<td>Total Records: ' . $total_records . '</td>';
        echo '<td>Hadir Lengkap: ' . $total_hadir_lengkap . '</td>';
        echo '<td>Total Lembur: ' . $total_lembur . '</td>';
        echo '<td>Terlambat: ' . $total_terlambat . '</td>';
        echo '<td colspan="8"></td>';
        echo '</tr>';
        
        echo '</table>';
        echo '</body></html>';
        
    } catch (PDOException $e) {
        die("Error mengambil data: " . $e->getMessage());
    }
} else {
    header('Location: laporan.php');
    exit();
}
?>