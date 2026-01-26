<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_group_id')->constrained('role_groups')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'role_group_id'], 'unique_user_group');
            $table->index('user_id');
            $table->index('role_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
