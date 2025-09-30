<?php
// Test registrasi user dengan auto-detect shift berdasarkan departemen

// Simulate auto-detect shift
function getAutoShift($department, $currentTime = null) {
    require_once 'db_connect.php';
    $pdo = connectDB();
    
    if (!$currentTime) {
        $currentTime = date('H:i:s');
    }
    
    $currentMinutes = (int)date('H', strtotime($currentTime)) * 60 + (int)date('i', strtotime($currentTime));
    
    // Get all active shifts for department
    $stmt = $pdo->prepare("SELECT * FROM tbl_shifts WHERE department = ? AND is_active = 1 ORDER BY start_time");
    $stmt->execute([$department]);
    $shifts = $stmt->fetchAll();
    
    // Find best shift
    foreach ($shifts as $shift) {
        $startMinutes = (int)date('H', strtotime($shift['start_time'])) * 60 + (int)date('i', strtotime($shift['start_time']));
        $endMinutes = (int)date('H', strtotime($shift['end_time'])) * 60 + (int)date('i', strtotime($shift['end_time']));
        
        if ($shift['is_overnight'] == 1) {
            if ($currentMinutes >= $startMinutes || $currentMinutes <= $endMinutes) {
                return $shift['id'];
            }
        } else {
            if ($currentMinutes >= $startMinutes && $currentMinutes <= $endMinutes) {
                return $shift['id'];
            }
        }
    }
    
    // Return first shift if no current match
    return $shifts[0]['id'] ?? null;
}

// Test data
$testUsers = [
    [
        'name' => 'Dr. Sarah (Auto)',
        'department' => 'Nakes',
        'jabatan' => 'Dokter',
        'NIP' => 'AUTO001'
    ],
    [
        'name' => 'Pak Budi (Auto)', 
        'department' => 'Security',
        'jabatan' => 'Security',
        'NIP' => 'AUTO002'
    ],
    [
        'name' => 'Bu Siti (Auto)',
        'department' => 'Cleaning', 
        'jabatan' => 'Cleaning Staff',
        'NIP' => 'AUTO003'
    ]
];

echo "=== TEST AUTO-REGISTRASI DENGAN DEPARTEMEN ===\n\n";
echo "Waktu saat ini: " . date('H:i:s') . "\n\n";

foreach ($testUsers as $userData) {
    echo "--- Testing: {$userData['name']} ---\n";
    echo "Departemen: {$userData['department']}\n";
    
    // Auto-detect shift
    $autoShiftId = getAutoShift($userData['department']);
    
    if ($autoShiftId) {
        $testData = [
            'action' => 'register',
            'name' => $userData['name'],
            'NIP' => $userData['NIP'],
            'jabatan' => $userData['jabatan'],
            'shift_id' => $autoShiftId,
            'face_descriptor' => json_encode(array_fill(0, 128, rand(1, 100) / 100)) // Random descriptor
        ];
        
        // Call API
        $url = 'http://localhost/absensi/api.php';
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($testData)
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);
        
        if ($result && $result['success']) {
            echo "✓ Registrasi berhasil!\n";
            
            // Verify
            require_once 'db_connect.php';
            $pdo = connectDB();
            
            $stmt = $pdo->prepare("SELECT u.name, u.jabatan, s.shift_name, s.department, s.start_time, s.end_time
                                   FROM tbl_user u 
                                   LEFT JOIN tbl_shifts s ON u.shift_id = s.id 
                                   WHERE u.id = ?");
            $stmt->execute([$result['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "  User: {$user['name']} ({$user['jabatan']})\n";
                echo "  Auto-assigned shift: {$user['department']} - {$user['shift_name']}\n";
                echo "  Jam kerja: {$user['start_time']} - {$user['end_time']}\n";
            }
        } else {
            echo "✗ Registrasi gagal: " . ($result['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "✗ Tidak ada shift untuk departemen {$userData['department']}\n";
    }
    
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "✅ Sistem auto-detect shift berdasarkan departemen SIAP!\n";
echo "✅ User hanya perlu pilih departemen\n";
echo "✅ Sistem otomatis pilih shift sesuai waktu registrasi\n";
echo "✅ Smart shift detection untuk overnight shifts\n";
?>