<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    // อนุญาตให้แก้ไขข้อมูล 4 คอลัมน์นี้ได้
    protected $fillable = [
        'name', 
        'points_required', 
        'image_url', 
        'stock'
    ];
}