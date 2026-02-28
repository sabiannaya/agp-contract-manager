<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Contract master: creator is auto-master, plus one assignable
            $table->foreignId('assigned_master_user_id')
                ->nullable()
                ->after('updated_by_user_id')
                ->constrained('users')
                ->nullOnDelete();

            // Cached payment totals (Option B: computed on write, fast on read)
            $table->decimal('payment_total_paid', 20, 2)
                ->default(0)
                ->after('assigned_master_user_id')
                ->comment('Cached sum of all paid ticket amounts');

            $table->decimal('payment_balance', 20, 2)
                ->default(0)
                ->after('payment_total_paid')
                ->comment('Cached outstanding balance (amount - payment_total_paid)');

            $table->timestamp('payment_last_synced_at')
                ->nullable()
                ->after('payment_balance');

            $table->index('assigned_master_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['assigned_master_user_id']);
            $table->dropColumn([
                'assigned_master_user_id',
                'payment_total_paid',
                'payment_balance',
                'payment_last_synced_at',
            ]);
        });
    }
};
