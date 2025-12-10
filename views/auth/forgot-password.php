<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Affiliate Tracking Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900">
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="bg-gray-800 rounded-lg shadow-lg p-8">
                <h1 class="text-2xl font-bold text-white mb-6 text-center">
                    Reset Password
                </h1>

                <?php if (isset($message)): ?>
                    <div class="bg-green-500 text-white p-4 rounded mb-4">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/forgot-password" class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Email Address</label>
                        <input type="email" name="email" required 
                            class="w-full px-4 py-2 bg-gray-700 text-white rounded border border-gray-600">
                    </div>

                    <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Send Reset Link
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="/login" class="text-blue-500 hover:text-blue-400">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
