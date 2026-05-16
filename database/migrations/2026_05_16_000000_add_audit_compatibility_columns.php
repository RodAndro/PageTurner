<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            if (!Schema::hasColumn('audit_logs', 'entity_type')) {
                $table->string('entity_type')->nullable()->after('auditable_id');
            }

            if (!Schema::hasColumn('audit_logs', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            }

            if (!Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action')->nullable()->after('entity_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('audit_logs', 'action')) {
                $table->dropColumn('action');
            }

            if (Schema::hasColumn('audit_logs', 'entity_id')) {
                $table->dropColumn('entity_id');
            }

            if (Schema::hasColumn('audit_logs', 'entity_type')) {
                $table->dropColumn('entity_type');
            }
        });
    }
};
