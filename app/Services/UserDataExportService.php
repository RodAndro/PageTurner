<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

/**
 * User Data Export Service
 * 
 * GDPR-compliant personal data export functionality
 * Exports user profile, preferences, and metadata
 * 
 * @package App\Services
 */
class UserDataExportService
{
    /**
     * Export user personal data as JSON
     * 
     * @param User $user
     * @return array
     */
    public static function exportPersonalData(User $user): array
    {
        return [
            'export_date' => now()->toIso8601String(),
            'export_version' => '1.0',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name ?? null,
                'suffix' => $user->suffix ?? null,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'account_created' => $user->created_at->toIso8601String(),
                'account_updated' => $user->updated_at->toIso8601String(),
                'last_login' => $user->last_login_at?->toIso8601String() ?? null,
            ],
            'profile' => [
                'date_of_birth' => $user->date_of_birth ?? null,
                'gender' => $user->gender ?? null,
                'country' => $user->country ?? null,
                'state' => $user->state ?? null,
                'city' => $user->city ?? null,
                'postal_code' => $user->postal_code ?? null,
                'address' => $user->address ?? null,
            ],
            'preferences' => [
                'newsletter_subscribed' => $user->newsletter_subscribed ?? false,
                'marketing_emails' => $user->marketing_emails ?? false,
                'notifications_enabled' => $user->notifications_enabled ?? true,
            ],
            'account_status' => [
                'is_active' => !$user->deleted_at,
                'is_verified' => $user->email_verified_at !== null,
                'two_factor_enabled' => $user->two_factor_secret !== null,
            ],
            'data_summary' => [
                'total_orders' => $user->orders()->count(),
                'total_reviews' => $user->reviews()->count(),
                'total_wishlist_items' => $user->wishlistItems()->count() ?? 0,
            ],
        ];
    }

    /**
     * Export with audit information
     * 
     * @param User $user
     * @return array
     */
    public static function exportWithAudit(User $user): array
    {
        $data = self::exportPersonalData($user);
        
        /** @var int|null $authId */
        $authId = Auth::id();
        $data['audit'] = [
            'export_timestamp' => now()->toIso8601String(),
            'export_requested_by' => ($authId !== null && $authId === $user->id) ? 'user' : 'admin',
            'data_format_version' => '1.0',
            'gdpr_compliant' => true,
        ];

        return $data;
    }

    /**
     * Generate downloadable JSON file
     * 
     * @param User $user
     * @return string Path to file
     */
    public static function generateJsonFile(User $user): string
    {
        $data = self::exportWithAudit($user);
        $fileName = "personal_data_{$user->id}_" . now()->format('Y-m-d-His') . ".json";
        $filePath = "exports/" . $fileName;

        Storage::disk('local')->put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Storage::path($filePath);
    }

    /**
     * Calculate total data size
     * 
     * @param User $user
     * @return float Size in MB
     */
    public static function calculateDataSize(User $user): float
    {
        $data = self::exportPersonalData($user);
        $jsonSize = strlen(json_encode($data));
        
        return $jsonSize / 1024 / 1024;
    }
}
