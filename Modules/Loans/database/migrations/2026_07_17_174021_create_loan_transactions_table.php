<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->string('type', 50);
            $table->unsignedBigInteger('amount');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->string('reference', 50);
            $table->timestamp('transacted_at')->nullable();
            $table->timestamps();
            $table->index(['loan_id', 'type']);
            $table->index('performed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_transactions');
    }
};
