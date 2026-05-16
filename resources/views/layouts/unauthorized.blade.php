<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unauthorized Access</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">   

            <!-- Error Message -->
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Unauthorized Access</h1>
            <p class="text-gray-600 mb-6">You don't have permission to access this page.</p>

            <!-- Action Button -->
            <a href="{{ url('/dashboard') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded transition-colors duration-200">
                Go to Home
            </a>
        </div>
    </div>
</body>
</html>