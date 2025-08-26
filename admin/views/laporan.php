<?php
 try {
        $pdo = connectDB();
        
        // Filter tanggal
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        $search_name = $_GET['search_name'] ?? '';
        
        // Query data absensi dengan filter
        $query = "SELECT a.id, u.name, u.jabatan,u.NIDN, a.attendance_time 
                  FROM tbl_attendance a
                  JOIN tbl_user u ON a.user_id = u.id
                  WHERE a.attendance_time BETWEEN :start_date AND :end_date + INTERVAL 1 DAY";
        
        $params = [
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ];
        
        if (!empty($search_name)) {
            $query .= " AND u.name LIKE :search_name";
            $params[':search_name'] = "%$search_name%";
        }
        
        $query .= " ORDER BY a.attendance_time DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $attendance_data = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        die("Error mengambil data: " . $e->getMessage());
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
        .kop-surat {
        display: none; 
    }
        @media print {
            .kop-surat {
            display: block; /* Tampilkan saat mencetak */
            text-align: center; /* Rata tengah */
            margin-bottom: 20px; /* Jarak bawah */
        }
        .kop-surat h1 {
            font-size: 24px; /* Ukuran font untuk judul */
            margin: 0; /* Menghilangkan margin */
        }
        .kop-surat p {
            margin: 5px 0; /* Jarak antar paragraf */
        }
            .no-print, .filters {
                display: none !important;
            }
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
            table {
                font-size: 12px;
            }
        }
</style>
<form method="GET" class="no-print">
                <div class="filters">
                    <div class="filter-group">
                        <label for="start_date">Dari Tanggal</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="filter-group">
                        <label for="end_date">Sampai Tanggal</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="filter-group">
                        <label for="search_name">Cari Nama</label>
                        <input type="text" id="search_name" name="search_name" 
                               value="<?php echo htmlspecialchars($search_name); ?>" 
                               placeholder="Masukkan nama user">
                    </div>
                </div>
                <div class="actions no-print">
                    <button type="button" onclick="window.print()">Cetak Laporan</button>
                </div>
            </form>
            
            <div class="kop-surat">
    <h1>Data Absensi</h1>
    <p>Universitas Adzkia</p>
    <hr>
</div>
            <!-- Tabel Data Absensi -->
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>NIDN</th>
                        <th>Nama user</th>
                        <th>Jabatan</th>
                        <th>Waktu Absensi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance_data)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center;">Tidak ada data absensi</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance_data as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['NIDN']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['jabatan'])?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($row['attendance_time'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>