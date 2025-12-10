<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="p-8">
        <h2 class="text-3xl font-bold mb-6">Reports</h2>

        <div class="mb-4">
            <a href="?days=7" class="px-4 py-2 bg-gray-800 rounded hover:bg-gray-700">Last 7 Days</a>
            <a href="?days=30" class="px-4 py-2 bg-gray-800 rounded hover:bg-gray-700">Last 30 Days</a>
            <a href="?days=90" class="px-4 py-2 bg-gray-800 rounded hover:bg-gray-700">Last 90 Days</a>
        </div>

        <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-left">Clicks</th>
                        <th class="px-6 py-3 text-left">Conversions</th>
                        <th class="px-6 py-3 text-left">Conv. Rate</th>
                        <th class="px-6 py-3 text-left">Earnings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat): ?>
                        <tr class="border-t border-gray-700 hover:bg-gray-700">
                            <td class="px-6 py-3"><?= $stat['date'] ?></td>
                            <td class="px-6 py-3"><?= $stat['clicks'] ?></td>
                            <td class="px-6 py-3"><?= $stat['conversions'] ?></td>
                            <td class="px-6 py-3">
                                <?= $stat['clicks'] > 0 ? number_format(($stat['conversions'] / $stat['clicks']) * 100, 2) : 0 ?>%
                            </td>
                            <td class="px-6 py-3 font-bold text-green-400">
                                $<?= number_format($stat['earnings'] ?? 0, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
