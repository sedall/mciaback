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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('principal_amount');   // مبلغ اصل وام (ریال)
            $table->unsignedBigInteger('fee_amount')->default(0); // کارمزد کل
            $table->unsignedBigInteger('total_payable');      // اصل + کارمزد
            $table->unsignedBigInteger('approved_amount')->nullable();
            $table->unsignedInteger('approved_term_months')->nullable();
            $table->unsignedTinyInteger('installments_count'); // 3,6,12
            $table->string('status')->index()->default('pending'); // draft,submitted,under_review,approved,rejected,funded,active,completed,defaulted
            $table->text('admin_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
