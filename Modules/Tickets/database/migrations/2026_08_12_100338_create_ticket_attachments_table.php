<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_message_id')->constrained('ticket_messages')->cascadeOnDelete();
            $table->string('disk', 50)->default('tickets_attachments');
            $table->string('path');
            $table->string('original_name');
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
    }
};
