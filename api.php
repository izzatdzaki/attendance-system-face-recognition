<?php
header('Content-Type: application/json');
require_once 'db_connect.php'; // File koneksi database

// Set timezone Indonesia
date_default_timezone_set('Asia/Makassar');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    $pdo = connectDB();
    
    switch ($action) {
        case 'register':
            // Validasi input
            if (empty($input['name'])) {
                throw new Exception("Nama tidak boleh kosong");
            }
            if (empty($input['face_descriptor'])) {
                throw new Exception("Face descriptor tidak ditemukan");
            }
            
            // Set default values jika tidak ada
            $jabatan = $input['jabatan'] ?? 'Staff';
            $nip = $input['NIP'] ?? '';
            $shiftId = !empty($input['shift_id']) ? $input['shift_id'] : null;
            
            // Simpan ke tbl_user dengan shift_id
            $stmt = $pdo->prepare("INSERT INTO tbl_user (name, jabatan, NIP, shift_id, face_descriptor, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$input['name'], $jabatan, $nip, $shiftId, $input['face_descriptor']]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Data berhasil disimpan',
                    'user_id' => $pdo->lastInsertId()
                ]);
            } else {
                throw new Exception("Gagal menyimpan data ke database");
            }
            break;
            
        case 'clock_in':
            if (empty($input['face_descriptor'])) {
                throw new Exception("Descriptor wajah tidak ditemukan");
            }
            
            if (!isset($input['latitude']) || !isset($input['longitude'])) {
                throw new Exception("Lokasi tidak ditemukan. Pastikan GPS aktif dan izinkan akses lokasi.");
            }
            
            // Validasi lokasi user
            $locationValidation = validateUserLocation($pdo, $input['latitude'], $input['longitude']);
            if (!$locationValidation['valid']) {
                $nearestInfo = $locationValidation['nearest_location'] ? 
                    " Lokasi terdekat: " . $locationValidation['nearest_location']['name'] . 
                    " (jarak: " . $locationValidation['nearest_location']['distance'] . "m)" : "";
                
                echo json_encode([
                    'success' => false,
                    'message' => $locationValidation['message'] . $nearestInfo
                ]);
                return;
            }
            
            // Ambil semua data wajah dari database
            $users = $pdo->query("SELECT id, name, face_descriptor FROM tbl_user")->fetchAll();
            
            $bestMatch = null;
            $minDistance = 0.6; // Threshold kemiripan
            
            foreach ($users as $user) {
                $savedDescriptor = json_decode($user['face_descriptor'], true);
                $distance = euclideanDistance(json_decode($input['face_descriptor'], true), $savedDescriptor);
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $bestMatch = $user;
                }
            }
            
            if ($bestMatch) {
                $today = date('Y-m-d');
                $currentTime = date('H:i:s');
                
                // Validasi shift dan jam kerja
                $shiftValidation = validateUserShiftTime($pdo, $bestMatch['id'], $currentTime);
                if (!$shiftValidation['valid']) {
                    echo json_encode([
                        'success' => false,
                        'message' => $shiftValidation['message']
                    ]);
                    return;
                }
                
                // Cek status absensi hari ini
                $checkStmt = $pdo->prepare("SELECT id, status_absen, status_lembur FROM tbl_attendance WHERE user_id = ? AND tanggal_absen = ?");
                $checkStmt->execute([$bestMatch['id'], $today]);
                $existingAttendance = $checkStmt->fetch();
                
                if ($existingAttendance) {
                    if ($existingAttendance['status_absen'] == 'datang') {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Anda sudah melakukan absen datang hari ini! Silakan lakukan absen pulang terlebih dahulu.'
                        ]);
                    } else if ($existingAttendance['status_absen'] == 'lengkap') {
                        // Cek apakah bisa mulai lembur
                        if ($existingAttendance['status_lembur'] == 'lembur_mulai') {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Anda sudah memulai lembur hari ini! Silakan selesaikan lembur terlebih dahulu.'
                            ]);
                        } else if ($existingAttendance['status_lembur'] == 'lembur_selesai') {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Anda sudah menyelesaikan lembur hari ini!'
                            ]);
                        } else {
                            // Mulai lembur
                            $stmt = $pdo->prepare("UPDATE tbl_attendance SET jam_lembur_mulai = ?, status_lembur = 'lembur_mulai', user_latitude = ?, user_longitude = ?, location_name = ?, location_verified = ? WHERE id = ?");
                            $stmt->execute([$currentTime, $input['latitude'], $input['longitude'], $locationValidation['location_name'], 1, $existingAttendance['id']]);
                            
                            echo json_encode([
                                'success' => true,
                                'data' => [
                                    'recognized' => true,
                                    'user_name' => $bestMatch['name'],
                                    'action' => 'overtime_start',
                                    'time' => $currentTime,
                                    'location' => $locationValidation['location_name'],
                                    'distance' => $locationValidation['distance'] . 'm'
                                ],
                                'message' => 'Berhasil Mulai Lembur: ' . $bestMatch['name'] . ' di ' . $locationValidation['location_name'],
                            ]);
                        }
                    }
                } else {
                    // Simpan absensi datang reguler
                    $stmt = $pdo->prepare("INSERT INTO tbl_attendance (user_id, attendance_time, tanggal_absen, jam_datang, status_absen, user_latitude, user_longitude, location_name, location_verified) VALUES (?, NOW(), ?, ?, 'datang', ?, ?, ?, ?)");
                    $stmt->execute([$bestMatch['id'], $today, $currentTime, $input['latitude'], $input['longitude'], $locationValidation['location_name'], 1]);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'recognized' => true,
                            'user_name' => $bestMatch['name'],
                            'action' => 'clock_in',
                            'time' => $currentTime,
                            'location' => $locationValidation['location_name'],
                            'distance' => $locationValidation['distance'] . 'm'
                        ],
                        'message' => 'Berhasil Absen Datang: ' . $bestMatch['name'] . ' di ' . $locationValidation['location_name'],
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => ['recognized' => false],
                    'message' => 'Wajah tidak dikenali!'
                ]);
            }
            break;
            
        case 'clock_out':
            if (empty($input['face_descriptor'])) {
                throw new Exception("Descriptor wajah tidak ditemukan");
            }
            
            if (!isset($input['latitude']) || !isset($input['longitude'])) {
                throw new Exception("Lokasi tidak ditemukan. Pastikan GPS aktif dan izinkan akses lokasi.");
            }
            
            // Validasi lokasi user
            $locationValidation = validateUserLocation($pdo, $input['latitude'], $input['longitude']);
            if (!$locationValidation['valid']) {
                $nearestInfo = $locationValidation['nearest_location'] ? 
                    " Lokasi terdekat: " . $locationValidation['nearest_location']['name'] . 
                    " (jarak: " . $locationValidation['nearest_location']['distance'] . "m)" : "";
                
                echo json_encode([
                    'success' => false,
                    'message' => $locationValidation['message'] . $nearestInfo
                ]);
                return;
            }
            
            // Ambil semua data wajah dari database
            $users = $pdo->query("SELECT id, name, face_descriptor FROM tbl_user")->fetchAll();
            
            $bestMatch = null;
            $minDistance = 0.6; // Threshold kemiripan
            
            foreach ($users as $user) {
                $savedDescriptor = json_decode($user['face_descriptor'], true);
                $distance = euclideanDistance(json_decode($input['face_descriptor'], true), $savedDescriptor);
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $bestMatch = $user;
                }
            }
            
            if ($bestMatch) {
                $today = date('Y-m-d');
                $currentTime = date('H:i:s');
                
                // Validasi shift dan jam kerja
                $shiftValidation = validateUserShiftTime($pdo, $bestMatch['id'], $currentTime);
                if (!$shiftValidation['valid']) {
                    echo json_encode([
                        'success' => false,
                        'message' => $shiftValidation['message']
                    ]);
                    return;
                }
                
                // Cek status absensi dan lembur hari ini
                $checkStmt = $pdo->prepare("SELECT id, status_absen, status_lembur FROM tbl_attendance WHERE user_id = ? AND tanggal_absen = ?");
                $checkStmt->execute([$bestMatch['id'], $today]);
                $attendance = $checkStmt->fetch();
                
                if (!$attendance) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Anda belum melakukan absen datang hari ini! Silakan lakukan absen datang terlebih dahulu.'
                    ]);
                } else if ($attendance['status_absen'] == 'datang') {
                    // Pulang reguler
                    $stmt = $pdo->prepare("UPDATE tbl_attendance SET jam_pulang = ?, status_absen = 'lengkap', user_latitude = ?, user_longitude = ?, location_name = ?, location_verified = ? WHERE id = ? AND status_absen = 'datang'");
                    $stmt->execute([$currentTime, $input['latitude'], $input['longitude'], $locationValidation['location_name'], 1, $attendance['id']]);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'recognized' => true,
                            'user_name' => $bestMatch['name'],
                            'action' => 'clock_out',
                            'time' => $currentTime,
                            'location' => $locationValidation['location_name'],
                            'distance' => $locationValidation['distance'] . 'm'
                        ],
                        'message' => 'Berhasil Absen Pulang: ' . $bestMatch['name'] . ' di ' . $locationValidation['location_name'],
                    ]);
                } else if ($attendance['status_absen'] == 'lengkap') {
                    // Cek apakah sedang lembur
                    if ($attendance['status_lembur'] == 'lembur_mulai') {
                        // Selesai lembur
                        $stmt = $pdo->prepare("UPDATE tbl_attendance SET jam_lembur_selesai = ?, status_lembur = 'lembur_selesai', user_latitude = ?, user_longitude = ?, location_name = ?, location_verified = ? WHERE id = ? AND status_lembur = 'lembur_mulai'");
                        $stmt->execute([$currentTime, $input['latitude'], $input['longitude'], $locationValidation['location_name'], 1, $attendance['id']]);
                        
                        echo json_encode([
                            'success' => true,
                            'data' => [
                                'recognized' => true,
                                'user_name' => $bestMatch['name'],
                                'action' => 'overtime_end',
                                'time' => $currentTime,
                                'location' => $locationValidation['location_name'],
                                'distance' => $locationValidation['distance'] . 'm'
                            ],
                            'message' => 'Berhasil Selesai Lembur: ' . $bestMatch['name'] . ' di ' . $locationValidation['location_name'],
                        ]);
                    } else if ($attendance['status_lembur'] == 'lembur_selesai') {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Anda sudah menyelesaikan lembur hari ini!'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Anda sudah menyelesaikan absensi reguler hari ini! Gunakan tombol Jam Datang untuk memulai lembur.'
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Status absensi tidak valid. Silakan hubungi administrator.'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => ['recognized' => false],
                    'message' => 'Wajah tidak dikenali!'
                ]);
            }
            break;
            
        case 'get_attendance':
            $query = "SELECT u.name, u.NIP, u.jabatan, 
                             a.tanggal_absen, a.jam_datang, a.jam_pulang, 
                             a.status_absen, a.jam_lembur_mulai, a.jam_lembur_selesai, 
                             a.status_lembur, a.attendance_time 
                      FROM tbl_attendance a
                      JOIN tbl_user u ON a.user_id = u.id
                      ORDER BY a.attendance_time DESC
                      LIMIT 10";
            $data = $pdo->query($query)->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);
            break;
            
        case 'test_connection':
            // Test koneksi untuk debugging mobile
            echo json_encode([
                'success' => true,
                'message' => 'Koneksi berhasil!',
                'server_time' => date('Y-m-d H:i:s'),
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            ]);
            break;

            
        default:
            throw new Exception("Aksi tidak valid");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function euclideanDistance($arr1, $arr2) {
    $sum = 0;
    for ($i = 0; $i < count($arr1); $i++) {
        $sum += pow($arr1[$i] - $arr2[$i], 2);
    }
    return sqrt($sum);
}

