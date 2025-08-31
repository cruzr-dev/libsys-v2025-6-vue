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
        Schema::create('theses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('records')->onDelete('cascade');

            $table->json('researchers');
            $table->text('adviser')->nullable();
            $table->year('year')->nullable();
            $table->string('month')->nullable();

            $table->string('institution')->nullable();
            $table->string('college')->nullable();
            $table->string('degree_program')->nullable();
            $table->string('degree_level')->nullable();

            $table->foreignId('ddc_class_id')->nullable()
                ->constrained('ddc_classifications')->onDelete('set null');
            $table->foreignId('lc_class_id')->nullable()
                ->constrained('lc_classifications')->onDelete('set null');
            $table->string('call_number')->nullable();
            $table->foreignId('physical_location_id')->nullable()
                ->constrained('physical_locations')->onDelete('set null');
            $table->string('abstract')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // indexes later for performance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theses');
    }
};
