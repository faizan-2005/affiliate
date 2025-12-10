<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 border-r border-gray-700">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-blue-500">Admin Panel</h1>
            </div>
            <nav class="space-y-2 p-4">
                <a href="/admin/dashboard" class="block px-4 py-2 rounded bg-blue-600">Dashboard</a>
                <a href="/admin/users" class="block px-4 py-2 rounded hover:bg-gray-700">Users</a>
                <a href="/admin/offers" class="block px-4 py-2 rounded hover:bg-gray-700">Offers</a>
                <a href="/admin/affiliates" class="block px-4 py-2 rounded hover:bg-gray-700">Affiliates</a>
                <a href="/admin/advertisers" class="block px-4 py-2 rounded hover:bg-gray-700">Advertisers</a>
                <a href="/admin/reports" class="block px-4 py-2 rounded hover:bg-gray-700">Reports</a>
                <a href="/admin/fraud" class="block px-4 py-2 rounded hover:bg-gray-700">Fraud Monitoring</a>
                <a href="/admin/settings" class="block px-4 py-2 rounded hover:bg-gray-700">Settings</a>
                <a href="/logout" class="block px-4 py-2 rounded hover:bg-gray-700 text-red-400">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-8">
                <h2 class="text-3xl font-bold mb-8">Platform Overview</h2>

                <!-- Stats Grid -->
                <div class="grid grid-cols-4 gap-4 mb-8">
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <p class="text-gray-400 text-sm">Total Users</p>
                        <p class="text-3xl font-bold text-blue-400"><?= $stats['total_users'] ?? 0 ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <p class="text-gray-400 text-sm">Total Affiliates</p>
                        <p class="text-3xl font-bold text-green-400"><?= $stats['total_affiliates'] ?? 0 ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <p class="text-gray-400 text-sm">Total Advertisers</p>
                        <p class="text-3xl font-bold text-yellow-400"><?= $stats['total_advertisers'] ?? 0 ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <p class="text-gray-400 text-sm">Active Offers</p>
                        <p class="text-3xl font-bold text-purple-400"><?= $stats['total_offers'] ?? 0 ?></p>
                    </div>
                </div>

                <!-- Today's Performance -->
                <div class="grid grid-cols-3 gap-4 mb-8">
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <h3 class="text-lg font-bold mb-2">Today's Clicks</h3>
                        <p class="text-4xl font-bold text-blue-400"><?= $stats['today_clicks'] ?? 0 ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <h3 class="text-lg font-bold mb-2">Today's Conversions</h3>
                        <p class="text-4xl font-bold text-green-400"><?= $stats['today_conversions'] ?? 0 ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <h3 class="text-lg font-bold mb-2">Today's Payout</h3>
                        <p class="text-4xl font-bold text-yellow-400">$<?= number_format($stats['today_payout'] ?? 0, 2) ?></p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <h3 class="text-xl font-bold mb-4">Quick Actions</h3>
                        <button class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded mb-2">
                            Create New Offer
                        </button>
                        <button class="w-full bg-green-600 hover:bg-green-700 px-4 py-2 rounded mb-2">
                            Approve Affiliates
                        </button>
                        <button class="w-full bg-yellow-600 hover:bg-yellow-700 px-4 py-2 rounded">
                            Process Payouts
                        </button>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <h3 class="text-xl font-bold mb-4">System Status</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Database</span>
                                <span class="text-green-400">✓ Connected</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Redis Cache</span>
                                <span class="text-yellow-400">⚠ Optional</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Email Service</span>
                                <span class="text-blue-400">○ Configured</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
