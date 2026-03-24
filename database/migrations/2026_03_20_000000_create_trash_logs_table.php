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
    Schema::create('trash_logs', function (Blueprint $table) {
        $table->id();
        $table->string('line_id')->nullable(); // เก็บ ID ของผู้ใช้ Line
        $table->string('trash_type');           // ประเภทขยะ (เช่น plastic, bottle)
        $table->integer('points')->default(0);  // คะแนนที่ได้
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trash_logs');
    }
};
