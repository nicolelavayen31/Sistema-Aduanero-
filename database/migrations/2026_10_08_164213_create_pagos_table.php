<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('declaracion_id')
                ->constrained('declaraciones')
                ->onDelete('cascade');

            $table->decimal('monto', 10, 2);

            $table->string('metodo_pago');

            $table->string('estado')->default('pendiente');

            $table->dateTime('fecha_pago')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};