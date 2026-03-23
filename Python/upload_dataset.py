import os
from roboflow import Roboflow

# 1. ใส่ API Key ของคุณ
rf = Roboflow(api_key="Az5inoAotZkxAYDXvNdo")

# 2. ใส่ชื่อ Workspace และ Project (ดูได้จาก URL บนเว็บ Roboflow)
# เช่น ถ้า URL คือ https://app.roboflow.com/phaiboon-ws/bin-genius/
# workspace คือ "phaiboon-ws" และ project คือ "bin-genius"
workspace_name = "vipers-workspace" 
project_name = "projectbingenius"

# ดึงตัวแปร workspace มาใช้งาน (ไม่ต้องมี .project ต่อท้ายแล้ว)
workspace = rf.workspace(workspace_name)

# 3. กำหนด Path หลัก
base_path = r"C:\Users\ADMIN\Desktop\ProjectBinGenius"

# 4. รายชื่อโฟลเดอร์ย่อยทั้งหมดที่ต้องการอัปโหลด
folders_to_upload = [
    r"Recycle_watse\Bottle.v8i.yolov11",
    r"Recycle_watse\carton.v1-carton1226.yolov11",
    r"Recycle_watse\detect can.v1i.yolov11",
    r"Hazardous_watse\Battery.v4i.yolov11",
    r"Hazardous_watse\cutter.v3i.yolov11",
    r"Hazardous_watse\nail"
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