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
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('user_id', 36);
            $table->char('account_id', 36);
            $table->char('secondary_id', 36)->nullable();
            $table->char('created_user_id', 36)->nullable();
            $table->char('modified_user_id', 36)->nullable();
            $table->char('deleted_user_id', 36)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};
