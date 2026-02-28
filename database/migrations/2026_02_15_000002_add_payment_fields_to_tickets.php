<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Payment amount for this ticket/payment request
            $table->decimal('amount', 20, 2)
                ->nullable()
                ->after('status')
                ->comment('Payment amount for this ticket');

            // Approval status for payment workflow
            $table->enum('approval_status', ['draft', 'pending', 'approved', 'rejected', 'paid'])
                ->default('draft')
                ->after('amount');

            $table->timestamp('submitted_at')
                ->nullable()
                ->after('approval_status');

            $table->timestamp('approved_at')
                ->nullable()
                ->after('submitted_at');

            $table->timestamp('paid_at')
                ->nullable()
                ->after('approved_at');

            $table->string('reference_no', 100)
                ->nullable()
                ->after('paid_at')
                ->comment('Payment reference number');

            // Re-request traceability: links to the rejected ticket this replaces
            $table->foreignId('replaces_ticket_id')
                ->nullable()
                ->after('reference_no')
                ->constrained('tickets')
                ->nullOnDelete();

            $table->index('approval_status');
            $table->index('replaces_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['replaces_ticket_id']);
            $table->dropColumn([
                'amount',
                'approval_status',
                'submitted_at',
                'approved_at',
                'paid_at',
                'reference_no',
                'replaces_ticket_id',
            ]);
        });
    }
};
