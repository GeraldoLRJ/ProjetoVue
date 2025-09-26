<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE tasks ADD COLUMN due_date_tz timestamptz NULL');

        DB::statement("UPDATE tasks SET due_date_tz = ((due_date::text || ' 23:59')::timestamp AT TIME ZONE 'America/Sao_Paulo') WHERE due_date IS NOT NULL");

        DB::statement('ALTER TABLE tasks DROP COLUMN due_date');
        DB::statement('ALTER TABLE tasks RENAME COLUMN due_date_tz TO due_date');

        DB::statement('DROP INDEX IF EXISTS tasks_tenant_due_date_index');
        DB::statement('CREATE INDEX tasks_tenant_due_date_index ON tasks (tenant_id, due_date)');

        $tables = ['tasks', 'users', 'companies'];
        foreach ($tables as $t) {
            DB::statement("ALTER TABLE {$t} ALTER COLUMN created_at TYPE timestamptz USING (created_at AT TIME ZONE 'UTC')");
            DB::statement("ALTER TABLE {$t} ALTER COLUMN updated_at TYPE timestamptz USING (updated_at AT TIME ZONE 'UTC')");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $tables = ['tasks', 'users', 'companies'];
        foreach ($tables as $t) {
            DB::statement("ALTER TABLE {$t} ALTER COLUMN updated_at TYPE timestamp USING (updated_at AT TIME ZONE 'UTC')");
            DB::statement("ALTER TABLE {$t} ALTER COLUMN created_at TYPE timestamp USING (created_at AT TIME ZONE 'UTC')");
        }

        DB::statement('ALTER TABLE tasks ADD COLUMN due_date_old date NULL');
        DB::statement("UPDATE tasks SET due_date_old = (due_date AT TIME ZONE 'America/Sao_Paulo')::date WHERE due_date IS NOT NULL");
        DB::statement('ALTER TABLE tasks DROP COLUMN due_date');
        DB::statement('ALTER TABLE tasks RENAME COLUMN due_date_old TO due_date');
        DB::statement('DROP INDEX IF EXISTS tasks_tenant_due_date_index');
        DB::statement('CREATE INDEX tasks_tenant_due_date_index ON tasks (tenant_id, due_date)');
    }
};
