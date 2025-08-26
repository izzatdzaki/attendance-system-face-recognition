<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Tentukan halaman aktif
$current_page = isset($_GET['page']) ? $_GET['page'] : 'laporan';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar bg-indigo-800 text-white w-64 px-4 py-8 no-print">
            <div class="flex items-center space-x-2 px-4 mb-10">
                <img src="../logo.png" alt="Logo Admin" class="h-10 w-10 rounded-full">
                <span class="text-xl font-semibold">Admin Panel</span>
            </div>
            <nav>
                <div class="space-y-2">
                    <a href="?page=laporan" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'laporan' ? 'bg-indigo-900' : 'hover:bg-indigo-700' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Laporan</span>
                    </a>
                    <a href="?page=add" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'add' ? 'bg-indigo-900' : 'hover:bg-indigo-700' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>Tambahkan Pengguna</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm no-print">
                <div class="flex justify-between items-center px-6 py-4">
                    <h1 class="text-2xl font-semibold text-gray-800">
                        <?php 
                        switch($current_page) {
                            case 'laporan': echo 'Laporan'; break;
                            case 'users': echo 'Manajemen Pengguna'; break;
                            case 'add': echo 'Tambahkan User'; break;
                            default: echo 'Laporan';
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
                    </div>
                </div>
            </header>
            <!-- Dynamic Content -->
            <main class="p-6">
                <?php
                switch($current_page) {
                    case 'laporan':
                        include 'views/laporan.php';
                        break;
                    case 'users':
                        include 'views/users.php';
                        break;
                    case 'add':
                        include 'views/add.php';
                        break;   
                    default:
                        include 'views/laporan.php';
                }
                ?>
            </main>
        </div>
    </div>
    
    <script>
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
