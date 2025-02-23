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
        Schema::table('user_accounts', function (Blueprint $table) {
            $table->string('host', 100)->nullable();
            $table->boolean('is_subdomain')->default(0);
            $table->boolean('is_activated')->default(0);
            $table->boolean('is_banned')->default(0);
            $table->string('banned_reason')->nullable();
            $table->dateTime('activated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_accounts', function (Blueprint $table) {
            
        });
    }
};
