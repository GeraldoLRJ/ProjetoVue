<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasColumn('tasks', 'tenant_id')) {
            DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_tenant_id_foreign');

            DB::statement('DROP INDEX IF EXISTS tasks_tenant_id_status_index');
            DB::statement('DROP INDEX IF EXISTS tasks_tenant_id_due_date_index');
            DB::statement('DROP INDEX IF EXISTS tasks_tenant_due_date_index');

            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('companies')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'due_date']);
        });
    }
};
