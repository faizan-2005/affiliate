<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Affiliate Tracking Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900">
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="bg-gray-800 rounded-lg shadow-lg p-8">
                <h1 class="text-3xl font-bold text-white mb-6 text-center">
                    Affiliate Tracking
                </h1>
                
                <?php if (isset($error)): ?>
                    <div class="bg-red-500 text-white p-4 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login" class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Email</label>
                        <input 
                            type="email" 
                            name="email" 
                            required 
                            class="w-full px-4 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                        >
                        <?php if (isset($errors['email'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= $errors['email'][0] ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-gray-300 mb-2">Password</label>
                        <input 
                            type="password" 
                            name="password" 
                            required 
                            class="w-full px-4 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                        >
                        <?php if (isset($errors['password'])): ?>
                            <p class="text-red-500 text-sm mt-1"><?= $errors['password'][0] ?></p>
                        <?php endif; ?>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition"
                    >
                        Login
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-400">
                        Don't have an account? 
                        <a href="/register" class="text-blue-500 hover:text-blue-400">Register here</a>
                    </p>
                </div>

                <div class="mt-4 text-center">
                    <a href="/forgot-password" class="text-gray-500 hover:text-gray-400 text-sm">
                        Forgot password?
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
