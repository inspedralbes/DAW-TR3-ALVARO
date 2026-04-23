<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->string('row', 10);
            $table->integer('number');
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->string('session_id', 255)->nullable();
            $table->dateTime('reserved_at')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['row', 'number']);
            $table->index('status');
            $table->index('reserved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
