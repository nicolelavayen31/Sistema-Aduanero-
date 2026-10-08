<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['clientes', 'declaraciones', 'pagos'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tables = ['clientes', 'declaraciones', 'pagos'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign([$tableName . '_created_by_foreign']);
                $table->dropForeign([$tableName . '_updated_by_foreign']);
                $table->dropColumn(['created_by', 'updated_by', 'deleted_at']);
            });
        }
    }
};
