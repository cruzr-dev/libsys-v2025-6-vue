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
        Schema::create('borrowing_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Student Policy', 'Faculty Policy'
            $table->foreignId('user_type_id')->nullable()
                ->constrained('user_types')->nullOnDelete();
            $table->enum('college_type', ['undergraduate', 'graduate'])->nullable();
            $table->integer('max_items')->default(5); // Maximum items that can be borrowed
            $table->integer('loan_period_days')->default(14); // Loan period in days
            $table->integer('renewal_limit')->default(2); // Maximum renewals allowed
            $table->integer('renewal_period_days')->default(7); // Days added per renewal
            $table->integer('hold_period_days')->default(7); // Days to hold reserved item (kept for potential future use)
            $table->decimal('overdue_fine_per_day', 8, 2)->default(5.00); // Fine per day
            $table->decimal('max_fine_amount', 8, 2)->default(500.00); // Maximum fine
            $table->integer('grace_period_days')->default(0); // Grace period before fines
            $table->boolean('can_place_holds')->default(false); // Disabled since holds are excluded
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowing_policies');
    }
};
