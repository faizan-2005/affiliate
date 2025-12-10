<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payouts</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="p-8">
        <h2 class="text-3xl font-bold mb-6">Payouts</h2>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 mb-6">
            <h3 class="text-xl font-bold mb-2">Payment Information</h3>
            <p class="text-gray-400">Method: <strong><?= ucwords(str_replace('_', ' ', $affiliate->payout_method)) ?></strong></p>
            <p class="text-gray-400">Email: <strong><?= $affiliate->payout_email ?></strong></p>
        </div>

        <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left">Amount</th>
                        <th class="px-6 py-3 text-left">Method</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payouts as $payout): ?>
                        <tr class="border-t border-gray-700 hover:bg-gray-700">
                            <td class="px-6 py-3 font-bold text-green-400">$<?= number_format($payout->amount, 2) ?></td>
                            <td class="px-6 py-3"><?= ucwords(str_replace('_', ' ', $payout->method)) ?></td>
                            <td class="px-6 py-3">
                                <span class="px-3 py-1 rounded text-sm
                                    <?= $payout->status === 'paid' ? 'bg-green-900 text-green-200' : 
                                        ($payout->status === 'pending' ? 'bg-yellow-900 text-yellow-200' : 'bg-gray-700') ?>">
                                    <?= ucfirst($payout->status) ?>
                                </span>
                            </td>
                            <td class="px-6 py-3"><?= $payout->created_at ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
