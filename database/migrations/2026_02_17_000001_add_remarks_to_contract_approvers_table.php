<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_approvers', function (Blueprint $table) {
            $table->text('remarks')
                ->nullable()
                ->after('sequence_no')
                ->comment('Why this approver is included for the contract');
        });
    }

    public function down(): void
    {
        Schema::table('contract_approvers', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
