<?php
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = connectDB();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO tbl_location_settings (location_name, latitude, longitude, radius_meters, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['location_name'],
                    $_POST['latitude'],
                    $_POST['longitude'],
                    $_POST['radius_meters'],
                    isset($_POST['is_active']) ? 1 : 0
                ]);
                $success_message = "Lokasi berhasil ditambahkan!";
                break;
                
            case 'edit':
                $stmt = $pdo->prepare("UPDATE tbl_location_settings SET location_name = ?, latitude = ?, longitude = ?, radius_meters = ?, is_active = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['location_name'],
                    $_POST['latitude'],
                    $_POST['longitude'],
                    $_POST['radius_meters'],
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['location_id']
                ]);
                $success_message = "Lokasi berhasil diupdate!";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM tbl_location_settings WHERE id = ?");
                $stmt->execute([$_POST['location_id']]);
                $success_message = "Lokasi berhasil dihapus!";
                break;
                
            case 'toggle_status':
                $stmt = $pdo->prepare("UPDATE tbl_location_settings SET is_active = ? WHERE id = ?");
                $stmt->execute([$_POST['is_active'], $_POST['location_id']]);
                $success_message = "Status lokasi berhasil diubah!";
                break;
        }
    }
}

// Get all locations
$pdo = connectDB();
$locations = $pdo->query("SELECT * FROM tbl_location_settings ORDER BY location_name")->fetchAll();
?>

<div class="space-y-6">
    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Add New Location Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Tambah Lokasi Baru</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="location_name" class="block text-sm font-medium text-gray-700 mb-2">Nama Lokasi</label>
                    <input type="text" id="location_name" name="location_name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="Contoh: Kantor Pusat">
                </div>
                
                <div>
                    <label for="radius_meters" class="block text-sm font-medium text-gray-700 mb-2">Radius (meter)</label>
                    <input type="number" id="radius_meters" name="radius_meters" required min="1" max="1000" value="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                    <input type="number" id="latitude" name="latitude" required step="any" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="-6.200000">
                </div>
                
                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                    <input type="number" id="longitude" name="longitude" required step="any"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="106.816666">
                </div>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="is_active" name="is_active" checked
                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">Aktif</label>
            </div>
            
            <div class="flex space-x-4">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Tambah Lokasi
                </button>
                <button type="button" onclick="getCurrentLocation()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Gunakan Lokasi Saat Ini
                </button>
            </div>
        </form>
    </div>

    <!-- Locations List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Daftar Lokasi</h2>
        
        <?php if (empty($locations)): ?>
            <p class="text-gray-500 text-center py-8">Belum ada lokasi yang ditambahkan.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lokasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Koordinat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Radius</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($locations as $location): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($location['location_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($location['latitude'], 6) ?>, <?= number_format($location['longitude'], 6) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $location['radius_meters'] ?>m
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $location['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $location['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button onclick="editLocation(<?= htmlspecialchars(json_encode($location)) ?>)" 
                                            class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= $location['is_active'] ? 0 : 1 ?>">
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                            <?= $location['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus lokasi ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Lokasi</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="location_id" id="edit_location_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lokasi</label>
                        <input type="text" name="location_name" id="edit_location_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                        <input type="number" name="latitude" id="edit_latitude" required step="any"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                        <input type="number" name="longitude" id="edit_longitude" required step="any"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Radius (meter)</label>
                        <input type="number" name="radius_meters" id="edit_radius_meters" required min="1" max="1000"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label class="ml-2 block text-sm text-gray-900">Aktif</label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function getCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
            alert('Lokasi berhasil didapatkan!');
        }, function(error) {
            alert('Gagal mendapatkan lokasi: ' + error.message);
        });
    } else {
        alert('Geolocation tidak didukung oleh browser ini.');
    }
}

function editLocation(location) {
    document.getElementById('edit_location_id').value = location.id;
    document.getElementById('edit_location_name').value = location.location_name;
    document.getElementById('edit_latitude').value = location.latitude;
    document.getElementById('edit_longitude').value = location.longitude;
    document.getElementById('edit_radius_meters').value = location.radius_meters;
    document.getElementById('edit_is_active').checked = location.is_active == 1;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>