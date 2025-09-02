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
        Schema::create('library_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('entry_time');
            $table->dateTime('exit_time')->nullable();
            $table->enum('entry_method', ['manual', 'card_scan', 'qr_code'])->default('manual');
            $table->timestamps();

            $table->index(['user_id', 'entry_time']);
            $table->index(['entry_time', 'exit_time']);
            $table->index('visit_purpose_id');
            $table->index(['created_at']); // For daily/monthly reports
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('library_visits');
    }
};
