<?php

class CacheDB {

  /* Wrapper around SQLite3 DB to cache failed archive file parsing attempts. */

  private $conn;
  private $sel_stmt;
  private $ins_stmt;
  private int $version;

  public function __construct() {
    $this->create(G::CACHE_PRV_PATH . '/thumbnails.sqlite');
    $this->setup_version();
  }

  public function __destruct() {
    if (isset($this->conn)) {
      $this->conn->close();
    }
  }

  public function create($path): void {
    if (!extension_loaded('sqlite3')) {
      // error_log("H5AI warning: sqlite3 module not found.");
      $this->conn = null;
      return;
    }
    if (file_exists($path)) {
      $this->conn = new SQLite3($path);
      return;
    }
    $db = new SQLite3($path);
    // Record handled file types.
    $db->exec('CREATE TABLE types ( id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, type TEXT UNIQUE );');
    foreach (Thumb::HANDLED_TYPES as $key => $array) {
      foreach ($array as $type) {
        $db->exec('INSERT OR IGNORE INTO types VALUES (NULL, \'' . $type . '\');');
      }
    }
    /* Record files that either caused a problem while trying to generate thumbnails, or whose actual type got misdetected from their filename. */
    $db->exec('CREATE TABLE archives (hashedp TEXT NOT NULL UNIQUE PRIMARY KEY, typeid INTEGER, error INTEGER, ver INTEGER, tstamp INTEGER, FOREIGN KEY(typeid) REFERENCES types(id)) WITHOUT ROWID;');
    $this->conn = $db;
  }

  public function insert($hash, $type, $error = null): void {
    if (!$this->conn) {
      return;
    }
    if (!isset($this->ins_stmt)) {
      // Cache this statement for reuse.
      $this->ins_stmt = $this->conn->prepare(
        'INSERT OR REPLACE INTO archives VALUES (:id, :typeid, :err, :ver, :time);');
    }
    $stmt = $this->ins_stmt;
    $stmt->reset();

    $stmt->bindValue(':id', $hash, SQLITE3_TEXT);

    $typeid = $this->conn->querySingle('SELECT id FROM types WHERE type = \'' . $type . '\';');
    if (!$typeid) {
      // New type, then get back its index from the types table.
      $this->conn->exec('INSERT INTO types VALUES (NULL, \'' . $type . '\');');
      $typeid = $this->conn->querySingle('SELECT id FROM types WHERE type = \'' . $type . '\';');
    }
    $stmt->bindValue(':typeid', $typeid, SQLITE3_INTEGER);
    $stmt->bindValue(':err', $error, SQLITE3_INTEGER);
    $stmt->bindValue(':ver', $this->version, SQLITE3_INTEGER);
    $stmt->bindvalue(':time', time(), SQLITE3_INTEGER);
    $stmt->execute();
  }

  public function select($hash) {
    if (!$this->conn) {
      return [];
    }
    if (!isset($this->sel_stmt)) {
      // Cache this statement for reuse, might speed things up.
      $this->sel_stmt = $this->conn->prepare('SELECT archives.ver, archives.tstamp, types.type FROM archives, types WHERE hashedp = :id and archives.typeid = types.id;');
    }
    $stmt = $this->sel_stmt;
    $stmt->reset();

    $stmt->bindValue(':id', $hash, SQLITE3_TEXT);
    $res = $stmt->execute();

    $row = $res->fetchArray(SQLITE3_ASSOC);
    $res->finalize();

    if ($row) {
      return $row;
    }
    return null;
  }

  public function obsolete_entry($row, $mtime) {
    return ($mtime > $row['tstamp']) || ($this->version !== $row['ver']);
  }

  public function setup_version(): int {

    /* Returns an integer representing the available file handlers at the time of failure. */

    $hash = 0;
    $hash |= 0b0001;
    $hash |= 0b0010;
    $hash |= 0b0100;
    $hash |= 0b1000;
    $hash |= 0b10000;
    $this->version = $hash;
    return $hash;
  }
}
