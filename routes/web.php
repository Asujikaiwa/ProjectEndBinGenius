<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache; // ต้องใช้ Cache เพื่อให้เชื่อมกับ AI ได้
use App\Http\Controllers\AdminController;
use Illuminate\Support\Str; // ห้ามลืม! เพื่อใช้สุ่มรหัส
use App\Models\TrashLog;

// 1. หน้า Dashboard
Route::get('/', [AdminController::class, 'index']);

// 2. ระบบจัดการของรางวัล
Route::post('/admin/rewards', [AdminController::class, 'storeReward']);
Route::get('/admin/rewards/delete/{id}', [AdminController::class, 'deleteReward']);

// 2. ทางเข้าหน้าเว็บ LIFF
Route::get('/liff', function () {
    return view('liff');
});

Route::post('/admin/users/{id}/add-points', [App\Http\Controllers\AdminController::class, 'addPoints']);
Route::post('/admin/verify-code', [App\Http\Controllers\AdminController::class, 'verifyCode']);
Route::post('/api/line/login', function (Illuminate\Http\Request $request) {
    $lineId = $request->input('line_id');
    $name = $request->input('display_name');
    $picture = $request->input('picture_url'); // รับรูปโปรไฟล์ด้วย

    // 1. คำสั่งนี้แหละครับที่จะเซฟข้อมูลลง Database (ตาราง line_users)
    App\Models\LineUser::updateOrCreate(
        ['line_id' => $lineId], // ค้นหาว่ามี ID นี้หรือยัง
        [
            'display_name' => $name, 
            'picture_url' => $picture
        ]
    );

    // 2. บันทึกคิวลง Cache ให้ AI ดึงไปบวกแต้ม
    Illuminate\Support\Facades\Cache::put('active_user_id', $lineId, 180);
    Illuminate\Support\Facades\Cache::put('active_user_name', $name, 180);

    return response()->json(['status' => 'success']);
});

// ดึงข้อมูลทั้งหมดให้หน้ามือถือ (แต้มรวม, ประวัติ, ของรางวัล)
Route::post('/api/line/dashboard', function (Illuminate\Http\Request $request) {
    $name = $request->input('display_name'); // อ้างอิงจากชื่อ หรือ line_id
    
    // 1. แต้มรวม
    $totalPoints = App\Models\TrashLog::where('line_id', $name)->sum('points');
    
    // 2. ประวัติการทิ้งขยะ (เอา 10 รายการล่าสุด)
    $history = App\Models\TrashLog::where('line_id', $name)->orderBy('created_at', 'desc')->take(10)->get();
    
    // 3. ของรางวัลที่ยังมีในสต็อก
    $rewards = App\Models\Reward::where('stock', '>', 0)->get();

    return response()->json([
        'total_points' => $totalPoints,
        'history' => $history,
        'rewards' => $rewards
    ]);
});

// ระบบแลกของรางวัล


Route::post('/api/line/redeem', function (Illuminate\Http\Request $request) {
    $name = $request->input('display_name');
    $rewardId = $request->input('reward_id');

    $reward = App\Models\Reward::find($rewardId);
    $totalPoints = App\Models\TrashLog::where('line_id', $name)->sum('points');

    if (!$reward || $reward->stock <= 0) {
        return response()->json(['status' => 'error', 'message' => 'ของหมดครับ']);
    }

    if ($totalPoints < $reward->points_required) {
        return response()->json(['status' => 'error', 'message' => 'แต้มไม่พอครับ']);
    }

    // สุ่มรหัสยืนยัน
    $redeemCode = 'BG-' . strtoupper(Str::random(6));

    $log = new App\Models\TrashLog();
    $log->line_id = $name;
    $log->trash_type = '🎁 แลก: ' . $reward->name;
    $log->points = -($reward->points_required);
    $log->redeem_code = $redeemCode; // ต้องรัน Migration เพิ่มคอลัมน์นี้แล้ว
    $log->is_redeemed = false;
    $log->save();

    $reward->decrement('stock'); // ตัดสต็อก

    return response()->json([
        'status' => 'success', 
        'redeem_code' => $redeemCode
    ]);
});

// API รอรับข้อมูลการทิ้งขยะเพื่อบวกแต้ม (จาก AI / Node-RED)
Route::post('/api/trash/detect', function (Illuminate\Http\Request $request) {
    $trashType = $request->input('trash_type'); 
    $userId = Illuminate\Support\Facades\Cache::get('active_user_name', 'Guest'); 
    
    $points = ($trashType == 'recycle') ? 10 : 5;

    $log = new App\Models\TrashLog();
    $log->user_id = $userId;
    $log->trash_type = $trashType;
    $log->points = $points;
    $log->save();

    Illuminate\Support\Facades\Cache::forget('active_user_id');
    Illuminate\Support\Facades\Cache::forget('active_user_name');

    return response()->json(['status' => 'success', 'message' => "เพิ่ม $points แต้ม ให้คุณ $userId สำเร็จ!"]);
});