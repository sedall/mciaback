<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->date('due_date');
            $table->unsignedBigInteger('principal_amount');
            $table->unsignedBigInteger('interest_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('status')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
