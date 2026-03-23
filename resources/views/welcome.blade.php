<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>BinGenius Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
    <style>body { font-family: 'Prompt', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-white p-8">
    <div class="max-w-6xl mx-auto mb-4">
        @if(session('success'))
            <div class="bg-emerald-500/20 border border-emerald-500 text-emerald-400 p-4 rounded-xl mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-500/20 border border-red-500 text-red-400 p-4 rounded-xl mb-4">
                {{ session('error') }}
            </div>
        @endif
    </div>
    <div class="max-w-6xl mx-auto space-y-8">
        
        <div class="flex justify-between items-center bg-slate-800 p-6 rounded-2xl shadow-lg border border-slate-700">
            <h1 class="text-4xl font-bold text-emerald-400">🤖 BinGenius Admin</h1>
            <div class="bg-emerald-500/20 px-6 py-3 rounded-xl border border-emerald-500/50">
                แต้มสะสมรวมทั้งระบบ: <span class="text-3xl font-bold text-emerald-400">{{ $totalPoints }}</span>
            </div>
        </div>
        
        <div class="bg-gradient-to-r from-purple-900 to-slate-800 p-6 rounded-2xl shadow-xl border border-purple-500/30 mb-8">
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
        🔍 ตรวจสอบรหัสรับของรางวัล
        </h2>
        <form action="/admin/verify-code" method="POST" class="flex flex-wrap gap-4">
        @csrf
        <input type="text" name="redeem_code" placeholder="กรอกรหัสจากมือถือลูกค้า (เช่น BG-XXXXXX)" 
               class="flex-1 min-w-[300px] bg-slate-900 border border-purple-400 rounded-xl px-4 py-3 text-white font-mono text-xl uppercase">
        <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white font-bold px-8 py-3 rounded-xl transition shadow-lg shadow-purple-900/50">
            ตรวจสอบข้อมูลรหัส
        </button>
        </form>
        </div>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 space-y-8">
                
                <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6">
                    <h2 class="text-2xl font-bold text-yellow-400 mb-4">🏆 Top 5 ผู้รักษ์โลก</h2>
                    <div class="grid grid-cols-1 gap-3">
                        @foreach($leaderboard as $index => $top)
                        <div class="flex justify-between items-center bg-slate-700/50 p-4 rounded-xl">
                            <div class="flex items-center gap-4">
                                <span class="text-2xl font-bold text-slate-400">#{{ $index + 1 }}</span>
                                <span class="font-semibold text-lg">{{ $top->user_id }}</span>
                            </div>
                            <span class="text-emerald-400 font-bold text-xl">{{ $top->total_points }} แต้ม</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6">
                    <h2 class="text-2xl font-bold text-blue-400 mb-4">📝 ประวัติล่าสุด (Live)</h2>
                    <table class="w-full text-left">
                        <thead class="text-slate-400 border-b border-slate-700">
                            <tr>
                                <th class="pb-3">ผู้ใช้</th>
                                <th class="pb-3">ประเภทขยะ</th>
                                <th class="pb-3 text-right">คะแนน</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                                <td class="py-3">{{ $log->user_id }}</td>
                                <td class="py-3">
                                    @if($log->trash_type == 'recycle')
                                        <span class="text-blue-400 text-sm border border-blue-500/30 px-2 py-1 rounded bg-blue-500/10">รีไซเคิล</span>
                                    @elseif(str_contains($log->trash_type, '⭐'))
                                        <span class="text-yellow-400 text-sm border border-yellow-500/30 px-2 py-1 rounded bg-yellow-500/10">แต้มพิเศษ</span>
                                    @else
                                        <span class="text-red-400 text-sm border border-red-500/30 px-2 py-1 rounded bg-red-500/10">อันตราย</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right text-emerald-400 font-bold">
                                    {{ $log->points > 0 ? '+' : '' }}{{ $log->points }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6">
                <h2 class="text-2xl font-bold text-purple-400 mb-4">🎁 จัดการของรางวัล</h2>
                
                <form action="/admin/rewards" method="POST" class="bg-slate-700/30 p-4 rounded-xl mb-6 space-y-4 border border-slate-600">
                    @csrf
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">ชื่อของรางวัล</label>
                        <input type="text" name="name" required class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">ใช้กี่แต้ม</label>
                            <input type="number" name="points_required" required class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-white">
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">จำนวนสต็อก</label>
                            <input type="number" name="stock" value="10" required class="w-full bg-slate-900 border border-slate-600 rounded p-2 text-white">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 rounded transition">
                        + เพิ่มของรางวัล
                    </button>
                </form>

                <div class="space-y-3">
                    @foreach($rewards as $reward)
                    <div class="bg-slate-700/50 p-3 rounded-lg flex justify-between items-center border border-slate-600">
                        <div>
                            <div class="font-bold text-purple-300">{{ $reward->name }}</div>
                            <div class="text-xs text-slate-400">ใช้ {{ $reward->points_required }} แต้ม | เหลือ {{ $reward->stock }} ชิ้น</div>
                        </div>
                        <a href="/admin/rewards/delete/{{ $reward->id }}" onclick="return confirm('ลบของรางวัลนี้ใช่ไหม?')" class="text-red-400 hover:text-red-300 font-bold text-sm bg-red-500/10 px-3 py-1 rounded">ลบ</a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6 mt-8">
            <h2 class="text-2xl font-bold text-sky-400 mb-4">👥 รายชื่อผู้ใช้งานในระบบ</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="text-slate-400 border-b border-slate-700 bg-slate-700/30">
                        <tr>
                            <th class="p-4">โปรไฟล์</th>
                            <th class="p-4">ชื่อ (LINE)</th>
                            <th class="p-4 text-center">แต้มปัจจุบัน</th>
                            <th class="p-4">วันที่เข้าร่วม</th>
                            <th class="p-4 text-right">จัดการ / แจกแต้ม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr class="border-b border-slate-700/50 hover:bg-slate-700/30 transition">
                            <td class="p-4">
                                @if($user->picture_url)
                                    <img src="{{ $user->picture_url }}" class="w-12 h-12 rounded-full border-2 border-slate-500 object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-slate-600 border-2 border-slate-500 flex items-center justify-center text-xl">👤</div>
                                @endif
                            </td>
                            <td class="p-4 font-semibold text-white text-lg">{{ $user->display_name }}</td>
                            
                            <td class="p-4 text-center">
                                <span class="bg-yellow-500/20 text-yellow-400 px-4 py-1 rounded-full border border-yellow-500/50 font-bold text-xl">
                                    {{ $user->current_points }}
                                </span>
                            </td>

                            <td class="p-4 text-sm text-slate-400">{{ $user->created_at->format('d M Y') }}</td>
                            
                            <td class="p-4">
                                <div class="flex justify-end items-center gap-4">
                                    <form action="/admin/users/{{ $user->id }}/add-points" method="POST" class="flex items-center gap-2 bg-slate-900 p-1 rounded-lg border border-slate-600">
                                        @csrf
                                        <input type="number" name="points" required placeholder="+แต้ม" class="w-20 bg-transparent border-none focus:ring-0 px-2 text-white text-sm">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded-md transition">เพิ่ม</button>
                                    </form>

                                    <a href="/admin/users/delete/{{ $user->id }}" onclick="return confirm('ลบผู้ใช้งานคนนี้ พร้อมล้างแต้มทั้งหมดของเขาทิ้งใช่ไหม?')" class="text-red-400 hover:text-red-300 font-bold text-sm bg-red-500/10 px-4 py-2 rounded-lg border border-red-500/20 transition">ลบแบน</a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>