<?php
// Test validasi absensi dengan shift

$testData = [
    'action' => 'validate_attendance',
    'user_id' => 8  // User yang sudah ada shift
];

$url = 'http://localhost/absensi/api_shifts.php?action=validate_attendance&user_id=8';

echo "Testing validasi absensi untuk user dengan shift...\n";
echo "URL: $url\n\n";

$response = file_get_contents($url);
$result = json_decode($response, true);

if ($result) {
    echo "Response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    echo "Error: Tidak dapat mengakses API\n";
}
?>