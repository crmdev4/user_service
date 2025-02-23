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
            $table->uuid('id')->primary();
            $table->string('account')->comment('Stake, Gajian, Karyawan, etc');
            $table->boolean('is_single_device')->default(0);
            $table->boolean('is_banned')->default(0);
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
        Schema::dropIfExists('accounts');
    }
};
