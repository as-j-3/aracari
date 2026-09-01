<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('provider_services')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('file', 500);
            $table->string('file_type', 50)->default('document');
            $table->string('status', 50)->default('active');
            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_files');
    }
};
