<?php
require_once 'db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$pdo = connectDB();

// Get action from URL or POST data
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_shifts':
            getShifts($pdo);
            break;
        case 'get_shift':
            getShift($pdo);
            break;
        case 'create_shift':
            createShift($pdo);
            break;
        case 'update_shift':
            updateShift($pdo);
            break;
        case 'delete_shift':
            deleteShift($pdo);
            break;
        case 'get_departments':
            getDepartments($pdo);
            break;
        case 'validate_attendance':
            validateAttendance($pdo);
            break;
        case 'record_attendance':
            recordAttendance($pdo);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getShifts($pdo) {
    $sql = "SELECT s.*, 
                   COUNT(u.id) as user_count,
                   CASE 
                       WHEN s.is_overnight = 1 THEN 
                           TIMESTAMPDIFF(HOUR, s.start_time, ADDTIME(s.end_time, '24:00:00'))
                       ELSE 
                           TIMESTAMPDIFF(HOUR, s.start_time, s.end_time)
                   END as duration_hours
            FROM tbl_shifts s
            LEFT JOIN tbl_user u ON s.id = u.shift_id
            GROUP BY s.id
            ORDER BY s.department, s.start_time";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll()
    ]);
}

function getShift($pdo) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        throw new Exception('ID shift diperlukan');
    }
    
    $sql = "SELECT * FROM tbl_shifts WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    $shift = $stmt->fetch();
    if (!$shift) {
        throw new Exception('Shift tidak ditemukan');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $shift
    ]);
}

function createShift($pdo) {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $required = ['shift_name', 'department', 'start_time', 'end_time'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field $field diperlukan");
        }
    }
    
    // Validasi format waktu
    if (!validateTimeFormat($data['start_time']) || !validateTimeFormat($data['end_time'])) {
        throw new Exception('Format waktu tidak valid (HH:MM:SS)');
    }
    
    // Cek apakah shift melewati tengah malam
    $isOvernight = isOvernightShift($data['start_time'], $data['end_time']);
    
    $sql = "INSERT INTO tbl_shifts (shift_name, department, start_time, end_time, is_overnight, tolerance_minutes, description, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['shift_name'],
        $data['department'],
        $data['start_time'],
        $data['end_time'],
        $isOvernight ? 1 : 0,
        $data['tolerance_minutes'] ?? 15,
        $data['description'] ?? null,
        $data['is_active'] ?? 1
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Shift berhasil ditambahkan',
        'id' => $pdo->lastInsertId()
    ]);
}

function updateShift($pdo) {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = $data['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID shift diperlukan');
    }
    
    // Validasi format waktu jika ada
    if (isset($data['start_time']) && !validateTimeFormat($data['start_time'])) {
        throw new Exception('Format start_time tidak valid');
    }
    if (isset($data['end_time']) && !validateTimeFormat($data['end_time'])) {
        throw new Exception('Format end_time tidak valid');
    }
    
    // Update is_overnight jika waktu berubah
    if (isset($data['start_time']) && isset($data['end_time'])) {
        $data['is_overnight'] = isOvernightShift($data['start_time'], $data['end_time']) ? 1 : 0;
    }
    
    $updateFields = [];
    $params = [];
    
    $allowedFields = ['shift_name', 'department', 'start_time', 'end_time', 'is_overnight', 'tolerance_minutes', 'description', 'is_active'];
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('Tidak ada field yang akan diupdate');
    }
    
    $params[] = $id; // untuk WHERE clause
    
    $sql = "UPDATE tbl_shifts SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Shift berhasil diupdate'
    ]);
}

function deleteShift($pdo) {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID shift diperlukan');
    }
    
    // Cek apakah shift masih digunakan
    $checkSql = "SELECT COUNT(*) FROM tbl_user WHERE shift_id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$id]);
    
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception('Shift tidak dapat dihapus karena masih digunakan oleh user');
    }
    
    $sql = "DELETE FROM tbl_shifts WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Shift berhasil dihapus'
    ]);
}

function getDepartments($pdo) {
    $sql = "SELECT DISTINCT department FROM tbl_shifts WHERE is_active = 1 ORDER BY department";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)
    ]);
}

function validateAttendance($pdo) {
    $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    $currentTime = date('Y-m-d H:i:s');
    
    if (!$userId) {
        throw new Exception('User ID diperlukan');
    }
    
    // Get user dengan shift info
    $sql = "SELECT u.*, s.* FROM tbl_user u 
            LEFT JOIN tbl_shifts s ON u.shift_id = s.id 
            WHERE u.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception('User tidak ditemukan');
    }
    
    if (!$user['shift_id']) {
        throw new Exception('User belum memiliki shift yang ditentukan');
    }
    
    // Validasi waktu absensi
    $validation = validateWorkTime($user, $currentTime);
    
    echo json_encode([
        'success' => true,
        'data' => $validation
    ]);
}

