<?php
// Test registrasi user dengan shift

$testData = [
    'action' => 'register',
    'name' => 'Test User Shift',
    'NIP' => '12345678',
    'jabatan' => 'Staff Test',
    'shift_id' => 4, // Kantor Full
    'face_descriptor' => json_encode(array_fill(0, 128, 0.1)) // Dummy descriptor
];

$url = 'http://localhost/absensi/api.php';

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($testData)
    ]
]);

echo "Testing registrasi user dengan shift...\n";
echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

$response = file_get_contents($url, false, $context);
$result = json_decode($response, true);

if ($result) {
    echo "Response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if ($result['success']) {
        echo "✓ User berhasil didaftarkan dengan shift!\n";
        
        // Verify di database
        require_once 'db_connect.php';
        $pdo = connectDB();
        
        $stmt = $pdo->prepare("SELECT u.name, u.jabatan, s.shift_name, s.department 
                               FROM tbl_user u 
                               LEFT JOIN tbl_shifts s ON u.shift_id = s.id 
                               WHERE u.id = ?");
        $stmt->execute([$result['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "Verifikasi: {$user['name']} ({$user['jabatan']}) - Shift: {$user['department']} {$user['shift_name']}\n";
        }
    } else {
        echo "✗ Registrasi gagal: " . $result['message'] . "\n";
    }
} else {
    echo "Error: Tidak dapat mengakses API\n";
}
?>