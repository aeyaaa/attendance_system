#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <hd44780.h>           // Main hd44780 header
#include <hd44780ioClass/hd44780_I2Cexp.h> // i2c expander i/o class header

// Network credentials
const char* ssid = "Sioson_WF_2.4";
const char* password = "muller101";

// RFID pins for NodeMCU
#define SS_PIN D8    
#define RST_PIN D0   

// Create instances
ESP8266WebServer server(80);
MFRC522 rfid(SS_PIN, RST_PIN);
hd44780_I2Cexp lcd; // Declare lcd object: auto locate & auto config expander chip

String lastScannedTag = "";
bool isScanning = false;
unsigned long tagDisplayStartTime = 0;
const unsigned long TAG_DISPLAY_DURATION = 3000;  // 3 seconds in milliseconds
bool displayingTag = false;
bool canScanNewCard = true;
bool isAdminScanning = false;  // New flag for admin scanning mode

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\nStarting...");

  // Initialize LCD
  lcd.begin(16, 2); // Initialize the lcd for 16x2 display
  lcd.print("Starting...");

  // Initialize SPI and RFID
  SPI.begin();
  rfid.PCD_Init();

  // Test if RFID reader is responding
  byte v = rfid.PCD_ReadRegister(rfid.VersionReg);
  Serial.print("MFRC522 Version: 0x");
  Serial.println(v, HEX);

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  lcd.setCursor(0, 1);
  lcd.print("Connecting...");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi connected");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());

  // Display IP address on LCD
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("IP Address:");
  lcd.setCursor(0, 1);
  lcd.print(WiFi.localIP().toString());
  delay(3000); // Display IP address for 3 seconds

  // Display ready message
  lcd.clear();
  lcd.print("Ready to scan");

  // Set up server endpoints
  server.on("/start_admin_scan", HTTP_GET, handleStartAdminScan);
  server.on("/start_scan", HTTP_GET, handleStartScan);
  server.on("/check_card", HTTP_GET, handleCheckCard);

  // Enable CORS
  server.enableCORS(true);

  server.begin();
  Serial.println("HTTP server started");
  Serial.println("Ready to scan RFID cards!");
}

void loop() {
    server.handleClient();

    if (isScanning) {
        // Only check for new cards if we're not displaying a tag and can scan new cards
        if (!displayingTag && canScanNewCard) {
            if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
                String tag = "";
                for (byte i = 0; i < rfid.uid.size; i++) {
                    if (rfid.uid.uidByte[i] < 0x10) {
                        tag += "0";
                    }
                    tag += String(rfid.uid.uidByte[i], HEX);
                }
                tag.toUpperCase();
                lastScannedTag = tag;

                // Display tag and start timer
                lcd.clear();
                if (isAdminScanning) {
                    lcd.print("New Student Tag:");
                } else {
                    lcd.print("Tag: ");
                }
                lcd.print(tag);
                
                tagDisplayStartTime = millis();
                displayingTag = true;
                canScanNewCard = false;

                rfid.PICC_HaltA();
                rfid.PCD_StopCrypto1();
            }
        }

        // Check if display duration has elapsed
        if (displayingTag && (millis() - tagDisplayStartTime >= TAG_DISPLAY_DURATION)) {
            lcd.clear();
            if (isAdminScanning) {
                lcd.print("Scan New Tag");
            } else {
                lcd.print("Tap to Scan");
            }
            displayingTag = false;
            canScanNewCard = true;
            tagDisplayStartTime = 0;
        }
    }
}

void handleStartAdminScan() {
    isScanning = true;
    isAdminScanning = true;  // Set admin scanning mode
    lastScannedTag = "";
    displayingTag = false;
    canScanNewCard = true;
    tagDisplayStartTime = 0;
    
    lcd.clear();
    lcd.print("Scan New Tag");
    
    server.send(200, "application/json", "{\"status\": \"scanning\"}");
}

void handleStartScan() {
    isScanning = true;
    isAdminScanning = false;  // Regular scanning mode
    lastScannedTag = "";
    displayingTag = false;
    canScanNewCard = true;
    tagDisplayStartTime = 0;
    
    lcd.clear();
    lcd.print("Tap to Scan");
    
    server.send(200, "application/json", "{\"status\": \"scanning\"}");
}

