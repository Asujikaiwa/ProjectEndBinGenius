<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrashLog extends Model
{
    protected $table = 'trash_logs'; // บอกว่าใช้ตารางชื่อนี้
    protected $fillable = ['line_user_id', 'trash_type', 'points'];
}
