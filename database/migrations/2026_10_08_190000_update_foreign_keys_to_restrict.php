<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')
                ->references('id')
                ->on('clientes')
                ->onDelete('restrict');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['declaracion_id']);
            $table->foreign('declaracion_id')
                ->references('id')
                ->on('declaraciones')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('declaraciones', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->foreign('cliente_id')
                ->references('id')
                ->on('clientes')
                ->onDelete('cascade');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['declaracion_id']);
            $table->foreign('declaracion_id')
                ->references('id')
                ->on('declaraciones')
                ->onDelete('cascade');
        });
    }
};
