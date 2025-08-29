<?php

/**
 * db.php — SQLite bootstrap.
 *
 * Opens/creates `database.sqlite`, configures PDO exceptions, and ensures tables `staff` and `shifts` exist.
 *
 * @return PDO Configured SQLite PDO instance.
 * @throws PDOException On connection or schema errors.
 */

$pdo = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// create tables if not exist
$pdo->exec("
CREATE TABLE IF NOT EXISTS staff (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  role TEXT NOT NULL,
  phone TEXT
);

CREATE TABLE IF NOT EXISTS shifts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  day TEXT NOT NULL,
  start_time TEXT NOT NULL,
  end_time TEXT NOT NULL,
  role TEXT NOT NULL,
  staff_id INTEGER,
  FOREIGN KEY(staff_id) REFERENCES staff(id)
);
");
?>