void handleCheckCard() {
    if (!lastScannedTag.isEmpty()) {
        String jsonResponse = "{\"success\": true, \"rfid_tag\": \"" + lastScannedTag + "\"}";
        server.send(200, "application/json", jsonResponse);
        Serial.println("Tag sent to client: " + lastScannedTag);
        
        // Only clear the tag after sending if not in admin mode
        if (!isAdminScanning) {
            lastScannedTag = "";
        }
    } else if (isScanning) {
        server.send(200, "application/json", "{\"success\": true, \"rfid_tag\": \"none\"}");
    } else {
        server.send(200, "application/json", "{\"status\": \"idle\"}");
    }
}
#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <hd44780.h>           // Main hd44780 header
#include <hd44780ioClass/hd44780_I2Cexp.h> // i2c expander i/o class header

// Network credentials
const char* ssid = "Sioson_WF_2.4";
const char* password = "muller101";

// RFID pins for NodeMCU
#define SS_PIN D8    
#define RST_PIN D0   

// Create instances
ESP8266WebServer server(80);
MFRC522 rfid(SS_PIN, RST_PIN);
hd44780_I2Cexp lcd; // Declare lcd object: auto locate & auto config expander chip

String lastScannedTag = "";
bool isScanning = false;
unsigned long tagDisplayStartTime = 0;  // To track when tag was displayed
const unsigned long TAG_DISPLAY_DURATION = 3000;  // 3 seconds in milliseconds
bool initialScanMessage = false;  // New variable to track initial message

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\nStarting...");

  // Initialize LCD
  lcd.begin(16, 2); // Initialize the lcd for 16x2 display
  lcd.print("Starting...");

  // Initialize SPI and RFID
  SPI.begin();
  rfid.PCD_Init();

  // Test if RFID reader is responding
  byte v = rfid.PCD_ReadRegister(rfid.VersionReg);
  Serial.print("MFRC522 Version: 0x");
  Serial.println(v, HEX);

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  lcd.setCursor(0, 1);
  lcd.print("Connecting...");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi connected");
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());

  // Display IP address on LCD
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("IP Address:");
  lcd.setCursor(0, 1);
  lcd.print(WiFi.localIP().toString());
  delay(3000); // Display IP address for 3 seconds

  // Display ready message
  lcd.clear();
  lcd.print("Ready to scan");

  // Set up server endpoints
  server.on("/start_scan", HTTP_GET, handleStartScan);
  server.on("/check_card", HTTP_GET, handleCheckCard);

  // Enable CORS
  server.enableCORS(true);

  server.begin();
  Serial.println("HTTP server started");
  Serial.println("Ready to scan RFID cards!");
}

void loop() {
  server.handleClient();

  if (isScanning) {
    // Show initial "Tap to Scan" message
    if (initialScanMessage) {
      lcd.clear();
      lcd.print("Tap to Scan");
      initialScanMessage = false;  // Reset flag after showing message
    }
    
    // Handle tag display timer
    if (tagDisplayStartTime > 0 && millis() - tagDisplayStartTime >= TAG_DISPLAY_DURATION) {
      lcd.clear();
      lcd.print("Tap to Scan");
      tagDisplayStartTime = 0;
    }

    // RFID scanning logic
    if (rfid.PICC_IsNewCardPresent()) {
      if (rfid.PICC_ReadCardSerial()) {
        String tag = "";
        for (byte i = 0; i < rfid.uid.size; i++) {
          if (rfid.uid.uidByte[i] < 0x10) {
            tag += "0";
          }
          tag += String(rfid.uid.uidByte[i], HEX);
        }

        tag.toUpperCase();
        lastScannedTag = tag;

        // Display tag and start timer
        lcd.clear();
        lcd.print("Tag: ");
        lcd.print(tag);
        tagDisplayStartTime = millis();

        rfid.PICC_HaltA();
        rfid.PCD_StopCrypto1();
      }
    }
  }
}

void handleStartScan() {
  isScanning = true;
  lastScannedTag = "";
  tagDisplayStartTime = 0;
  initialScanMessage = true;  // Set flag for initial message
  
  lcd.clear();
  lcd.print("Tap to Scan");
  
  server.send(200, "application/json", "{\"status\": \"scanning\"}");
}

void handleCheckCard() {
  if (!lastScannedTag.isEmpty()) {
    String jsonResponse = "{\"success\": true, \"rfid_tag\": \"" + lastScannedTag + "\"}";
    server.send(200, "application/json", jsonResponse);
    Serial.println("Tag sent to client: " + lastScannedTag);
    lastScannedTag = "";
    // Don't stop scanning and don't clear LCD here - let the timer handle it
  } else if (isScanning) {
    server.send(200, "application/json", "{\"status\": \"scanning\"}");
  } else {
    server.send(200, "application/json", "{\"status\": \"idle\"}");
  }
}
