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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();

            $table->unsignedTinyInteger('sequence'); // شماره قسط
            $table->date('due_date');

            $table->unsignedBigInteger('principal_amount');
            $table->unsignedBigInteger('fee_amount')->default(0);
            $table->unsignedBigInteger('late_fee_amount')->default(0);

            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->timestamp('paid_at')->nullable();

            $table->string('status')->index(); // pending,paid,overdue,waived

            $table->timestamps();

            $table->unique(['loan_id', 'sequence']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
