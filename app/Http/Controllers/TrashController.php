<?php

namespace App\Http\Controllers;

use App\Models\TrashLog;
use Illuminate\Http\Request;

class TrashController extends Controller
{
    public function index()
    {
        // ดึงข้อมูล 10 รายการล่าสุด และคำนวณแต้มรวม
        $logs = TrashLog::orderBy('id', 'desc')->take(10)->get();
        $totalPoints = TrashLog::sum('points');

        return view('welcome', compact('logs', 'totalPoints'));
    }
}