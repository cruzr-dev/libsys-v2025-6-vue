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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id') // Foreign key to resources table
            ->constrained('records')->onDelete('cascade');

            $table->string('volume')->nullable();
            $table->year('publication_year')->nullable();
            $table->string('publisher')->nullable();
            $table->string('publication_place')->nullable();
            $table->string('edition')->nullable();
            $table->string('isbn')->nullable();

            $table->string('call_number')->nullable();
            $table->foreignId('ddc_class_id')->nullable()
                ->constrained('ddc_classifications')->onDelete('set null');
            $table->foreignId('lc_class_id')->nullable()
                ->constrained('lc_classifications')->onDelete('set null');
            $table->foreignId('physical_location_id')->nullable()
                ->constrained('physical_locations')->onDelete('set null');

            $table->foreignId('cover_type_id')->nullable()
                ->constrained('cover_types')->onDelete('set null');
            $table->string('cover_image')->nullable();
            $table->string('qrcode_file')->nullable();

            $table->string('ics_number')->nullable();
            $table->date('ics_date')->nullable();
            $table->string('pr_number')->nullable();
            $table->date('pr_date')->nullable();
            $table->string('po_number')->nullable();
            $table->date('po_date')->nullable();

            $table->foreignId('source_id')->nullable()
                ->constrained('sources')->onDelete('set null');
            $table->decimal('purchase_amount', 10, 2)->nullable();
            $table->decimal('lot_cost', 10, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->string('donated_by')->nullable();
            $table->string('replaced_by')->nullable();
            $table->string('transferred_from')->nullable();

            $table->text('table_of_contents')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // indexes here later
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
