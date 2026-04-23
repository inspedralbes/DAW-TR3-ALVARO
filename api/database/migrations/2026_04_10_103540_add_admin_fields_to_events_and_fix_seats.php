<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix unique constraint: allow same row/number across different events
        Schema::table('seats', function (Blueprint $table) {
            $table->dropUnique(['row', 'number']);
            $table->unique(['event_id', 'row', 'number']);
        });

        // Add admin fields to events table
        Schema::table('events', function (Blueprint $table) {
            $table->string('venue')->nullable()->after('description');
            $table->integer('capacity')->nullable()->after('venue');
            $table->json('zones')->nullable()->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'row', 'number']);
            $table->unique(['row', 'number']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['venue', 'capacity', 'zones']);
        });
    }
};
