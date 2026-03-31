import cv2
import json
import paho.mqtt.client as mqtt
import warnings
warnings.filterwarnings("ignore")
import google.generativeai as genai
from inference_sdk import InferenceHTTPClient
from dotenv import load_dotenv
import os

load_dotenv()
# ==========================================
# 🔑 1. ตั้งค่า API Keys 
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")
ROBOFLOW_API_KEY = os.getenv("ROBOFLOW_API_KEY")
# ==========================================
ROBOFLOW_MODEL_ID = "bingenius-ubkvg/1"
CLIENT = InferenceHTTPClient(
    api_url="https://serverless.roboflow.com",
    api_key=ROBOFLOW_API_KEY  # API Key ของคุณ
)

genai.configure(api_key=GEMINI_API_KEY)
gemini_model = genai.GenerativeModel('gemini-2.5-flash') 

MQTT_BROKER = "broker.hivemq.com" 
MQTT_PORT = 1883
MQTT_TOPIC = "smartbin/command"

# ==========================================
# ⚙️ 2. ฟังก์ชันเตรียมความพร้อม
# ==========================================
mqtt_client = mqtt.Client()
try:
    mqtt_client.connect(MQTT_BROKER, MQTT_PORT, 60)
    mqtt_client.loop_start()
    print("🌐 เชื่อมต่อ MQTT Broker สำเร็จ!")
except Exception as e:
    print(f"❌ เชื่อมต่อ MQTT ไม่สำเร็จ: {e}")

def ask_gemini_for_help(image_path):
    print("🧠 กำลังส่งภาพให้รุ่นพี่ Gemini ช่วยวิเคราะห์...")
    import PIL.Image
    img = PIL.Image.open(image_path)
    
    # 🔴 ปรับ Prompt ใหม่ให้รู้จักทั้ง 3 คลาส พร้อมอธิบายให้ Gemini เข้าใจว่าแต่ละอันคืออะไร
    prompt = "ภาพนี้คือขยะประเภทไหน ตอบแค่คำว่า 'recycle_watse' (พลาสติก, แก้ว, กระดาษ, โลหะ , กระป๋อง), 'hazardous_watse' (ถ่าน, แบตเตอรี่, หลอดไฟ, กระป๋องสเปรย์ , อุปกรณ์อิเล็กทรอนิกส์ , คัตเตอร์ , ) หรือ 'notclass' (ขยะทั่วไป, เศษอาหาร, หรือไม่ใช่สองประเภทแรก , มือ , อาหาร , สัตว์ , น้ำ , ใบไม้ , ถุงขยะ , ผลไม้ , ไม้ , ขนม , ทิชชู่ , คน , ไข่ ) เท่านั้น ห้ามตอบคำอื่นหรือใส่เครื่องหมายวรรคตอน"
    
    try:
        response = gemini_model.generate_content([prompt, img])
        result = response.text.strip().lower()
        
        # เช็คคำตอบจาก Gemini
        if 'recycle' in result: 
            return 'recycle_watse'
        elif 'hazardous' in result: 
            return 'hazardous_watse'
        elif 'notclass' in result: 
            return 'notclass'
            
        return 'unknown'
    except Exception as e:
        print(f"❌ Gemini Error: {e}")
        return 'unknown'

# ==========================================
# 🚀 3. เริ่มการทำงานหลัก (Main Loop - Manual Detect)
# ==========================================
cap = cv2.VideoCapture(0)

print("\n========================================")
print("🤖 ถังขยะอัจฉริยะ Hybrid AI (Manual Detect) พร้อมทำงาน!")
print("👉 กดปุ่ม 's' ที่คีย์บอร์ดเพื่อสแกนขยะ")
print("👉 กดปุ่ม 'q' เพื่อปิดระบบ")
print("========================================")

valid_classes = ['recycle_watse', 'hazardous_watse', 'notclass']

