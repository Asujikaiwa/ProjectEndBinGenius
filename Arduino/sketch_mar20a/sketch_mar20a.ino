#include <WiFi.h>
#include <PubSubClient.h>
#include <ESP32Servo.h>

// 1. ตั้งค่า Wi-Fi และ MQTT
const char* ssid = "ชื่อไวไฟของคุณ";
const char* password = "รหัสไวไฟ";
const char* mqtt_server = "192.168.1.4"; // IP คอมพิวเตอร์ของคุณที่รัน Mosquitto

WiFiClient espClient;
PubSubClient client(espClient);
Servo myServo;

int servoPin = 18; // ขาที่ต่อ Servo
int centerPos = 90; // มุมตรงกลาง (รอรับขยะ)

// 2. ฟังก์ชันรับข้อความจาก Node-RED
void callback(char* topic, byte* payload, unsigned int length) {
  String message = "";
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  
  Serial.print("ได้รับคำสั่ง: ");
  Serial.println(message);

  // 3. สั่ง Servo หมุนตามคำสั่ง
  if (message == "1") {
    Serial.println("♻️ ปัดลงถังรีไซเคิล!");
    myServo.write(45); // หมุนไปทางรีไซเคิล
    delay(2000);       // รอให้ขยะตกลงไป
    myServo.write(centerPos); // หมุนกลับมาตรงกลาง
  } 
  else if (message == "2") {
    Serial.println("☠️ ปัดลงถังอันตราย!");
    myServo.write(135); // หมุนไปทางอันตราย
    delay(2000);
    myServo.write(centerPos);
  }
}

void setup() {
  Serial.begin(115200);
  myServo.attach(servoPin);
  myServo.write(centerPos); // ตั้งท่ารอตรงกลาง
  
  // ต่อ Wi-Fi และ MQTT
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) { delay(500); }
  
  client.setServer(mqtt_server, 1883);
  client.setCallback(callback);
}

void loop() {
  if (!client.connected()) {
    // ถ้าหลุดให้ต่อใหม่
    if (client.connect("ESP32_SmartBin")) {
      client.subscribe("esp32/servo"); // ดักฟัง Topic นี้
    }
  }
  client.loop();
}