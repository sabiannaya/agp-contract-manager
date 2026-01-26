<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 100)->unique();
            $table->date('date');
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->decimal('amount', 20, 2);
            $table->enum('cooperation_type', ['progress', 'routine']);
            $table->unsignedTinyInteger('term_count')->nullable()->comment('Number of payment terms for progress type');
            $table->json('term_percentages')->nullable()->comment('Array of percentages per term');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('number');
            $table->index('vendor_id');
            $table->index('date');
            $table->index('cooperation_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
