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
        Schema::create('moon_extractions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('structure_id')->unique();
            $table->string('structure_name');
            $table->bigInteger('corporation_id')->index();
            $table->string('corporation_name');
            $table->bigInteger('system_id')->index();
            $table->string('system_name');
            $table->bigInteger('region_id')->index();
            $table->string('region_name');
            $table->timestamp('chunk_arrival_time');
            $table->timestamp('extraction_start_time');
            $table->timestamp('natural_decay_time');
            $table->json('moon_materials')->nullable();
            $table->decimal('moon_value', 15, 2)->nullable();
            $table->string('status')->default('scheduled'); // scheduled, active, completed, cancelled
            $table->timestamps();
            
            $table->index(['corporation_id', 'status']);
            $table->index(['system_id', 'status']);
            $table->index('chunk_arrival_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moon_extractions');
    }
};
