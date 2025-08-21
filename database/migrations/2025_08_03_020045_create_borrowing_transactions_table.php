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
        Schema::create('borrowing_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')->onDelete('cascade');
            $table->foreignId('record_id')->constrained('records')->onDelete('cascade');
            $table->foreignId('borrowing_policy_id')->constrained('borrowing_policies');

            $table->enum('transaction_type', ['borrow-inside','checkout', 'checkin', 'renewal', 'lost', 'damaged']);
            $table->enum('status', ['borrowed-inside', 'active', 'returned', 'overdue', 'lost', 'damaged', 'renewed']);

            $table->datetime('checkout_date');
            $table->datetime('due_date')->nullable();
            $table->datetime('return_date')->nullable();
            $table->datetime('last_renewal_date')->nullable();
            $table->integer('renewal_count')->default(0);

            $table->foreignId('checked_out_by')->constrained('users'); // Staff who processed checkout
            $table->foreignId('checked_in_by')->nullable()->constrained('users'); // Staff who processed return

            $table->text('checkout_notes')->nullable();
            $table->text('return_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowing_transactions');
    }
};
