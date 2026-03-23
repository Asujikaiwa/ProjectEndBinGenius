import cv2
from inference_sdk import InferenceHTTPClient

# 1. ตั้งค่าการเชื่อมต่อ API ของ Roboflow
CLIENT = InferenceHTTPClient(
    api_url="https://serverless.roboflow.com",
    api_key="R5Ax5YA3aqJtJbYl64CO"  # API Key ของคุณ
)

# 2. เปิดกล้องเว็บแคม (เลข 0 คือกล้องตัวแรกของเครื่อง)
cap = cv2.VideoCapture(0)

if not cap.isOpened():
    print("❌ ไม่สามารถเปิดกล้องได้ กรุณาเช็คว่าเสียบกล้องแล้วหรือยัง")
    exit()

print("✅ เปิดกล้องสำเร็จ! (คลิกที่หน้าต่างวิดีโอแล้วกดปุ่ม 'q' บนคีย์บอร์ดเพื่อปิด)")

while True:
    # 3. อ่านภาพจากกล้องทีละเฟรม
    ret, frame = cap.read()
    if not ret:
        print("❌ ไม่สามารถรับภาพจากกล้องได้")
        break

    try:
        # 4. ส่งเฟรมภาพ (frame) ไปให้ AI วิเคราะห์
        result = CLIENT.infer(frame, model_id="projectbingenius/2")
        predictions = result.get('predictions', [])
        
        # 5. นำผลลัพธ์มาวาดกรอบสี่เหลี่ยมลงบนภาพแบบเรียลไทม์
        for item in predictions:
            class_name = item['class']
            conf_percent = item['confidence'] * 100
            
            # ดึงตำแหน่ง x, y, กว้าง, สูง
            x = int(item['x'])
            y = int(item['y'])
            w = int(item['width'])
            h = int(item['height'])
            
            # คำนวณจุดมุมซ้าย-บน และ ขวา-ล่าง
            start_point = (int(x - w/2), int(y - h/2))
            end_point = (int(x + w/2), int(y + h/2))
            
            # วาดกรอบสีเขียวและใส่ข้อความ
            cv2.rectangle(frame, start_point, end_point, (0, 255, 0), 2)
            cv2.putText(frame, f"{class_name} {conf_percent:.1f}%", (start_point[0], start_point[1] - 10), 
                        cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)
                        
    except Exception as e:
        # หากส่งข้อมูลไม่ทันหรือเน็ตกระตุก ให้ข้ามเฟรมนั้นไปก่อน ภาพจะได้ไม่ค้าง
        pass

    # 6. แสดงหน้าต่างกล้องที่ตีกรอบแล้ว
    cv2.imshow("Smart Trash Bin - AI Camera", frame)

    # 7. รอรับการกดปุ่ม 'q' เพื่อออกจากโปรแกรม
    if cv2.waitKey(1) & 0xFF == ord('q'):
        print("🛑 ปิดกล้องและออกจากโปรแกรม...")
        break

# คืนการทำงานของกล้องและปิดหน้าต่างทั้งหมด
cap.release()
cv2.destroyAllWindows()