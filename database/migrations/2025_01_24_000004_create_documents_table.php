<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->enum('type', ['contract', 'invoice', 'handover_report', 'tax_id', 'tax_invoice']);
            $table->string('original_name', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->comment('File size in bytes');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('type');
            $table->unique(['ticket_id', 'type'], 'unique_ticket_document_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
