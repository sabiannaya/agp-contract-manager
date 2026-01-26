<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_group_privilege_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_group_id')->constrained('role_groups')->onDelete('cascade');
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->boolean('__create')->default(false);
            $table->boolean('__read')->default(false);
            $table->boolean('__update')->default(false);
            $table->boolean('__delete')->default(false);
            $table->timestamps();

            $table->unique(['role_group_id', 'page_id'], 'unique_group_page');
            $table->index('role_group_id');
            $table->index('page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_group_privilege_mapping');
    }
};
