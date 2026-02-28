<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores the list of approvers configured per contract (by contract master)
        Schema::create('contract_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('sequence_no')->comment('Approval order (1-based)');
            $table->timestamps();

            $table->unique(['contract_id', 'user_id']);
            $table->unique(['contract_id', 'sequence_no']);
            $table->index('user_id');
        });

        // Tracks each approver's decision per ticket (payment request)
        Schema::create('ticket_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('approver_user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('sequence_no')->comment('Inherited from contract_approvers at submission time');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('acted_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'approver_user_id']);
            $table->unique(['ticket_id', 'sequence_no']);
            $table->index(['ticket_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_approval_steps');
        Schema::dropIfExists('contract_approvers');
    }
};
