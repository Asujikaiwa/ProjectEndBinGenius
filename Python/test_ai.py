import requests
import time

# URL ของระบบหลังบ้าน Laravel ที่เรารอรับข้อมูล
# (เนื่องจาก Python รันในคอมเครื่องเดียวกัน เลยใช้ localhost ได้เลยครับ)
API_URL = "http://127.0.0.1:8000/api/trash/detect"

print("🤖 [ตู้ขยะอัจฉริยะกำลังทำงาน...]")
print("รอให้คนสแกน QR Code หน้าตู้...")
time.sleep(2)

# สมมติว่ากล้อง AI ตรวจจับ "ขวดพลาสติก" ได้!
trash_data = {
    "trash_type": "recycle" # ถ้าเปลี่ยนเป็น hazardous จะได้ 5 แต้ม
}

print("📸 AI ตรวจพบ: ขยะรีไซเคิล (Recycle)!")
print("กำลังส่งข้อมูลไปบวกแต้มให้คนที่เพิ่งสแกน...")
time.sleep(1)

# ยิงข้อมูลทะลวงเข้าไปที่ Laravel
try:
    response = requests.post(API_URL, json=trash_data)
    
    if response.status_code == 200:
        result = response.json()
        print("✅ สำเร็จ! ระบบหลังบ้านตอบกลับมาว่า:")
        print(f"👉 {result['message']}")
    else:
        print(f"❌ โดนบล็อกหรือเกิดข้อผิดพลาด: {response.status_code}")
except Exception as e:
    print(f"❌ เซิร์ฟเวอร์ปิดอยู่หรือพัง: {e}")