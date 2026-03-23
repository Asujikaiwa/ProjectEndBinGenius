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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อของรางวัล (เช่น สมุด, ปากกา)
            $table->integer('points_required'); // จำนวนแต้มที่ต้องใช้แลก
            $table->string('image_url')->nullable(); // ลิงก์รูปภาพของรางวัล (เผื่อโชว์ในมือถือ)
            $table->integer('stock')->default(10); // จำนวนของที่เหลือในสต็อก (เริ่มต้นให้มี 10 ชิ้น)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
