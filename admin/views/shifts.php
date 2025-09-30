<?php
// Session sudah dimulai di admin.php, tidak perlu session_start() lagi
require_once '../db_connect.php';

$conn = connectDB();

// Get all shifts
try {
    $query = "SELECT s.*, 
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
    $stmt = $conn->query($query);
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching shifts: " . $e->getMessage());
}
?>
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Master Data Shift Kerja</h2>
        <button onclick="openShiftModal()" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Tambah Shift</span>
        </button>
    </div>

    <!-- Alert -->
    <div id="alert-container" class="mb-4"></div>

    <!-- Filter -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter Departemen:</label>
            <select id="departmentFilter" onchange="filterShifts()" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Semua Departemen</option>
                <option value="Nakes">Nakes</option>
                <option value="Kantor">Kantor</option>
                <option value="Cleaning">Cleaning</option>
                <option value="Pramusaji">Pramusaji</option>
                <option value="Security">Security</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter Status:</label>
            <select id="statusFilter" onchange="filterShifts()" 
                    class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Semua Status</option>
                <option value="1">Aktif</option>
                <option value="0">Tidak Aktif</option>
            </select>
        </div>
    </div>

    <!-- Shifts Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departemen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Kerja</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Toleransi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="shiftsTableBody">
                <?php foreach ($shifts as $index => $shift): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $index + 1 ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($shift['shift_name']) ?></div>
                                <?php if ($shift['is_overnight']): ?>
                                    <div class="text-xs text-red-600">
                                        <i class="fas fa-moon"></i> Overnight
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                            <?= $shift['department'] == 'Nakes' ? 'bg-green-100 text-green-800' : 
                               ($shift['department'] == 'Security' ? 'bg-red-100 text-red-800' : 
                               ($shift['department'] == 'Cleaning' ? 'bg-yellow-100 text-yellow-800' : 
                               ($shift['department'] == 'Pramusaji' ? 'bg-purple-100 text-purple-800' : 
                               'bg-blue-100 text-blue-800'))) ?>">
                            <?= htmlspecialchars($shift['department']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= date('H:i', strtotime($shift['start_time'])) ?> - <?= date('H:i', strtotime($shift['end_time'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= $shift['duration_hours'] ?> jam
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= $shift['tolerance_minutes'] ?> menit
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            <?= $shift['user_count'] ?> user
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                            <?= $shift['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $shift['is_active'] ? 'Aktif' : 'Tidak Aktif' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <button onclick="editShift(<?= $shift['id'] ?>)" 
                                class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="deleteShift(<?= $shift['id'] ?>, <?= $shift['user_count'] ?>)" 
                                class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Shift Modal -->
<div id="shiftModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="shiftModalTitle">Tambah Shift</h3>
                <button onclick="closeShiftModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="shiftForm">
                <input type="hidden" id="shiftId" name="id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="shiftName" class="block text-sm font-medium text-gray-700 mb-2">Nama Shift *</label>
                        <input type="text" id="shiftName" name="shift_name" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Departemen *</label>
                        <select id="department" name="department" required
                                class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">Pilih Departemen</option>
                            <option value="Nakes">Nakes</option>
                            <option value="Kantor">Kantor</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="Pramusaji">Pramusaji</option>
                            <option value="Security">Security</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="startTime" class="block text-sm font-medium text-gray-700 mb-2">Jam Mulai *</label>
                        <input type="time" id="startTime" name="start_time" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label for="endTime" class="block text-sm font-medium text-gray-700 mb-2">Jam Selesai *</label>
                        <input type="time" id="endTime" name="end_time" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="toleranceMinutes" class="block text-sm font-medium text-gray-700 mb-2">Toleransi Keterlambatan (menit)</label>
                        <input type="number" id="toleranceMinutes" name="tolerance_minutes" value="15" min="0" max="60"
                               class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label for="isActive" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="isActive" name="is_active"
                                class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-md p-3 mb-4">
                    <div class="flex">
                        <i class="fas fa-info-circle text-blue-400 mt-0.5 mr-2"></i>
                        <div class="text-sm text-blue-800">
                            <strong>Info:</strong> Jika jam selesai lebih kecil dari jam mulai, sistem akan otomatis mendeteksi sebagai shift malam (melewati tengah malam).
                        </div>
                    </div>
                </div>
            </form>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeShiftModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    Batal
                </button>
                <button type="button" onclick="saveShift()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Konfirmasi Hapus</h3>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="text-gray-600 mb-4">Apakah Anda yakin ingin menghapus shift ini?</p>
            
            <div id="deleteWarning" class="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-4 hidden">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-yellow-400 mt-0.5 mr-2"></i>
                    <div class="text-sm text-yellow-800">
                        Shift ini masih digunakan oleh beberapa user dan tidak dapat dihapus.
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    Batal
                </button>
                <button type="button" id="confirmDeleteBtn" onclick="confirmDelete()" 
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    <i class="fas fa-trash mr-1"></i> Hapus
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    let currentShiftId = null;

    function filterShifts() {
        const deptFilter = document.getElementById('departmentFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('#shiftsTableBody tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 1) {
                const dept = cells[2].textContent.trim();
                const status = cells[7].textContent.includes('Aktif') ? '1' : '0';
                
                let showRow = true;
                
                if (deptFilter && !dept.includes(deptFilter)) {
                    showRow = false;
                }
                
                if (statusFilter !== '' && status !== statusFilter) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            }
        });
    }

    function openShiftModal(shiftId = null) {
        currentShiftId = shiftId;
        const modal = document.getElementById('shiftModal');
        const title = document.getElementById('shiftModalTitle');
        const form = document.getElementById('shiftForm');
        
        form.reset();
        
        if (shiftId) {
            title.textContent = 'Edit Shift';
            loadShiftData(shiftId);
        } else {
            title.textContent = 'Tambah Shift';
            document.getElementById('shiftId').value = '';
        }
        
        modal.classList.remove('hidden');
    }

    function closeShiftModal() {
        document.getElementById('shiftModal').classList.add('hidden');
    }

    async function loadShiftData(shiftId) {
        try {
            const response = await fetch(`../api_shifts.php?action=get_shift&id=${shiftId}`);
            const result = await response.json();
            
            if (result.success) {
                const shift = result.data;
                document.getElementById('shiftId').value = shift.id;
                document.getElementById('shiftName').value = shift.shift_name;
                document.getElementById('department').value = shift.department;
                document.getElementById('startTime').value = shift.start_time;
                document.getElementById('endTime').value = shift.end_time;
                document.getElementById('toleranceMinutes').value = shift.tolerance_minutes;
                document.getElementById('isActive').value = shift.is_active;
                document.getElementById('description').value = shift.description || '';
            } else {
                showAlert('error', 'Gagal memuat data shift: ' + result.message);
            }
        } catch (error) {
            showAlert('error', 'Error: ' + error.message);
        }
    }

    function editShift(shiftId) {
        openShiftModal(shiftId);
    }

    async function saveShift() {
        const form = document.getElementById('shiftForm');
        const formData = new FormData(form);
        
        // Validate required fields
        const shiftName = formData.get('shift_name');
        const department = formData.get('department');
        const startTime = formData.get('start_time');
        const endTime = formData.get('end_time');
        
        if (!shiftName || !department || !startTime || !endTime) {
            showAlert('warning', 'Mohon lengkapi semua field yang wajib diisi');
            return;
        }
        
        const data = Object.fromEntries(formData);
        const isUpdate = data.id && data.id !== '';
        
        try {
            const response = await fetch('../api_shifts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ...data,
                    action: isUpdate ? 'update_shift' : 'create_shift'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                setTimeout(() => location.reload(), 1500);
                closeShiftModal();
            } else {
                showAlert('error', result.message);
            }
        } catch (error) {
            showAlert('error', 'Error: ' + error.message);
        }
    }

    function deleteShift(shiftId, userCount) {
        currentShiftId = shiftId;
        const warning = document.getElementById('deleteWarning');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        if (userCount > 0) {
            warning.classList.remove('hidden');
            confirmBtn.style.display = 'none';
        } else {
            warning.classList.add('hidden');
            confirmBtn.style.display = 'block';
        }
        
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    async function confirmDelete() {
        try {
            const response = await fetch(`../api_shifts.php?action=delete_shift&id=${currentShiftId}`, {
                method: 'GET'
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                setTimeout(() => location.reload(), 1500);
                closeDeleteModal();
            } else {
                showAlert('error', result.message);
            }
        } catch (error) {
            showAlert('error', 'Error: ' + error.message);
        }
    }

    function showAlert(type, message) {
        const container = document.getElementById('alert-container');
        const colors = {
            success: 'bg-green-100 border-green-400 text-green-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
        };
        
        container.innerHTML = `
            <div class="border px-4 py-3 rounded ${colors[type]} alert-dismissible">
                ${message}
                <button onclick="this.parentElement.style.display='none'" class="float-right text-xl leading-none hover:text-gray-600">&times;</button>
            </div>
        `;
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            const alert = container.querySelector('.alert-dismissible');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 5000);
    }

    // Close modals when clicking outside
    document.getElementById('shiftModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeShiftModal();
        }
    });

    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });
</script>