function recordAttendance($pdo) {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $userId = $data['user_id'] ?? null;
    $faceDescriptor = $data['face_descriptor'] ?? null;
    
    if (!$userId || !$faceDescriptor) {
        throw new Exception('User ID dan face descriptor diperlukan');
    }
    
    $currentTime = date('Y-m-d H:i:s');
    $currentDate = date('Y-m-d');
    
    // Get user dengan shift info
    $sql = "SELECT u.*, s.* FROM tbl_user u 
            LEFT JOIN tbl_shifts s ON u.shift_id = s.id 
            WHERE u.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    $user = $stmt->fetch();
    if (!$user || !$user['shift_id']) {
        throw new Exception('User atau shift tidak valid');
    }
    
    // Validasi waktu kerja
    $validation = validateWorkTime($user, $currentTime);
    
    // Cek attendance log hari ini
    $logSql = "SELECT * FROM tbl_attendance_log WHERE user_id = ? AND attendance_date = ?";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$userId, $currentDate]);
    $existingLog = $logStmt->fetch();
    
    if ($existingLog) {
        // Update check out jika belum ada
        if (!$existingLog['check_out']) {
            $workHours = calculateWorkHours($existingLog['check_in'], $currentTime);
            $updateSql = "UPDATE tbl_attendance_log SET check_out = ?, work_hours = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$currentTime, $workHours, $existingLog['id']]);
            
            $action = 'check_out';
        } else {
            throw new Exception('Anda sudah melakukan check in dan check out hari ini');
        }
    } else {
        // Insert check in baru
        $insertSql = "INSERT INTO tbl_attendance_log (user_id, shift_id, attendance_date, check_in, status, late_minutes) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            $userId,
            $user['shift_id'],
            $currentDate,
            $currentTime,
            $validation['status'],
            $validation['late_minutes']
        ]);
        
        $action = 'check_in';
    }
    
    // Insert ke tbl_attendance untuk kompatibilitas
    $attendanceSql = "INSERT INTO tbl_attendance (user_id, attendance_time) VALUES (?, ?)";
    $attendanceStmt = $pdo->prepare($attendanceSql);
    $attendanceStmt->execute([$userId, $currentTime]);
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'check_in' ? 'Check in berhasil' : 'Check out berhasil',
        'data' => [
            'action' => $action,
            'time' => $currentTime,
            'status' => $validation['status'],
            'late_minutes' => $validation['late_minutes'] ?? 0
        ]
    ]);
}

// Helper functions
function validateTimeFormat($time) {
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time);
}

function isOvernightShift($startTime, $endTime) {
    $start = strtotime($startTime);
    $end = strtotime($endTime);
    return $end <= $start;
}

function validateWorkTime($user, $currentTime) {
    $currentTimeOnly = date('H:i:s', strtotime($currentTime));
    $startTime = $user['start_time'];
    $endTime = $user['end_time'];
    $tolerance = $user['tolerance_minutes'] ?? 15;
    
    // Convert times to minutes for easier calculation
    $currentMinutes = timeToMinutes($currentTimeOnly);
    $startMinutes = timeToMinutes($startTime);
    $endMinutes = timeToMinutes($endTime);
    
    if ($user['is_overnight']) {
        // Handle overnight shift
        if ($currentMinutes >= $startMinutes || $currentMinutes <= $endMinutes) {
            $lateMinutes = 0;
            if ($currentMinutes > $startMinutes) {
                $lateMinutes = $currentMinutes - $startMinutes;
            }
            
            $status = $lateMinutes <= $tolerance ? 'on_time' : 'late';
            
            return [
                'valid' => true,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'message' => $status === 'on_time' ? 'Tepat waktu' : "Terlambat $lateMinutes menit"
            ];
        }
    } else {
        // Handle regular shift
        if ($currentMinutes >= $startMinutes && $currentMinutes <= $endMinutes) {
            $lateMinutes = max(0, $currentMinutes - $startMinutes);
            $status = $lateMinutes <= $tolerance ? 'on_time' : 'late';
            
            return [
                'valid' => true,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'message' => $status === 'on_time' ? 'Tepat waktu' : "Terlambat $lateMinutes menit"
            ];
        }
    }
    
    return [
        'valid' => false,
        'status' => 'out_of_schedule',
        'late_minutes' => 0,
        'message' => 'Diluar jam kerja yang ditentukan'
    ];
}

function timeToMinutes($time) {
    list($hours, $minutes) = explode(':', $time);
    return ($hours * 60) + $minutes;
}

function calculateWorkHours($checkIn, $checkOut) {
    $start = new DateTime($checkIn);
    $end = new DateTime($checkOut);
    $diff = $start->diff($end);
    return round(($diff->h + ($diff->i / 60)), 2);
}
?>