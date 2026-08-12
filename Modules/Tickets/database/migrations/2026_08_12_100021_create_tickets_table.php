<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('entry_point', 20); // customer|clinic|admin
            $table->string('subject', 190);
            $table->string('status', 20)->default('open'); // open|answered|closed
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['creator_id', 'entry_point']);
            $table->index(['status', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
