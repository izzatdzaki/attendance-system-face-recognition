<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Tentukan halaman aktif
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4338CA">
    <meta http-equiv="Permissions-Policy" content="camera=*, microphone=*, geolocation=*">
    <title>Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .sidebar {
            transition: all 0.3s ease;
        }
        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .nav-link.active {
            background-color: #4338CA;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                height: 100vh;
                z-index: 50;
                width: 280px;
            }
            .sidebar.open {
                left: 0;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .mobile-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 40;
                display: none;
            }
            .mobile-overlay.show {
                display: block;
            }
            .mobile-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem;
                background: white;
                border-bottom: 1px solid #e5e7eb;
            }
            .hamburger {
                display: flex;
                flex-direction: column;
                cursor: pointer;
                padding: 0.5rem;
            }
            .hamburger span {
                width: 25px;
                height: 3px;
                background: #374151;
                margin: 3px 0;
                transition: 0.3s;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-header {
                display: none;
            }
            .hamburger {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Mobile Header -->
    <div class="mobile-header md:hidden">
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <h1 class="text-lg font-semibold text-gray-800">Admin Panel</h1>
        <div class="flex items-center">
            <img src="../logo.png" alt="Profile" class="h-8 w-8 rounded-full">
        </div>
    </div>
    
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
    
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar bg-indigo-800 text-white w-64 px-4 py-8 no-print" id="sidebar">
            <div class="flex items-center space-x-2 px-4 mb-10">
                <img src="../logo.png" alt="Logo Admin" class="h-10 w-10 rounded-full">
                <span class="text-xl font-semibold">Admin Panel</span>
            </div>
            <nav>
                <div class="space-y-2">
                    <a href="?page=dashboard" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'dashboard' ? 'bg-indigo-900' : 'hover:bg-indigo-700' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="?page=laporan" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'laporan' ? 'bg-indigo-900' : 'hover:bg-indigo-700' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Laporan</span>
                    </a>
                    <a href="?page=users" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'users' ? 'bg-indigo-900' : 'hover:bg-indigo-700' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>Data User</span>
                    </a>
                    <a href="?page=add" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'add' ? 'bg-indigo-900' : 'hover:bg-indigo-700' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>Tambahkan Pengguna</span>
                    </a>
                    <a href="?page=locations" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'locations' ? 'bg-indigo-900' : 'hover:bg-indigo-700' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Pengaturan Lokasi</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto main-content" id="mainContent">
            <!-- Header -->
            <header class="bg-white shadow-sm no-print">
                <div class="flex justify-between items-center px-6 py-4">
                    <h1 class="text-2xl font-semibold text-gray-800">
                        <?php 
                        switch($current_page) {
                            case 'dashboard': echo 'Dashboard'; break;
                            case 'laporan': echo 'Laporan'; break;
                            case 'users': echo 'Data User'; break;
                            case 'add': echo 'Tambahkan User'; break;
                            case 'locations': echo 'Pengaturan Lokasi'; break;
                            default: echo 'Dashboard';
                        }
                        ?>
                    </h1>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <img src="../logo.png" alt="Profile Admin" class="h-10 w-10 rounded-full">
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                                <p class="text-xs text-gray-500">Administrator</p>
                            </div>
                        </div>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Logout
                        </a>
                    </div>
                </div>
            </header>
            <!-- Dynamic Content -->
            <main class="p-6">
                <?php
                switch($current_page) {
                    case 'dashboard':
                        include 'views/dashboard.php';
                        break;
                    case 'laporan':
                        include 'views/laporan.php';
                        break;
                    case 'users':
                        include 'views/users.php';
                        break;
                    case 'add':
                        include 'views/add.php';
                        break;
                    case 'locations':
                        include 'views/locations.php';
                        break;   
                    default:
                        include 'views/dashboard.php';
                }
                ?>
            </main>
        </div>
    </div>
    
    <script>
        // Mobile menu functionality
        const hamburger = document.getElementById('hamburger');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        function toggleMobileMenu() {
            sidebar.classList.toggle('open');
            mobileOverlay.classList.toggle('show');
        }
        
        function closeMobileMenu() {
            sidebar.classList.remove('open');
            mobileOverlay.classList.remove('show');
        }
        
        if (hamburger) {
            hamburger.addEventListener('click', toggleMobileMenu);
        }
        
        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', closeMobileMenu);
        }
        
        // Close mobile menu when clicking on navigation links
        const navLinks = document.querySelectorAll('.sidebar a');
        navLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
        
        // Close mobile menu on window resize if screen becomes larger
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
        
        // Logout functionality
        document.querySelector('header .items-center').addEventListener('click', function(e) {
            if (e.target.closest('[data-logout]')) {
                fetch('logout.php', {
                    method: 'POST'
                }).then(response => {
                    if (response.ok) {
                        window.location.href = 'login.php';
                    }
                });
            }
        });
    </script>
</body>
</html>