// Fungsi untuk menghitung jarak antara dua koordinat (Haversine formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Radius bumi dalam meter
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c; // Jarak dalam meter
}

// Fungsi untuk validasi lokasi user
function validateUserLocation($pdo, $userLat, $userLon) {
    // Ambil semua lokasi yang aktif
    $stmt = $pdo->prepare("SELECT * FROM tbl_location_settings WHERE is_active = 1");
    $stmt->execute();
    $locations = $stmt->fetchAll();
    
    foreach ($locations as $location) {
        $distance = calculateDistance($userLat, $userLon, $location['latitude'], $location['longitude']);
        
        if ($distance <= $location['radius_meters']) {
            return [
                'valid' => true,
                'location_name' => $location['location_name'],
                'distance' => round($distance, 2)
            ];
        }
    }
    
    return [
        'valid' => false,
        'message' => 'Anda berada di luar area yang diizinkan untuk absensi',
        'nearest_location' => findNearestLocation($pdo, $userLat, $userLon)
    ];
}

// Fungsi untuk mencari lokasi terdekat
function findNearestLocation($pdo, $userLat, $userLon) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_location_settings WHERE is_active = 1");
    $stmt->execute();
    $locations = $stmt->fetchAll();
    
    $nearest = null;
    $minDistance = PHP_FLOAT_MAX;
    
    foreach ($locations as $location) {
        $distance = calculateDistance($userLat, $userLon, $location['latitude'], $location['longitude']);
        
        if ($distance < $minDistance) {
            $minDistance = $distance;
            $nearest = [
                'name' => $location['location_name'],
                'distance' => round($distance, 2)
            ];
        }
    }
    
    return $nearest;
}

