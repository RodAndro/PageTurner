<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    protected $filters = [];
    protected $selectedColumns = [];
    protected $redactPII = false;

    public function __construct($filters = [], $selectedColumns = [], $redactPII = false)
    {
        $this->filters = is_string($filters) ? json_decode($filters, true) : $filters;
        $this->selectedColumns = is_string($selectedColumns) ? json_decode($selectedColumns, true) : ($selectedColumns ?: $this->defaultColumns());
        $this->redactPII = $redactPII;
    }

    public function query()
    {
        $query = User::query();

        // Apply role filter
        if (!empty($this->filters['role'])) {
            $query->where('role', $this->filters['role']);
        }

        // Apply date range filter
        if (!empty($this->filters['created_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['created_from'], 'and');
        }
        if (!empty($this->filters['created_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['created_to'], 'and');
        }

        // Apply verification status filter
        if (!empty($this->filters['verified_only'])) {
            $query->whereNotNull('email_verified_at', 'and');
        }

        return $query;
    }

    public function headings(): array
    {
        $headingMap = [
            'id' => 'User ID',
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'email_verified' => 'Email Verified',
            'email_verified_at' => 'Email Verified Date',
            'two_factor_enabled' => '2FA Enabled',
            'created_at' => 'Registration Date',
            'updated_at' => 'Last Updated',
            'last_login' => 'Last Active',
        ];

        return array_map(fn($col) => $headingMap[$col] ?? ucfirst($col), $this->selectedColumns);
    }

    public function map($user): array
    {
        $data = [];
        foreach ($this->selectedColumns as $column) {
            $value = match($column) {
                'id' => $user->id,
                'name' => $this->redactPII ? $this->redactName($user->name) : $user->name,
                'email' => $this->redactPII ? $this->redactEmail($user->email) : $user->email,
                'role' => ucfirst($user->role),
                'email_verified' => $user->email_verified_at ? 'Yes' : 'No',
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i') ?? 'N/A',
                'two_factor_enabled' => $user->two_factor_enabled ? 'Yes' : 'No',
                'created_at' => $user->created_at->format('Y-m-d'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i'),
                'last_login' => $user->last_login_at?->format('Y-m-d H:i') ?? 'N/A',
                default => '',
            };
            $data[] = $value;
        }
        return $data;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Redact user name for GDPR compliance
     */
    private function redactName(string $name): string
    {
        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            $parts[0] = substr($parts[0], 0, 1) . '***';
            $parts[count($parts) - 1] = substr($parts[count($parts) - 1], 0, 1) . '***';
        } else {
            $parts[0] = substr($parts[0], 0, 1) . '***';
        }
        return implode(' ', $parts);
    }

    /**
     * Redact email for GDPR compliance
     */
    private function redactEmail(string $email): string
    {
        $parts = explode('@', $email);
        $localPart = substr($parts[0], 0, 2) . '***';
        return $localPart . '@***';
    }

    private function defaultColumns(): array
    {
        return ['id', 'name', 'email', 'role', 'email_verified', 'created_at'];
    }
}
