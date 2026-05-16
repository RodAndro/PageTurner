#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\ImportExportController;

echo "Testing Export Endpoint\n";
echo "=======================\n\n";

// Get or create a user
$user = User::query()->first(['*']);
if (!$user) {
    echo "✗ No user found\n";
    exit(1);
}

// Simulate authentication
Auth::login($user);
echo "✓ User authenticated: " . $user->email . "\n\n";

// Create a request object
$request = Request::create(
    '/admin/import-export/orders/export',
    'POST',
    [
        'format' => 'csv',
        'status' => '',
        'date_from' => '',
        'date_to' => '',
        'columns' => ['id', 'order_number', 'total_amount', 'status'],
    ]
);

$request->setUserResolver(function () use ($user) {
    return $user;
});

// Call the controller method
$controller = new ImportExportController();
try {
    echo "Submitting export request...\n";
    $response = $controller->exportOrders($request);
    
    echo "\n✓ Export request processed\n";
    echo "Response: " . $response->getTargetUrl() . "\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n=======================\n";
echo "Export test completed ✓\n";
echo "=======================\n";
