<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BinGenius Member</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Prompt', sans-serif; background-color: #f3f4f6; }</style>
</head>
<body class="pb-10">

    <div id="loading" class="fixed inset-0 bg-white flex flex-col items-center justify-center z-50">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-emerald-500 mb-4"></div>
        <p class="text-slate-500">กำลังเชื่อมต่อระบบ...</p>
    </div>

    <div class="bg-emerald-600 rounded-b-3xl p-6 text-white shadow-lg text-center">
        <img id="profileImage" src="https://via.placeholder.com/150" class="w-24 h-24 rounded-full mx-auto border-4 border-white mb-3 shadow-md object-cover">
        <h2 id="displayName" class="text-xl font-bold mb-1">สวัสดี, กำลังโหลด...</h2>
        <p class="text-emerald-200 text-sm mb-4">พร้อมรักษ์โลกไปด้วยกันแล้วหรือยัง?</p>
        
        <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-4 inline-block min-w-[200px]">
            <p class="text-sm">แต้มสะสมของคุณ</p>
            <h1 id="userPoints" class="text-5xl font-bold text-yellow-300 mt-1">0</h1>
        </div>
    </div>

    <div class="max-w-md mx-auto px-4 mt-6 space-y-6">
        
        <div>
            <h3 class="text-lg font-bold text-slate-700 mb-3 pl-2 border-l-4 border-purple-500">🎁 แลกของรางวัล</h3>
            <div id="rewardsList" class="grid grid-cols-2 gap-3">
                </div>
        </div>

        <div>
            <h3 class="text-lg font-bold text-slate-700 mb-3 pl-2 border-l-4 border-blue-500">📝 ประวัติการใช้งาน</h3>
            <div id="historyList" class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 space-y-3">
                </div>
        </div>

    </div>

    <script>
        // ใส่ LIFF ID ของคุณที่นี่
        const LIFF_ID = "2009536961-kRWusAaK"; 
        let currentProfile = null;

        async function main() {
            await liff.init({ liffId: LIFF_ID });
            if (!liff.isLoggedIn()) {
                liff.login();
                return;
            }

            currentProfile = await liff.getProfile();
            document.getElementById('profileImage').src = currentProfile.pictureUrl;
            document.getElementById('displayName').innerText = currentProfile.displayName;

            // ส่งข้อมูลไปบอกตู้ขยะ (เปิดคิว)
            await fetch('/api/line/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    line_id: currentProfile.userId,
                    display_name: currentProfile.displayName,
                    picture_url: currentProfile.pictureUrl
                })
            });

            // ดึงข้อมูลแต้ม ประวัติ และของรางวัล
            loadDashboardData();
        }

        async function loadDashboardData() {
            const response = await fetch('/api/line/dashboard', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ display_name: currentProfile.displayName })
            });
            const data = await response.json();

            // โชว์แต้ม
            document.getElementById('userPoints').innerText = data.total_points;

            // โชว์ของรางวัล
            let rewardsHtml = '';
            if(data.rewards.length === 0) {
                rewardsHtml = '<p class="text-sm text-slate-400 col-span-2 text-center py-4">ยังไม่มีของรางวัลในขณะนี้</p>';
            } else {
                data.rewards.forEach(reward => {
                    // เช็คว่าแต้มพอไหม ถ้าไม่พอให้ปุ่มเป็นสีเทา
                    const canRedeem = data.total_points >= reward.points_required;
                    const btnClass = canRedeem ? 'bg-purple-600 hover:bg-purple-700 text-white' : 'bg-slate-300 text-slate-500 cursor-not-allowed';
                    
                    rewardsHtml += `
                        <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h4 class="font-bold text-slate-800 text-sm">${reward.name}</h4>
                                <p class="text-xs text-purple-600 font-semibold mb-2">ใช้ ${reward.points_required} แต้ม</p>
                                <p class="text-[10px] text-slate-400">เหลือ ${reward.stock} ชิ้น</p>
                            </div>
                            <button onclick="confirmRedeem(${reward.id}, '${reward.name}', ${reward.points_required})" 
                                    class="w-full mt-2 py-1.5 rounded-lg text-xs font-bold transition ${btnClass}"
                                    ${!canRedeem ? 'disabled' : ''}>
                                แลกรางวัล
                            </button>
                        </div>
                    `;
                });
            }
            document.getElementById('rewardsList').innerHTML = rewardsHtml;

            // โชว์ประวัติ
            let historyHtml = '';
            if(data.history.length === 0) {
                historyHtml = '<p class="text-sm text-slate-400 text-center py-2">ยังไม่มีประวัติการใช้งาน</p>';
            } else {
                data.history.forEach(log => {
                    const date = new Date(log.created_at).toLocaleString('th-TH', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'});
                    const isRedeem = log.points < 0;
                    const pointColor = isRedeem ? 'text-red-500' : 'text-emerald-500';
                    const pointSign = isRedeem ? '' : '+';
                    
                    historyHtml += `
                        <div class="flex justify-between items-center border-b border-slate-50 pb-2 last:border-0 last:pb-0">
                            <div>
                                <p class="text-sm font-bold text-slate-700">${log.trash_type}</p>
                                <p class="text-xs text-slate-400">${date}</p>
                            </div>
                            <div class="font-bold ${pointColor}">${pointSign}${log.points}</div>
                        </div>
                    `;
                });
            }
            document.getElementById('historyList').innerHTML = historyHtml;

            // ปิดหน้าจอโหลด
            document.getElementById('loading').style.display = 'none';
        }

        // ฟังก์ชัน Pop-up ยืนยันการแลกรางวัล (SweetAlert2)
        function confirmRedeem(rewardId, rewardName, pointsRequired) {
            Swal.fire({
                title: 'ยืนยันการแลกรางวัล?',
                html: `คุณต้องการใช้ <b>${pointsRequired} แต้ม</b><br>เพื่อแลก <b>${rewardName}</b> ใช่หรือไม่?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#9333ea',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'ใช่, แลกเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    processRedeem(rewardId);
                }
            })
        }

        // ส่งคำสั่งแลกรางวัลไปที่ระบบ
        async function processRedeem(rewardId) {
            Swal.fire({ title: 'กำลังประมวลผล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
            
            try {
                const response = await fetch('/api/line/redeem', { // ตรวจสอบว่าสะกดตรงกับ web.php
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        display_name: currentProfile.displayName,
                        reward_id: rewardId
                    })
                });
                const result = await response.json();

                if(result.status === 'success') {
                    // แก้ไขตรงนี้: ให้โชว์รหัสตัวใหญ่ๆ เพื่อให้ลูกค้าเอาไปโชว์แอดมิน
                    Swal.fire({
                        title: 'แลกของรางวัลสำเร็จ!',
                        html: `
                            <p class="mb-2">รหัสรับของรางวัลของคุณคือ:</p>
                            <h1 class="text-4xl font-bold text-purple-600 bg-slate-100 p-4 rounded-lg border-2 border-dashed border-purple-400">
                                ${result.redeem_code}
                            </h1>
                            <p class="mt-4 text-sm text-slate-500 text-left">
                                * กรุณาโชว์หน้าจอนี้ให้เจ้าหน้าที่ตรวจสอบเพื่อรับของรางวัล
                            </p>
                        `,
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        loadDashboardData(); // โหลดข้อมูลใหม่เพื่อตัดแต้มในหน้าจอ
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', result.message, 'error');
                }
            } catch (error) {
                // ถ้าเด้งมาตรงนี้ แสดงว่าเรียก URL /api/line/redeem ไม่ติด
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อระบบได้ (Network Error)', 'error');
            }
        }

        main();
    </script>
</body>
</html>