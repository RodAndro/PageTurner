#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\ExportLog;
use Illuminate\Support\Facades\Auth;

echo "Checking Orders Database\n";
echo "========================\n\n";

// Check order count
$orderCount = Order::query()->count('id');
echo "Total orders: " . $orderCount . "\n";

if ($orderCount === 0) {
    echo "\n❌ No orders found in database. Creating sample orders...\n\n";
    
    // Get a user ID to associate orders
    $user = \App\Models\User::query()->first(['*']);
    if (!$user) {
        echo "❌ No users found. Please register a user first.\n";
        exit(1);
    }
    
    // Create sample orders
    for ($i = 1; $i <= 5; $i++) {
        Order::create([
            'user_id' => $user->id,
            'total_amount' => rand(50, 500),
            'status' => ['Pending', 'Processing', 'Delivered', 'Cancelled'][array_rand(['Pending', 'Processing', 'Delivered', 'Cancelled'])],
        ]);
    }
    
    echo "✓ Created 5 sample orders\n\n";
}

// Verify export log creation
echo "Testing ExportLog Creation\n";
echo "==========================\n\n";

$user = \App\Models\User::query()->first(['*']);

try {
    $log = ExportLog::create([
        'user_id' => $user->id,
        'module_type' => 'orders',
        'export_format' => 'csv',
        'file_name' => 'test_' . time() . '.csv',
        'file_path' => 'exports/test_' . time() . '.csv',
        'status' => 'pending',
        'total_records' => Order::query()->count('id'),
        'filters' => [],
        'selected_columns' => ['id', 'order_number', 'total_amount', 'status'],
    ]);
    
    echo "✓ ExportLog created: ID " . $log->id . "\n";
    echo "✓ Export ready for processing\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "========================\n";
echo "Orders export test ready ✓\n";
echo "========================\n";
