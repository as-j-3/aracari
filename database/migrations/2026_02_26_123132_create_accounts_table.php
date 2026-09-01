<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('account_code', 20)->unique();
            $table->string('account_name');
            $table->enum('account_type', ['asset', 'liability', 'revenue', 'expense']);
            $table->tinyInteger('level')->default(1);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('created_by_user')->nullable();
            $table->timestamps();
            $table->foreign('parent_id')->references('id')->on('accounts');
            $table->foreign('created_by_user')->references('id')->on('users');

            $table->index(['account_code', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