// Fungsi untuk validasi shift dan jam kerja user
function validateUserShiftTime($pdo, $userId, $currentTime) {
    // Get user dengan shift info
    $stmt = $pdo->prepare("SELECT u.*, s.* FROM tbl_user u 
                          LEFT JOIN tbl_shifts s ON u.shift_id = s.id 
                          WHERE u.id = ? AND s.is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return [
            'valid' => false,
            'message' => 'User tidak ditemukan atau belum memiliki shift yang ditentukan'
        ];
    }
    
    if (!$user['shift_id']) {
        return [
            'valid' => false,
            'message' => 'Anda belum memiliki shift yang ditentukan. Silakan hubungi administrator.'
        ];
    }
    
    // Convert times to minutes for easier calculation
    $currentMinutes = timeToMinutes($currentTime);
    $startMinutes = timeToMinutes($user['start_time']);
    $endMinutes = timeToMinutes($user['end_time']);
    $tolerance = $user['tolerance_minutes'] ?? 15;
    
    if ($user['is_overnight']) {
        // Handle overnight shift (misal: 21:00 - 08:00)
        if ($currentMinutes >= $startMinutes || $currentMinutes <= $endMinutes) {
            $lateMinutes = 0;
            if ($currentMinutes > $startMinutes) {
                $lateMinutes = $currentMinutes - $startMinutes;
            }
            
            $status = $lateMinutes <= $tolerance ? 'on_time' : 'late';
            $message = $status === 'on_time' ? 
                'Tepat waktu untuk shift ' . $user['department'] . ' - ' . $user['shift_name'] :
                "Terlambat $lateMinutes menit untuk shift " . $user['department'] . ' - ' . $user['shift_name'];
            
            return [
                'valid' => true,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'message' => $message,
                'shift_info' => [
                    'name' => $user['shift_name'],
                    'department' => $user['department'],
                    'start_time' => $user['start_time'],
                    'end_time' => $user['end_time']
                ]
            ];
        }
    } else {
        // Handle regular shift
        $shiftStart = $startMinutes - $tolerance; // Boleh masuk sebelum jam kerja dengan toleransi
        $shiftEnd = $endMinutes + 60; // Boleh sampai 1 jam setelah jam kerja berakhir
        
        if ($currentMinutes >= $shiftStart && $currentMinutes <= $shiftEnd) {
            $lateMinutes = max(0, $currentMinutes - $startMinutes);
            $status = $lateMinutes <= $tolerance ? 'on_time' : 'late';
            $message = $status === 'on_time' ? 
                'Tepat waktu untuk shift ' . $user['department'] . ' - ' . $user['shift_name'] :
                "Terlambat $lateMinutes menit untuk shift " . $user['department'] . ' - ' . $user['shift_name'];
            
            return [
                'valid' => true,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'message' => $message,
                'shift_info' => [
                    'name' => $user['shift_name'],
                    'department' => $user['department'],
                    'start_time' => $user['start_time'],
                    'end_time' => $user['end_time']
                ]
            ];
        }
    }
    
    return [
        'valid' => false,
        'message' => 'Diluar jam kerja yang ditentukan. Shift Anda: ' . 
                    $user['department'] . ' - ' . $user['shift_name'] . 
                    ' (' . $user['start_time'] . ' - ' . $user['end_time'] . ')'
    ];
}

// Helper function untuk konversi waktu ke menit
function timeToMinutes($time) {
    list($hours, $minutes) = explode(':', $time);
    return ($hours * 60) + $minutes;
}
