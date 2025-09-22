<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remover unique global em email para permitir emails repetidos entre tenants diferentes
            if (Schema::hasColumn('users', 'email')) {
                // Nome padrão criado pela migration original do Laravel
                $table->dropUnique('users_email_unique');
            }
            $table->foreignId('tenant_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('role')->default('user'); // valores: master, admin, user (sugestão)
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('role');
            // Recriar unique global em email (estado original da tabela users)
            $table->unique('email');
        });
    }
};
