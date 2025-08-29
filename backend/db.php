<?php

/**
 * db.php
 *
 * Purpose
 * -------
 * - Open (or create) a SQLite database file in this directory.
 * - Ensure the minimal schema exists by creating the following tables if absent:
 *   • staff:   application users/employees
 *   • shifts:  scheduled shifts that may be assigned to a staff member
 *
 * Important SQLite notes
 * ----------------------
 * - Foreign keys in SQLite are **disabled by default**. This script does NOT enable
 *   them. If you need real FK enforcement at runtime, execute:
 *       $pdo->exec('PRAGMA foreign_keys = ON');
 * - This schema does NOT currently define CHECK constraints or indexes beyond PRIMARY KEYs.
 *   If your queries need faster lookups (e.g., by role or day), consider adding indexes.
 *
 * Schema (created if missing)
 * ---------------------------
 * TABLE staff
 *   id    INTEGER PRIMARY KEY AUTOINCREMENT   -- surrogate key
 *   name  TEXT NOT NULL                       -- human-readable full name
 *   role  TEXT NOT NULL                       -- e.g., "server", "cook", "manager"
 *   phone TEXT                                -- optional contact number (unvalidated)
 *
 * TABLE shifts
 *   id         INTEGER PRIMARY KEY AUTOINCREMENT
 *   day        TEXT NOT NULL                   -- e.g., '2025-08-28' or 'Mon' (your convention)
 *   start_time TEXT NOT NULL                   -- e.g., '09:00' (HH:MM, 24h recommended)
 *   end_time   TEXT NOT NULL                   -- e.g., '17:00'
 *   role       TEXT NOT NULL                   -- role required for the shift
 *   staff_id   INTEGER                         -- nullable; when set, references staff(id)
 *   FOREIGN KEY (staff_id) REFERENCES staff(id)
 *
 * Usage
 * -----
 *   // Include this file and receive a configured PDO connection:
 *   $pdo = require __DIR__ . '/db.php';
 *
 * Return
 * ------
 * @return PDO Configured PDO connection to the SQLite database.
 *
 * Exceptions
 * ----------
 * @throws PDOException If the database cannot be opened or a schema statement fails.
 *
 * File/Environment Assumptions
 * ----------------------------
 * - Database file path: <this directory>/database.sqlite
 * - The PHP process must have read/write permissions to this directory.
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
