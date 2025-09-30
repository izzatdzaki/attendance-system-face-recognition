<?php
// Test shift detection untuk departemen Kantor jam 07:30
date_default_timezone_set('Asia/Makassar');

echo "=== TEST SHIFT DETECTION - DEPARTEMEN KANTOR JAM 07:30 ===\n\n";

$testTime = '07:30:00';
$testDepartment = 'Kantor';

echo "Test Parameters:\n";
echo "- Departemen: $testDepartment\n";
echo "- Waktu Test: $testTime (07:30 pagi)\n";
echo "- Timezone: " . date_default_timezone_get() . "\n\n";

// Get all shifts for Kantor department
echo "1. Semua Shift Departemen Kantor:\n";
try {
    $url = 'http://localhost/absensi/api_shifts.php?action=get_shifts&department=' . urlencode($testDepartment);
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "   ✓ Shifts loaded successfully\n\n";
        foreach ($result['data'] as $shift) {
            echo "   📋 {$shift['shift_name']}: {$shift['start_time']} - {$shift['end_time']}\n";
            echo "      Toleransi: {$shift['tolerance_minutes']} menit\n";
            echo "      ID: {$shift['id']}\n\n";
        }
        
        // Analyze which shift is active at 07:30
        echo "2. Analisis Shift Aktif pada jam $testTime:\n\n";
        
        foreach ($result['data'] as $shift) {
            $startTime = $shift['start_time'];
            $endTime = $shift['end_time'];
            $tolerance = $shift['tolerance_minutes'];
            $isOvernight = $shift['is_overnight'];
            
            // Convert times to comparable format
            $testMinutes = timeToMinutes($testTime);
            $startMinutes = timeToMinutes($startTime);
            $endMinutes = timeToMinutes($endTime);
            
            $isActive = false;
            $status = '';
            
            if ($isOvernight) {
                // Overnight shift
                if ($testMinutes >= $startMinutes || $testMinutes <= $endMinutes) {
                    $isActive = true;
                }
            } else {
                // Regular shift with tolerance
                $shiftStart = $startMinutes - $tolerance;
                $shiftEnd = $endMinutes + 60; // 1 hour after end
                
                if ($testMinutes >= $shiftStart && $testMinutes <= $shiftEnd) {
                    $isActive = true;
                    
                    // Determine if on time or late
                    if ($testMinutes < $startMinutes) {
                        $earlyMinutes = $startMinutes - $testMinutes;
                        $status = "✅ EARLY ($earlyMinutes menit sebelum jam kerja)";
                    } elseif ($testMinutes <= $startMinutes + $tolerance) {
                        $lateMinutes = $testMinutes - $startMinutes;
                        $status = $lateMinutes == 0 ? "✅ TEPAT WAKTU" : "⚠️ TERLAMBAT ($lateMinutes menit, masih dalam toleransi)";
                    } else {
                        $lateMinutes = $testMinutes - $startMinutes;
                        $status = "❌ TERLAMBAT ($lateMinutes menit, melebihi toleransi)";
                    }
                }
            }
            
            $activeIcon = $isActive ? "🟢" : "⚪";
            $activeText = $isActive ? "AKTIF" : "TIDAK AKTIF";
            
            echo "   $activeIcon $activeText - {$shift['shift_name']}\n";
            echo "      Jam: $startTime - $endTime\n";
            echo "      Toleransi: {$tolerance} menit\n";
            if ($isActive && $status) {
                echo "      Status: $status\n";
            }
            echo "\n";
        }
        
    } else {
        echo "   ✗ Failed to load shifts\n";
    }
} catch (Exception $e) {
    echo "   ✗ API Error: " . $e->getMessage() . "\n";
}

echo "3. Rekomendasi Shift untuk jam 07:30:\n";
echo "   Berdasarkan analisis di atas, berikut adalah shift yang paling cocok:\n\n";

// Helper function
function timeToMinutes($time) {
    list($hours, $minutes) = explode(':', $time);
    return ($hours * 60) + $minutes;
}

// Manual check for best match
try {
    $url = 'http://localhost/absensi/api_shifts.php?action=get_shifts&department=' . urlencode($testDepartment);
    $response = file_get_contents($url);
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        $bestShifts = [];
        $testMinutes = timeToMinutes($testTime);
        
        foreach ($result['data'] as $shift) {
            $startMinutes = timeToMinutes($shift['start_time']);
            $endMinutes = timeToMinutes($shift['end_time']);
            $tolerance = $shift['tolerance_minutes'];
            
            if (!$shift['is_overnight']) {
                $shiftStart = $startMinutes - $tolerance;
                $shiftEnd = $endMinutes + 60;
                
                if ($testMinutes >= $shiftStart && $testMinutes <= $shiftEnd) {
                    $lateMinutes = max(0, $testMinutes - $startMinutes);
                    $bestShifts[] = [
                        'shift' => $shift,
                        'late_minutes' => $lateMinutes,
                        'within_tolerance' => $lateMinutes <= $tolerance
                    ];
                }
            }
        }
        
        // Sort by late minutes (ascending)
        usort($bestShifts, function($a, $b) {
            return $a['late_minutes'] - $b['late_minutes'];
        });
        
        if (!empty($bestShifts)) {
            $best = $bestShifts[0];
            echo "   🎯 SHIFT TERBAIK: {$best['shift']['shift_name']}\n";
            echo "      Jam: {$best['shift']['start_time']} - {$best['shift']['end_time']}\n";
            echo "      Status: ";
            if ($best['late_minutes'] == 0) {
                echo "TEPAT WAKTU ✅\n";
            } elseif ($best['within_tolerance']) {
                echo "TERLAMBAT {$best['late_minutes']} menit (dalam toleransi) ⚠️\n";
            } else {
                echo "TERLAMBAT {$best['late_minutes']} menit (melebihi toleransi) ❌\n";
            }
            echo "      ID Shift: {$best['shift']['id']}\n";
        } else {
            echo "   ❌ Tidak ada shift yang cocok untuk jam 07:30\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== KESIMPULAN ===\n";
echo "Untuk karyawan departemen KANTOR yang absen pada jam 07:30,\n";
echo "sistem akan menentukan shift yang paling sesuai berdasarkan:\n";
echo "1. Apakah masih dalam rentang waktu shift (termasuk toleransi)\n";
echo "2. Tingkat keterlambatan paling minimal\n";
echo "3. Apakah masih dalam batas toleransi yang ditentukan\n";
?>