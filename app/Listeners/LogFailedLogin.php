<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;
use App\Models\User;

class LogFailedLogin
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function handle(Failed $event): void
    {
        // Try to find the user
        $user = User::where('email', request('email'))->first();

        if ($user) {
            $this->auditLogService->logLogin($user, false, 'Invalid credentials');
        } else {
            // Log failed attempt even if user doesn't exist (security)
            \App\Models\AuditLog::create([
                'user_id' => null,
                'event' => 'login',
                'description' => "Failed login attempt for email: " . request('email'),
                'level' => 'warning',
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'url' => request()->url(),
                    'method' => request()->method(),
                    'attempted_email' => request('email'),
                ],
                'is_sensitive' => true,
            ]);
        }
    }
}
