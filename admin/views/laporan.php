<?php
 try {
        $pdo = connectDB();
        
        // Filter tanggal
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        $search_name = $_GET['search_name'] ?? '';
        $status_lembur_filter = $_GET['status_lembur'] ?? '';
        $department_filter = $_GET['department'] ?? '';
        
        // Query data absensi dengan filter termasuk data lembur, lokasi, dan shift
        $query = "SELECT a.id, u.name, u.jabatan, u.NIP, a.tanggal_absen, a.jam_datang, a.jam_pulang, a.status_absen,
                         a.jam_lembur_mulai, a.jam_lembur_selesai, a.status_lembur,
                         a.location_name, a.location_verified, a.user_latitude, a.user_longitude,
                         s.shift_name, s.department, s.start_time, s.end_time, s.tolerance_minutes,
                         u.shift_id
                  FROM tbl_attendance a
                  JOIN tbl_user u ON a.user_id = u.id
                  LEFT JOIN tbl_shifts s ON u.shift_id = s.id
                  WHERE a.tanggal_absen BETWEEN :start_date AND :end_date";
        
        $params = [
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ];
        
        if (!empty($search_name)) {
            $query .= " AND u.name LIKE :search_name";
            $params[':search_name'] = "%$search_name%";
        }
        
        if (!empty($status_lembur_filter)) {
            $query .= " AND a.status_lembur = :status_lembur";
            $params[':status_lembur'] = $status_lembur_filter;
        }
        
        if (!empty($department_filter)) {
            $query .= " AND s.department = :department";
            $params[':department'] = $department_filter;
        }
        
        $query .= " ORDER BY a.tanggal_absen DESC, a.jam_datang DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $attendance_data = $stmt->fetchAll();
        
        // Get departments for filter
        $dept_query = "SELECT DISTINCT department FROM tbl_shifts WHERE is_active = 1 ORDER BY department";
        $dept_stmt = $pdo->prepare($dept_query);
        $dept_stmt->execute();
        $departments = $dept_stmt->fetchAll();
        
        // Hitung statistik
        $total_records = count($attendance_data);
        $total_hadir_lengkap = count(array_filter($attendance_data, function($row) {
            return $row['status_absen'] === 'lengkap';
        }));
        $total_lembur = count(array_filter($attendance_data, function($row) {
            return $row['status_lembur'] === 'lembur_selesai';
        }));
        $total_terlambat = 0;
        
        // Hitung keterlambatan berdasarkan shift
        foreach ($attendance_data as $row) {
            if ($row['jam_datang'] && $row['start_time']) {
                $jamDatang = strtotime($row['jam_datang']);
                $jamMulaiShift = strtotime($row['start_time']);
                $toleransi = ($row['tolerance_minutes'] ?? 15) * 60;
                if ($jamDatang > ($jamMulaiShift + $toleransi)) {
                    $total_terlambat++;
                }
            }
        }
        
    } catch (PDOException $e) {
        die("Error mengambil data: " . $e->getMessage());
    }
    ?>
<style>
.report-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.report-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
}

