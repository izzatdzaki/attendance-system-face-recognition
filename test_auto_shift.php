<?php
// Test auto shift detection berdasarkan departemen dan waktu

require_once 'db_connect.php';

function timeToMinutes($timeString) {
    list($hours, $minutes) = explode(':', $timeString);
    return ($hours * 60) + $minutes;
}

function isInShiftTime($shift, $currentMinutes) {
    $startMinutes = timeToMinutes($shift['start_time']);
    $endMinutes = timeToMinutes($shift['end_time']);
    
    if ($shift['is_overnight'] == 1) {
        return $currentMinutes >= $startMinutes || $currentMinutes <= $endMinutes;
    } else {
        return $currentMinutes >= $startMinutes && $currentMinutes <= $endMinutes;
    }
}

function findBestShift($department, $currentTime = null) {
    global $pdo;
    
    if (!$currentTime) {
        $currentTime = date('H:i:s');
    }
    
    $currentMinutes = timeToMinutes($currentTime);
    
    // Get all active shifts for department
    $stmt = $pdo->prepare("SELECT * FROM tbl_shifts WHERE department = ? AND is_active = 1 ORDER BY start_time");
    $stmt->execute([$department]);
    $shifts = $stmt->fetchAll();
    
    if (empty($shifts)) {
        return null;
    }
    
    // Find current shift
    foreach ($shifts as $shift) {
        if (isInShiftTime($shift, $currentMinutes)) {
            return [
                'shift' => $shift,
                'status' => 'current',
                'message' => 'Sedang dalam jam kerja'
            ];
        }
    }
    
    // Find next upcoming shift
    $bestMatch = null;
    $minDistance = PHP_INT_MAX;
    
    foreach ($shifts as $shift) {
        $startMinutes = timeToMinutes($shift['start_time']);
        
        if ($currentMinutes < $startMinutes) {
            $distance = $startMinutes - $currentMinutes;
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestMatch = $shift;
            }
        }
    }
    
    // If no upcoming shift today, return first shift (for next day)
    if (!$bestMatch) {
        $bestMatch = $shifts[0];
    }
    
    return [
        'shift' => $bestMatch,
        'status' => 'upcoming',
        'message' => 'Shift yang akan dipilih'
    ];
}

try {
    $pdo = connectDB();
    
    echo "=== TEST AUTO SHIFT DETECTION ===\n\n";
    
    // Test different departments at current time
    $departments = ['Nakes', 'Kantor', 'Security', 'Pramusaji', 'Cleaning'];
    $currentTime = date('H:i:s');
    
    echo "Waktu saat ini: $currentTime\n\n";
    
    foreach ($departments as $dept) {
        echo "--- DEPARTEMEN: $dept ---\n";
        
        $result = findBestShift($dept);
        
        if ($result) {
            $shift = $result['shift'];
            $status = $result['status'];
            $message = $result['message'];
            
            echo "✓ Shift terpilih: {$shift['shift_name']}\n";
            echo "  Jam kerja: {$shift['start_time']} - {$shift['end_time']}\n";
            echo "  Status: $message\n";
            echo "  Overnight: " . ($shift['is_overnight'] ? 'Ya' : 'Tidak') . "\n";
            echo "  Toleransi: {$shift['tolerance_minutes']} menit\n";
        } else {
            echo "✗ Tidak ada shift tersedia\n";
        }
        
        echo "\n";
    }
    
    // Test at specific times
    echo "=== TEST WAKTU SPESIFIK ===\n\n";
    
    $testTimes = [
        '07:30:00' => 'Pagi (07:30)',
        '09:00:00' => 'Pagi (09:00)', 
        '15:00:00' => 'Sore (15:00)',
        '22:00:00' => 'Malam (22:00)',
        '02:00:00' => 'Dini hari (02:00)'
    ];
    
    foreach ($testTimes as $time => $label) {
        echo "--- WAKTU: $label ---\n";
        
        $result = findBestShift('Security', $time);
        if ($result) {
            $shift = $result['shift'];
            echo "Security: {$shift['shift_name']} ({$shift['start_time']}-{$shift['end_time']}) - {$result['message']}\n";
        }
        
        $result = findBestShift('Nakes', $time);
        if ($result) {
            $shift = $result['shift'];
            echo "Nakes: {$shift['shift_name']} ({$shift['start_time']}-{$shift['end_time']}) - {$result['message']}\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>