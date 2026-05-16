<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'event' => fake()->randomElement(['created', 'updated', 'deleted']),
            'entity_type' => fake()->randomElement(['book', 'category', 'user']),
            'entity_id' => fake()->numberBetween(1, 100000),
            'action' => 'created',
            'old_values' => json_encode([]),
            'new_values' => json_encode(['name' => fake()->words(3, true)]),
            'metadata' => json_encode(['ip_address' => fake()->ipv4()]),
            'level' => 'info',
            'is_sensitive' => false,
        ];
    }
}
