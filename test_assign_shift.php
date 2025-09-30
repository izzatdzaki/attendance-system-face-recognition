<?php
// Test API untuk assign shift ke user

require_once 'db_connect.php';

try {
    $pdo = connectDB();
    
    // Get first user
    $stmt = $pdo->prepare("SELECT id, name FROM tbl_user LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "Tidak ada user untuk testing\n";
        exit;
    }
    
    // Get first shift
    $stmt = $pdo->prepare("SELECT id, shift_name, department FROM tbl_shifts WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $shift = $stmt->fetch();
    
    if (!$shift) {
        echo "Tidak ada shift untuk testing\n";
        exit;
    }
    
    echo "Testing assign shift...\n";
    echo "User: {$user['name']} (ID: {$user['id']})\n";
    echo "Shift: {$shift['department']} - {$shift['shift_name']} (ID: {$shift['id']})\n\n";
    
    // Assign shift
    $updateStmt = $pdo->prepare("UPDATE tbl_user SET shift_id = ? WHERE id = ?");
    $result = $updateStmt->execute([$shift['id'], $user['id']]);
    
    if ($result) {
        echo "✓ Shift berhasil diassign!\n";
        
        // Verify
        $verifyStmt = $pdo->prepare("SELECT u.name, s.shift_name, s.department 
                                     FROM tbl_user u 
                                     LEFT JOIN tbl_shifts s ON u.shift_id = s.id 
                                     WHERE u.id = ?");
        $verifyStmt->execute([$user['id']]);
        $result = $verifyStmt->fetch();
        
        echo "Verifikasi: {$result['name']} sekarang memiliki shift {$result['department']} - {$result['shift_name']}\n";
    } else {
        echo "✗ Gagal assign shift\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>