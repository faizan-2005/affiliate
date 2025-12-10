<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Offers</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="p-8">
        <h2 class="text-3xl font-bold mb-6">Available Offers</h2>

        <div class="grid grid-cols-3 gap-4">
            <?php foreach ($offers as $offer): ?>
                <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 hover:border-blue-500">
                    <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($offer['name']) ?></h3>
                    <p class="text-gray-400 text-sm mb-4"><?= htmlspecialchars($offer['description']) ?></p>
                    
                    <div class="mb-4">
                        <p class="text-green-400 font-bold">
                            Payout: <?= ucfirst($offer['payout_type']) ?> - 
                            $<?= number_format($offer['payout_value'], 2) ?>
                        </p>
                    </div>

                    <button onclick="copyTrackingLink(<?= $offer['id'] ?>)" 
                        class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">
                        Get Tracking Link
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function copyTrackingLink(offerId) {
        const affiliateId = <?= $affiliate->id ?? 0 ?>;
        const link = `<?= config('app.url') ?>/click?offer_id=${offerId}&aff_id=${affiliateId}&click_id=[CLICK_ID]`;
        navigator.clipboard.writeText(link).then(() => {
            alert('Tracking link copied to clipboard!');
        });
    }
    </script>
</body>
</html>
