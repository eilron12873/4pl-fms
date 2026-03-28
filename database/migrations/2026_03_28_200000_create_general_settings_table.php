<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_logo')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('email_address')->nullable();
            $table->string('website')->nullable();
            $table->string('default_timezone')->default('Asia/Manila');
            $table->string('default_date_format')->default('Y-m-d');
            $table->string('default_currency', 8)->default('PHP');
            $table->string('registration_number')->nullable();
            $table->unsignedTinyInteger('fiscal_year_start_month')->nullable();
            $table->unsignedTinyInteger('fiscal_year_start_day')->nullable();
            $table->timestamps();
        });

        DB::table('general_settings')->insert([
            'company_name' => null,
            'company_address' => null,
            'company_logo' => null,
            'telephone_number' => null,
            'email_address' => null,
            'website' => null,
            'default_timezone' => 'Asia/Manila',
            'default_date_format' => 'Y-m-d',
            'default_currency' => 'PHP',
            'registration_number' => null,
            'fiscal_year_start_month' => null,
            'fiscal_year_start_day' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
