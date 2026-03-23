<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrashLog;
use App\Models\Reward;
use App\Models\LineUser;

class AdminController extends Controller
{
    // 1. ดึงข้อมูลไปโชว์หน้า Dashboard
    public function index()
    {
        // 🔴 2. ตอนเรียกใช้ ให้พิมพ์แค่ชื่อสั้นๆ แบบนี้ครับ ไม่ต้องมี App\Models นำหน้าแล้ว
        $totalPoints = TrashLog::sum('points'); 
        $logs = TrashLog::orderBy('created_at', 'desc')->take(10)->get(); 
        $rewards = Reward::all(); 
        
        $users = LineUser::all(); 

        foreach($users as $user) {
            $user->current_points = TrashLog::where('user_id', $user->display_name)->sum('points');
        }

        $leaderboard = TrashLog::selectRaw('user_id, sum(points) as total_points')
            ->groupBy('user_id')
            ->orderBy('total_points', 'desc')
            ->take(5)
            ->get();

        return view('welcome', compact('totalPoints', 'logs', 'rewards', 'leaderboard', 'users'));
    }

    // 2. ฟังก์ชันเพิ่มของรางวัล (Create)
    public function storeReward(Request $request)
    {
        Reward::create([
            'name' => $request->name,
            'points_required' => $request->points_required,
            'stock' => $request->stock ?? 10 // ถ้าไม่กรอกให้ค่าเริ่มต้นเป็น 10
        ]);
        return back()->with('success', 'เพิ่มของรางวัลสำเร็จ!');
    }

    // 3. ฟังก์ชันลบของรางวัล (Delete)
    public function deleteReward($id)
    {
        Reward::find($id)->delete();
        return back()->with('success', 'ลบของรางวัลสำเร็จ!');
    }

    // แก้ไขฟังก์ชันเพิ่มแต้ม (ตัดคำให้สั้นลงเพื่อป้องกัน Error)
    public function addPoints(Request $request, $id)
    {
        $user = LineUser::find($id);
        if($user) {
            $log = new TrashLog();
            $log->user_id = $user->display_name;
            $log->trash_type = 'Admin Bonus'; // ใช้คำภาษาอังกฤษสั้นๆ เพื่อความชัวร์
            $log->points = $request->points;
            $log->save();
        }
        return back()->with('success', 'เพิ่มแต้มให้ผู้ใช้งานสำเร็จ!');
    }

    // [ใหม่] ฟังก์ชันสำหรับแอดมินใช้ตรวจสอบรหัสจากมือถือลูกค้า
    public function verifyCode(Request $request)
    {
        $code = strtoupper($request->redeem_code);
        // ค้นหารหัสในฐานข้อมูล
        $log = TrashLog::where('redeem_code', $code)->first();

        if (!$log) {
            return back()->with('error', '❌ ไม่พบรหัสนี้ในระบบ!');
        }

        if ($log->is_redeemed) {
            return back()->with('error', '⚠️ รหัสนี้ถูกใช้รับของไปแล้วเมื่อ: ' . $log->updated_at);
        }

        // ถ้าผ่าน ให้มาร์คว่าได้รับของแล้ว
        $log->is_redeemed = true;
        $log->save();

        return back()->with('success', '✅ รหัสถูกต้อง! ลูกค้าแลก: ' . $log->trash_type . ' ยืนยันมอบของรางวัลแล้ว');
    }
}