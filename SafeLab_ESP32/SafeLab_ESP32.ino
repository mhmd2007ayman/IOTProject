#include <DHT.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ArduinoJson.h>

// ===== WiFi =====
// Replace with your own network credentials (never commit real ones!)
const char* ssid     = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// ===== Railway API =====
// Replace with the URL of your deployed Laravel app
const char* serverURL = "https://YOUR-APP-NAME.up.railway.app/api/sensor";

// ===== Pins =====
#define FLAME_PIN     18
#define DO_PIN        14
#define DHT22_PIN     21
#define FLAME_LED_PIN 19
#define GAS_LED_PIN   25
#define TEMP_LED_PIN  4
#define SAFE_LED_PIN  26
#define BUZZER_PIN    27
#define RELAY_PIN     5

DHT dht22(DHT22_PIN, DHT22);

bool flameAlarm       = false;
bool gasAlarm         = false;
bool tempAlarm        = false;
bool flameAlarmActive = false;
bool gasAlarmActive   = false;
bool buzzerState      = false;

unsigned long previousMillis  = 0;
unsigned long lastSendMillis  = 0;
const long buzzerOnTime  = 200;
const long buzzerOffTime = 200;
const long sendInterval  = 3000;

void setup() {
  Serial.begin(115200);
  dht22.begin();

  pinMode(FLAME_PIN,     INPUT);
  pinMode(DO_PIN,        INPUT);
  pinMode(FLAME_LED_PIN, OUTPUT);
  pinMode(GAS_LED_PIN,   OUTPUT);
  pinMode(TEMP_LED_PIN,  OUTPUT);
  pinMode(SAFE_LED_PIN,  OUTPUT);
  pinMode(BUZZER_PIN,    OUTPUT);
  pinMode(RELAY_PIN,     OUTPUT);

  digitalWrite(FLAME_LED_PIN, LOW);
  digitalWrite(GAS_LED_PIN,   LOW);
  digitalWrite(TEMP_LED_PIN,  LOW);
  digitalWrite(SAFE_LED_PIN,  HIGH);
  digitalWrite(RELAY_PIN,     HIGH);
  digitalWrite(BUZZER_PIN,    LOW);
  noTone(BUZZER_PIN);

  // Connect to WiFi
  WiFi.setAutoReconnect(true);
  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi Connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
}

void sendData(float temp, float humi, bool flame, bool gas) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected!");
    return;
  }

  // ===== HTTPS Client =====
  WiFiClientSecure client;
  client.setInsecure(); // <-- Important: allows HTTPS without certificate validation

  HTTPClient http;
  http.begin(client, serverURL);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(10000);

  StaticJsonDocument<256> doc;
  doc["temperature"] = temp;
  doc["humidity"]    = humi;
  doc["flame"]       = flame;
  doc["gas"]         = gas;

  String body;
  serializeJson(doc, body);
  Serial.println("Sending JSON: " + body);

  int code = http.POST(body);
  Serial.print("HTTP Response Code: ");
  Serial.println(code);

  if (code == 200) {
    String response = http.getString();
    Serial.println("Server OK: " + response);
  } else if (code > 0) {
    String response = http.getString();
    Serial.println("Server Error (" + String(code) + "): " + response);
  } else {
    Serial.println("HTTP Failed! Error: " + String(http.errorToString(code).c_str()));
  }

  http.end();
}

void handleFlame() {
  int val = digitalRead(FLAME_PIN);
  if (val == LOW) {
    flameAlarm = true;
    if (!flameAlarmActive) {
      flameAlarmActive = true;
      digitalWrite(FLAME_LED_PIN, HIGH);
      tone(BUZZER_PIN, 2000);
    }
    Serial.println("FLAME DETECTED!");
  } else {
    flameAlarm       = false;
    flameAlarmActive = false;
    digitalWrite(FLAME_LED_PIN, LOW);
  }
}

void handleGas() {
  int val = digitalRead(DO_PIN);
  if (val == LOW) {
    gasAlarm = true;
    if (!gasAlarmActive) {
      gasAlarmActive = true;
      digitalWrite(GAS_LED_PIN, HIGH);
      digitalWrite(RELAY_PIN,   LOW);
    }
    Serial.println("GAS DETECTED!");
  } else {
    gasAlarm       = false;
    gasAlarmActive = false;
    digitalWrite(GAS_LED_PIN, LOW);
    digitalWrite(RELAY_PIN,   HIGH);
  }
}

void handleTemp(float tempC) {
  if (tempC > 30.0) {
    tempAlarm = true;
    digitalWrite(TEMP_LED_PIN, HIGH);
  } else {
    tempAlarm = false;
    digitalWrite(TEMP_LED_PIN, LOW);
  }
}

void handleSafeLed() {
  if (flameAlarm || gasAlarm || tempAlarm) {
    digitalWrite(SAFE_LED_PIN, LOW);
  } else {
    digitalWrite(SAFE_LED_PIN, HIGH);
  }
}

void handleBuzzer() {
  if (flameAlarm) {
    tone(BUZZER_PIN, 2000);
    return;
  }
  unsigned long currentMillis = millis();
  bool anyAlarm = gasAlarm || tempAlarm;
  if (anyAlarm) {
    if (!buzzerState && currentMillis - previousMillis >= buzzerOffTime) {
      buzzerState = true;
      previousMillis = currentMillis;
      tone(BUZZER_PIN, 2000);
    } else if (buzzerState && currentMillis - previousMillis >= buzzerOnTime) {
      buzzerState = false;
      previousMillis = currentMillis;
      noTone(BUZZER_PIN);
    }
  } else {
    noTone(BUZZER_PIN);
    digitalWrite(BUZZER_PIN, LOW);
    buzzerState = false;
  }
}

void loop() {
  float tempC = dht22.readTemperature();
  float humi  = dht22.readHumidity();

  if (!isnan(tempC)) {
    Serial.print("Temp: "); Serial.print(tempC); Serial.println(" C");
    Serial.print("Humi: "); Serial.print(humi);  Serial.println(" %");
    handleTemp(tempC);
  } else {
    Serial.println("DHT read failed!");
  }

  handleFlame();
  handleGas();
  handleSafeLed();
  handleBuzzer();

  // Send data every 3 seconds
  unsigned long currentMillis = millis();
  if (currentMillis - lastSendMillis >= sendInterval) {
    lastSendMillis = currentMillis;
    if (!isnan(tempC)) {
      sendData(tempC, humi, flameAlarm, gasAlarm);
    }
  }

  delay(300);
}
