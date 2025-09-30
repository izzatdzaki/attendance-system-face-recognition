<?php
// Session sudah dimulai di admin.php, tidak perlu session_start() lagi
require_once '../db_connect.php';

$conn = connectDB();

// Handle delete action
$delete_success = false;
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM tbl_attendance WHERE user_id = ?");
        $stmt->execute([$_GET['id']]);

        $stmt = $conn->prepare("DELETE FROM tbl_user WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $delete_success = true;
    } catch (PDOException $e) {
        $delete_error = "Error deleting user: " . $e->getMessage();
    }
}

// Get all users with shift information
try {
    $query = "SELECT u.*, s.shift_name, s.department, s.start_time, s.end_time 
              FROM tbl_user u 
              LEFT JOIN tbl_shifts s ON u.shift_id = s.id 
              ORDER BY u.created_at DESC";
    $stmt = $conn->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching users: " . $e->getMessage());
}

// Get all active shifts for dropdown
try {
    $shiftQuery = "SELECT id, shift_name, department, start_time, end_time FROM tbl_shifts WHERE is_active = 1 ORDER BY department, shift_name";
    $shiftStmt = $conn->query($shiftQuery);
    $shifts = $shiftStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $shifts = [];
}

// Handle shift assignment
$shift_success = false;
if (isset($_POST['assign_shift'])) {
    $userId = $_POST['user_id'];
    $shiftId = $_POST['shift_id'] ?: null;
    
    try {
        $updateStmt = $conn->prepare("UPDATE tbl_user SET shift_id = ? WHERE id = ?");
        $updateStmt->execute([$shiftId, $userId]);
        $shift_success = true;
        // Refresh data after update
        $stmt = $conn->query($query);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $shift_error = "Error assigning shift: " . $e->getMessage();
    }
}
?>
<style>
    .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0060c0ff;
            text-align: center;
            margin-bottom: 30px;
        }
        .login-form {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        input[type="password"], input[type="date"], input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #3251ffff;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .b{
    display: flex;
    justify-content: right;
    margin-left: auto;
}
        button:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #000000ff;
        }
        th {
            background-color: #0176d6ff;
            font-weight: 600;
        }
        tr:hover {
            background-color: #90ff99ff;
        }
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .actions {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
</style>
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Manajemen Pengguna</h2>
    </div>

    <!-- Success Notification -->
    <?php if ($delete_success): ?>
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        Berhasil: User telah dihapus!
    </div>
    <script>
        setTimeout(function() {
            window.location.href = '?page=users';
        }, 1500);
    </script>
    <?php elseif ($shift_success): ?>
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        Berhasil: Shift berhasil diassign ke user!
    </div>
    <?php elseif (isset($delete_error)): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        Error: <?= htmlspecialchars($delete_error) ?>
    </div>
    <?php elseif (isset($shift_error)): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
        Error: <?= htmlspecialchars($shift_error) ?>
    </div>
    <?php elseif (isset($_GET['success'])): ?>
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        Berhasil: 
        <?= $_GET['success'] == 'delete' ? 'User berhasil dihapus' : 
           ($_GET['success'] == 'update' ? 'User berhasil diperbarui' : 
           ($_GET['success'] == 'shift_assigned' ? 'Shift berhasil diassign' : 'User berhasil ditambahkan')) ?>
    </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>NIP</th>
                    <th>Shift</th>
                    <th>Jam Kerja</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user=> $row): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $user + 1 ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900"><?= htmlspecialchars($row['name']) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['NIP'] ?? '-') ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($row['shift_name']): ?>
                            <div class="text-sm">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($row['department']) ?>
                                </span>
                                <div class="mt-1 text-gray-600"><?= htmlspecialchars($row['shift_name']) ?></div>
                            </div>
                        <?php else: ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                Belum ada shift
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <?php if ($row['start_time'] && $row['end_time']): ?>
                            <?= date('H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap space-x-2">
                        <button onclick="openShiftModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', <?= $row['shift_id'] ?? 'null' ?>)" 
                                class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-clock"></i> Set Shift
                        </button>
                        <a href="?page=users&action=delete&id=<?= $row['id'] ?>" 
                           onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')"
                           class="text-red-600 hover:text-red-900">
                           <i class="fas fa-trash"></i> Hapus
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Shift Assignment Modal -->
<div id="shiftModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Assign Shift</h3>
                <button onclick="closeShiftModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="assign_shift" value="1">
                <input type="hidden" name="user_id" id="modalUserId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">User:</label>
                    <div id="modalUserName" class="text-gray-900 font-medium"></div>
                </div>
                
                <div class="mb-4">
                    <label for="shiftSelect" class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Shift:
                    </label>
                    <select name="shift_id" id="shiftSelect" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">-- Tidak ada shift --</option>
                        <?php foreach ($shifts as $shift): ?>
                            <option value="<?= $shift['id'] ?>" 
                                    data-department="<?= htmlspecialchars($shift['department']) ?>"
                                    data-time="<?= date('H:i', strtotime($shift['start_time'])) ?> - <?= date('H:i', strtotime($shift['end_time'])) ?>">
                                <?= htmlspecialchars($shift['department']) ?> - <?= htmlspecialchars($shift['shift_name']) ?>
                                (<?= date('H:i', strtotime($shift['start_time'])) ?> - <?= date('H:i', strtotime($shift['end_time'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeShiftModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openShiftModal(userId, userName, currentShiftId) {
        document.getElementById('modalUserId').value = userId;
        document.getElementById('modalUserName').textContent = userName;
        document.getElementById('modalTitle').textContent = 'Assign Shift - ' + userName;
        
        // Set current shift if exists
        const shiftSelect = document.getElementById('shiftSelect');
        shiftSelect.value = currentShiftId || '';
        
        document.getElementById('shiftModal').classList.remove('hidden');
    }
    
    function closeShiftModal() {
        document.getElementById('shiftModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    document.getElementById('shiftModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeShiftModal();
        }
    });
</script>