while True:
    ret, frame = cap.read()
    if not ret: break
        
    cv2.imshow("BinGenius Smart Camera", frame)
    key = cv2.waitKey(1)
    
    if key == ord('q'): 
        print("🛑 กำลังปิดระบบ...")
        break
    
    elif key == ord('s'): # กลับมาใช้การกดปุ่ม S
        print("\n📸 ถ่ายภาพแล้ว! กำลังวิเคราะห์...")
        image_filename = "temp_scan.jpg"
        cv2.imwrite(image_filename, frame)
        
        try:
            # ส่งภาพให้ Roboflow วิเคราะห์
            result = CLIENT.infer(image_filename, model_id=ROBOFLOW_MODEL_ID)
            
            # ถ้ามองเห็นขยะ
            if len(result.get('predictions', [])) > 0:
                best_match = result['predictions'][0]
                raw_class = best_match['class'].lower()
                confidence = best_match['confidence'] * 100 
                
                print(f"\n👀 พบวัตถุ: {raw_class} (ความมั่นใจ {confidence:.2f}%)")
                
                final_trash_type = None
                
                # 🔴 แปลงชื่อคลาสให้ตรงกับระบบของเรา 3 คลาส
                if 'recycle' in raw_class:
                    final_trash_type = 'recycle_watse'
                elif 'hazardous' in raw_class or 'danger' in raw_class:
                    final_trash_type = 'hazardous_watse'
                elif 'notclass' in raw_class:
                    final_trash_type = 'notclass'
                
                # เช็คความมั่นใจ
                if confidence >= 60 and final_trash_type:
                    print("🟩 มั่นใจสูง! ฟันธงเลย!")
                else:
                    print("🟥 ไม่ชัวร์ หรือชื่อคลาสไม่ตรง... เรียก Gemini ช่วยดูหน่อย!")
                    final_trash_type = ask_gemini_for_help(image_filename)
                    
                # 🔴 ส่งข้อมูลขึ้น MQTT (ตรวจสอบทั้ง 3 คลาส)
                if final_trash_type in valid_classes:
                    print(f"🎯 สรุปผลสุดท้ายคือ: {final_trash_type.upper()}")
                    payload = json.dumps({"trash_type": final_trash_type})
                    mqtt_client.publish(MQTT_TOPIC, payload)
                    print(f"📡 ส่ง '{payload}' เข้า MQTT สำเร็จ!\n")
                else:
                    print(f"❌ วิเคราะห์ไม่สำเร็จ (ผลลัพธ์ที่ได้: {final_trash_type})\n")
            
            # ถ้า Roboflow มองไม่เห็นอะไรเลย ให้ Gemini ช่วย
            else:
                print("🤷‍♂️ Roboflow มองไม่เห็นอะไรเลย... ส่งให้ Gemini ดูแทน!")
                final_trash_type = ask_gemini_for_help(image_filename)
                
                # 🔴 ส่งข้อมูลขึ้น MQTT (ตรวจสอบทั้ง 3 คลาส)
                if final_trash_type in valid_classes:
                    print(f"🎯 สรุปผลสุดท้ายคือ: {final_trash_type.upper()}")
                    payload = json.dumps({"trash_type": final_trash_type})
                    mqtt_client.publish(MQTT_TOPIC, payload)
                    print(f"📡 ส่ง '{payload}' เข้า MQTT สำเร็จ!\n")
                else:
                    print(f"❌ วิเคราะห์ไม่สำเร็จ (ผลลัพธ์ที่ได้: {final_trash_type})\n")
                    
        except Exception as e:
            print(f"⚠️ Error: {e}")
            print("🔄 สลับไปใช้ Gemini เป็นตัวสำรองทันที!")
            final_trash_type = ask_gemini_for_help(image_filename)
            
            # 🔴 ส่งข้อมูลขึ้น MQTT (ตรวจสอบทั้ง 3 คลาส)
            if final_trash_type in valid_classes:
                print(f"🎯 สรุปผลสุดท้ายคือ: {final_trash_type.upper()}")
                payload = json.dumps({"trash_type": final_trash_type})
                mqtt_client.publish(MQTT_TOPIC, payload)
                print(f"📡 ส่ง '{payload}' เข้า MQTT สำเร็จ!\n")
            else:
                print(f"❌ วิเคราะห์ไม่สำเร็จ (ผลลัพธ์ที่ได้: {final_trash_type})\n")

cap.release()
cv2.destroyAllWindows()
mqtt_client.disconnect()