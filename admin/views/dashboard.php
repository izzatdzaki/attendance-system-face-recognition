<?php
try {
    $pdo = connectDB();
    
    // Get filter parameters
    $filter_date_start = $_GET['filter_date_start'] ?? date('Y-m-01');
    $filter_date_end = $_GET['filter_date_end'] ?? date('Y-m-d');
    $filter_department = $_GET['filter_department'] ?? '';
    $filter_status = $_GET['filter_status'] ?? '';
    
    // Get current date info
    $today = date('Y-m-d');
    $current_month = date('Y-m');
    $current_year = date('Y');
    
    // Build WHERE conditions for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($filter_date_start) && !empty($filter_date_end)) {
        $where_conditions[] = "DATE(a.tanggal_absen) BETWEEN ? AND ?";
        $params[] = $filter_date_start;
        $params[] = $filter_date_end;
    }
    
    if (!empty($filter_department)) {
        $where_conditions[] = "u.jabatan = ?";
        $params[] = $filter_department;
    }
    
    if (!empty($filter_status)) {
        $where_conditions[] = "a.status_absen = ?";
        $params[] = $filter_status;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Total Users (filtered by department if selected)
    if (!empty($filter_department)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM tbl_user WHERE jabatan = ?");
        $stmt->execute([$filter_department]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM tbl_user");
    }
    $total_users = $stmt->fetch()['total_users'];
    
    // Filtered Attendance Count
    $attendance_query = "SELECT COUNT(*) as filtered_attendance FROM tbl_attendance a JOIN tbl_user u ON a.user_id = u.id";
    if (!empty($where_conditions)) {
        $attendance_query .= " WHERE " . implode(" AND ", $where_conditions);
        $stmt = $pdo->prepare($attendance_query);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($attendance_query);
    }
    $filtered_attendance = $stmt->fetch()['filtered_attendance'];
    
    // Today's Attendance
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_attendance FROM tbl_attendance WHERE DATE(tanggal_absen) = ?");
    $stmt->execute([$today]);
    $today_attendance = $stmt->fetch()['today_attendance'];
    
    // This Month's Attendance
    $stmt = $pdo->prepare("SELECT COUNT(*) as month_attendance FROM tbl_attendance WHERE DATE_FORMAT(tanggal_absen, '%Y-%m') = ?");
    $stmt->execute([$current_month]);
    $month_attendance = $stmt->fetch()['month_attendance'];
    
    // Present Today
    $stmt = $pdo->prepare("SELECT COUNT(*) as present_today FROM tbl_attendance WHERE DATE(tanggal_absen) = ? AND jam_datang IS NOT NULL");
    $stmt->execute([$today]);
    $present_today = $stmt->fetch()['present_today'];
    
    // Late Today (assuming work starts at 08:00)
    $stmt = $pdo->prepare("SELECT COUNT(*) as late_today FROM tbl_attendance WHERE DATE(tanggal_absen) = ? AND TIME(jam_datang) > '08:00:00'");
    $stmt->execute([$today]);
    $late_today = $stmt->fetch()['late_today'];
    
    // Recent Attendance (with filters applied)
    $recent_query = "
        SELECT u.name, u.jabatan, a.tanggal_absen, a.jam_datang, a.jam_pulang, a.status_absen 
        FROM tbl_attendance a 
        JOIN tbl_user u ON a.user_id = u.id 
    ";
    if (!empty($where_conditions)) {
        $recent_query .= " WHERE " . implode(" AND ", $where_conditions);
        $recent_query .= " ORDER BY a.tanggal_absen DESC, a.jam_datang DESC LIMIT 10";
        $stmt = $pdo->prepare($recent_query);
        $stmt->execute($params);
    } else {
        $recent_query .= " ORDER BY a.tanggal_absen DESC, a.jam_datang DESC LIMIT 10";
        $stmt = $pdo->query($recent_query);
    }
    $recent_attendance = $stmt->fetchAll();
    
    // Statistics for Chart (filtered by date range)
    $chart_start = !empty($filter_date_start) ? $filter_date_start : $current_month . '-01';
    $chart_end = !empty($filter_date_end) ? $filter_date_end : date('Y-m-t');
    
    $chart_query = "
        SELECT 
            DATE_FORMAT(a.tanggal_absen, '%Y-%m-%d') as date,
            COUNT(*) as count 
        FROM tbl_attendance a 
        JOIN tbl_user u ON a.user_id = u.id
        WHERE DATE(a.tanggal_absen) BETWEEN ? AND ?
    ";
    
    $chart_params = [$chart_start, $chart_end];
    
    if (!empty($filter_department)) {
        $chart_query .= " AND u.jabatan = ?";
        $chart_params[] = $filter_department;
    }
    
    if (!empty($filter_status)) {
        $chart_query .= " AND a.status_absen = ?";
        $chart_params[] = $filter_status;
    }
    
    $chart_query .= " GROUP BY DATE_FORMAT(a.tanggal_absen, '%Y-%m-%d') ORDER BY date";
    
    $stmt = $pdo->prepare($chart_query);
    $stmt->execute($chart_params);
    $monthly_stats = $stmt->fetchAll();
    
    // Department Statistics (filtered)
    $dept_query = "
        SELECT 
            u.jabatan,
            COUNT(DISTINCT u.id) as total_employees,
            COUNT(a.id) as total_attendance
        FROM tbl_user u
        LEFT JOIN tbl_attendance a ON u.id = a.user_id
    ";
    
    $dept_params = [];
    $dept_conditions = [];
    
    if (!empty($filter_date_start) && !empty($filter_date_end)) {
        $dept_conditions[] = "DATE(a.tanggal_absen) BETWEEN ? AND ?";
        $dept_params[] = $filter_date_start;
        $dept_params[] = $filter_date_end;
    } else {
        $dept_conditions[] = "DATE_FORMAT(a.tanggal_absen, '%Y-%m') = ?";
        $dept_params[] = $current_month;
    }
    
    if (!empty($filter_department)) {
        $dept_conditions[] = "u.jabatan = ?";
        $dept_params[] = $filter_department;
    }
    
    if (!empty($filter_status)) {
        $dept_conditions[] = "a.status_absen = ?";
        $dept_params[] = $filter_status;
    }
    
    if (!empty($dept_conditions)) {
        $dept_query .= " AND " . implode(" AND ", $dept_conditions);
    }
    
    $dept_query .= " GROUP BY u.jabatan ORDER BY total_employees DESC";
    
    $stmt = $pdo->prepare($dept_query);
    $stmt->execute($dept_params);
    $department_stats = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error mengambil data dashboard: " . $e->getMessage());
}
?>

<style>
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    background: #f8fafc;
    min-height: 100vh;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 3rem;
    padding: 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    color: white;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
}

