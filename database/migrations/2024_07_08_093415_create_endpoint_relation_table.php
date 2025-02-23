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
        Schema::create('endpoint_relation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('endpoint_register_id', 36);
            $table->char('relation_endpoint_register_id', 36);
            $table->string('relation_references_name', 100);
            $table->boolean('status')->default(null);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endpoint_relation');
    }
};
