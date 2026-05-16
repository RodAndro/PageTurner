<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    private array $sensitive = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function created(Model $model): void
    {
        $this->record('created', $model, [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->record('updated', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->record('deleted', $model, $model->getOriginal(), []);
    }

    private function record(string $event, Model $model, array $oldValues, array $newValues): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'entity_type' => strtolower(class_basename($model)),
            'entity_id' => $model->getKey(),
            'action' => $event,
            'old_values' => json_encode($this->filter($oldValues)),
            'new_values' => json_encode($this->filter($newValues)),
            'metadata' => json_encode([
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
            ]),
            'description' => class_basename($model) . " {$event}",
            'level' => $event === 'deleted' ? 'critical' : 'info',
            'is_sensitive' => $model instanceof \App\Models\User,
        ]);
    }

    private function filter(array $values): array
    {
        return collect($values)
            ->reject(fn ($value, $key) => in_array(strtolower((string) $key), $this->sensitive, true))
            ->all();
    }
}