.report-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.filters-section {
    padding: 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.filter-group input {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.filter-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
    transform: translateY(-2px);
}

.table-container {
    padding: 1.5rem;
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    background: #f8fafc;
    color: #374151;
    font-weight: 600;
    padding: 1rem 0.75rem;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    color: #6b7280;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-hadir {
    background: #d1fae5;
    color: #065f46;
}

.status-datang {
    background: #fef3c7;
    color: #92400e;
}

.status-default {
    background: #f3f4f6;
    color: #6b7280;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.empty-state svg {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    opacity: 0.5;
}

.kop-surat {
    display: none;
}

@media print {
    .kop-surat {
        display: block;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .kop-surat h1 {
        font-size: 24px;
        margin: 0;
    }
    
    .kop-surat p {
        margin: 5px 0;
    }
    
    .no-print, .filters-section {
        display: none !important;
    }
    
    .report-container {
        box-shadow: none;
    }
    
    .data-table {
        font-size: 12px;
    }
}

/* Status badges khusus untuk keterlambatan */
.status-tepat-waktu {
    background: #d1fae5;
    color: #065f46;
}

.status-toleransi {
    background: #fef3c7;
    color: #92400e;
}

.status-terlambat {
    background: #fee2e2;
    color: #991b1b;
}

/* Summary cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
}

.summary-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    text-align: center;
    border-left: 4px solid #667eea;
}

.summary-card .number {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.summary-card .label {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .filters {
        grid-template-columns: 1fr;
    }
    
    .actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
    
    .data-table {
        font-size: 0.75rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.5rem 0.25rem;
    }
}
</style>
<div class="report-container">
    <div class="report-header">
        <h1 class="report-title">📊 Laporan Absensi</h1>
        <div style="margin-top: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $total_records; ?></div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Total Records</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $total_hadir_lengkap; ?></div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Hadir Lengkap</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $total_lembur; ?></div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Total Lembur</div>
            </div>
            <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold;"><?php echo $total_terlambat; ?></div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Terlambat</div>
            </div>
        </div>
    </div>
    
    <div class="filters-section no-print">
        <form method="GET">
            <div class="filters">
                <div class="filter-group">
                    <label for="start_date">📅 Dari Tanggal</label>
                    <input type="date" id="start_date" name="start_date" 
                           value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="filter-group">
                    <label for="end_date">📅 Sampai Tanggal</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                <div class="filter-group">
                    <label for="search_name">👤 Cari Nama</label>
                    <input type="text" id="search_name" name="search_name" 
                           value="<?php echo htmlspecialchars($search_name); ?>" 
                           placeholder="Masukkan nama user">
                </div>
                <div class="filter-group">
                    <label for="department">🏢 Departemen</label>
                    <select id="department" name="department" style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                        <option value="">Semua Departemen</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                    <?php echo $department_filter === $dept['department'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="status_lembur">⏰ Status Lembur</label>
                    <select id="status_lembur" name="status_lembur" style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                        <option value="">Semua Status</option>
                        <option value="tidak_lembur" <?php echo $status_lembur_filter === 'tidak_lembur' ? 'selected' : ''; ?>>Tidak Lembur</option>
                        <option value="lembur_mulai" <?php echo $status_lembur_filter === 'lembur_mulai' ? 'selected' : ''; ?>>Mulai Lembur</option>
                        <option value="lembur_selesai" <?php echo $status_lembur_filter === 'lembur_selesai' ? 'selected' : ''; ?>>Selesai Lembur</option>
                    </select>
                </div>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn btn-primary">
                    🔍 Filter Data
                </button>
                <button type="button" onclick="window.print()" class="btn btn-secondary">
                    🖨️ Cetak Laporan
                </button>
                <button type="button" onclick="exportToExcel()" class="btn btn-secondary">
                    📊 Export Excel
                </button>
            </div>
        </form>
    </div>
    
    <div class="kop-surat">
        <h1>LAPORAN ABSENSI</h1>
        <p>Universitas Adzkia</p>
        <p>Periode: <?php echo date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)); ?></p>
        <hr>
    </div>
    
    <div class="table-container">
        <?php if (empty($attendance_data)): ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3>Tidak ada data absensi</h3>
                <p>Tidak ada data absensi ditemukan untuk kriteria yang dipilih.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>NIP</th>
                        <th>Nama User</th>
                        <th>Jabatan</th>
                        <th>Departemen</th>
                        <th>Shift</th>
                        <th>Tanggal</th>
                        <th>Jam Datang</th>
                        <th>Jam Pulang</th>
                        <th>Status Keterlambatan</th>
                        <th>Lembur Mulai</th>
                        <th>Lembur Selesai</th>
                        <th>Status Lembur</th>
                        <th>Lokasi</th>
                        <th>Status Absen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_data as $index => $row): ?>
                        <?php
                        // Hitung status keterlambatan
                        $statusTerlambat = '-';
                        $terlambatClass = 'status-default';
                        if ($row['jam_datang'] && $row['start_time']) {
                            $jamDatang = strtotime($row['jam_datang']);
                            $jamMulaiShift = strtotime($row['start_time']);
                            $toleransi = ($row['tolerance_minutes'] ?? 15) * 60; // dalam detik
                            
                            if ($jamDatang <= $jamMulaiShift) {
                                $statusTerlambat = '✅ Tepat Waktu';
                                $terlambatClass = 'status-hadir';
                            } elseif ($jamDatang <= ($jamMulaiShift + $toleransi)) {
                                $menitTerlambat = ceil(($jamDatang - $jamMulaiShift) / 60);
                                $statusTerlambat = "⚠️ Terlambat {$menitTerlambat}m (Toleransi)";
                                $terlambatClass = 'status-datang';
                            } else {
                                $menitTerlambat = ceil(($jamDatang - $jamMulaiShift) / 60);
                                $statusTerlambat = "❌ Terlambat {$menitTerlambat}m";
                                $terlambatClass = 'status-default';
                            }
                        }
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['NIP']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                            <td><?php echo htmlspecialchars($row['department'] ?? '-'); ?></td>
                            <td>
                                <?php if ($row['shift_name']): ?>
                                    <strong><?php echo htmlspecialchars($row['shift_name']); ?></strong><br>
                                    <small><?php echo $row['start_time'] . ' - ' . $row['end_time']; ?></small>
                                <?php else: ?>
                                    <span style="color: #ef4444;">Belum Ada Shift</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['tanggal_absen'] ? date('d/m/Y', strtotime($row['tanggal_absen'])) : '-'; ?></td>
                            <td><?php echo $row['jam_datang'] ? date('H:i:s', strtotime($row['jam_datang'])) : '-'; ?></td>
                            <td><?php echo $row['jam_pulang'] ? date('H:i:s', strtotime($row['jam_pulang'])) : '-'; ?></td>
                            <td><span class="status-badge <?php echo $terlambatClass; ?>"><?php echo $statusTerlambat; ?></span></td>
                            <td><?php echo $row['jam_lembur_mulai'] ? date('H:i:s', strtotime($row['jam_lembur_mulai'])) : '-'; ?></td>
                            <td><?php echo $row['jam_lembur_selesai'] ? date('H:i:s', strtotime($row['jam_lembur_selesai'])) : '-'; ?></td>
                            <td>
                                <?php 
                                $status_lembur = htmlspecialchars($row['status_lembur'] ?? 'tidak_lembur');
                                $lemburClass = '';
                                switch($status_lembur) {
                                    case 'lembur_mulai':
                                        $lemburClass = 'status-datang';
                                        $status_lembur = 'Mulai Lembur';
                                        break;
                                    case 'lembur_selesai':
                                        $lemburClass = 'status-hadir';
                                        $status_lembur = 'Selesai Lembur';
                                        break;
                                    case 'tidak_lembur':
                                    default:
                                        $lemburClass = 'status-default';
                                        $status_lembur = 'Tidak Lembur';
                                }
                                echo "<span class='status-badge $lemburClass'>$status_lembur</span>";
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($row['location_name']) {
                                    $verified = $row['location_verified'] ? '✅' : '❌';
                                    echo $verified . ' ' . htmlspecialchars($row['location_name']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $status = htmlspecialchars($row['status_absen']);
                                $statusClass = '';
                                switch($status) {
                                    case 'lengkap':
                                        $statusClass = 'status-hadir';
                                        $status = 'Lengkap';
                                        break;
                                    case 'datang':
                                        $statusClass = 'status-datang';
                                        $status = 'Datang';
                                        break;
                                    case 'pulang':
                                        $statusClass = 'status-datang';
                                        $status = 'Pulang';
                                        break;
                                    default:
                                        $statusClass = 'status-default';
                                }
                                echo "<span class='status-badge $statusClass'>$status</span>";
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function exportToExcel() {
    // Get current URL parameters for filter
    const urlParams = new URLSearchParams(window.location.search);
    const params = {
        start_date: urlParams.get('start_date') || '<?php echo $start_date; ?>',
        end_date: urlParams.get('end_date') || '<?php echo $end_date; ?>',
        search_name: urlParams.get('search_name') || '',
        department: urlParams.get('department') || '',
        status_lembur: urlParams.get('status_lembur') || '',
        export: 'excel'
    };
    
    // Build URL
    const queryString = Object.keys(params)
        .filter(key => params[key] !== '')
        .map(key => `${key}=${encodeURIComponent(params[key])}`)
        .join('&');
    
    // Create download link
    window.location.href = `export_laporan.php?${queryString}`;
}

// Auto-refresh setiap 30 detik jika tidak ada filter aktif
<?php if (empty($search_name) && empty($status_lembur_filter) && empty($department_filter)): ?>
setTimeout(() => window.location.reload(), 30000);
<?php endif; ?>

// Highlight row on hover untuk table besar
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f0f9ff';
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'all 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        });
    });
});
</script>