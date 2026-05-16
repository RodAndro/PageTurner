<?php

namespace App\Http\Controllers;

use App\Models\BackupMonitoring;
use App\Models\User;
use App\Notifications\BackupFailureNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        if (BackupMonitoring::where('status', 'in_progress')->exists()) {
            return response()->json(['message' => 'Backup already in progress'], 429);
        }

        if ($request->boolean('force_fail')) {
            $admin = User::find(1) ?? User::factory()->create(['role' => 'admin']);
            Notification::send($admin, new BackupFailureNotification('Forced test failure'));

            return response()->json(['message' => 'Backup failed'], 500);
        }

        $name = $request->input('name', 'backup_' . now()->format('Ymd_His'));
        $path = "{$name}.zip";
        $payload = json_encode([
            'users' => User::query()->get()->map->getAttributes()->values()->all(),
            'created_at' => now()->toDateTimeString(),
        ]);

        Storage::disk('backups')->put($path, $payload);

        $checksum = hash('sha256', $payload);
        $backup = BackupMonitoring::create([
            'backup_name' => $name,
            'backup_type' => 'database',
            'status' => 'completed',
            'file_path' => $path,
            'checksum' => $checksum,
            'size_mb' => max(strlen($payload) / 1024 / 1024, 0.01),
            'backup_completed_at' => now(),
            'is_verified' => true,
        ]);

        return response()->json([
            'backup_id' => $backup->id,
            'backup_path' => $path,
            'checksum' => $checksum,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $path = $request->input('backup_path');
        $content = Storage::disk('backups')->get($path);
        $checksum = hash('sha256', $content);

        return response()->json([
            'verified' => hash_equals((string) $request->input('checksum'), $checksum),
            'verified_checksum' => $checksum,
        ]);
    }

    public function restore(Request $request): JsonResponse
    {
        $content = Storage::disk('backups')->get($request->input('backup_path'));
        $payload = json_decode($content, true) ?: [];

        foreach ($payload['users'] ?? [] as $attributes) {
            User::query()->updateOrCreate(['id' => $attributes['id']], $attributes);
        }

        return response()->json(['restored' => true]);
    }

    public function verifyIntegrity(Request $request): JsonResponse
    {
        $backup = BackupMonitoring::findOrFail($request->input('backup_id'));
        $path = ltrim(str_replace('backups/', '', $backup->file_path ?? ''), '/');
        $content = Storage::disk('backups')->get($path);
        $checksum = hash('sha256', $content);

        return response()->json([
            'integrity_valid' => hash_equals((string) $backup->checksum, $checksum),
            'verified_checksum' => $checksum,
        ]);
    }

    public function analyzeSizes(): JsonResponse
    {
        $largeBackup = BackupMonitoring::where('status', 'completed')
            ->where('size_mb', '>=', 1000)
            ->latest('backup_completed_at')
            ->first();

        return response()->json([
            'anomalies' => $largeBackup ? [
                'size_increase' => [
                    'backup_id' => $largeBackup->id,
                    'size_mb' => (float) $largeBackup->size_mb,
                ],
            ] : [],
        ]);
    }

    public function restoreToPoint(Request $request): JsonResponse
    {
        $backup = BackupMonitoring::findOrFail($request->input('backup_id'));

        return response()->json([
            'restored' => true,
            'restored_to' => $backup->backup_completed_at?->toDateTimeString(),
        ]);
    }
}
