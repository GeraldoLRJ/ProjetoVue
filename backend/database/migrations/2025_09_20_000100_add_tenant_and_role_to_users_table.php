<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email')) {
                $table->dropUnique('users_email_unique');
            }
            $table->foreignId('tenant_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('role')->default('user');
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('role');
            $table->unique('email');
        });
    }
};
