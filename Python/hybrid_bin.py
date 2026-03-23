import cv2
import json
import time
import base64
import requests
import paho.mqtt.client as mqtt
import google.generativeai as genai

# ==========================================
# 🔑 1. ตั้งค่า API Keys 
# ==========================================
# 1.1 Roboflow (ยิงตรงผ่าน API ไม่ต้องใช้ SDK)
ROBOFLOW_API_KEY = "Az5inoAotZkxAYDXvNdo"
ROBOFLOW_MODEL_ID = "projectbingenius/2" # ระบุชื่อโปรเจค/เวอร์ชัน

# 1.2 Gemini AI (สมองรุ่นพี่)
GEMINI_API_KEY = "AIzaSyDg6dCvQbW36RQdS25-_Y5HkWuDsxBj4o4"
genai.configure(api_key=GEMINI_API_KEY)
gemini_model = genai.GenerativeModel('gemini-1.5-flash') 

# 1.3 MQTT Broker
MQTT_BROKER = "127.0.0.1" 
MQTT_PORT = 1883
MQTT_TOPIC = "smartbin/command"

# ==========================================
# ⚙️ 2. ฟังก์ชันเตรียมความพร้อม
# ==========================================
# เชื่อมต่อ MQTT
mqtt_client = mqtt.Client()
try:
    mqtt_client.connect(MQTT_BROKER, MQTT_PORT, 60)
    print("🌐 เชื่อมต่อ MQTT Broker สำเร็จ!")
except Exception as e:
    print(f"❌ เชื่อมต่อ MQTT ไม่สำเร็จ: {e}")

def ask_gemini_for_help(image_path):
    print("🧠 กำลังส่งภาพให้รุ่นพี่ Gemini ช่วยวิเคราะห์...")
    import PIL.Image
    img = PIL.Image.open(image_path)
    prompt = "ภาพนี้คือขยะประเภทไหน ตอบแค่คำว่า 'recycle' (พลาสติก, แก้ว, กระดาษ, โลหะ) หรือ 'hazardous' (ถ่าน, แบตเตอรี่, หลอดไฟ, ยาหมดอายุ) เท่านั้น ห้ามตอบคำอื่น"
    
    try:
        response = gemini_model.generate_content([prompt, img])
        result = response.text.strip().lower()
        if 'recycle' in result:
            return 'recycle'
        elif 'hazardous' in result:
            return 'hazardous'
        else:
            return 'unknown'
    except Exception as e:
        print(f"❌ Gemini Error: {e}")
        return 'unknown'

# ==========================================
# 🚀 3. เริ่มการทำงานหลัก (Main Loop)
# ==========================================
cap = cv2.VideoCapture(0)

print("\n========================================")
print("🤖 ถังขยะอัจฉริยะ Hybrid AI (Direct API) พร้อมทำงาน!")
print("👉 กดปุ่ม 's' ที่คีย์บอร์ดเพื่อสแกนขยะ")
print("👉 กดปุ่ม 'q' เพื่อปิดระบบ")
print("========================================")

while True:
    ret, frame = cap.read()
    if not ret:
        break
        
    cv2.imshow("BinGenius Smart Camera", frame)
    key = cv2.waitKey(1)
    
    if key == ord('s'): # สแกนขยะ
        print("\n📸 ถ่ายภาพแล้ว! กำลังวิเคราะห์...")
        image_filename = "temp_scan.jpg"
        cv2.imwrite(image_filename, frame)
        
        final_trash_type = None
        
        try:
            # 1️⃣ แปลงภาพเป็นรหัส Base64 เพื่อส่งข้ามอินเทอร์เน็ต
            with open(image_filename, "rb") as image_file:
                img_str = base64.b64encode(image_file.read()).decode("ascii")

            # 2️⃣ ยิง API ไปที่ Roboflow Serverless ตรงๆ
            api_url = f"https://detect.roboflow.com/{ROBOFLOW_MODEL_ID}?api_key={ROBOFLOW_API_KEY}&confidence=10"
            response = requests.post(api_url, data=img_str, headers={"Content-Type": "application/x-www-form-urlencoded"})
            
            # เช็คว่าเครดิตหมด หรือ API มีปัญหาไหม
            if response.status_code != 200:
                raise Exception(f"Roboflow Error: {response.text}")
                
            result = response.json()
            
            # เช็คว่ามีผลลัพธ์การตรวจจับไหม
            if len(result.get('predictions', [])) > 0:
                best_match = result['predictions'][0]
                trash_class = best_match['class'].lower()
                confidence = best_match['confidence'] * 100 
                
                print(f"👀 Roboflow มองเห็น: {trash_class} (ความมั่นใจ {confidence:.2f}%)")
                
                # เช็คความมั่นใจ (Threshold = 60%)
                if confidence >= 60:
                    print("🟩 ความมั่นใจสูง! เชื่อ Roboflow ฟันธงเลย!")
                    final_trash_type = trash_class
                else:
                    print("🟥 ความมั่นใจต่ำกว่า 60%... ต้องเรียกผู้ช่วย!")
                    final_trash_type = ask_gemini_for_help(image_filename)
            else:
                print("🤷‍♂️ Roboflow มองไม่เห็นอะไรเลย... ส่งให้ Gemini ดูแทน!")
                final_trash_type = ask_gemini_for_help(image_filename)
                
        except Exception as e:
            print(f"⚠️ {e}")
            print("🔄 สลับไปใช้ Gemini เป็นตัวสำรองทันที!")
            final_trash_type = ask_gemini_for_help(image_filename)
            
        # 3️⃣ สรุปผลและส่งขึ้น MQTT
        if final_trash_type in ['recycle', 'hazardous']:
            print(f"🎯 สรุปผลสุดท้ายคือ: {final_trash_type.upper()}")
            
            payload = json.dumps({"trash_type": final_trash_type})
            mqtt_client.publish(MQTT_TOPIC, payload)
            print(f"📡 ส่งข้อมูล '{payload}' ขึ้น MQTT Topic '{MQTT_TOPIC}' เรียบร้อย!\n")
        else:
            print("❌ วิเคราะห์ไม่สำเร็จ กรุณาลองวางขยะใหม่แล้วสแกนอีกครั้ง\n")

    elif key == ord('q'):
        print("🛑 กำลังปิดระบบ...")
        break

cap.release()
cv2.destroyAllWindows()
mqtt_client.disconnect()