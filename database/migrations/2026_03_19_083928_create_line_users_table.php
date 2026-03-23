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
        Schema::create('line_users', function (Blueprint $table) {
            $table->id();
            $table->string('line_id')->unique(); // เก็บค่า U123abc... จาก LINE
            $table->string('display_name')->nullable();
            $table->string('picture_url')->nullable();
            $table->integer('total_points')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_users');
    }
};
