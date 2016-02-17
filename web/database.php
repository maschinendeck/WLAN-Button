<?php
include("common.php");

class Database {

   private $db;

   function __construct() {
       $this->db = new SQLite3('../database.db');
       $this->db->busyTimeout(1000);
       $this->db->exec("BEGIN TRANSACTION");
       $this->db->exec("PRAGMA foreign_keys = ON");
       $this->db->exec("CREATE TABLE IF NOT EXISTS devices (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              description TEXT
           )");
       $this->db->exec("CREATE TABLE IF NOT EXISTS challenges (
              device INTEGER,
              challenge TEXT,
              client TEXT,
              timestamp INTEGER,
              FOREIGN KEY(device) REFERENCES devices(id) ON UPDATE CASCADE ON DELETE CASCADE
           )");
       $this->db->exec("CREATE TABLE IF NOT EXISTS telemetry (
              device INTEGER,
              timestamp INTEGER,
              key TEXT,
              value TEXT,
              FOREIGN KEY(device) REFERENCES devices(id) ON UPDATE CASCADE ON DELETE CASCADE
           )");
       $this->cleanup();
   }

   function __destruct() {
       $this->db->exec("COMMIT");
       $this->db->close();
   }

   function writeTelemetry($device, $key, $value) {
      $stmt = $this->db->prepare('INSERT INTO telemetry(device, timestamp, key, value) VALUES (:device, :timestamp, :key, :value)');
      $stmt->bindValue(':device', $device, SQLITE3_INTEGER);
      $stmt->bindValue(':key', $key, SQLITE3_TEXT);
      $stmt->bindValue(':value', $value, SQLITE3_TEXT);
      $stmt->bindValue(':timestamp', time(), SQLITE3_INTEGER);
      $result = $stmt->execute();
   }

   function insertChallenge($device, $challenge) {
      $stmt = $this->db->prepare('INSERT INTO challenges(device, challenge, client, timestamp) VALUES (:device, :challenge, :client, :timestamp)');
      $stmt->bindValue(':device', $device, SQLITE3_INTEGER);
      $stmt->bindValue(':challenge', $challenge, SQLITE3_TEXT);
      $stmt->bindValue(':client', getClient(), SQLITE3_TEXT);
      $stmt->bindValue(':timestamp', time(), SQLITE3_INTEGER);
      $result = $stmt->execute();
   }

   function isValidChallenge($device, $challenge) {
      $stmt = $this->db->prepare('SELECT device FROM challenges WHERE challenge = :challenge AND client = :client AND device = :device');
      $stmt->bindValue(':device', $device, SQLITE3_INTEGER);
      $stmt->bindValue(':challenge', $challenge, SQLITE3_TEXT);
      $stmt->bindValue(':client', getClient(), SQLITE3_TEXT);
      $result = $stmt->execute();
      if($result->fetchArray())
         return true;
      else
         return false;
   }

   function removeChallenge($device, $challenge) {
      $stmt = $this->db->prepare('DELETE FROM challenges WHERE challenge = :challenge AND client = :client AND device = :device');
      $stmt->bindValue(':device', $device, SQLITE3_INTEGER);
      $stmt->bindValue(':challenge', $challenge, SQLITE3_TEXT);
      $stmt->bindValue(':client', getClient(), SQLITE3_TEXT);
      $result = $stmt->execute();
   }

   function isValid($device) {
      $stmt = $this->db->prepare('SELECT id FROM devices WHERE id = :device');
      $stmt->bindValue(':device', $device, SQLITE3_INTEGER);
      $result = $stmt->execute();
      if($result->fetchArray())
         return true;
      else
         return false;
   }

   //deletes all challenges older than 60 seconds
   private function cleanup() {
      $stmt = $this->db->prepare('DELETE FROM challenges WHERE timestamp < :time');
      $stmt->bindValue(':time', time()-60, SQLITE3_INTEGER);
      $result = $stmt->execute();
   }
}
$db = new Database();
