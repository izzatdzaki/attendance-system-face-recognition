<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar bg-indigo-800 text-white w-64 px-4 py-8">
            <div class="flex items-center space-x-2 px-4 mb-10">
                <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/7e8338fe-ab39-44eb-89fb-efb1c278aa28.png" alt="Logo Admin" class="h-10 w-10 rounded-full">
                <span class="text-xl font-semibold">Admin Panel</span>
            </div>
            <nav>
                <div class="space-y-2">
                    <a href="#" class="flex items-center px-4 py-3 bg-indigo-900 rounded-lg">
                        <span>Dashboard</span>
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 hover:bg-indigo-700 rounded-lg">
                        <span>Pengguna</span>
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 hover:bg-indigo-700 rounded-lg">
                        <span>Produk</span>
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 hover:bg-indigo-700 rounded-lg">
                        <span>Laporan</span>
                    </a>
                    <a href="#" class="flex items-center px-4 py-3 hover:bg-indigo-700 rounded-lg">
                        <span>Pengaturan</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="flex justify-between items-center px-6 py-4">
                    <h1 class="text-2xl font-semibold text-gray-800">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-1 rounded-full text-gray-500 hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </button>
                        <div class="flex items-center">
                            <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/ca720f28-288f-43c9-af61-e7f305583f19.png" alt="Profile Admin" class="h-10 w-10 rounded-full">
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($admin_username); ?></p>
                                <p class="text-xs text-gray-500">Administrator</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="dashboard-card bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between">
                            <div>
                                <p class="text-gray-500">Total Pengguna</p>
                                <h3 class="text-3xl font-bold text-gray-800">1,248</h3>
                            </div>
                            <div class="bg-indigo learned-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between">
                            <div>
                                <p class="text-gray-500">Total Produk</p>
                                <h3 class="text-3xl font-bold text-gray-800">754</h3>
                            </div>
                            <div class="bg-indigo-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between">
                            <div>
                                <p class="text-gray-500">Pesanan Hari Ini</p>
                                <h3 class="text-3xl font-bold text-gray-800">56</h3>
                            </div>
                            <div class="bg-indigo-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between">
                            <div>
                                <p class="text-gray-500">Pendapatan</p>
                                <h3 class="text-3xl font-bold text-gray-800">$12,489</h3>
                            </div>
                            <div class="bg-indigo-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Aktivitas Terbaru</h2>
                        <button class="text-sm text-indigo-600 hover:text-indigo-800">Lihat Semua</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktivitas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full" src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/5e9438a3-6148-4803-811f-d400ffffa543.png" alt="User avatar">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">Pembaruan produk baru</div>
                                                <div class="text-sm text-gray-500">oleh Admin</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">10 menit lalu</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full" src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/4c211e3e-5d2e-47b0-85bc-a5a3699e5bf4.png" alt="User avatar">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">Pengguna baru terdaftar</div>
                                                <div class="text-sm text-gray-500">oleh Sistem</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 jam lalu</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        // Logout functionality
        document.querySelector('header .items-center').addEventListener('click', function(e) {
            if (e.target.closest('[data-logout]')) {
                fetch('logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
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

