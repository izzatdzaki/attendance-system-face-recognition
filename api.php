<?php
header('Content-Type: application/json');
require_once 'db_connect.php'; // File koneksi database

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    $pdo = connectDB();
    
    switch ($action) {
        case 'register':
            // Validasi input
            if (empty($input['name']) || empty($input['face_descriptor'])) {
                throw new Exception("Data tidak lengkap");
            }
            
            // Simpan ke tbl_user
            $stmt = $pdo->prepare("INSERT INTO tbl_user (name, jabatan, NIDN, face_descriptor, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$input['name'],  $input['jabatan'], $input['NIDN'], $input['face_descriptor']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Data berhasil disimpan'
            ]);
            break;
            
        case 'recognize':
            if (empty($input['face_descriptor'])) {
                throw new Exception("Descriptor wajah tidak ditemukan");
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
                // Simpan absensi
                $stmt = $pdo->prepare("INSERT INTO tbl_attendance (user_id, attendance_time) VALUES (?, NOW())");
                $stmt->execute([$bestMatch['id']]);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'recognized' => true,
                        'user_name' => $bestMatch['name']
                    ],
                    'message' => 'Berhasil Absensi',
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => ['recognized' => false],
                ]);
            }
            break;
            
        case 'get_attendance':
    $query = "SELECT u.name, u.NIDN, u.jabatan, a.attendance_time 
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
