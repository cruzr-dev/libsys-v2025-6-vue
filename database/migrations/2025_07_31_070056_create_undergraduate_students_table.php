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
        Schema::create('undergraduate_students', function (Blueprint $table) {
            $table->id();
            $table->string('contact_number', 10)->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('college_id')->constrained()->onDelete('restrict');
            $table->foreignId('course_id')->constrained()->onDelete('restrict');
            $table->foreignId('major_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('undergraduate_students');
    }
};
