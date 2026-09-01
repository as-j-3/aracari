<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('gender_id')->nullable()->constrained('genders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name'); 
            $table->date('birthday');
            $table->string('phone_number', 20)->nullable();
            $table->text('personal_card_id')->nullable();
            $table->string('personal_card_id_photo', 500)->nullable();
            $table->string('personal_card_place_of_issue', 255)->nullable();
            $table->date('personal_card_date_issu')->nullable();
            $table->date('personal_card_date_expiry')->nullable();
            $table->text('passport_number')->nullable();
            $table->string('passport_photo', 500)->nullable();
            $table->date('passport_date_issu')->nullable();
            $table->date('passport_date_expiry')->nullable();
            $table->string('passport_place_of_issue', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};
