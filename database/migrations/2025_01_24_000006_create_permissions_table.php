<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 100)->comment('e.g. vendors, contracts, tickets, roles');
            $table->enum('action', ['read', 'create', 'update', 'delete']);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['resource', 'action'], 'unique_resource_action');
            $table->index('resource');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
