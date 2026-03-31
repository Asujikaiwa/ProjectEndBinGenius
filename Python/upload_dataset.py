import os
from roboflow import Roboflow

# 1. ใส่ API Key ของคุณ
rf = Roboflow(api_key="tO5pn320HzL6eQ3wMGLf")

# 2. ใส่ชื่อ Workspace และ Project (ดูได้จาก URL บนเว็บ Roboflow)
# เช่น ถ้า URL คือ https://app.roboflow.com/phaiboon-ws/bin-genius/
# workspace คือ "phaiboon-ws" และ project คือ "bin-genius"
workspace_name = "phaiboonaias-workspace" 
project_name = "bingenius-ubkvg"

# ดึงตัวแปร workspace มาใช้งาน (ไม่ต้องมี .project ต่อท้ายแล้ว)
workspace = rf.workspace(workspace_name)

# 3. กำหนด Path หลัก
base_path = r"H:\My Drive\ProjectEndBinGenius"
# 4. รายชื่อโฟลเดอร์ย่อยทั้งหมดที่ต้องการอัปโหลด
folders_to_upload = [
#    "Hazardous_Watse" 
    # "Recycle_watse"
     "Notclass"
]

print("🚀 เริ่มกระบวนการอัปโหลด Dataset ขึ้น Roboflow...")

for folder in folders_to_upload:
    dataset_path = os.path.join(base_path, folder)
    print(f"\nกำลังอัปโหลด: {folder} ...")
    
    try:
        # สั่งอัปโหลดผ่าน workspace และระบุชื่อ project เข้าไปด้านในแทน
        workspace.upload_dataset(
            dataset_path=dataset_path,
            dataset_format="yolov8",
            project_name=project_name, # ระบุชื่อโปรเจคตรงนี้
            project_license="MIT",
            project_type="object-detection",
            num_workers=5  # ลดจำนวน thread ลงมานิดนึงเพื่อป้องกันเว็บรวน
        )
        print(f"✅ อัปโหลด {folder} สำเร็จ!")
        
    except Exception as e:
        print(f"❌ เกิดข้อผิดพลาดกับ {folder}: {e}")

print("\n🎉 อัปโหลดข้อมูลทั้งหมดเสร็จเรียบร้อยแล้ว! กลับไปดูที่เว็บ Roboflow ได้เลยครับ")

# ----------------------------------------------------
# import os
# from google.colab import drive

# # 1. เชื่อมต่อ Google Drive
# print("กำลังเชื่อมต่อ Google Drive...")
# drive.mount('/content/drive')

# # 2. ติดตั้งและเรียกใช้ Roboflow
# # !pip install roboflow -q
# from roboflow import Roboflow

# # 3. ใส่ API Key
# rf = Roboflow(api_key="rf_QIEU0QJnh5V6nVE7y5WkIHMqO9y1")
# workspace = rf.workspace("boonpoks-workspace/bingenius")

# # 4. 🔴 ระบุ Path หลักที่เก็บโฟลเดอร์ Dataset ใน Google Drive 
# # (แก้ตรงนี้ให้ตรงกับโฟลเดอร์ที่คุณไพบูลย์เอาไปวางไว้นะครับ)
# base_path = "/content/drive/MyDrive/ProjectEndBinGenius/Dataset" 

# # 5. ใส่ชื่อโฟลเดอร์ย่อย (ที่ข้างในมีรูป, ไฟล์ .txt และไฟล์ data.yaml)
# folders_to_upload = [
#     "Hazardous_Watse", 
#     "Recycle_watse", 
#     "Notclass"
# ]

# print("\n🚀 เริ่มอัปโหลดรูปพร้อม Label ขึ้น Roboflow...")

# for folder in folders_to_upload:
#     dataset_path = os.path.join(base_path, folder)
    
#     if os.path.exists(dataset_path):
#         print(f"\n📂 กำลังอัปโหลด: {folder} ...")
#         try:
#             # คำสั่งนี้จะดูดทั้งรูปและ Label (yolov8 format) ขึ้นเว็บอัตโนมัติ
#             workspace.upload_dataset(
#                 dataset_path=dataset_path,
#                 dataset_format="yolov8",
#                 project_name="projectbingenius", 
#                 project_license="MIT",
#                 project_type="object-detection",
#                 num_workers=5  
#             )
#             print(f"✅ อัปโหลด {folder} สำเร็จ!")
#         except Exception as e:
#             print(f"❌ เกิดข้อผิดพลาดกับ {folder}: {e}")
#     else:
#         print(f"⚠️ หาโฟลเดอร์ไม่เจอ: {dataset_path} (เช็คชื่อโฟลเดอร์อีกรอบนะครับ)")

# print("\n🎉 ดึงข้อมูลทั้งหมดเสร็จเรียบร้อย! ไปกด Train ในเว็บ Roboflow ต่อได้เลยครับ")




