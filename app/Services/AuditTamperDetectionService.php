<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditTamperDetectionService
{
    public function verifyIntegrity(AuditLog $auditLog): bool
    {
        return $auditLog->verifyChecksum();
    }
}
