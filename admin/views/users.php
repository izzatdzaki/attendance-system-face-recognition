<?php
$conn = connectDB();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM tbl_attendance WHERE user_id = ?");
        $stmt->execute([$_GET['id']]);

        $stmt = $conn->prepare("DELETE FROM tbl_user WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: $current_page");
        exit;
    } catch (PDOException $e) {
        die("Error deleting user: " . $e->getMessage());
    }
}

// Get all users
try {
    $query = "SELECT * FROM tbl_user";
    $stmt = $conn->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching users: " . $e->getMessage());
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
    <?php if (isset($_GET['success'])): ?>
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        Berhasil: 
        <?= $_GET['success'] == 'delete' ? 'User berhasil dihapus' : 
           ($_GET['success'] == 'update' ? 'User berhasil diperbarui' : 'User berhasil ditambahkan') ?>
    </div>
    <?php endif; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th >No</th>
                    <th >Nama</th>
                    <th >Jabatan</th>
                    <th >Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user=> $row): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $user +1 ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['jabatan']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap space-x-2">
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
