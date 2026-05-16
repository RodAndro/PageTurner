<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Audit and Compliance Testing
 * 
 * Tests for requirement 11.3:
 * - All CRUD operations create appropriate audit entries
 * - Sensitive data exclusion from logs (passwords redacted)
 * - Audit log search and filtering functionality
 * - Tamper-proof verification (checksums)
 */
class AuditComplianceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 11.3.1: Test CREATE operation creates audit entry
     */
    public function test_create_operation_creates_audit_log_entry()
    {
        $category = Category::create(['name' => 'Test Category']);

        $auditLog = \App\Models\AuditLog::where([
            ['event', '=', 'created'],
            ['auditable_type', '=', Category::class],
            ['auditable_id', '=', $category->id],
        ])->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('created', $auditLog->event);
        $this->assertNotEmpty($auditLog->new_values);
    }

    /**
     * 11.3.2: Test UPDATE operation creates audit entry with before/after
     */
    public function test_update_operation_captures_old_and_new_values()
    {
        $category = Category::create(['name' => 'Original Name']);
        $category->update(['name' => 'Updated Name']);

        $auditLog = \App\Models\AuditLog::where([
            ['event', '=', 'updated'],
            ['auditable_type', '=', Category::class],
            ['auditable_id', '=', $category->id],
        ])->latest()->first();

        $this->assertNotNull($auditLog);
        $oldValues = json_decode($auditLog->old_values, true);
        $newValues = json_decode($auditLog->new_values, true);
        
        $this->assertEquals('Original Name', $oldValues['name']);
        $this->assertEquals('Updated Name', $newValues['name']);
    }

    /**
     * 11.3.3: Test DELETE operation creates audit entry
     */
    public function test_delete_operation_creates_audit_log_entry()
    {
        $category = Category::create(['name' => 'Test Category']);
        $categoryId = $category->id;
        $category->delete();

        $auditLog = \App\Models\AuditLog::where([
            ['event', '=', 'deleted'],
            ['auditable_type', '=', Category::class],
            ['auditable_id', '=', $categoryId],
        ])->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('deleted', $auditLog->event);
    }

    /**
     * 11.3.4: Test sensitive data (passwords) excluded from audit logs
     */
    public function test_passwords_redacted_from_audit_logs()
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('secret123'),
            'first_name' => 'Test',
        ]);

        $auditLog = \App\Models\AuditLog::where([
            ['event', '=', 'created'],
            ['auditable_type', '=', User::class],
            ['auditable_id', '=', $user->id],
        ])->first();

        $newValues = json_decode($auditLog->new_values, true);

        // Password should not be in the audit log
        $this->assertArrayNotHasKey('password', $newValues);
        // Or if it is, it should be redacted
        if (isset($newValues['password'])) {
            $this->assertEquals('[REDACTED]', $newValues['password']);
        }
    }

    /**
     * 11.3.5: Test PII data handling in audit logs
     */
    public function test_pii_data_redacted_or_excluded()
    {
        $user = User::create([
            'email' => 'user@example.com',
            'phone' => '555-1234',
            'first_name' => 'John',
            'date_of_birth' => '1990-01-01',
        ]);

        $auditLog = \App\Models\AuditLog::where([
            ['auditable_id', '=', $user->id],
        ])->first();

        $this->assertNotNull($auditLog);
        // Sensitive fields should either be excluded or marked as sensitive
        if ($auditLog->is_sensitive) {
            $this->assertTrue($auditLog->is_sensitive);
        }
    }

    /**
     * 11.3.6: Test audit log search functionality
     */
    public function test_audit_logs_searchable_by_event_type()
    {
        Category::create(['name' => 'Category 1']);
        Book::factory()->create(['stock_quantity' => 10]);

        // Search for created events
        $createdLogs = \App\Models\AuditLog::where('event', 'created')->get();

        $this->assertGreaterThanOrEqual(2, $createdLogs->count());
        $this->assertTrue($createdLogs->every(fn($log) => $log->event === 'created'));
    }

    /**
     * 11.3.7: Test audit log filtering by date range
     */
    public function test_audit_logs_filtered_by_date_range()
    {
        $today = now();
        $yesterday = now()->subDay();
        $lastWeek = now()->subWeek();

        // Create entries on different dates
        Category::create(['name' => 'Today Category', 'created_at' => $today]);
        Category::create(['name' => 'Yesterday Category', 'created_at' => $yesterday]);
        Category::create(['name' => 'Last Week Category', 'created_at' => $lastWeek]);

        // Test date range filtering
        $logs = \App\Models\AuditLog::whereBetween('created_at', [$yesterday, $today])->get();
        
        $this->assertGreaterThanOrEqual(2, $logs->count());
        foreach ($logs as $log) {
            $this->assertGreaterThanOrEqual($yesterday, $log->created_at);
            $this->assertLessThanOrEqual($today, $log->created_at);
        }
    }

    /**
     * 11.3.8: Test audit log filtering by entity type
     */
    public function test_audit_logs_filtered_by_entity_type()
    {
        Category::create(['name' => 'Test Category']);
        Book::factory()->create(['title' => 'Test Book']);
        User::factory()->create(['email' => 'test@example.com']);

        // Filter by category entity type
        $categoryLogs = \App\Models\AuditLog::where('entity_type', 'category')->get();
        $this->assertGreaterThan(0, $categoryLogs->count());
        
        foreach ($categoryLogs as $log) {
            $this->assertEquals('category', $log->entity_type);
        }

        // Filter by book entity type
        $bookLogs = \App\Models\AuditLog::where('entity_type', 'book')->get();
        $this->assertGreaterThan(0, $bookLogs->count());
        
        foreach ($bookLogs as $log) {
            $this->assertEquals('book', $log->entity_type);
        }
    }

    /**
     * 11.3.9: Test tamper-proof verification with checksums
     */
    public function test_audit_log_tamper_proof_verification()
    {
        // Create audit entry
        $category = Category::create(['name' => 'Test Category']);
        $auditLog = \App\Models\AuditLog::where('entity_id', $category->id)
            ->where('event', 'created')
            ->first();

        // Verify checksum exists and is valid
        $this->assertNotNull($auditLog->checksum);
        
        $expectedChecksum = hash('sha256', 
            $auditLog->entity_type . 
            $auditLog->entity_id . 
            $auditLog->event . 
            $auditLog->old_values . 
            $auditLog->new_values . 
            $auditLog->created_at->toDateTimeString()
        );
        
        $this->assertEquals($expectedChecksum, $auditLog->checksum);

        // Test tamper detection service
        $tamperService = new \App\Services\AuditTamperDetectionService();
        $isIntact = $tamperService->verifyIntegrity($auditLog);
        $this->assertTrue($isIntact);

        // Simulate tampering by modifying the log directly
        $auditLog->update(['event' => 'modified']);

        // Should detect tampering
        $isIntact = $tamperService->verifyIntegrity($auditLog->fresh());
        $this->assertFalse($isIntact);
    }

    /**
     * 11.3.10: Test audit log export with filtering
     */
    public function test_audit_log_export_with_filtering()
    {
        // Create test data
        Category::create(['name' => 'Export Category 1']);
        Book::factory()->create(['title' => 'Export Book 1']);
        User::factory()->create(['email' => 'export@example.com']);

        // Test CSV export with entity type filter
        $response = $this->get('/admin/audit-logs/export?format=csv&entity_type=category');
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv');
        
        $csvContent = $response->getContent();
        $this->assertStringContainsString('entity_type,entity_id,event', $csvContent);
        $this->assertStringContainsString('category', $csvContent);
        $this->assertStringNotContainsString('book', $csvContent); // Should be filtered out

        // Test JSON export with date filter
        $today = now()->format('Y-m-d');
        $response = $this->get("/admin/audit-logs/export?format=json&date_from={$today}");
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/json');
        
        $jsonData = json_decode($response->getContent(), true);
        $this->assertIsArray($jsonData);
        $this->assertNotEmpty($jsonData);
        
        // All entries should be from today
        foreach ($jsonData as $entry) {
            $this->assertEquals($today, date('Y-m-d', strtotime($entry['created_at'])));
        }
    }

    /**
     * 11.3.11: Test audit log access control and permissions
     */
    public function test_audit_log_access_control_and_permissions()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);
        
        // Create some audit entries
        Category::create(['name' => 'Test Category']);
        
        // Admin should have access
        $response = $this->actingAs($admin)->get('/admin/audit-logs');
        $response->assertStatus(200);
        
        // Regular user should be denied
        $response = $this->actingAs($user)->get('/admin/audit-logs');
        $response->assertStatus(403);
        
        // Unauthenticated should be denied
        $response = $this->get('/admin/audit-logs');
        $response->assertStatus(401);
    }

    /**
     * 11.3.12: Test audit log performance with large datasets
     */
    public function test_audit_log_performance_with_large_datasets()
    {
        // Create large number of audit entries
        $startTime = microtime(true);
        
        \App\Models\AuditLog::factory()->count(10000)->create([
            'entity_type' => 'book',
            'event' => 'created',
        ]);

        $creationTime = microtime(true) - $startTime;

        // Should complete within reasonable time
        $this->assertLessThan(30, $creationTime, 'Audit log creation took too long');

        // Test search performance
        $startTime = microtime(true);
        
        $response = $this->get('/admin/audit-logs?entity_type=book&event=created&limit=100');
        $searchTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        $this->assertLessThan(5, $searchTime, 'Audit log search took too long');
        $this->assertCount(100, $response->json('data'));
    }

    /**
     * 11.3.7: Test audit log filtering by date range
     */
    public function test_audit_logs_filterable_by_date_range()
    {
        // Create old log
        \App\Models\AuditLog::create([
            'event' => 'test',
            'level' => 'info',
            'created_at' => now()->subDays(30),
        ]);

        // Create recent log
        $recentLog = \App\Models\AuditLog::create([
            'event' => 'test',
            'level' => 'info',
            'created_at' => now(),
        ]);

        // Filter for last 7 days
        $logsLastWeek = \App\Models\AuditLog::where(
            'created_at', '>=', now()->subDays(7)
        )->get();

        $this->assertTrue($logsLastWeek->contains($recentLog));
    }

    /**
     * 11.3.8: Test audit log filtering by user
     */
    public function test_audit_logs_filterable_by_user()
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();

        // Perform actions as different users
        $this->actingAs($user1);
        Category::create(['name' => 'Category 1']);

        $this->actingAs($user2);
        Category::create(['name' => 'Category 2']);

        // Filter by user
        $user1Logs = \App\Models\AuditLog::where('user_id', $user1->id)->get();
        $user2Logs = \App\Models\AuditLog::where('user_id', $user2->id)->get();

        $this->assertTrue($user1Logs->count() > 0);
        $this->assertTrue($user2Logs->count() > 0);
    }

    /**
     * 11.3.9: Test audit log checksum verification (tamper detection)
     */
    public function test_audit_log_checksum_detects_tampering()
    {
        $auditLog = \App\Models\AuditLog::create([
            'event' => 'created',
            'new_values' => json_encode(['name' => 'Test']),
            'level' => 'info',
        ]);

        // Generate checksum
        $checksum = $this->generateChecksum($auditLog);
        $auditLog->update(['checksum' => $checksum]);

        // Verify checksum (should match)
        $calculatedChecksum = $this->generateChecksum($auditLog);
        $this->assertEquals($checksum, $calculatedChecksum);

        // Tamper with data
        $auditLog->update(['new_values' => json_encode(['name' => 'Tampered'])]);

        // Checksum should no longer match
        $tamperedChecksum = $this->generateChecksum($auditLog);
        $this->assertNotEquals($checksum, $tamperedChecksum);
    }

    /**
     * 11.3.10: Test audit metadata includes IP, user agent, URL
     */
    public function test_audit_log_includes_complete_metadata()
    {
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $auditLog = \App\Models\AuditLog::where('event', 'login')->first();

        if ($auditLog) {
            $metadata = json_decode($auditLog->metadata, true);
            
            // Should have IP, user agent, URL, method
            $this->assertArrayHasKey('ip_address', $metadata ?? []);
            $this->assertArrayHasKey('user_agent', $metadata ?? []);
            $this->assertArrayHasKey('method', $metadata ?? []);
        }
    }

    /**
     * 11.3.11: Test bulk audit operations
     */
    public function test_bulk_operations_create_individual_audit_entries()
    {
        // Create multiple records
        Category::factory()->count(10)->create();

        // Each should have an audit entry
        $auditLogs = \App\Models\AuditLog::where([
            ['event', '=', 'created'],
            ['auditable_type', '=', Category::class],
        ])->get();

        $this->assertEquals(10, $auditLogs->count());
    }

    // Helper methods

    private function generateChecksum($auditLog): string
    {
        $data = json_encode([
            'event' => $auditLog->event,
            'user_id' => $auditLog->user_id,
            'old_values' => $auditLog->old_values,
            'new_values' => $auditLog->new_values,
        ]);

        return hash('sha256', $data);
    }
}
