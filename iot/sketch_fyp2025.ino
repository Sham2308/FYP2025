#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>

// ===== Pins =====
#define RC522_SS   5
#define RC522_RST  4
#define SDA_PIN    22
#define SCL_PIN    21

MFRC522 rfid(RC522_SS, RC522_RST);
LiquidCrystal_I2C *lcd = nullptr;

// ===== WiFi Config =====
const char* ssid     = "23";          // iPhone Hotspot SSID
const char* password = "antam238";    // Hotspot password

// ===== Laravel API URLs =====
String serverBase      = "http://178.128.81.130";
String apiScanNext     = serverBase + "/api/scan-next";
String apiScanComplete = serverBase + "/api/scan-complete/"; // + request ID

// ===== Setup =====
void setup() {
  Serial.begin(115200);
  SPI.begin();
  rfid.PCD_Init();

  Wire.begin(SDA_PIN, SCL_PIN);
  lcd = new LiquidCrystal_I2C(0x27, 16, 2);
  lcd->init();
  lcd->backlight();
  lcd->clear();
  lcd->print("Connecting WiFi");

  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  lcd->clear();
  lcd->print("WiFi Connected");
  Serial.println("\n✅ WiFi connected, IP: " + WiFi.localIP().toString());
}

// ===== Helpers =====
int fetchScanRequestId() {
  if (WiFi.status() != WL_CONNECTED) return -1;
  HTTPClient http;
  http.begin(apiScanNext);
  int code = http.GET();
  if (code > 0) {
    String payload = http.getString();
    Serial.println("ScanNext Response: " + payload);

    int pos = payload.indexOf("\"id\":");
    if (pos != -1) {
      int start = pos + 5;
      int end = payload.indexOf("}", start);
      int id = payload.substring(start, end).toInt();
      http.end();
      return id;
    }
  }
  http.end();
  return -1;
}

void completeScanRequest(int reqId, String chipUid) {
  if (WiFi.status() != WL_CONNECTED) return;
  HTTPClient http;
  String url = apiScanComplete + String(reqId);
  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  String json = "{\"uid\":\"" + chipUid + "\"}";
  int code = http.POST(json);

  Serial.print("Complete response code: "); Serial.println(code);
  if (code > 0) Serial.println(http.getString());
  http.end();
}

// ===== Main Loop =====
void loop() {
  static unsigned long lastPoll = 0;

  if (millis() - lastPoll > 2000) { // poll every 2s
    lastPoll = millis();

    int reqId = fetchScanRequestId();
    if (reqId > 0) {
      lcd->clear();
      lcd->print("scan now");
      Serial.println("➡ Server requested scan. Waiting for card...");

      unsigned long startWait = millis();
      while (millis() - startWait < 15000) { // wait 15s
        if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
          String chipUid = "";
          for (byte i = 0; i < rfid.uid.size; i++) {
            chipUid += String(rfid.uid.uidByte[i], HEX);
          }
          chipUid.toUpperCase();

          Serial.println("✅ Card detected UID: " + chipUid);
          lcd->clear(); lcd->print("UID:");
          lcd->setCursor(0,1); lcd->print(chipUid.substring(0,16));

          completeScanRequest(reqId, chipUid);

          rfid.PICC_HaltA();
          rfid.PCD_StopCrypto1();
          break;
        }
        delay(50);
      }
    }
  }
}
