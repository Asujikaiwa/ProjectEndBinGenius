#include <Servo.h>
#include <PubSubClient.h>
#include <ESP8266WiFi.h>  

// ==========================================
// 🌐 1. ตั้งค่า Wi-Fi และ MQTT
// ==========================================
const char* ssid = "CITELAB_2.4G";
const char* password = "Citelab12345";
const char* mqtt_server = "broker.hivemq.com"; 
WiFiClient espClient;
PubSubClient client(espClient);

// ==========================================
// ⚙️ 2. ประกาศตัวแปรอุปกรณ์
// ==========================================
Servo leftServo;  // สลักซ้าย (รีไซเคิล)
Servo rightServo; // สลักขวา (อันตราย)

const int motorIN1 = 0; // ขา D3 (พลิกซ้าย)
const int motorIN2 = 2; // ขา D4 (พลิกขวา)

// --------------------------------------------------
// 🔴 โซนปรับจูนกลไก
// --------------------------------------------------

// 📐 1. องศา Servo ฝั่งซ้าย (รีไซเคิล)
int leftLockPos = 175;    // ท่าแนวนอน (ค้ำถาดไว้)
int leftUnlockPos = 60;   // ท่าตีหลบลงไปข้างล่าง (เปิดทาง)

// 📐 2. องศา Servo ฝั่งขวา (อันตราย) 
int rightLockPos = 5;     // ท่าแนวนอน (ค้ำถาดไว้)
int rightUnlockPos = 120; // ท่าตีหลบลงไปข้างล่าง (เปิดทาง)

// ⏱️ 3. เวลาของมอเตอร์ DC (มิลลิวินาที)
int timeDown = 1000;  // เวลาที่มอเตอร์หมุนให้ถาดตกลงไป
int timeUp = 1000;    // เวลาที่มอเตอร์หมุนดึงถาดกลับมา
// --------------------------------------------------

void setup_wifi() {
  delay(10);
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
}

void callback(char* topic, byte* payload, unsigned int length) {
  String message = "";
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  Serial.print("ได้รับคำสั่งจาก AI: ");
  Serial.println(message);

  // ==========================================
  // ♻️ โหมด 1: รีไซเคิล (ซ้ายล็อค - ขวาเปิด)
  // ==========================================
  if (message == "1") {
    Serial.println(">> ทำงาน: โหมดรีไซเคิล");
    
    // 1. ซ้ายอยู่เฉยๆ (ล็อคเป็นบานพับ) / ขวาเปิดทาง (หลบ)
    leftServo.write(leftLockPos); 
    rightServo.write(rightUnlockPos); 
    delay(500); // รอให้สลักขวาหลบพ้นทาง
    
    // 2. มอเตอร์หมุนซ้าย เพื่อให้ถาดฝั่งขวาร่วงลงไป
    digitalWrite(motorIN1, HIGH); 
    digitalWrite(motorIN2, LOW);
    delay(timeDown); 
    
    // 3. หยุดมอเตอร์ เพื่อรอให้ขยะตกลงถัง
    digitalWrite(motorIN1, LOW); 
    digitalWrite(motorIN2, LOW);
    delay(1500); 
    
    // 4. มอเตอร์หมุนกลับ เพื่อดึงถาดขึ้นมาระนาบเดิม
    digitalWrite(motorIN1, LOW); 
    digitalWrite(motorIN2, HIGH);
    delay(timeUp);
    
    // 5. หยุดมอเตอร์ (ถาดกลับมาตรงแล้ว)
    digitalWrite(motorIN1, LOW); 
    digitalWrite(motorIN2, LOW);
    delay(500); // รอให้ถาดนิ่งสนิท
    
    // 6. ขวาปิด (สวิงกลับมาค้ำถาดไว้เหมือนเดิม)
    rightServo.write(rightLockPos); 
    
    Serial.println(">> เสร็จสิ้น");
  } 
  
  // ==========================================
  // ☠️ โหมด 2: อันตราย (ขวาล็อค - ซ้ายเปิด)
  // ==========================================
  else if (message == "2") {
    Serial.println(">> ทำงาน: โหมดอันตราย");
    
    // 1. ขวาอยู่เฉยๆ (ล็อคเป็นบานพับ) / ซ้ายเปิดทาง (หลบ)
    rightServo.write(rightLockPos); 
    leftServo.write(leftUnlockPos); 
    delay(500); // รอให้สลักซ้ายหลบพ้นทาง
    
    // 2. มอเตอร์หมุนขวา เพื่อให้ถาดฝั่งซ้ายร่วงลงไป
    digitalWrite(motorIN1, LOW); 
    digitalWrite(motorIN2, HIGH);
    delay(timeDown); 
    
    // 3. หยุดมอเตอร์ เพื่อรอให้ขยะตกลงถัง
    digitalWrite(motorIN1, LOW); 
    digitalWrite(motorIN2, LOW);
    delay(1500); 

    // 4. มอเตอร์หมุนกลับ เพื่อดึงถาดขึ้นมาระนาบเดิม
    digitalWrite(motorIN1, HIGH); 
    digitalWrite(motorIN2, LOW);
    delay(timeUp);
    
    // 5. หยุดมอเตอร์ (ถาดกลับมาตรงแล้ว)
    digitalWrite(motorIN1, LOW); 
    digitalWrite(motorIN2, LOW);
    delay(500); // รอให้ถาดนิ่งสนิท
    
    // 6. ซ้ายปิด (สวิงกลับมาค้ำถาดไว้เหมือนเดิม)
    leftServo.write(leftLockPos); 
    
    Serial.println(">> เสร็จสิ้น");
  }
}

void reconnect() {
  while (!client.connected()) {
    Serial.print("Attempting MQTT connection...");
    yield(); 
    String clientId = "BinGenius-ESP8266-";
    clientId += String(random(0xffff), HEX);
    if (client.connect(clientId.c_str())) {
      Serial.println("connected");
      client.subscribe("esp32/servo"); 
    } else {
      Serial.print("failed, rc=");
      Serial.print(client.state());
      Serial.println(" try again in 5 seconds");
      for(int i=0; i<50; i++) {
        delay(100);
        yield();
      }
    }
  }
}

void setup() {
  Serial.begin(115200);
  
  leftServo.attach(5);  
  rightServo.attach(4); 
  
  pinMode(motorIN1, OUTPUT);
  pinMode(motorIN2, OUTPUT);

  // เปิดเครื่องมา ให้ล็อคถาดไว้ทั้ง 2 ข้างทันที
  leftServo.write(leftLockPos);
  rightServo.write(rightLockPos);
  
  digitalWrite(motorIN1, LOW); 
  digitalWrite(motorIN2, LOW);

  setup_wifi();
  client.setServer(mqtt_server, 1883); 
  client.setCallback(callback); 
}

void loop() {
  if (!client.connected()) {
    reconnect();
  }
  client.loop(); 
}