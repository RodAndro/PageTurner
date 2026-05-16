#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExportLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "Testing Export Functionality\n";
echo "============================\n\n";

// Test 1: Check if table exists
echo "1. Checking if export_logs table exists...\n";
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='export_logs'");
if (count($tables) > 0) {
    echo "   ✓ export_logs table exists\n\n";
} else {
    echo "   ✗ export_logs table NOT found\n\n";
    exit(1);
}

// Test 2: Get or create a test user
echo "2. Getting a test user...\n";
$user = User::query()->first(['*']);
if (!$user) {
    echo "   ✗ No users found in database\n\n";
    exit(1);
}
echo "   ✓ Using user ID: " . $user->id . "\n\n";

// Test 3: Create an export log
echo "3. Creating an export log...\n";
try {
    $filePath = 'exports/orders_' . time() . '.csv';
    $log = ExportLog::create([
        'user_id' => $user->id,
        'module_type' => 'orders',
        'export_format' => 'csv',
        'file_name' => 'orders_' . time() . '.csv',
        'file_path' => $filePath,
        'status' => 'pending',
        'filters' => [],
        'selected_columns' => ['id', 'user_id', 'total_amount'],
    ]);
    echo "   ✓ Export log created successfully\n";
    echo "   - ID: " . $log->id . "\n";
    echo "   - Module Type: " . $log->module_type . "\n";
    echo "   - Status: " . $log->status . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Error creating export log: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Query the created log
echo "4. Querying the created export log...\n";
try {
    $retrieved = ExportLog::query()->find($log->id, ['*']);
    if ($retrieved) {
        echo "   ✓ Export log retrieved successfully\n";
        echo "   - ID: " . $retrieved->id . "\n";
        echo "   - Module Type: " . $retrieved->module_type . "\n";
        echo "   - Status: " . $retrieved->status . "\n\n";
    } else {
        echo "   ✗ Export log not found\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ✗ Error retrieving export log: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 5: Count total records
echo "5. Counting total export logs...\n";
try {
    $count = ExportLog::query()->count('id');
    echo "   ✓ Total export logs: " . $count . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Error counting export logs: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "============================\n";
echo "All export tests passed! ✓\n";
echo "============================\n";
