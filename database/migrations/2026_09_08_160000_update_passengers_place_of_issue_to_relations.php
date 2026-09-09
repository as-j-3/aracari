<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passengers', function (Blueprint $table) {
            // إزالة حقول النص القديمة لمكان الإصدار
            $table->dropColumn(['personal_card_place_of_issue', 'passport_place_of_issue']);

            // إضافة حقول علائقية جديدة مرتبطة بجداول الدول والمدن
            $table->foreignId('personal_country_place_of_issue')
                ->nullable()
                ->after('personal_card_id')
                ->constrained('countries')
                ->nullOnDelete();

            $table->foreignId('personal_city_place_of_issue')
                ->nullable()
                ->after('personal_country_place_of_issue')
                ->constrained('cities')
                ->nullOnDelete();

            $table->foreignId('passport_country_place_of_issue')
                ->nullable()
                ->after('passport_number')
                ->constrained('countries')
                ->nullOnDelete();

            $table->foreignId('passport_city_place_of_issue')
                ->nullable()
                ->after('passport_country_place_of_issue')
                ->constrained('cities')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('passengers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_country_place_of_issue');
            $table->dropConstrainedForeignId('personal_city_place_of_issue');
            $table->dropConstrainedForeignId('passport_country_place_of_issue');
            $table->dropConstrainedForeignId('passport_city_place_of_issue');

            $table->string('personal_card_place_of_issue', 255)->nullable();
            $table->string('passport_place_of_issue', 255)->nullable();
        });
    }
};
