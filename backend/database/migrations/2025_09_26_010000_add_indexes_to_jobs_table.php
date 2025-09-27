<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('jobs')) {
            return; // tabela ainda não criada
        }

        Schema::table('jobs', function (Blueprint $table) {
            try { $table->index(['queue', 'reserved_at'], 'jobs_queue_reserved_at_index'); } catch (Throwable $e) {}
            try { $table->index(['available_at'], 'jobs_available_at_index'); } catch (Throwable $e) {}
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('jobs')) {
            return;
        }
        Schema::table('jobs', function (Blueprint $table) {
            try { $table->dropIndex('jobs_queue_reserved_at_index'); } catch (Throwable $e) {}
            try { $table->dropIndex('jobs_available_at_index'); } catch (Throwable $e) {}
        });
    }
};
