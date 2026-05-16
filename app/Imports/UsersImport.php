<?php

namespace App\Imports;

use App\Models\User;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class UsersImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, SkipsOnFailure
{
    protected $importLog;
    protected $failureDetails = [];

    public function __construct(ImportLog $importLog)
    {
        $this->importLog = $importLog;
    }

    /**
     * @param array $row
     * @return User|null
     */
    public function model(array $row)
    {
        // Validate required fields
        if (empty($row['email']) || empty($row['first_name'])) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => 'Missing required fields (Email or First Name)',
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Validate email format
        if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => "Invalid email format: {$row['email']}",
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Check for duplicate email
        $existingUser = User::where('email', $row['email'])->first();
        if ($existingUser) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => "Duplicate email found: {$row['email']}",
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Validate role if provided
        $role = $row['role'] ?? 'customer';
        if (!in_array($role, ['admin', 'customer', 'visitor'])) {
            $this->failureDetails[] = [
                'row' => $this->importLog->successful_rows + $this->importLog->failed_rows + 1,
                'error' => "Invalid role: {$role}. Must be 'admin', 'customer', or 'visitor'",
                'data' => $row,
            ];
            $this->importLog->increment('failed_rows');
            return null;
        }

        // Generate password if not provided
        $password = $row['password'] ?? bin2hex(random_bytes(12));

        $this->importLog->increment('successful_rows');

        return new User([
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'] ?? 'User',
            'suffix' => $row['suffix'] ?? null,
            'email' => $row['email'],
            'password' => Hash::make($password),
            'role' => $role,
            'email_verified_at' => now(),
        ]);
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failureDetails[] = [
                'row' => $failure->row(),
                'error' => implode(', ', $failure->errors()),
                'attribute' => $failure->attribute(),
            ];
        }
    }

    public function getFailureDetails()
    {
        return $this->failureDetails;
    }
}
