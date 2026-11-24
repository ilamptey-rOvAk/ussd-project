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
        Schema::create('ussd_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 120)->unique()->index();
            $table->string('msisdn', 30)->nullable()->index();
            $table->string('network', 30)->nullable();
            $table->string('step', 30)->default('WELCOME');
            $table->string('selected_feeling', 100)->nullable();
            $table->string('selected_reason', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ussd_sessions');
    }
};
