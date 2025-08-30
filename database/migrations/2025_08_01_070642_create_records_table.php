<?php

use Carbon\Carbon;
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
        Schema::create('records', function (Blueprint $table) {
            $table->id(); // Primary key, auto-generated

            $table->string('accession_number')->unique()->nullable();
            $table->string('title');
            $table->date('date_received')->nullable();
            $table->enum('status', ['available', 'damaged', 'missing', 'borrowed', 'discarded']);
            $table->text('subject')->nullable();

            $table->foreignId('added_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->foreignId('imported_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // For soft delete functionality

            // make idex here for performance in searching
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