.dashboard-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.dashboard-subtitle {
    font-size: 1.1rem;
    margin: 0;
    opacity: 0.9;
    font-weight: 400;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-card.users::before {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.filtered::before {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.today::before {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.month::before {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-card.present::before {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.stat-card.late::before {
    background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
    line-height: 1;
}

.stat-label {
    font-size: 0.95rem;
    color: #6b7280;
    font-weight: 500;
    line-height: 1.4;
}

.chart-container {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
}

.chart-container h3 {
    margin-bottom: 1.5rem !important;
    color: #1f2937 !important;
    font-weight: 600 !important;
    font-size: 1.25rem !important;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.chart-container h3::before {
    content: '📊';
    font-size: 1.5rem;
}

.chart-wrapper {
    position: relative;
    height: 300px;
    margin-top: 1rem;
}

#attendanceChart {
    border-radius: 8px;
}

.filter-section {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2.5rem;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.filter-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-toggle {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.btn-toggle:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

.filter-content {
    padding: 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group input,
.filter-group select {
    padding: 0.875rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    transform: translateY(-1px);
}

.filter-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.btn {
    padding: 0.875rem 1.75rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.department-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
    margin-top: 1.5rem;
}

.department-card {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.department-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.department-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
}

.department-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.75rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.department-name::before {
    content: '🏢';
    font-size: 1.2rem;
}

.department-stats {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    color: #6b7280;
    gap: 1rem;
}

.department-stats span {
    background: white;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-weight: 500;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

.recent-activity {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.recent-activity h3 {
    margin-bottom: 1.5rem !important;
    color: #1f2937 !important;
    font-weight: 600 !important;
    font-size: 1.25rem !important;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.recent-activity h3::before {
    content: '🕒';
    font-size: 1.5rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    position: relative;
}

.activity-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0 4px 4px 0;
}

.activity-item:hover {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.activity-item:last-child {
    margin-bottom: 0;
}

.activity-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.activity-info {
    flex: 1;
    min-width: 0;
}

.activity-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.activity-details {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.activity-time {
    color: #9ca3af;
    font-size: 0.8rem;
    font-weight: 500;
    background: rgba(156, 163, 175, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    display: inline-block;
}

.notifications-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.notifications-header h3 {
    margin: 0 !important;
    color: #1f2937 !important;
    font-weight: 600 !important;
    font-size: 1.25rem !important;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.notifications-header h3::before {
    content: '🔔';
    font-size: 1.5rem;
}

.notification-badge {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(240, 147, 251, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 2px 8px rgba(240, 147, 251, 0.4);
    }
    50% {
        box-shadow: 0 2px 16px rgba(240, 147, 251, 0.6);
    }
    100% {
        box-shadow: 0 2px 8px rgba(240, 147, 251, 0.4);
    }
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem;
    border-radius: 12px;
    margin-bottom: 0.75rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    position: relative;
}

.notification-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 0 4px 4px 0;
}

.notification-item:hover {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.notification-item:last-child {
    margin-bottom: 0;
}

.notification-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.notification-message {
    color: #6b7280;
    font-size: 0.85rem;
    line-height: 1.4;
}

.notification-time {
    color: #9ca3af;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
    background: rgba(156, 163, 175, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
}

.no-notifications,
.loading-notifications {
    text-align: center;
    color: #6b7280;
    padding: 2rem;
    font-style: italic;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.loading-notifications::before {
    content: '⏳';
    font-size: 1.5rem;
    display: block;
    margin-bottom: 0.5rem;
}

.no-notifications::before {
    content: '📭';
    font-size: 1.5rem;
    display: block;
    margin-bottom: 0.5rem;
}

@media (max-width: 1024px) {
    .dashboard-container {
        padding: 1.5rem;
    }
    
    .dashboard-title {
        font-size: 2rem;
    }
    
    .dashboard-subtitle {
        font-size: 1rem;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .dashboard-header {
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .dashboard-title {
        font-size: 1.75rem;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
    
    .filter-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        padding: 1.5rem;
    }
    
    .chart-wrapper {
        height: 250px;
    }
    
    .chart-container,
    .recent-activity,
    .notifications-section {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .dashboard-container {
        padding: 0.75rem;
    }
    
    .dashboard-header {
        padding: 1rem;
        border-radius: 12px;
    }
    
    .dashboard-title {
        font-size: 1.5rem;
    }
    
    .dashboard-subtitle {
        font-size: 0.9rem;
    }
    
    .stat-card {
        padding: 1.5rem;
    }
    
    .stat-number {
        font-size: 1.75rem;
    }
    
    .chart-wrapper {
        height: 200px;
    }
}
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">📊 Dashboard Admin</h1>
        <p class="dashboard-subtitle">Selamat datang di panel administrasi sistem absensi</p>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-header">
            <h3>🔍 Filter & Pencarian</h3>
            <button type="button" id="toggleFilters" class="btn-toggle">
                <span id="toggleText">Tampilkan Filter</span>
                <svg id="toggleIcon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                </svg>
            </button>
        </div>
        <div class="filter-content" id="filterContent" style="display: none;">
            <form method="GET" id="filterForm">
                <input type="hidden" name="page" value="dashboard">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="filter_date_start">📅 Tanggal Mulai</label>
                        <input type="date" id="filter_date_start" name="filter_date_start" 
                               value="<?php echo $_GET['filter_date_start'] ?? date('Y-m-01'); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filter_date_end">📅 Tanggal Akhir</label>
                        <input type="date" id="filter_date_end" name="filter_date_end" 
                               value="<?php echo $_GET['filter_date_end'] ?? date('Y-m-d'); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filter_department">🏢 Departemen</label>
                        <select id="filter_department" name="filter_department">
                            <option value="">Semua Departemen</option>
                            <?php
                             $dept_query = "SELECT DISTINCT jabatan FROM tbl_user WHERE jabatan IS NOT NULL AND jabatan != ''";
                             $dept_result = $pdo->query($dept_query);
                             while($dept = $dept_result->fetch()) {
                                 $selected = ($_GET['filter_department'] ?? '') == $dept['jabatan'] ? 'selected' : '';
                                 echo "<option value='" . htmlspecialchars($dept['jabatan']) . "' $selected>" . htmlspecialchars($dept['jabatan']) . "</option>";
                             }
                             ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_status">📊 Status</label>
                        <select id="filter_status" name="filter_status">
                            <option value="">Semua Status</option>
                            <option value="Hadir" <?php echo ($_GET['filter_status'] ?? '') == 'Hadir' ? 'selected' : ''; ?>>Hadir</option>
                            <option value="Datang" <?php echo ($_GET['filter_status'] ?? '') == 'Datang' ? 'selected' : ''; ?>>Datang</option>
                            <option value="Tidak Hadir" <?php echo ($_GET['filter_status'] ?? '') == 'Tidak Hadir' ? 'selected' : ''; ?>>Tidak Hadir</option>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        🔍 Terapkan Filter
                    </button>
                    <button type="button" onclick="resetFilters()" class="btn btn-secondary">
                        🔄 Reset Filter
                    </button>
                    <button type="button" onclick="exportData()" class="btn btn-success">
                        📊 Export Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <div class="stat-card users">
            <div class="stat-number"><?php echo $total_users; ?></div>
            <div class="stat-label">👥 Total Pengguna<?php echo !empty($filter_department) ? ' (' . $filter_department . ')' : ''; ?></div>
        </div>
        
        <div class="stat-card filtered">
            <div class="stat-number"><?php echo $filtered_attendance; ?></div>
            <div class="stat-label">🔍 Data Terfilter</div>
        </div>
        
        <div class="stat-card today">
            <div class="stat-number"><?php echo $today_attendance; ?></div>
            <div class="stat-label">📅 Absensi Hari Ini</div>
        </div>
        
        <div class="stat-card month">
            <div class="stat-number"><?php echo $month_attendance; ?></div>
            <div class="stat-label">📊 Absensi Bulan Ini</div>
        </div>
        
        <div class="stat-card present">
            <div class="stat-number"><?php echo $present_today; ?></div>
            <div class="stat-label">✅ Hadir Hari Ini</div>
        </div>
        
        <div class="stat-card late">
            <div class="stat-number"><?php echo $late_today; ?></div>
            <div class="stat-label">⏰ Terlambat Hari Ini</div>
        </div>
    </div>

    <!-- Department Statistics -->
    <div class="chart-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; color: #374151; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                🏢 Statistik per Jabatan
            </h3>
            <button id="toggleDeptView" style="padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer;">
                Tampilan Chart
            </button>
        </div>
        
        <!-- Chart View (Initially Hidden) -->
        <div id="deptChartView" style="display: none; height: 400px; margin-bottom: 1rem;">
            <canvas id="departmentChart"></canvas>
        </div>
        
        <!-- Grid View -->
        <div id="deptGridView" class="department-grid">
            <?php foreach ($department_stats as $dept): ?>
            <div class="department-card">
                <div class="department-name"><?php echo htmlspecialchars($dept['jabatan']); ?></div>
                <div class="department-stats">
                    <span><?php echo $dept['total_employees']; ?> Karyawan</span>
                    <span><?php echo $dept['total_attendance']; ?> Absensi</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="chart-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; color: #374151; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                📈 Grafik Absensi Bulanan
            </h3>
            <select id="chartTypeSelector" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem;">
                <option value="line">Line Chart</option>
                <option value="bar">Bar Chart</option>
                <option value="area">Area Chart</option>
            </select>
        </div>
        <div class="chart-wrapper">
            <canvas id="attendanceChart"></canvas>
        </div>
        
        <!-- Chart Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold;" id="totalAttendance">0</div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Total Absensi</div>
            </div>
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold;" id="avgAttendance">0</div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Rata-rata Harian</div>
            </div>
            <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold;" id="maxAttendance">0</div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Tertinggi</div>
            </div>
            <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1rem; border-radius: 12px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold;" id="trendIndicator">📈</div>
                <div style="font-size: 0.875rem; opacity: 0.9;">Trend</div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h3>Aktivitas Terbaru</h3>
        <?php if (empty($recent_attendance)): ?>
            <div style="text-align: center; color: #6b7280; padding: 2rem; font-style: italic;">
                📭 Tidak ada aktivitas terbaru
            </div>
        <?php else: ?>
            <?php foreach ($recent_attendance as $activity): ?>
            <div class="activity-item">
                <div class="activity-avatar">
                    <?php echo strtoupper(substr($activity['name'], 0, 1)); ?>
                </div>
                <div class="activity-info">
                    <div class="activity-name"><?php echo htmlspecialchars($activity['name']); ?></div>
                    <div class="activity-details">
                        <?php echo htmlspecialchars($activity['jabatan']); ?> • 
                        Status: <?php echo htmlspecialchars($activity['status_absen']); ?>
                        <?php if ($activity['jam_datang']): ?>
                            • Datang: <?php echo date('H:i', strtotime($activity['jam_datang'])); ?>
                        <?php endif; ?>
                        <?php if ($activity['jam_pulang']): ?>
                            • Pulang: <?php echo date('H:i', strtotime($activity['jam_pulang'])); ?>
                        <?php endif; ?>
                    </div>
                    <div class="activity-time">
                        <?php echo date('d M Y', strtotime($activity['tanggal_absen'])); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Real-time Notifications -->
    <div class="notifications-section">
        <div class="notifications-header">
            <h3>Notifikasi Real-time</h3>
            <div class="notification-badge" id="notificationBadge">0</div>
        </div>
        <div id="notificationsList">
            <div class="loading-notifications">
                Memuat notifikasi...
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart data from PHP
const labels = <?php echo json_encode(array_column($monthly_stats, 'date')); ?>;
const data = <?php echo json_encode(array_map('intval', array_column($monthly_stats, 'count'))); ?>;

// Enhanced Chart functionality
let currentChart = null;
let departmentChart = null;

// Calculate chart statistics
function calculateChartStats() {
    const total = data.reduce((sum, val) => sum + val, 0);
    const avg = data.length > 0 ? Math.round(total / data.length) : 0;
    const max = data.length > 0 ? Math.max(...data) : 0;
    
    // Calculate trend (simple comparison of first half vs second half)
    const midPoint = Math.floor(data.length / 2);
    const firstHalf = data.slice(0, midPoint);
    const secondHalf = data.slice(midPoint);
    const firstAvg = firstHalf.length > 0 ? firstHalf.reduce((sum, val) => sum + val, 0) / firstHalf.length : 0;
    const secondAvg = secondHalf.length > 0 ? secondHalf.reduce((sum, val) => sum + val, 0) / secondHalf.length : 0;
    const trend = secondAvg > firstAvg ? '📈' : secondAvg < firstAvg ? '📉' : '➡️';
    
    // Update statistics
    document.getElementById('totalAttendance').textContent = total;
    document.getElementById('avgAttendance').textContent = avg;
    document.getElementById('maxAttendance').textContent = max;
    document.getElementById('trendIndicator').textContent = trend;
}

// Create enhanced chart with multiple types
function createChart(type = 'line') {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    
    // Destroy existing chart
    if (currentChart) {
        currentChart.destroy();
    }
    
    const config = {
        type: type,
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Absensi',
                data: data,
                borderColor: '#667eea',
                backgroundColor: type === 'area' ? 
                    'rgba(102, 126, 234, 0.3)' : 
                    type === 'bar' ? 
                        'rgba(102, 126, 234, 0.8)' :
                        'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: type === 'area' || type === 'line',
                tension: type === 'line' || type === 'area' ? 0.4 : 0,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: type === 'bar' ? 0 : 6,
                pointHoverRadius: type === 'bar' ? 0 : 8,
                borderCapStyle: 'round',
                borderJoinStyle: 'round'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            return `Tanggal ${context[0].label}`;
                        },
                        label: function(context) {
                            return `Absensi: ${context.parsed.y} orang`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12
                        },
                        padding: 10
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12
                        },
                        padding: 10
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    };
    
    currentChart = new Chart(ctx, config);
    calculateChartStats();
}

// Create department chart
function createDepartmentChart() {
    const deptData = <?php echo json_encode($department_stats); ?>;
    const deptLabels = deptData.map(dept => dept.jabatan);
    const deptValues = deptData.map(dept => parseInt(dept.total_attendance));
    
    const ctx = document.getElementById('departmentChart').getContext('2d');
    
    if (departmentChart) {
        departmentChart.destroy();
    }
    
    departmentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: deptLabels,
            datasets: [{
                data: deptValues,
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(240, 147, 251, 0.8)',
                    'rgba(79, 172, 254, 0.8)',
                    'rgba(67, 233, 123, 0.8)',
                    'rgba(247, 112, 154, 0.8)',
                    'rgba(255, 154, 158, 0.8)'
                ],
                borderColor: [
                    '#667eea',
                    '#f093fb',
                    '#4facfe',
                    '#43e97b',
                    '#f7709a',
                    '#ff9a9e'
                ],
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                duration: 1000
            }
        }
    });
}

// Chart type selector
document.getElementById('chartTypeSelector').addEventListener('change', function() {
    const selectedType = this.value;
    createChart(selectedType);
});

// Department view toggle
document.getElementById('toggleDeptView').addEventListener('click', function() {
    const chartView = document.getElementById('deptChartView');
    const gridView = document.getElementById('deptGridView');
    const button = this;
    
    if (chartView.style.display === 'none') {
        chartView.style.display = 'block';
        gridView.style.display = 'none';
        button.textContent = 'Tampilan Grid';
        createDepartmentChart();
    } else {
        chartView.style.display = 'none';
        gridView.style.display = 'block';
        button.textContent = 'Tampilan Chart';
    }
});

// Initialize chart
createChart('line');

// Notification functions
function loadNotifications() {
    // Simulate loading notifications
    setTimeout(() => {
        const notifications = [
            {
                icon: '👤',
                title: 'Absensi Baru',
                message: 'John Doe telah melakukan absensi masuk',
                time: '2 menit yang lalu'
            },
            {
                icon: '⏰',
                title: 'Keterlambatan',
                message: 'Jane Smith terlambat 15 menit',
                time: '5 menit yang lalu'
            },
            {
                icon: '📊',
                title: 'Laporan Harian',
                message: 'Laporan absensi hari ini telah dibuat',
                time: '1 jam yang lalu'
            }
        ];
        
        updateNotifications(notifications);
    }, 1000);
}

function updateNotifications(notifications) {
    const notificationsList = document.getElementById('notificationsList');
    const badge = document.getElementById('notificationBadge');
    
    badge.textContent = notifications.length;
    
    if (notifications.length === 0) {
        notificationsList.innerHTML = '<div class="no-notifications">Tidak ada notifikasi baru</div>';
        return;
    }
    
    notificationsList.innerHTML = notifications.map(notification => `
        <div class="notification-item">
            <div class="notification-icon">${notification.icon}</div>
            <div class="notification-content">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
            </div>
            <div class="notification-time">${notification.time}</div>
        </div>
    `).join('');
}

// Auto-refresh notifications
function autoRefreshNotifications() {
    loadNotifications();
    setTimeout(autoRefreshNotifications, 30000); // Refresh every 30 seconds
}

// Auto-refresh dashboard
function autoRefreshDashboard() {
    // Refresh chart data
    setTimeout(() => {
        window.location.reload();
    }, 300000); // Refresh every 5 minutes
}

// Filter toggle functionality
document.getElementById('toggleFilters').addEventListener('click', function() {
    const content = document.getElementById('filterContent');
    const text = document.getElementById('toggleText');
    const icon = document.getElementById('toggleIcon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        text.textContent = 'Sembunyikan Filter';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        text.textContent = 'Tampilkan Filter';
        icon.style.transform = 'rotate(0deg)';
    }
});

// Reset filters
function resetFilters() {
    document.getElementById('filter_date_start').value = '<?php echo date('Y-m-01'); ?>';
    document.getElementById('filter_date_end').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('filter_department').value = '';
    document.getElementById('filter_status').value = '';
    document.getElementById('filterForm').submit();
}

// Export data
function exportData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '?' + params.toString();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    autoRefreshNotifications();
    autoRefreshDashboard();
});
</script>