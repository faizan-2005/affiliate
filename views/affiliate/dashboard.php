<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliate Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 border-r border-gray-700">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-blue-500">Affiliate Panel</h1>
            </div>
            <nav class="space-y-2 p-4">
                <a href="/affiliate/dashboard" class="block px-4 py-2 rounded bg-blue-600">Dashboard</a>
                <a href="/affiliate/offers" class="block px-4 py-2 rounded hover:bg-gray-700">Offers</a>
                <a href="/affiliate/reports" class="block px-4 py-2 rounded hover:bg-gray-700">Reports</a>
                <a href="/affiliate/payouts" class="block px-4 py-2 rounded hover:bg-gray-700">Payouts</a>
                <a href="/logout" class="block px-4 py-2 rounded hover:bg-gray-700 text-red-400">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-8">
                <h2 class="text-3xl font-bold mb-8">Dashboard</h2>

                <!-- Stats Grid -->
                <div class="grid grid-cols-4 gap-4 mb-8">
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <p class="text-gray-400 text-sm">Today's Clicks</p>
                        <p class="text-3xl font-bold text-blue-400"><?= $todayStats['clicks'] ?? 0 ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <p class="text-gray-400 text-sm">Today's Conversions</p>
                        <p class="text-3xl font-bold text-green-400"><?= $todayStats['conversions'] ?? 0 ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <p class="text-gray-400 text-sm">Today's Earnings</p>
                        <p class="text-3xl font-bold text-yellow-400">$<?= number_format($todayStats['earnings'] ?? 0, 2) ?></p>
                    </div>
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <p class="text-gray-400 text-sm">Month's Earnings</p>
                        <p class="text-3xl font-bold text-purple-400">$<?= number_format($monthlyStats['earnings'] ?? 0, 2) ?></p>
                    </div>
                </div>

                <!-- API Credentials -->
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                    <h3 class="text-xl font-bold mb-4">Tracking Code</h3>
                    <p class="text-gray-400 text-sm mb-2">Use this to track clicks:</p>
                    <div class="bg-gray-900 p-4 rounded font-mono text-sm overflow-x-auto">
                        <?= config('app.url') ?>/click?offer_id=OFFER_ID&aff_id=<?= $affiliate->id ?>&click_id=[CLICK_ID]&sub1=[SUB1]
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
