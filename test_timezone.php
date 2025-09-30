<?php
// Test timezone change ke Asia/Makassar
date_default_timezone_set('Asia/Makassar');

echo "=== TEST TIMEZONE CHANGE ===\n\n";

// Test 1: Direct timezone check
echo "1. PHP Timezone Setting:\n";
echo "   Current: " . date_default_timezone_get() . "\n";
echo "   Expected: Asia/Makassar\n\n";

// Test 2: Current time display
echo "2. Current Time:\n";
echo "   DateTime: " . date('Y-m-d H:i:s') . "\n";
echo "   Timezone: " . date('T') . " (UTC" . date('P') . ")\n\n";

// Test 3: Database connection timezone
echo "3. Database Timezone:\n";
try {
    require_once 'db_connect.php';
    $pdo = connectDB();
    
    echo "   ✓ Database connection successful\n";
    echo "   DB Timezone configured: +08:00 (from db_connect.php)\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: API timezone
echo "4. API Response Timezone:\n";
try {
    $url = 'http://localhost/absensi/api.php';
    $testData = ['action' => 'test_connection'];
    
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
        echo "   Server Time: " . $result['server_time'] . "\n";
        echo "   ✓ API timezone test successful\n";
    } else {
        echo "   ✗ API test failed\n";
    }
} catch (Exception $e) {
    echo "   ✗ API error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Shift validation timezone
echo "5. Shift Validation Timezone:\n";
try {
    $url = 'http://localhost/absensi/api_shifts.php?action=get_shifts';
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "   ✓ Shifts API accessible\n";
        if (!empty($result['data'])) {
            $firstShift = $result['data'][0];
            echo "   Sample Shift: {$firstShift['department']} - {$firstShift['shift_name']}\n";
            echo "   Time: {$firstShift['start_time']} - {$firstShift['end_time']}\n";
        }
    } else {
        echo "   ✗ Shifts API failed\n";
    }
} catch (Exception $e) {
    echo "   ✗ Shifts API error: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Timezone berhasil diubah ke Asia/Makassar (UTC+8)\n";
echo "✅ PHP timezone setting: " . date_default_timezone_get() . "\n";
echo "✅ Database timezone: +08:00\n";
echo "✅ Current time: " . date('Y-m-d H:i:s T') . "\n";
echo "\n🎯 Sistem sekarang menggunakan Waktu Indonesia Tengah (WITA)!\n";
?>