#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ExportLog;
use App\Jobs\ProcessExport;
use Illuminate\Support\Facades\Auth;

echo "Full Export Workflow Test\n";
echo "========================\n\n";

// 1. Get user
$user = User::query()->first(['*']);
if (!$user) {
    echo "✗ No user found\n";
    exit(1);
}
Auth::login($user);
echo "✓ User: " . $user->email . "\n\n";

// 2. Create export log
echo "Creating export log...\n";
$filters = ['status' => '', 'date_from' => '', 'date_to' => ''];
$selectedColumns = ['id', 'order_number', 'total_amount', 'status'];

try {
    $exportLog = ExportLog::create([
        'user_id' => $user->id,
        'module_type' => 'orders',
        'export_format' => 'csv',
        'file_name' => 'orders_export_' . time() . '.csv',
        'file_path' => 'exports/orders_export_' . time() . '.csv',
        'status' => 'pending',
        'total_records' => 0,
        'filters' => $filters,
        'selected_columns' => $selectedColumns,
    ]);
    echo "✓ Export log created: ID " . $exportLog->id . "\n";
    echo "  - File: " . $exportLog->file_name . "\n";
    echo "  - Status: " . $exportLog->status . "\n\n";
} catch (\Exception $e) {
    echo "✗ Error creating export log: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Process export synchronously
echo "Processing export...\n";
try {
    ProcessExport::dispatchSync($exportLog);
    
    // Refresh to see updated status
    $exportLog->refresh();
    
    echo "✓ Export processed\n";
    echo "  - Status: " . $exportLog->status . "\n";
    echo "  - Total Records: " . $exportLog->total_records . "\n";
    echo "  - File Path: " . $exportLog->file_path . "\n\n";
    
} catch (\Exception $e) {
    echo "✗ Error processing export: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    
    // Try to update the export log with error
    try {
        $exportLog->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
        echo "✓ Export log updated with error status\n";
    } catch (\Exception $e2) {
        echo "✗ Could not update export log: " . $e2->getMessage() . "\n";
    }
}

echo "\n========================\n";
echo "Export workflow test complete\n";
echo "========================\n";
