/**
   StreamHTTPClient.ino

    Created on: 24.05.2015

*/
#define DEBUG_HTTPCLIENT(...) Serial.printf( __VA_ARGS__ )
#include <Arduino.h>

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>

#include <ESP8266HTTPClient.h>
#include <Ticker.h>
//the firmware contains a hmac_sha256-function. I noticed this because the in-firmware-hmac_sha256 did a
//name-collision with the https://github.com/ogay/hmac I wanted to use. The headerfiles of this lib seem
//to work with the in-firmware-hmac_sha256 so we define this function to be existent to be able to use it
extern "C" {
void hmac_sha256(const char *key, unsigned int key_size,
                 const char *message, unsigned int message_len,
                 char *mac, unsigned mac_size);
}
#include "secrets.h"

String charToHex(const char *digest, unsigned int digest_size);

#define USE_SERIAL Serial
#define LED_RED 13
#define LED_GREEN 15
#define POWER 15
#define DEVBOARD_RELAY 15
ESP8266WiFiMulti WiFiMulti;
WiFiServer server(23);
Ticker tickerTimeout;

void shutdown() {
  USE_SERIAL.println("timeout exeeded");
  tickerTimeout.detach();
}

void setup() {
  pinMode(POWER, OUTPUT);
  digitalWrite(POWER, HIGH);
  
  pinMode(LED_RED, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  digitalWrite(LED_RED, HIGH);
  digitalWrite(LED_GREEN, HIGH);
  
  pinMode(BUILTIN_LED, OUTPUT);
  pinMode(DEVBOARD_RELAY, OUTPUT);
  
  tickerTimeout.attach(120, shutdown);

  USE_SERIAL.begin(115200);
  // USE_SERIAL.setDebugOutput(true);

  USE_SERIAL.println();
  USE_SERIAL.println();
  USE_SERIAL.println();

  digitalWrite(BUILTIN_LED, HIGH);
  digitalWrite(DEVBOARD_RELAY, HIGH);

  Serial.println("scan start");

  // WiFi.scanNetworks will return the number of networks found
  int n = WiFi.scanNetworks();
  USE_SERIAL.println("scan done");
  if (n == 0)
    USE_SERIAL.println("no networks found");
  else
  {
    USE_SERIAL.print(n);
    USE_SERIAL.println(" networks found");
    for (int i = 0; i < n; ++i)
    {
      // Print SSID and RSSI for each network found
      USE_SERIAL.print(i + 1);
      USE_SERIAL.print(": ");
      USE_SERIAL.print(WiFi.SSID(i));
      USE_SERIAL.print(" (");
      USE_SERIAL.print(WiFi.RSSI(i));
      USE_SERIAL.print(")");
      USE_SERIAL.println((WiFi.encryptionType(i) == ENC_TYPE_NONE) ? " " : "*");
    }
  }
  USE_SERIAL.println("");
  USE_SERIAL.println("[WLAN] adding AP…");
  WiFi.mode(WIFI_STA);
#include "wlans.h"

  USE_SERIAL.println("[WLAN] connecting…");
  while ((WiFiMulti.run() != WL_CONNECTED)) {
    WiFi.printDiag(USE_SERIAL);
    USE_SERIAL.println();
    USE_SERIAL.println("[WLAN] not connected. sleeping 100ms");
    delay(100);
    WiFi.printDiag(USE_SERIAL);
    USE_SERIAL.println();
  }
  // wait for WiFi connection
  if ((WiFiMulti.run() == WL_CONNECTED)) {
    USE_SERIAL.println("[WLAN] WL_CONNECTED");

    HTTPClient http;
    http.setReuse(true);
    http.begin("10.172.0.11", 80, "/map/js/links.js");

    int httpCode = http.GET();
    if(httpCode) {
        USE_SERIAL.printf("[HTTP] GET... code: %d\r\n", httpCode);

        // file found at server
        if(httpCode == 200) {
            String payload = http.getString();
            USE_SERIAL.println(payload);
        }
    } else {
        USE_SERIAL.println("[HTTP] GET... failed, no connection or no HTTP server");
    }
  }

  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());
  Serial.println("Starting Server on Port 23…");
  server.begin();

  const char *message = "ohai";
  char digest[32];
  hmac_sha256(HMAC_KEY, strlen(HMAC_KEY), message, strlen(message), digest, sizeof(digest));
  String result = charToHex(digest, sizeof(digest));
  Serial.println(result);
    
  digitalWrite(LED_RED, LOW);
  digitalWrite(LED_GREEN, LOW);
  digitalWrite(BUILTIN_LED, LOW);
  digitalWrite(POWER, LOW);
  digitalWrite(DEVBOARD_RELAY, LOW);
}

String charToHex(const char *digest, unsigned int digest_size) {
  char output[2 * digest_size + 1];
  output[2 * digest_size] = '\0';
  for (int i = 0; i < (int) digest_size ; i++) {
    sprintf(output + 2*i, "%02x", digest[i]);
  }
  return String(output);
}

ADC_MODE(ADC_VCC);
void help(WiFiClient & client) {
  client.println("Ohai. n: on, f: off, e: disconnect, v: vcc, b: reboot, r: reset, h help, d deep sleep, s power down, a/c color");
}

void loop() {
  // wait for a new client:
  WiFiClient client = server.available();
  // when the client sends the first byte, say hello:
  if (client && client.connected())
    help(client);

  while (client && client.connected()) {
    while (client.available() > 0) {
      // read the bytes incoming from the client:
      char thisChar = client.read();
      // echo the bytes back to the client:
      //client.write(""+thisChar);
      if (thisChar == 'n') {
        client.println("turning on");
        digitalWrite(BUILTIN_LED, HIGH);
        digitalWrite(5, HIGH);
      } else if (thisChar == 'f') {
        client.println("turning off");
        digitalWrite(BUILTIN_LED, LOW);
        digitalWrite(5, LOW);
      } else if (thisChar == 'e') {
        client.println("disconnecting");
        client.stop();
      } else if (thisChar == 'v') {
        client.print("VCC: ");
        client.println(ESP.getVcc());
      } else if (thisChar == 'b') {
        client.println("restarting to bootloader…");
        client.stop();
        delay(100);
        ESP.restart();
      } else if (thisChar == 'r') {
        client.println("resetting…");
        client.stop();
        delay(100);
        ESP.reset();
      } else if (thisChar == 'h') {
        help(client);
      } else if (thisChar == 'd') {
        client.println("going down to deep sleep…");
        client.stop();
        delay(100);
        ESP.deepSleep(1e+7 + 1e+10);
      } else if (thisChar == 's') {
        client.println("power down");
        client.stop();
        delay(100);
        digitalWrite(16, LOW);
      } else if (thisChar == 'a') {
        digitalWrite(13, HIGH);
        digitalWrite(15, LOW);
      } else if (thisChar == 'c') {
        digitalWrite(13, LOW);
        digitalWrite(15, HIGH);
      }
      yield();
    }
    delay(100);
  }
  delay(100);
}


