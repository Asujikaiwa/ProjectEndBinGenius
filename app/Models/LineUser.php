<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineUser extends Model
{
    use HasFactory;

    // ต้องมีบรรทัดนี้ Database ถึงจะยอมรับข้อมูลจาก LINE LIFF
    protected $fillable = [
        'line_id', 
        'display_name', 
        'picture_url', 
        'total_points'
    ];
}