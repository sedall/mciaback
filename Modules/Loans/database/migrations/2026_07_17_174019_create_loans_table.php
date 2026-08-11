<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('principal_amount');
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->unsignedSmallInteger('term_months');
            $table->unsignedBigInteger('monthly_installment')->nullable();
            $table->string('status')->index();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->text('approved_notes')->nullable();
            $table->string('funding_reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
