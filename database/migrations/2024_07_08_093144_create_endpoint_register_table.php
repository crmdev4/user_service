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
        Schema::create('endpoint_register', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('permission_id')->nullable();
            $table->string('service_name');
            $table->string('base_uri');    
            $table->string('version')->nullable();
            $table->string('method');
            $table->string('path');
			$table->string('api_key')->default(null);
            $table->boolean('status')->default(0);
            $table->string('reference')->default(null);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endpoint_register');
    }
};
