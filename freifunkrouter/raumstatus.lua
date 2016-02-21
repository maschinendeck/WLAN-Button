#!/usr/bin/lua

local function hmac_sha256(message)
	local HMAC = require("lockbox.mac.hmac");
	local SHA2_256 = require("lockbox.digest.sha2_256");
	local Stream = require("lockbox.util.stream");

	local digest = SHA2_256;
	local blockSize = 64;

	local key = require("./secret");

	local hash = HMAC();
	local res = hash.setBlockSize(blockSize)
                .setDigest(digest)
		.setKey(key)
		.init()
		.update(Stream.fromString(message))
		.finish()
		.asHex();
	return res;
end

local function doHttpRequest(path)
	local handel = io.popen(string.format("wget -T 120 -O- 'http://internetbutton.starletp9.de/%s'", path), 'r')
	local result = handel:read('*all')
	handel:close()
	return result
end

local function getChallenge()
	return doHttpRequest("/getchallenge.php?device=2")
end

local function update(challenge)
	local payload = "challenge:" .. challenge
	print("payload "..payload);
	local hmac = hmac_sha256(payload)
	print("hmac "..hmac);
	return doHttpRequest("/doaction.php?device=2&data=" .. payload .. "&auth=" .. hmac)
end

local function writeToFile(file, content)
	local file = io.open(file, "a")
	file:write(content)
	file:close()
end
function sleep(n)
	os.execute("sleep " .. tonumber(n))
end

writeToFile("/sys/devices/platform/leds-gpio/leds/tp-link:blue:wlan2g/trigger", "none")
writeToFile("/sys/devices/platform/leds-gpio/leds/tp-link:blue:wlan2g/brightness", "0")

local challenge = getChallenge()
print(challenge)
if tonumber("0x" .. challenge) ~= nil then
	--it was a valid hex number, so it can not exploid anything
	local result = update(challenge)
	writeToFile("/sys/devices/pci0000:00/0000:00:00.0/leds/ath9k-phy1/trigger", "none")
	if result == "green" then
		writeToFile("/sys/devices/pci0000:00/0000:00:00.0/leds/ath9k-phy1/brightness", "1")
	elseif result == "red" then
		writeToFile("/sys/devices/pci0000:00/0000:00:00.0/leds/ath9k-phy1/brightness", "0")
	else
		writeToFile("/sys/devices/pci0000:00/0000:00:00.0/leds/ath9k-phy1/trigger", "timer")
		writeToFile("/sys/devices/pci0000:00/0000:00:00.0/leds/ath9k-phy1/delay_on", "50")
		writeToFile("/sys/devices/pci0000:00/0000:00:00.0/leds/ath9k-phy1/delay_on", "50")
	end
	print(result)
end
	
writeToFile("/sys/devices/platform/leds-gpio/leds/tp-link:blue:wlan2g/brightness", "1")

sleep(10)

writeToFile("/sys/devices/platform/leds-gpio/leds/tp-link:blue:wlan2g/trigger", "phy0tpt")
writeToFile("/sys/devices/pci0000:00/0000:00:00.0/leds/ath9k-phy1/trigger",     "phy1tpt")

